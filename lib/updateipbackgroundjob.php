<?php

namespace OCA\FidelApp;

use OCA\FidelApp\Db\ConfigItemMapper;
// Run at most every 5 minutes (maybe longer, depending on the cron job definition)
define('MIN_TIME_INTERVAL_SECS', 299);

class UpdateIpBackgroundJob {

	// TODO: Add documentation
	public static function run() {
		try {
			\OC_Log::write(FIDELAPP_APPNAME, 'Starting UpdateIpBackgroundJob', \OC_Log::DEBUG);

			$api = new API(FIDELAPP_APPNAME);
			// Add this task for next run (Queued tasks are only run once)
			$taskId = $api->addQueuedTask('OCA\FidelApp\UpdateIpBackgroundJob');
			$api->setAppValue('update_ip_task_id', $taskId);

			$lastUpdateTime = $api->getAppValue('last_ip_update');
			$current = new \DateTime();
			if ($lastUpdateTime != null) {
				$last = new \DateTime($lastUpdateTime);
				$dateInterval = new \DateInterval('PT' . MIN_TIME_INTERVAL_SECS . 'S');
				$current->sub($dateInterval);

				if ($current < $last) {
					\OC_Log::write(FIDELAPP_APPNAME, 'Skipping update of ip address, because of minimum time interval',
							\OC_Log::DEBUG);
					return;
				}
			}
			$config = new FidelboxConfig($api);
			$ip = $config->updateIp();
			$now = new \DateTime();
			$api->setAppValue('last_ip_update', $now->format(\DateTime::ATOM));
			\OC_Log::write(FIDELAPP_APPNAME, "Transmitted server IP address '$ip' to fidelbox server", \OC_Log::INFO);
		} catch(\Exception $e) {
			\OC_Log::write(FIDELAPP_APPNAME, "Error while executing regular update of ip address " . $e->getMessage(),
					\OC_Log::ERROR);
		}
	}
}