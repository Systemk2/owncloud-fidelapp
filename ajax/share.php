<?php

namespace OCA\FidelApp;

use OCA\FidelApp\Db\ContactShareItem;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\Db\ShareItem;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();



try {
	$api = new API();

	$userId = $api->getUserId();
	$email = htmlspecialchars_decode($_POST ['shareWith']);
	$fileId = $_POST ['itemSource'];
	$file = $_POST ['file'];

	$mapper = new Db\ContactShareItemMapper($api);

	$sharedItems = $mapper->findByUserFileEmail($userId, $fileId, $email);

	if (count($sharedItems) === 0) {
		$contactItem = new ContactItem();
		$shareItem = new ShareItem();

		$contactItem->setUserId($userId);
		$contactItem->setEmail($email);
		$contactsappId = $api->findContactsappIdByEmail($api, $email);
		if($contactsappId) {
			$contactItem->setContactsappId($contactsappId);
		}
		$shareItem->setDownloadType('SECURE');
		$shareItem->setFileId($fileId);

		$contactShareItem = new ContactShareItem($contactItem, $shareItem);

		$mapper->save($contactShareItem);

		$fidelConfig = new FidelboxConfig($api);
		$fidelConfig->calculateHashAsync($shareItem);
	}

	\OC_JSON::success();
} catch(Exception $e) {
	\OC_JSON::error($e->getMessage());
}

