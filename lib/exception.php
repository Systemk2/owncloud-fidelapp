<?php

namespace OCA\FidelApp;

/**
 * The entered captcha did not match the image
 */
class CaptchaNotMatchException extends \Exception {
}

/**
 * The fidelapp set-up has not been completed
 */
class InvalidConfigException extends \Exception {
}

/**
 * The provided credentials are not accepted
 */
class SecurityException extends \Exception {
	public function __construct($code) {
		parent::__construct('Security exception occured', $code);
	}
}

