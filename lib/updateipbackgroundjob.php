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
use \OC\BackgroundJob\TimedJob;

// Run at most every 5 minutes (maybe longer, depending on the cron job definition)
define('MIN_TIME_INTERVAL_SECS', 299);

class UpdateIpBackgroundJob extends TimedJob {

	public function __construct() {
		$this->setInterval(MIN_TIME_INTERVAL_SECS);
	}

	// TODO: Add documentation
	public function run($argument) {
		try {
			\OC_Log::write(FIDELAPP_APPNAME, 'Starting UpdateIpBackgroundJob', \OC_Log::DEBUG);

			$api = new API(FIDELAPP_APPNAME);
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