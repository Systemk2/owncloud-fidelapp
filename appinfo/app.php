<?php

namespace OCA\FidelApp;

define('FIDELAPP_APPNAME', 'fidelapp');

$config = parse_ini_file(FIDELAPP_APPNAME . '/config/config.ini');
define('FIDELBOX_URL', $config ['FIDELBOX_URL']);

class Init {

	public static function checkPrerequisites() {
		$l = new \OC_L10N(FIDELAPP_APPNAME);
		$return = array (
				'errors' => array (),
				'warnings' => array ()
		);
		if (! \OCP\App::isEnabled('appframework')) {
			$return ['errors'] [] = $l->t(
					'This App requires the Appframework App. Please install and activate the Appframework App');
		}
		if (! \OCP\App::isEnabled('contacts')) {
			$return ['errors'] [] = $l->t('This App requires the Contacts App. Please install and activate the Contacts App');
		}
		if (! function_exists('mcrypt_generic_init')) {
			$return ['warnings'] [] = $l->t(
					'Currently only BASIC file delivery is supported, because SECURE file transmission mode requires the mcrypt encryption library, see http://php.net/manual/en/mcrypt.setup.php');
		}
		if (\OC_BackgroundJob::getExecutionType() != 'cron') {
			$return ['warnings'] [] = $l->t(
					'When using SECURE file delivery mode, it is strongly recommended to use CRON for background job control ' .
							 '(see http://doc.owncloud.org/server/6.0/admin_manual/configuration/background_jobs.html) ' .
							 'Otherwise the App might not be able to calculate checksums on large files for secure file delivery');
		}
		return $return;
	}
}

$checkResult = Init::checkPrerequisites();
// dont break owncloud when the appframework or contacts app are not enabled
if (count($checkResult ['errors']) == 0) {
	$hasWarnings = count($checkResult ['warnings']) > 0;
	\OC::$CLASSPATH ['OCA\FidelApp\API'] = FIDELAPP_APPNAME . '/lib/api.php';
	\OCP\Util::connectHook('OC_Use', 'post_deleteUser', 'OCA\FidelApp\FidelappHooks', 'deleteUser');
	\OCP\Util::connectHook('\OCA\Contacts\VCard', 'pre_deleteVCard', 'OCA\FidelApp\Hooks', 'deleteContact');
	\OCP\Util::connectHook('\OCA\Contacts\VCard', 'post_updateVCard', 'OCA\FidelApp\Hooks', 'updateEmail');
	\OCP\Util::connectHook('\OCA\Files_Trashbin\Trashbin', 'post_moveToTrash', 'OCA\FidelApp\Hooks', 'moveFileToTrash');
	\OCP\Util::connectHook(\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_delete, 'OCA\FidelApp\Hooks',
			'deleteFile');

	// Load extension of OCA\AppFramework\Core\API
	$api = new API();

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
					// this file needs to exist in img/...
					'icon' => $api->imagePath($hasWarnings ? 'logo_warning.png' : 'logo.png'),

					// the title of your application. This will be used in the
					// navigation or on the settings page of your app
					'name' => 'FidelApp'
			));
} else {
	// Degraded mode because of missing appframework API
	\OCP\App::addNavigationEntry(
			array (
					'id' => FIDELAPP_APPNAME,
					'order' => 10,
					'href' => \OCP\Util::linkTo(FIDELAPP_APPNAME, 'fidelapp_init_failed.php'),
					'icon' => \OCP\Util::imagePath(FIDELAPP_APPNAME, 'logo_error.png'),
					'name' => 'FidelApp'
			));
	$msg = 'Cannot enable the fidelapp because the prequisites are not met: ' . print_r($checkResult, true);
	\OCP\Util::writeLog(FIDELAPP_APPNAME, $msg, \OCP\Util::ERROR);
}