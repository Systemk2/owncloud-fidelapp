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
