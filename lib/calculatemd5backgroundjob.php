<?php

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