<?php

namespace OCA\FidelApp;

\OC::$CLASSPATH ['OCA\FidelApp\CaptchaNotMatchException'] = 'fidelapp/lib/exception.php';

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
use \OCA\FidelApp\Db\ConfigItemMapper;
use OCA\FidelApp\Db\ConfigItem;

class FidelboxConfig {
	protected $api;

	public function __construct(API $api) {
		$this->api = $api;
	}

	public function createCaptchaURL($tempUserId) {
		if (! $tempUserId || ! (gettype($tempUserId) == 'string')) {
			throw new \BadMethodCallException("createCaptchaURL: Wrong tempUserId parameter [$tempUserId]");
		}
		return FIDELBOX_URL . '/fidelapp/captcha.php?userId=' . urlencode($tempUserId) . '&token=' . uniqid();
	}

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

	public function deleteAccount($accountId) {
		if (! $accountId || ! (gettype($accountId) == 'string')) {
			throw new \BadMethodCallException("deleteAccount: Wrong accountId parameter [$accountId]");
		}
		$return = $this->get('/fidelapp/manageaccount.php?accountId=' . urlencode($accountId) . '&action=delete');
		if($return['status'] != 'success') {
			if(isset($return['code']) && $return['code'] == ACCOUNT_DOES_NOT_EXIST) {
				// No need to delete this account, because it does not exist, so do not throw an exception,
				// just tell the caller that no deletion has been executed
				return false;
			}
			$this->raiseError($return);
		}
		return true;
	}

	public function validateAccount($accountId) {
		if (! $accountId || ! (gettype($accountId) == 'string')) {
			throw new \BadMethodCallException("validateAccount: Wrong accountId parameter [$accountId]");
		}
		$return = $this->get('/fidelapp/manageaccount.php?accountId=' . urlencode($accountId) . '&action=validate');
		if($return['status'] == 'success') {
			return true;
		} else if(isset($return['code']) && $return['code'] == ACCOUNT_DOES_NOT_EXIST) {
			return false;
		}
		$this->raiseError($return);
	}

	private function get($pathOnServer) {
		$l = $this->api->getTrans();

		$ctx = stream_context_create(array (
				'http' => array (
						'timeout' => 10
				)
		));
		$url = FIDELBOX_URL . $pathOnServer;
		$json = @file_get_contents($url, false, $ctx);
		if (! $json) {
			throw new \RuntimeException($url . $l->t(' did not return any result'), NO_RESULT_FROM_REMOTE_HOST);
		}
		$return = json_decode($json, true);
		if ($return == null) {
			throw new \RuntimeException($url . $l->t(' did return an unparsable  result: ') . $json, UNPARSABLE_RESULT_FROM_REMOTE_HOST);
		}
		if ($return ['status'] != 'success') {
			if(! isset($return ['message']) && ! isset($return ['code'])) {
				throw new \RuntimeException($l->t('Unexpected error while calling ' . $url), -1);
			}
			// Add URL parameter in case of error, to simplify debugging
			$return['called_url'] = $url;
		}
		return $return;
	}

	private function raiseError($jsonReturn) {
		$l = $this->api->getTrans();

		$code = isset($jsonReturn['code']) ? $jsonReturn['code'] : (-1);
		$message = isset($jsonReturn['message']) ? $jsonReturn['message'] : 'no message';
		$calledUrl = isset($jsonReturn['called_url']) ? $jsonReturn['called_url'] : 'unknown url';

		throw new \RuntimeException(
				$l->t('The following error occurred while while calling ') . $calledUrl . ": [$code] " . $message,
				$code);
	}
}