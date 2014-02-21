<?php
/**
 * ownCloud - FidelApp (File Delivery App)
 *
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH  info@systemk2.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\FidelApp;

use OC\Files\Filesystem;
use OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\FidelApp\Db\FileItemMapper;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\FileItem;

class CalculateMD5BackgroundJob {

	public static function run($shareId) {
		try {
			$api = new API(FIDELAPP_APPNAME);
			$contactShareItemMapper = new ContactShareItemMapper($api);
			$contactShareItem = $contactShareItemMapper->findByShareId($shareId);
			$fileId = $contactShareItem->getShareItem()->getFileId();

			$fileMapper = new FileItemMapper($api);
			try {
				$fileItem = $fileMapper->findByFileId($fileId);
				if ($fileItem->getCalculationInProgress() || $fileItem->getChecksum()) {
					return;
				}
			} catch(DoesNotExistException $e) {
				$fileItem = new FileItem();
				$fileItem->setFileId($fileId);
				$fileItem->setCalculationInProgress(false);
			}
			$fileItem->setCalculationInProgress(true);
			$fileMapper->save($fileItem);

			$api->setupFS($contactShareItem->getContactItem()->getUserId());
			$filename = $api->getPath($fileId);
			$localFile = Filesystem::getLocalFile($filename);
			if (! $localFile) {
				\OC_Log::write(FIDELAPP_APPNAME, "Could not access file $filename", \OC_Log::ERROR);
			}
			$md5 = md5_file($localFile);

			$fileItem->setCalculationInProgress(false);
			$fileItem->setChecksum($md5);
			$fileMapper->save($fileItem);
		} catch(\Exception $e) {
			\OC_Log::write(FIDELAPP_APPNAME,
					"Error while executing checksum calculation for file with id $shareId: " . $e->getMessage(), \OC_Log::ERROR);
		}
	}
}