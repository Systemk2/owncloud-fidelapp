<?php
/**
 * ownCloud - FidelApp (File Delivery App)
 *
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH  info@systemk2.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


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