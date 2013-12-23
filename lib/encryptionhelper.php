<?php

namespace OCA\FidelApp;

class EncryptionHelper {

	/**
	 * Decrypt and extract an URL encoded client ID with stuffing
	 *
	 * @param string $encryptedContactId
	 *        	the encrypted client ID
	 * @param string $password
	 *        	the password used for encryption
	 * @throws SecurityException when decryption failed
	 * @return number the decrypted client ID
	 */
	public static function processContactId($encryptedContactId, $password) {
		try {
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