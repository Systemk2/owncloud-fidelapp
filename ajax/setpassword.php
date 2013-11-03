<?php

namespace OCA\FidelApp;

use \OCA\FidelApp\Db\ContactItem;
use \OCA\FidelApp\Db\ContactItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();

$api = new API();

$contactId = $_POST ['contactId'];
$password = trim($_POST ['password']);

$mapper = new ContactItemMapper($api);

$contactItem = $mapper->findById($contactId);

$contactItem->setPassword($password);

$mapper->save($contactItem);

\OC_JSON::success();