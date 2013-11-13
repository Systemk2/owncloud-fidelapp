<?php

namespace OCA\FidelApp;

/**
 * The entered captcha did not match the image
 */
class CaptchaNotMatchException extends \Exception {
}

/**
 * An entered password does not fit (or does not meet the password requirements)
 */
class PasswordNotGoodException extends \Exception {
}