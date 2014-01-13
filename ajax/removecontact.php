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

	$contactManager = new ContactManager($api);
	$contactManager->removeContact($contactId);

	\OC_JSON::success(array (
			'contact' => $contactId,
			'action' => 'CONTACT_REMOVED'
	));
} catch(\Exception $e) {
	\OC_JSON::error(array (
			'message' => $e->getMessage()
	));
}