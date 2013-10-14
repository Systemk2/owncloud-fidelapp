<?php
namespace \OCA\FidelApp;

$userId = $api->getUserId();
$email = htmlspecialchars_decode($_POST['fidelapp_shareWith']);
$fileId = $_POST['itemSource'];

$api = new API();
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
$shareItem->email($email);
$shareItem->fileId($fileId);

$mapper->save($shareItem);

\OC_JSON::success();