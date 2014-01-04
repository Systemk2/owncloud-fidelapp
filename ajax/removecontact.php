<?php

namespace OCA\FidelApp;

use \OCA\FidelApp\Db\ContactItem;
use \OCA\FidelApp\Db\ContactItemMapper;
use OCA\FidelApp\Db\ShareItemMapper;
use OCA\FidelApp\Db\FileItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();

try {
	$api = new API();

	$contactId = $_REQUEST ['contactId'];

	$mapper = new ContactItemMapper($api);
	$shareMapper = new ShareItemMapper($api);
	$fileMapper = new FileItemMapper($api);

	$contactItem = $mapper->findById($contactId);

	// Cleanup: Remove shares that belong to the deleted contact
	$shares = $shareMapper->findByContact($contactId);
	foreach ( $shares as $share ) {
		$fileId = $share->getFileId();
		$shareMapper->delete($share);

		if (count($shareMapper->findByFileId($fileId)) == 0) {
			// File is not shared anymore, clean up checksum table too
			try {
				$fileItem = $fileMapper->findByFileId($fileId);
				$fileMapper->delete($fileItem);
			} catch(DoesNotExistException $e) {
				// The checksum information has already been deleted? Ignore....
			}
		}
	}
	// Finally delete the contact itself
	$mapper->delete($contactItem);

	\OC_JSON::success(array (
			'contact' => $contactId,
			'action' => 'CONTACT_REMOVED'
	));
} catch(\Exception $e) {
	\OC_JSON::error(array (
			'message' => $e->getMessage()
	));
}