<?php
OC::$CLASSPATH['OCA\FidelApp\API'] = 'fidelapp/lib/api.php';

// dont break owncloud when the appframework is not enabled
if(\OCP\App::isEnabled('appframework')){

	/**
	 * Dependencies
	 */
	// JQuery
	\OCP\Util::addScript('3rdparty', 'chosen/chosen.jquery.min');
	// file delivery context script
	\OCP\Util::addScript('fidelapp', 'fidelshare' );
	\OCP\Util::addStyle('fidelapp', 'style' );
	
	// Load extension of OCA\AppFramework\Core\API
    $api = new OCA\FidelApp\API();

    $api->addNavigationEntry(array(

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
  \OCP\Util::writeLog('fidelapp', $msg, \OCP\Util::ERROR);
}