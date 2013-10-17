<?php
namespace OCA\FidelApp;

$api = new API();

$userId = $api->getUserId();
$email = htmlspecialchars_decode($_POST['shareWith']);
$fileId = $_POST['itemSource'];
$file = $_POST['file'];

$mapper = new Db\ShareItemMapper($api);

$sharedItems = $mapper->findByUserFileEmail($userId, $fileId, $email);

if(count($sharedItems) === 0) {
	$shareItem = new Db\ShareItem();
} else if(count($sharedItems) === 1) {
	$shareItem = $sharedItems[0];
} else {
	throw new OCA\AppFramework\Db\MultipleObjectsReturnedException("More than one share for user $userId, file $fileId, email $email");
}

$shareItem->setUserId($userId);
$shareItem->setEmail($email);
$shareItem->setFileId($fileId);

$mapper->save($shareItem);

\OC_JSON::success();