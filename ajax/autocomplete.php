<?php

namespace OCA\FidelApp;

\OCP\JSON::callCheck();
\OCP\JSON::checkLoggedIn();
\OC_App::loadApps();

$api = new API();

$contacts = $api->findContactsByNameOrEmail($_GET ['search']);

\OC_JSON::success(array (
		'data' => $contacts
));