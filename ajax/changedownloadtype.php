<?php

namespace OCA\FidelApp;

use OCA\FidelApp\Db\ShareItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();

try {
	$api = new API();

	$shareId = $_POST ['shareId'];
	$downloadType = $_POST ['downloadType'];

	$mapper = new ShareItemMapper($api);
	$shareItem = $mapper->findById($shareId);
	$shareItem->setDownloadType($downloadType);
	$mapper->save($shareItem);

	\OC_JSON::success();
} catch(Exception $e) {
	\OC_JSON::error($e->getMessage());
}
