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

/**
 * Error codes from fidelbox.de
 */
define('NO_ACTION_SPECIFIED', 1000);
define('NO_USER_ID', 1001);
define('UNKNOWN_USER', 1002);
define('CAPTCHA_NOT_MATCH', 1003);
define('NO_ACCOUNT_ID', 1004);
define('ACCOUNT_DOES_NOT_EXIST', 1005);
define('INVALID_ACTION', 1006);

/**
 * Error codes for FidelBox config
 */
define('NO_RESULT_FROM_REMOTE_HOST', 7000);
define('UNPARSABLE_RESULT_FROM_REMOTE_HOST', 7001);

use \OCA\FidelApp\API;
use OCA\FidelApp\Db\ShareItem;
use OCP\BackgroundJob;
use OC\BackgroundJob\JobList;

/**
 * Manage interaction with fidelbox.de
 * <ul>
 * <li>Validate captchas</li>
 * <li>Create accounts</li>
 * <li>Delete accounts</li>
 * <li>Validate accounts</li>
 * <li>Handle checksum calculation</li>
 * <li>Handle dynamic IP update</li>
 * </ul>
 */
class FidelboxConfig {
	protected $api;

	public function __construct(API $api) {
		$this->api = $api;

		$this->api->registerFidelappException('CaptchaNotMatchException');
		$this->api->registerFidelappException('InvalidConfigException');
		$this->api->registerFidelappException('ServerNotReachableException');
	}

	/**
	 * Create a fidelbox.de URL for captcha generation
	 *
	 * @param string $tempUserId
	 *        	the temporary user ID needed for captcha generation and validation
	 * @throws \BadMethodCallException when the given paramter was not a string
	 * @return string the URL for captcha generation
	 */
	public function createCaptchaURL($tempUserId) {
		if (! $tempUserId || ! (gettype($tempUserId) == 'string')) {
			throw new \BadMethodCallException("createCaptchaURL: Wrong tempUserId parameter [$tempUserId]");
		}
		return FIDELAPP_FIDELBOX_URL . '/fidelapp/captcha.php?userId=' . urlencode($tempUserId) . '&token=' . uniqid();
	}

	/**
	 * Create a user account on fidelbox.de
	 *
	 * @param string $tempUserId
	 *        	the temporary user ID (needed for captcha generation and validation)
	 * @param string $captcha
	 *        	the captcha, entered by the user
	 * @throws \OCA\FidelApp\CaptchaNotMatchException when the $captcha paramter did not match the generated captcha on fidelbox.de
	 * @throws \RuntimeException if the call to fidelbox.de failed
	 *
	 * @return string the newly generated account ID
	 */
	public function createAccount($tempUserId, $captcha) {
		$l = $this->api->getTrans();

		$captchaNotMatchException = new CaptchaNotMatchException($l->t('Wrong captcha'));
		if (strlen($captcha) != 6) {
			throw $captchaNotMatchException;
		}

		$return = $this->get(
				'/fidelapp/manageaccount.php?userId=' . urlencode($tempUserId) . '&captcha=' . urlencode($captcha) .
						 '&action=create');
		if ($return ['status'] == 'success') {
			// Return the generated account id
			return ($return ['message']);
		}
		$errorCode = isset($return ['code']) ? $return ['code'] : (- 1);
		if (isset($return ['code']) && $return ['code'] == CAPTCHA_NOT_MATCH) {
			throw $captchaNotMatchException;
		}
		$this->raiseError($return);
	}

	/**
	 * Delete an existing account on fidelbox.de
	 *
	 * @param string $accountId
	 *        	the account to be deleted
	 * @throws \BadMethodCallException when the paramter is not a string
	 * @throws \RuntimeException if the call to fidelbox.de failed
	 * @return boolean <code>true</code> if the account existed and was deleted
	 */
	public function deleteAccount($accountId) {
		if (! $accountId || ! (gettype($accountId) == 'string')) {
			throw new \BadMethodCallException("deleteAccount: Wrong accountId parameter [$accountId]");
		}
		$return = $this->get('/fidelapp/manageaccount.php?accountId=' . urlencode($accountId) . '&action=delete');
		if ($return ['status'] != 'success') {
			if (isset($return ['code']) && $return ['code'] == ACCOUNT_DOES_NOT_EXIST) {
				// No need to delete this account, because it does not exist, so do not throw an exception,
				// just tell the caller that no deletion has been executed
				return false;
			}
			$this->raiseError($return);
		}
		return true;
	}

	/**
	 * Validate that the given account ID is active on fidelbox.de
	 *
	 * @param string $accountId
	 * @throws \BadMethodCallException if the parameter is not a string
	 * @return boolean <code>true</code>, if the fidelbox account is active
	 */
	public function validateAccount($accountId) {
		if (! $accountId || ! (gettype($accountId) == 'string')) {
			throw new \BadMethodCallException("validateAccount: Wrong accountId parameter [$accountId]");
		}
		$return = $this->get('/fidelapp/manageaccount.php?accountId=' . urlencode($accountId) . '&action=validate');
		if ($return ['status'] == 'success') {
			return true;
		} else if (isset($return ['code']) && $return ['code'] == ACCOUNT_DOES_NOT_EXIST) {
			return false;
		}
		$this->raiseError($return);
	}

	/**
	 * The download applet's notice of receipt is based on the calculated MD5 hash of a file.
	 * Hashsum - calculation might take quite a while, depending on the file size, so we do it
	 * asynchronously, through a queued task
	 *
	 * @param ShareItem $shareItem
	 *        	the item that designates the file for MD5 calculation
	 */
	public function calculateHashAsync(ShareItem $shareItem) {
		$job = new CalculateMD5BackgroundJob();
		BackgroundJob::registerJob($job, $shareItem->getId());
		//$this->api->addQueuedTask('OCA\FidelApp\CalculateMD5BackgroundJob', 'run', $shareItem->getId());
		// \OCA\FidelApp\CalculateMD5BackgroundJob::run($shareItem->getId());
	}

	/**
	 * Launch a queued task to communicate our current IP address to fidelbox.de on a regular basis
	 */
	public function startRegularIpUpdate() {
		$job = new UpdateIpBackgroundJob();
		// Run it once immediately
		$job->run(null);
		BackgroundJob::registerJob($job);
	}

	/**
	 * Remove the queued task communicating our current IP address to fidelbox.de on a regular basis
	 */
	public function stopRegularIpUpdate() {
		// Unfortunately there is no public API to remove timed tobs from scheduler,
		// So we need to use internal API instead
		$jobList = new JobList();
		$jobList->remove(new UpdateIpBackgroundJob());
	}

	/**
	 * Get the configured fidelbox account ID or throw an Exception
	 *
	 * @throws InvalidConfigException if no account ID is configured
	 *
	 * @return string the fidelbox account ID
	 */
	public function getFidelboxAccountId() {
		$l = $this->api->getTrans();

		$fidelboxAccount = $this->api->getAppValue('fidelbox_account');
		if (! $fidelboxAccount) {
			throw new InvalidConfigException($l->t('Cannot update IP address, because no fidelbox account has been created'));
		}
		return $fidelboxAccount;
	}

	/**
	 * Execute a HTTP/GET to tell fidelbox about our current IP address
	 *
	 * @return string the IP address that has been updated
	 */
	public function updateIp() {
		$url = '/fidelapp/manageaccount.php?accountId=' . urlencode($this->getFidelboxAccountId()) . '&action=updateip';
		$url = $this->addAdditionalInfo($url);
		$return = $this->get($url);
		if ($return ['status'] != 'success') {
			$this->raiseError($return);
		}
		return ($return ['ip']);
	}

	private function addAdditionalInfo($url) {
		$accessType = $this->api->getAppValue('access_type');
		if ($accessType != 'FIDELBOX_REDIRECT') {
			if ($accessType == 'DOMAIN_NAME') {
				$url .= '&ipAddress=' . $this->api->getAppValue('domain_name');
			} else {
				$url .= '&ipAddress=' . $this->api->getAppValue('fixed_ip');
			}
			$url .= '&port=' . $this->api->getAppValue('port');
			$url .= '&useSsl=' . $this->api->getAppValue('use_ssl');
		}
		return $url;
	}
	/**
	 * Check if the server can be reached from Internet
	 *
	 * @return boolean <code>true</code> if this Owncloud server is reacheable via Internet, throw an Exception otherwise
	 * @throws ServerNotReachableException when the ping back failed
	 * @throws \RuntimeException when the call to the fidelserver failed
	 * @throws InvalidConfigException if no account ID is configured
	 */
	public function pingBack() {
		$url = '/fidelapp/manageaccount.php?accountId=' . urlencode($this->getFidelboxAccountId()) .
				 '&action=pingback&pingbackPath=' . urlencode($this->api->linkToRoute('pingback'));
		$url = $this->addAdditionalInfo($url);
		$return = $this->get($url);
		if ($return ['status'] != 'success') {
			if (! isset($return ['message'])) {
				$l = $this->api->getTrans();
				throw new ServerNotReachableException(
						$l->t('Validation of Internet access to this Owncloud server failed for an unknown reason'));
			}
			throw new ServerNotReachableException($return ['message']);
		}
		return true;
	}

	/**
	 * Execute an HTTP/GET request on the fidelbox
	 *
	 * @param string $pathOnServer
	 * @return array JSON decoded response
	 *
	 * @throws \RuntimeException if the result was not JSON encoded or if the result does not contain the 'success' key
	 */
	private function get($pathOnServer) {
		$l = $this->api->getTrans();

		$ctx = stream_context_create(array (
				'http' => array (
						'timeout' => 10
				)
		));
		$url = FIDELAPP_FIDELBOX_URL . $pathOnServer;
		$json = @file_get_contents($url, false, $ctx);
		if (! $json) {
			throw new \RuntimeException($url . $l->t(' did not return any result'), NO_RESULT_FROM_REMOTE_HOST);
		}
		$return = json_decode($json, true);
		if ($return == null) {
			throw new \RuntimeException($url . $l->t(' did return an unparsable  result: ') . $json,
					UNPARSABLE_RESULT_FROM_REMOTE_HOST);
		}
		if ($return ['status'] != 'success') {
			if (! isset($return ['message']) && ! isset($return ['code'])) {
				throw new \RuntimeException($l->t('Unexpected error while calling ' . $url), - 1);
			}
			// Add URL parameter in case of error, to simplify debugging
			$return ['called_url'] = $url;
		}
		return $return;
	}

	/**
	 * Throw an exception
	 *
	 * @param string $jsonReturn
	 *        	JSON - Encoded result
	 * @throws \RuntimeException on every call
	 */
	private function raiseError($jsonReturn) {
		$l = $this->api->getTrans();

		$code = isset($jsonReturn ['code']) ? $jsonReturn ['code'] : (- 1);
		$message = isset($jsonReturn ['message']) ? $jsonReturn ['message'] : 'no message';
		$calledUrl = isset($jsonReturn ['called_url']) ? $jsonReturn ['called_url'] : 'unknown url';

		throw new \RuntimeException(
				$l->t('The following error occurred while while calling ') . $calledUrl . ": [$code] " . $message, $code);
	}
}