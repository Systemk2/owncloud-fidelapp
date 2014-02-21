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

class EncryptionHelper {
	private $api;

	public function __construct(API $api) {
		$this->api = $api;
	}

	/**
	 * Decrypt and extract an URL encoded client ID with stuffing
	 *
	 * @param string $encryptedContactId
	 *        	the encrypted client ID
	 * @throws SecurityException when decryption failed
	 * @return number the decrypted client ID
	 */
	public function processContactId($encryptedContactId) {
		$passwordBase64 = $this->api->getAppValue('secret');
		if (! $passwordBase64) {
			throw new SecurityException(ERROR_NO_SECRET_KEY);
		}
		try {
			$password = base64_decode($passwordBase64, true);
			$encryptedBinary = $bin_str = pack("H*", $encryptedContactId);
			$td = mcrypt_module_open('rijndael-128', '', 'ofb', '');
			mcrypt_generic_init($td, $password, '0000000000000000');
			$contactId = mdecrypt_generic($td, $encryptedBinary);
			mcrypt_generic_deinit($td);
			if (preg_match('?^[0-9]{32}$?', $contactId)) {
				$position = ( int ) substr($contactId, 0, 2);
				if ($position >= 2 && $position <= 28) {
					$decryptedContactId = ( int ) substr($contactId, $position, 4);
					return $decryptedContactId;
				}
			}
		} catch(Exception $e) {
			// ignore (Exception is thrown below)
		}
		throw new SecurityException(ERROR_WRONG_CLIENT_ID);
	}
}