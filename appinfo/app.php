<?php
define('FIDELBOX_URL', 'http://192.168.0.123/fidelserver');

OC::$CLASSPATH ['OCA\FidelApp\API'] = 'fidelapp/lib/api.php';

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
					'icon' => $api->imagePath('logo_small.png'),

					// the title of your application. This will be used in the
					// navigation or on the settings page of your app
					'name' => $api->getTrans()->t('FidelApp')
			));
} else {
	$msg = 'Can not enable the fidelapp because the App Framework App is disabled';
	\OCP\Util::writeLog($api->getAppName(), $msg, \OCP\Util::ERROR);
}