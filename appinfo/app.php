<?php
define('FIDELAPP_APPNAME', 'fidelapp');

$config = parse_ini_file(FIDELAPP_APPNAME . '/config/config.ini');
define('FIDELBOX_URL', $config['FIDELBOX_URL']);

OC::$CLASSPATH ['OCA\FidelApp\API'] = FIDELAPP_APPNAME . '/lib/api.php';
OCP\Util::connectHook('OC_Use', 'post_deleteUser', 'OCA\FidelApp\FidelappHooks', 'deleteUser');
OCP\Util::connectHook('\OCA\Contacts\VCard', 'pre_deleteVCard', 'OCA\FidelApp\Hooks', 'deleteContact');
OCP\Util::connectHook('\OCA\Contacts\VCard', 'post_updateVCard', 'OCA\FidelApp\Hooks', 'updateEmail');

// dont break owncloud when the appframework is not enabled
if (\OCP\App::isEnabled('appframework')) {
	// Load extension of OCA\AppFramework\Core\API
	$api = new OCA\FidelApp\API();

	/**
	 * Dependencies
	 */
	// JQuery

	$api->addScript('chosen/chosen.jquery.min', '3rdparty');
	$api->addScript('fidelshare');
	$api->addStyle('fidelapp_dropdown_style');
	$api->addStyle('fidelapp_style');


	$api->addNavigationEntry(
			array (

					// the string under which your app will be referenced in owncloud
					'id' => $api->getAppName(),

					// sorting weight for the navigation. The higher the number, the higher
					// will it be listed in the navigation
					'order' => 10,

					// the route that will be shown on startup
					'href' => $api->linkToRoute('fidelapp_index'),

					// the icon that will be shown in the navigation
					// this file needs to exist in img/example.png
					'icon' => $api->imagePath('logo.png'),

					// the title of your application. This will be used in the
					// navigation or on the settings page of your app
					'name' => $api->getTrans()->t('FidelApp')
			));
} else {
	// TODO: Show error message in GUI at least once
	$msg = 'Cannot enable the fidelapp because the App Framework App is disabled';
	\OCP\Util::writeLog(FIDELAPP_APPNAME, $msg, \OCP\Util::ERROR);
}