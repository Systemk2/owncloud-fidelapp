<?php
namespace OCA\FidelApp;

use OCA\FidelApp\Db\ContactShareItem;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\Db\ShareItem;
$api = new API();

$userId = $api->getUserId();
$email = htmlspecialchars_decode($_POST['shareWith']);
$fileId = $_POST['itemSource'];
$file = $_POST['file'];

$mapper = new Db\ContactShareItemMapper($api);

$sharedItems = $mapper->findByUserFileEmail($userId, $fileId, $email);

if(count($sharedItems) === 0) {
	$contactItem = new ContactItem();
	$shareItem = new ShareItem();

	$contactItem->setUserId($userId);
	$contactItem->setEmail($email);
	$shareItem->setFileId($fileId);

	$contactShareItem = new ContactShareItem($contactItem, $shareItem);
} else if(count($sharedItems) === 1) {
	$contactShareItem = $sharedItems[0];
} else {
	throw new \OCA\AppFramework\Db\MultipleObjectsReturnedException("More than one share for user $userId, file $fileId, email $email");
}

$mapper->save($contactShareItem);


\OC_JSON::success();