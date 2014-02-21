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


define('ERROR_WRONG_CLIENT_ID', 6000);
define('ERROR_NO_SECRET_KEY', 6001);
define('ERROR_NO_PASSWORD_SET', 6002);
define('ERROR_INVALID_PASSWORD_SET', 6003);
define('ERROR_NO_SALT_SET', 6004);
define('FILE_NOT_FOUND', 6005);
define('WRONG_DOWNLOAD_TYPE', 6006);

/**
 * The entered captcha did not match the image
 */
class CaptchaNotMatchException extends \Exception {
}

/**
 * The fidelapp set-up has not been completed, or other security issues
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
	public function __construct($fileName) {
		parent::__construct("FileNotFound: $fileName", FILE_NOT_FOUND);
	}
}
/**
 * The Owncloud server is not reachable from Internet
 */
class ServerNotReachableException extends \Exception {
}

/**
 * Somebody tries to download a file in basic mode, but it should be secure
 */
class WrongDownloadTypeException extends \Exception {
	public function __construct($fileName) {
		parent::__construct("Wrong download type: $fileName", WRONG_DOWNLOAD_TYPE);
	}
}
