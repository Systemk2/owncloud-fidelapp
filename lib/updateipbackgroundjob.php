<?php

namespace OCA\FidelApp;

use OCA\FidelApp\Db\ConfigItemMapper;
// Run at most every 5 minutes (maybe longer, depending on the cron job definition)
define('MIN_TIME_INTERVAL_SECS', 299);

class UpdateIpBackgroundJob {

	// TODO: Add documentation
	public static function run() {
		try {
			$api = new API(FIDELAPP_APPNAME);

			$lastUpdateTime = $api->getAppValue('last_ip_update');
			$current = new \DateTime();
			if($lastUpdateTime != null) {
				$last = new \DateTime($lastUpdateTime);
				$current->sub(new \DateInterval('PT' . MIN_TIME_INTERVAL_SECS . 'S'));

				if($current < $last) {
					return;
				}
			}
			$config = new FidelboxConfig($api);
			$config->updateIp();
			$api->setAppValue('last_ip_update', $current->format(\DateTime::ATOM));
		} catch(\Exception $e) {
			\OC_Log::write(FIDELAPP_APPNAME,
					"Error while executing regular update of ip address " . $e->getMessage(), \OC_Log::ERROR);
		}
	}
}