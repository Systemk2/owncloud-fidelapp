<?php

namespace OCA\FidelApp;

\OC::$CLASSPATH ['OCA\FidelApp\CaptchaNotMatchException'] = 'fidelapp/lib/exception.php';

use \OCA\FidelApp\API;
use \OCA\FidelApp\Db\ConfigItemMapper;
use OCA\FidelApp\Db\ConfigItem;

class FidelboxConfig {
	protected $api;

	public function __construct(API $api) {
		$this->api = $api;
	}

	public function createCaptchaURL($tempUserId) {
		if(!$tempUserId || ! (gettype($tempUserId) == 'string')) {
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

		// set a sensible timeout of 10 sec to stay responsive even if the fidelbox server is down.
		$ctx = stream_context_create(array (
				'http' => array (
						'timeout' => 10
				)
		));
		$json = @file_get_contents(
				FIDELBOX_URL . '/fidelapp/createaccount.php?userId=' . urlencode($tempUserId) . '&captcha=' . urlencode($captcha), false,
				$ctx);
		if (! $json) {
			throw new \RuntimeException(FIDELBOX_URL . $l->t(' did not return any result'));
		}
		$return = json_decode($json, true);
		if ($return == null) {
			throw new \RuntimeException(FIDELBOX_URL . $l->t(' did return an unparsable  result: ') . $json);
		}
		if ($return ['status'] != 'success') {
			if (! isset($return ['message'])) {
				throw new \RuntimeException($l->t('Unexpected error while trying to create account on ' . FIDELBOX_URL));
			}
			if ($return ['message'] == 'Captcha did not match') {
				throw $captchaNotMatchException;
			} else {
				throw new \RuntimeException(
						$l->t(
								'The following error occurred while trying to create account on ' . FIDELBOX_URL . ': ' .
										 $return ['message']));
			}
		}
		// Return the generated account id
		return ($return ['message']);
	}
}