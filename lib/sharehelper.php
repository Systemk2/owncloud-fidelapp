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
use OCA\FidelApp\Db\ReceiptItemMapper;
use OCA\FidelApp\Db\ReceiptItem;

\OC::$CLASSPATH ['OCA\FidelApp\AlreadySharedException'] = FIDELAPP_APPNAME . '/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\FileNotFoundException'] = FIDELAPP_APPNAME . '/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\WrongParameterException'] = FIDELAPP_APPNAME . '/lib/exception.php';

class ShareHelper {
	private $api;
	private $fidelboxConfig;
	private $contactShareMapper;
	private $shareMapper;
	private $receiptItemMapper;

	public function __construct(API $api) {
		$this->api = $api;
		$this->fidelboxConfig = new FidelboxConfig($api);
		$this->contactShareMapper = new ContactShareItemMapper($api);
		$this->shareMapper = new ShareItemMapper($api);
		$this->receiptItemMapper = new ReceiptItemMapper($api);
	}

	public function createAndSaveReceiptItemIfNotPresent(ContactShareItem $contactShareItem) {
		$receipts = $this->receiptItemMapper->findByShareId($contactShareItem->getShareItem()->getId());
		foreach ( $receipts as $receipt ) {
			// Check if a receipt item already exists for which the download has not finished yet
			if (! $receipt->getDownloadTime()) {
				return;
			}
		}
		// When we arrive here, no receipt item without download time has been found
		// Therefore create a new one
		$contactManager = new ContactManager($this->api);
		$receipt = new ReceiptItem();
		$receipt->setContactName($contactShareItem->getContactItem()->getEmail());
		$receipt->setDownloadStarted(date('Y-m-d H:i:s'));
		$userId = $contactShareItem->getContactItem()->getUserId();
		$this->api->setupFS($userId);
		$fileId = $contactShareItem->getShareItem()->getFileId();
		$fileName = trim($this->api->getPath($fileId), DIRECTORY_SEPARATOR);
		$receipt->setFileName($fileName);
		$receipt->setUserId($userId);
		$receipt->setShareId($contactShareItem->getShareItem()->getId());
		$receipt->setDownloadType($contactShareItem->getShareItem()->getDownloadType());
		$this->receiptItemMapper->save($receipt);
	}

	public function updateReceiptDownloadTime(ShareItem $shareItem) {
		\OC_Log::write($this->api->getAppName(),
				'Updating receipt download time for share with id ' . $shareItem->getId(), \OC_Log::INFO);
		$receiptItemMapper = new ReceiptItemMapper($this->api);
		$receipts = $receiptItemMapper->findByShareId($shareItem->getId());
		foreach ( $receipts as $receipt ) {
			\OC_Log::write($this->api->getAppName(),
					'Found receipt with id ' . $receipt->getId(), \OC_Log::INFO);
			if(!$receipt->getDownloadTime()) {
				\OC_Log::write($this->api->getAppName(),
						'Updating receipt with id ' . $receipt->getId(), \OC_Log::INFO);
				$receipt->setDownloadTime(date('Y-m-d H:i:s'));
				$receiptItemMapper->save($receipt);
			}
		}
	}

	public function share($fileId, ContactItem $contactItem) {
		$path = $this->api->getPath($fileId);
		$shareItem = $this->createShare($fileId, $contactItem);
		if (! $shareItem) {
			throw new AlreadySharedException();
		}
		$isDir = $this->api->isDir($path);
		if ($isDir) {
			$sharedDirs = $this->contactShareMapper->findDirectoriesByUser($contactItem->getUserId());
			foreach ( $sharedDirs as $sharedDir ) {
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
				// If directory was already shared, remove this share and all implicitly shared files
				foreach ( $sharedItems as $sharedItem ) {
					$childItems = $this->shareMapper->findByParentId($sharedItem->getShareItem()->getId());
					foreach ( $childItems as $childItem ) {
						$this->deleteShare($childItem->getId());
					}
					$this->deleteShare($sharedItem->getShareItem()->getId());
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
	 *
	 * @param integer $shareId
	 * @param boolean $fileDeleted
	 *        	[optional] defaults to <code>false</code>
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
	 * After a file or directory has been moved or renamed, this method can be used to remove all implicit shares that are not
	 * valid anymore (e.g.
	 * a file/directory has been moved out of a shared directory)
	 *
	 * The algorithm is as follows:
	 * <ol>
	 * <li>Get all shares for the given file id</li>
	 * <li>Keep explicit shares (stop processing)</li>
	 * <li>If it's an implicit share, check if the parent is still the parent, then keep it (stop processing)</li>
	 * <li>If file id describes a directory, do a recursive analysis of the contents, starting by 1.</li>
	 * <li>remove the implicit share</li>
	 * </ol>
	 *
	 * @param int $fileId
	 */
	public function removeObsoleteImplicitShares($fileId) {
		$path = $this->api->getPath($fileId);
		$isDir = $this->api->isDir($path);

		$contactShareItems = $this->contactShareMapper->findByUserFile($this->api->getUserId(), $fileId);
		foreach ( $contactShareItems as $item ) {
			$parentId = $item->getShareItem()->getParentShareId();
			if (! $parentId) {
				// Explicitly shared -> Nothing to do
				return;
			}
			$parent = $this->shareMapper->findById($parentId);
			$parentFileId = $parent->getFileId();
			$parentPath = $this->api->getPath($parentFileId);
			if ($this->isParentDir($parentPath, $path)) {
				// The parent is still the same -> nothing to do
				return;
			}
			// The parent has changed, delete old implicit share
			$this->deleteShare($item->getShareItem()->getId());
		}
		if ($isDir) {
			$directoryContents = $this->api->readDir($path);
			foreach ( $directoryContents as $entry ) {
				$entryPath = $path . DIRECTORY_SEPARATOR . $entry;
				$entryFileId = $this->api->getFileId($entryPath);
				if (! $entryFileId) {
					throw new FileNotFoundException($entryPath);
				}
				$this->removeObsoleteImplicitShares($entryFileId);
			}
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
		$isDir = $this->api->isDir($path);

		$directoryShares = $this->contactShareMapper->findDirectoriesByUser($this->api->getUserId());
		foreach ( $directoryShares as $contactShare ) {
			$sharedDir = $this->api->getPath($contactShare->getShareItem()->getFileId());
			if ($this->isParentDir($sharedDir, $path)) {
				if ($isDir) {
					$this->shareDirectoryRecursively($path, $contactShare->getShareItem()->getId(),
							$contactShare->getContactItem());
				} else {
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
		if (! $this->api->isDir($parent)) {
			return false;
		}
		if ($parent {strlen($parent) - 1} != DIRECTORY_SEPARATOR) {
			$parent .= DIRECTORY_SEPARATOR;
		}
		return stripos($child, $parent) === 0;
	}

	public function changeDownloadType($downloadType, $shareId) {
		if ($downloadType != 'SECURE' && $downloadType != 'BASIC') {
			throw new WrongParameterException('downloadType', $downloadType);
		}

		$shareItem = $this->shareMapper->findById($shareId);
		if ($shareItem->getIsDir()) {
			// Share is a directory, so change all associated file shares
			$itemsToBeChanged = $this->shareMapper->findByParentId($shareItem->getId());
			foreach ( $itemsToBeChanged as $item ) {
				$item->setDownloadType($downloadType);
				$this->shareMapper->save($item);
			}
		}
		$shareItem->setDownloadType($downloadType);
		$this->shareMapper->save($shareItem);
	}
}