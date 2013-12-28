<?php

namespace OCA\FidelApp;

use OCA\FidelApp\Db\ShareItemMapper;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\FileItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();

try {
	$api = new API();

	$shareId = $_POST ['shareId'];

	$mapper = new ShareItemMapper($api);
	$shareItem = $mapper->findById($shareId);
	$fileId = $shareItem->getFileId();
	$mapper->delete($shareItem);

	if (count($mapper->findByFileId($fileId)) == 0) {
		// File is not shared anymore, clean up checksum table too
		$fileMapper = new FileItemMapper($api);
		try {
			$fileItem = $fileMapper->findByFileId($fileId);
			$fileMapper->delete($fileItem);
		} catch(DoesNotExistException $e) {
			// The checksum information has already been deleted? Ignore....
		}
	}
	\OC_JSON::success();
} catch(Exception $e) {
	\OC_JSON::error($e->getMessage());
}

