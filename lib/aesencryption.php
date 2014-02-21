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

// testFileEncryption();
function testFileEncryption() {
	$chunksize = 64;
	$key = '12345678901234561234567890123456';
	$iv = '1234567890123456';

	$aesEncryption = new AESEncryption($key, $iv, $chunksize);

	$aesEncryption->encryptFile("C:\\wamp\\www\\cusweb_db.sql", "C:\\wamp\\www\\cusweb_db.sql.encrypted");

	$aesEncryption = new AESEncryption($key, $iv, $chunksize);
	$aesEncryption->decryptFile("C:\\wamp\\www\\cusweb_db.sql.encrypted", "C:\\wamp\\www\\cusweb_db.sql.decrypted");
}

function testEncryption() {
	$chunksize = 1024 * 1024;
	$key = '12345678901234561234567890123456';
	$iv = '1234567890123456';

	$aesEncryption = new AESEncryption($key, $iv, $chunksize);
	$encrypted = $aesEncryption->encrypt("This is a test text");
	echo "Encrypted: [" . bin2hex($encrypted) . "]<br>";
	echo "Decrypted: [" . $aesEncryption->decrypt($encrypted) . "]";
}

class AESEncryption {
	protected $chunksize;
	protected $key;
	protected $iv;

	public function __construct($theKey, $theIv, $theChunksize = 4096) {
		$this->chunksize = $theChunksize;
		$this->key = $theKey;
		$this->iv = $theIv;
	}

	public function getChunksize() {
		return $this->chunksize;
	}

	public function getKey() {
		return $this->key;
	}

	public function getIv() {
		return $this->iv;
	}

	public function encrypt($data) {
		$cipher = $this->initMcrypt();
		$blockSize = mcrypt_enc_get_block_size($cipher);
		$input = $this->pkcs5_pad($data, $blockSize);
		$cipherText = mcrypt_generic($cipher, $input);
		// $cipherText = mcrypt_generic($cipher, $this->pad($data));

		$this->closeMcrypt($cipher);
		return $cipherText;
	}

	private function pkcs5_pad($text, $blocksize) {
		$pad = $blocksize - (strlen($text) % $blocksize);
		if ($pad > 0)
			return $text . str_repeat(chr($pad), $pad);
		return $text . str_repeat(chr($blocksize), $blocksize);
	}

	private function pkcs5_unpad($text) {
		$pad = ord(substr($text, - 1));
		$unpadded = substr($text, 0, strlen($text) - $pad);
		return $unpadded;
	}

	public function decrypt($cipherText) {
		$cipher = $this->initMcrypt();
		$decoded = mdecrypt_generic($cipher, $cipherText);
		$this->closeMcrypt($cipher);
		return $this->pkcs5_unpad($decoded);
	}

	public function encryptChunk($fileName, $chunkNumber) {
		$inputFile = fopen($fileName, 'rb') or 		// TODO: Throw exception
		die("Could not open $inputFileName for reading");
		$cipher = $this->initMcrypt();
		$blockSize = mcrypt_enc_get_block_size($cipher);

		$chunkSize = $this->getChunksize();
		fseek($inputFile, ($chunkNumber - 1) * $chunkSize);
		$cipherText = "";
		$remaining = $this->getChunksize();
		// while (!feof($inputFile) && $remaining > 0) {
		$buffer = fread($inputFile, $chunkSize);
		$remaining -= strlen($buffer);
		$buffer = $this->pkcs5_pad($buffer, $blockSize);
		$cipherText .= mcrypt_generic($cipher, $buffer);
		// }
		fclose($inputFile);
		$this->closeMcrypt($cipher);
		return ($cipherText);
	}

	public function encryptFile($inputFileName, $outputFileName) {
		// TODO: Throw exception
		$inputFile = fopen($inputFileName, 'rb') or die("Could not open $inputFileName for reading");

		$outputFile = fopen($outputFileName, 'wb') or die("Could not open $outputFileName for writing");

		$cipher = $this->initMcrypt();
		$blockSize = mcrypt_enc_get_block_size($cipher);
		$fileSize = filesize($inputFileName);
		$chunkSize = $this->getChunksize();
		$numberOfChunks = floor($fileSize / $chunkSize);
		$padding = $blockSize - ($fileSize % $blockSize);
		if ($padding != 0)
			$numberOfChunks ++;
		$chunksRead = 0;
		while ( ! feof($inputFile) ) {
			$buffer = fread($inputFile, $chunkSize);
			$chunksRead ++;
			if ($chunksRead == $numberOfChunks) {
				$buffer = $this->pkcs5_pad($buffer, $blockSize);
			}
			$cipherText = mcrypt_generic($cipher, $buffer);
			fwrite($outputFile, $cipherText);
		}
		fclose($inputFile);
		fclose($outputFile);
		$this->closeMcrypt($cipher);
	}

	public function decryptFile($inputFileName, $outputFileName) {
		$inputFile2 = fopen($inputFileName, 'rb')
		// TODO: Throw exception
			or die or
("Could not open $inputFileName for reading");

		$outputFile2 = fopen($outputFileName, 'wb') or die("Could not open $outputFileName for writing");

		$cipher = $this->initMcrypt();

		$blockSize = mcrypt_enc_get_block_size($cipher);
		$fileSize = filesize($inputFileName);
		$chunkSize = $this->getChunksize();
		$numberOfChunks = floor($fileSize / $chunkSize);
		if ($fileSize % $chunkSize > 0)
			$numberOfChunks ++;
		$chunksRead = 0;
		while ( ! feof($inputFile2) ) {
			$cipherText = fread($inputFile2, $chunkSize);
			$chunksRead ++;
			if (strlen($cipherText) > 0) {
				$decoded = mdecrypt_generic($cipher, $cipherText);
				if ($chunksRead == $numberOfChunks) {
					$decoded = $this->pkcs5_unpad($decoded, $blockSize);
				}
				fwrite($outputFile2, $decoded);
			}
		}
		fclose($inputFile2);
		fclose($outputFile2);

		$this->closeMcrypt($cipher);
	}

	public function initMcrypt() {
		$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		$iv_size = mcrypt_enc_get_iv_size($cipher);
		if (mcrypt_generic_init($cipher, $this->getKey(), $this->getIv()) == - 1)
			die("mcrypt_generic_init failed"); // TODO: Throw exception
		if ($this->getChunksize() < mcrypt_enc_get_block_size($cipher) ||
				 ($this->getChunksize() % mcrypt_enc_get_block_size($cipher)) != 0)
			die("Chunksize must be multiple of blocksize");
		return $cipher;
	}

	public function closeMcrypt($cipher) {
		mcrypt_generic_deinit($cipher);
	}
}
?>