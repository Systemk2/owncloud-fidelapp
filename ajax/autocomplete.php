<?php

namespace OCA\FidelApp;

\OCP\JSON::callCheck();
\OCP\JSON::checkLoggedIn();
\OC_App::loadApps();

$api = new API();

// The API is not active -> nothing to do
if (! \OCP\Contacts::isEnabled()) {
	$msg = 'Autocomplete impossible, because contact app is not enabled';
	\OCP\Util::writeLog('fidelapp', $msg, \OCP\Util::WARN);
	\OC_JSON::error(array (
			'msg' => $msg
	));
}

$term = $_GET ['search'];
// Search in username and e-Mail
$result = $api->search($term, array (
		'FN',
		'EMAIL'
));

$contacts = array ();
foreach ( $result as $r ) {
	$id = $r ['id'];
	$fn = $r ['FN'];

	if (isset($r ['EMAIL'])) {
		$email = $r ['EMAIL'];
		// loop through all email addresses of this contact
		foreach ( $email as $e ) {
			$displayName = $fn . " <$e>";
			$contacts [] = array (
					'label' => $displayName,
					'value' => $e
			);
		}
	}
}
\OC_JSON::success(array (
		'data' => $contacts
));