<?php

namespace OCA\FidelApp;


define('ERROR_WRONG_CLIENT_ID', 6000);
define('ERROR_NO_SECRET_KEY', 6001);
define('ERROR_NO_PASSWORD_SET', 6002);
define('ERROR_INVALID_PASSWORD_SET', 6003);
define('ERROR_NO_SALT_SET', 6004);

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
		parent::__construct("Security exception occured, code = $code", $code);
	}
}

/**
 * The requested file is not available
 */
class FileNotFoundException extends \Exception {
}
