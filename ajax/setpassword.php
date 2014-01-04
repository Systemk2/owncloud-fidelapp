<?php

namespace OCA\FidelApp;

use \OCA\FidelApp\Db\ContactItem;
use \OCA\FidelApp\Db\ContactItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();
try {
	$api = new API();

	$contactId = $_POST ['contactId'];
	$password = trim($_POST ['password']);

	$mapper = new ContactItemMapper($api);

	$contactItem = $mapper->findById($contactId);

	$contactItem->setPassword($password);

	$mapper->save($contactItem);

	\OC_JSON::success(array (
			'contact' => $contactId,
			'password' => $password,
			'action' => 'PASSWORD_CHANGED'
	));
} catch(\Exception $e) {
	\OC_JSON::error(array (
			'message' => $e->getMessage()
	));
}