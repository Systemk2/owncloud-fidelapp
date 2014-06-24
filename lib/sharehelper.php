<?php

/**
* ownCloud - FidelApp (File Delivery App)
*
* @author Sebastian Kanzow
* @copyright 2014 System k2 GmbH info@systemk2.de
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library. If not, see <http://www.gnu.org/licenses/>.
*
*/
namespace OCA\FidelApp;

use OCA\FidelApp\Db\ShareItem;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\Db\ContactShareItem;
use OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\FidelApp\Db\ShareItemMapper;
use OCA\FidelApp\Db\FileItemMapper;
use OCA\AppFramework\Db\DoesNotExistException;

\OC::$CLASSPATH ['OCA\FidelApp\AlreadySharedException'] = FIDELAPP_APPNAME . '/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\FileNotFoundException'] = FIDELAPP_APPNAME . '/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\WrongParameterException'] = FIDELAPP_APPNAME . '/lib/exception.php';

class ShareHelper {
	private $api;
	private $fidelboxConfig;
	private $contactShareMapper;
	private $shareMapper;

	public function __construct(API $api) {
		$this->api = $api;
		$this->fidelboxConfig = new FidelboxConfig($api);
		$this->contactShareMapper = new ContactShareItemMapper($api);
		$this->shareMapper = new ShareItemMapper($api);
	}

	public function share($fileId, ContactItem $contactItem) {
		$path = $this->api->getPath($fileId);
		$shareItem = $this->createShare($fileId, $contactItem);
		if (! $shareItem) {
			throw new AlreadySharedException();
		}
		$isDir = $this->api->isDir($path);
		if($isDir) {
			$sharedDirs = $this->contactShareMapper->findByUser($contactItem->getUserId(), true);
			foreach($sharedDirs as $sharedDir) {
				$sharedDirPath = $this->api->getPath($sharedDir->getShareItem()->getFileId());
				if ($this->isParentDir($sharedDirPath, $path)) {
					// Directory was already shared through a parent directory
					throw new AlreadySharedException();
				}
			}
		}
		$shareItem->setIsDir($isDir);
		$contactShareItem = new ContactShareItem($contactItem, $shareItem);
		$contactShareItem = $this->contactShareMapper->save($contactShareItem);
		if ($isDir) {
			$this->shareDirectoryRecursively($path, $contactShareItem->getShareItem()->getId(), $contactItem);
		} else {
			$this->fidelboxConfig->calculateHashAsync($contactShareItem->getShareItem());
		}
	}

	private function createShare($fileId, ContactItem $contactItem) {
		$sharedItems = $this->contactShareMapper->findByUserFileEmail($contactItem->getUserId(), $fileId,
				$contactItem->getEmail());
		if (count($sharedItems) > 0) {
			// Already shared
			return null;
		}
		$shareItem = new ShareItem();
		$shareItem->setDownloadType('SECURE');
		$shareItem->setFileId($fileId);
		// Set share time to "now"
		$shareItem->setShareTime(date('Y-m-d H:i:s'));
		return ($shareItem);
	}

	private function shareDirectoryRecursively($directoryPath, $parentShareId, ContactItem $contactItem) {
		$directoryContents = $this->api->readDir($directoryPath);
		foreach ( $directoryContents as $entry ) {
			$entryPath = $directoryPath . DIRECTORY_SEPARATOR . $entry;
			$fileId = $this->api->getFileId($entryPath);
			if (! $fileId) {
				throw new FileNotFoundException($entryPath);
			}
			if ($this->api->isDir($entryPath)) {
				$sharedItems = $this->contactShareMapper->findByUserFileEmail($contactItem->getUserId(), $fileId,
						$contactItem->getEmail());
				if (count($sharedItems) > 0) {
					// Directory was already shared, remove this share and all implicitly shared files
					foreach ( $sharedItems as $sharedItem ) {
						$childItems = $this->shareMapper->findByParentId($sharedItem->getShareItem()->getId());
						foreach ( $childItems as $childItem ) {
							$this->deleteShare($childItem->getId());
						}
						$this->deleteShare($sharedItem->getShareItem()->getId());
					}
				}
				$this->shareDirectoryRecursively($entryPath, $parentShareId, $contactItem);
			} else {
				$shareItem = $this->createShare($fileId, $contactItem);
				if ($shareItem) {
					$shareItem->setParentShareId($parentShareId);
					$contactShareItem = new ContactShareItem($contactItem, $shareItem);
					$contactShareItem = $this->contactShareMapper->save($contactShareItem);
					$this->fidelboxConfig->calculateHashAsync($contactShareItem->getShareItem());
				}
			}
		}
	}

	/**
	 * Delete a share for a file, either because the user does not want to share
	 * the file anymore, or because the file has been deleted
	 * @param integer $shareId
	 * @param boolean $fileDeleted
	 */
	public function deleteShare($shareId, $fileDeleted = false) {
		$shareItem = $this->shareMapper->findById($shareId);
		$fileId = $shareItem->getFileId();
		$itemsToBeDeleted = array ();
		$isDir = $shareItem->getIsDir();
		if ($isDir) {
			// Share is a directory, so remove all associated file shares
			$itemsToBeDeleted = $this->shareMapper->findByParentId($shareItem->getId());
		}
		$itemsToBeDeleted [] = $shareItem;

		foreach ( $itemsToBeDeleted as $deleteItem ) {
			$this->shareMapper->delete($deleteItem);

			if (count($this->shareMapper->findByFileId($fileId)) == 0) {
				// File is not shared anymore, clean up checksum table too
				$fileMapper = new FileItemMapper($this->api);
				try {
					$fileItem = $fileMapper->findByFileId($fileId);
					$fileMapper->delete($fileItem);
				} catch(DoesNotExistException $e) {
					// The checksum information has already been deleted? Ignore....
				}
			}
		}
		// Check if the file is still shared implicitly through a parent directory
		if (! $isDir && ! $fileDeleted) {
			$this->createImplicitShares($fileId);
		}
	}

	/**
	 * Create shares for a file element, if parent directories are shared
	 *
	 * @param string $fileId
	 *        	identifies the file element
	 */
	public function createImplicitShares($fileId) {
		$path = $this->api->getPath($fileId);
		if ($this->api->isDir($path)) {
			return;
		}
		$directoryShares = $this->contactShareMapper->findByUser($this->api->getUserId(), true);
		foreach ( $directoryShares as $contactShare ) {
			$sharedDir = $this->api->getPath($contactShare->getShareItem()->getFileId());
			if ($this->isParentDir($sharedDir, $path)) {
				$shareItem = $this->createShare($fileId, $contactShare->getContactItem());
				if ($shareItem) {
					$shareItem->setParentShareId($contactShare->getShareItem()->getId());
					$contactShare = new ContactShareItem($contactShare->getContactItem(), $shareItem);
					$contactShareItem = $this->contactShareMapper->save($contactShare);
					$this->fidelboxConfig->calculateHashAsync($contactShareItem->getShareItem());
				}
			}
		}
	}

	/**
	 * Verify if the first argument is a parent of the second one
	 *
	 * @param string $parent
	 *        	path to the parent directory
	 * @param string $child
	 *        	path to the schild element
	 * @return boolean
	 */
	public function isParentDir($parent, $child) {
		if(! $this->api->isDir($parent)) {
			return false;
		}
		if ($parent {strlen($parent) - 1} != DIRECTORY_SEPARATOR) {
			$parent .= DIRECTORY_SEPARATOR;
		}
		return stripos($child, $parent) === 0;
	}

	public function changeDownloadType($downloadType, $shareId) {
		if($downloadType != 'SECURE' && $downloadType != 'BASIC') {
			throw new WrongParameterException('downloadType', $downloadType);
		}

		$shareItem = $this->shareMapper->findById($shareId);
		if($shareItem->getIsDir()) {
			// Share is a directory, so change all associated file shares
			$itemsToBeChanged = $this->shareMapper->findByParentId($shareItem->getId());
			foreach($itemsToBeChanged as $item) {
				$item->setDownloadType($downloadType);
				$this->shareMapper->save($item);
			}
		}
		$shareItem->setDownloadType($downloadType);
		$this->shareMapper->save($shareItem);


	}
}