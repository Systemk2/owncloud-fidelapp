<?php

namespace OCA\FidelApp\Controller;

\OC::$CLASSPATH ['OCA\FidelApp\SecurityException'] = FIDELAPP_APPNAME . '/lib/exception.php';

define('ERROR_WRONG_CLIENT_ID', 6000);

use OCA\AppFramework\Controller\Controller;
use OCA\FidelApp\Db\ShareItemMapper;
use OCA\FidelApp\SecurityException;
use OCA\FidelApp\FidelboxConfig;

class AppletAccessController extends Controller {

	public function __construct($api, $request) {
		parent::__construct($api, $request);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function getFilesForClient() {
		// TODO: Add brute force prevention
		// (e.g. http://stackoverflow.com/questions/15798918/what-is-the-best-method-to-prevent-a-brute-force-attack)
		try {
			$fidelboxConfig = new FidelboxConfig($this->api);

			$decryptedContactId = $this->processContactId($this->params('client_id'), $fidelboxConfig->getFidelboxAccountId());

			$shareItemMapper = new ShareItemMapper($api);
			$shareItems = $shareItemMapper->findByContact($decryptedContactId);
			return $this->render('filelistforapplet', array (
					'shareItems' => $shareItems
			), '');
		} catch(\Exception $e) {
			return ($this->render('error', array (
					'errors' => array (
							$e->getMessage()
					)
			), ''));
		}
	}

	public function processAppletRequest() {
		if ($this->params('chunk-id'))
			$chunkId = $this->params('chunk-id');
		else
			$chunkId = 1;
		$submissionId = $this->params('submission-id');
		$contactId = $this->processContactId($this->params('client-id'));

		$share = new Share(array (
				'id' => $submissionId
		));
		if (! $share->load(false))
			throw new CorruptDataException(ERROR_INVALID_PARAMETER . ': submissionId=$submissionId'); // TODO
		$fileName = $share->getFile();
		$pass = $share->getContact()->getPassword();
		if ($pass == '')
			throw new WrongPasswordException($pass); // TODO
		$pass = str_replace(' ', '/', $pass);
		while ( strlen($pass) < 12 ) // Stuff to at least 12 characters
			$pass .= $pass;
		$pass = substr($pass, 0, 12); // Cut to exactly 12 characters
		\OC_Log::write($this->api->getAppName(), 'pass=$pass', \OC_Log::DEBUG);
		$key = base64_decode($pass, true);
		if (! $key)
			throw new WrongPasswordException($pass); // TODO
		if (! $share->getSalt()) {
			$salt = '';
			for($i = 0; $i < 7; $i ++)
				$salt .= pack('C', mt_rand(0, 255));
			$share->setSalt(bin2hex($salt));
			$share->save(false);
		}
		// Combine password and salt
		$key .= pack('H*', $share->getSalt());

		\OC_Log::write($this->api->getAppName(), 'Salt=' . $share->getSalt(), \OC_Log::DEBUG);
		if (! file_exists($fileName))
			throw new FileNotFoundException($fileName); // TODO
		$fileLength = filesize($fileName);
		$chunkEncryptedLength = floor(CHUNK_SIZE / 16) * 16 + 16;
		$lastChunkPlainLength = $fileLength % CHUNK_SIZE;
		if ($lastChunkPlainLength) {
			$chunkCount = floor($fileLength / CHUNK_SIZE) + 1;
			$lastChunkEncryptedLength = floor($lastChunkPlainLength / 16) * 16 + 16;
		} else {
			$chunkCount = $fileLength / CHUNK_SIZE;
			$lastChunkEncryptedLength = $chunkEncryptedLength;
		}
		if ($this->params('request') == 'getchunk') {
			$fileEncryptedLength = max($chunkCount - 1, 0) * $chunkEncryptedLength + $lastChunkEncryptedLength;
			$plainPos = ($chunkId - 1) * CHUNK_SIZE;
			$encryptedPos = ($chunkId - 1) * $chunkEncryptedLength;
			// The download client does not handle Initialization Vectors
			$iv = pack('H*', '00000000000000000000000000000000');
			$aesEncryption = new AESEncryption($key, $iv, CHUNK_SIZE);
			$encryptedChunk = $aesEncryption->encryptChunk($fileName, $chunkId);

			$encryptedMessageDigest = md5($encryptedChunk);

			// Global submission information
			$this->createHeader('Content-Type', 'text/plain; charset=UTF-8');
			$this->createHeader('id', $submissionId);
			$this->createHeader('fileName', urlencode(preg_replace('?.*/?', '', $fileName)));
			$this->createHeader('filePlainLength', $fileLength);
			$this->createHeader('fileEncryptedLength', $fileEncryptedLength);
			$this->createHeader('fileBlockLength', $fileEncryptedLength);
			$this->createHeader('chunkCount', $chunkCount);
			$this->createHeader('comment', $share->getComment());
			$this->createHeader('filePlainDigest', $share->getChecksum());

			// Individual chunk information
			$this->createHeader('chunkId', $chunkId);
			$this->createHeader('chunk.$chunkId.plainPos', $plainPos);
			$this->createHeader('chunk.$chunkId.encryptedPos', $encryptedPos);
			$this->createHeader('chunk.$chunkId.blockPos', $encryptedPos);
			if ($chunkId == $chunkCount) {
				$this->createHeader('chunk.$chunkId.plainLength', $lastChunkPlainLength);
				$this->createHeader('chunk.$chunkId.encryptedLength', $lastChunkEncryptedLength);
				$this->createHeader('chunk.$chunkId.blockLength', $lastChunkEncryptedLength);
			} else {
				$this->createHeader('chunk.$chunkId.plainLength', CHUNK_SIZE);
				$this->createHeader('chunk.$chunkId.encryptedLength', $chunkEncryptedLength);

				// blockLength is MAX(chunkEncryptedLength, plainLength)
				// It might differ from chunkEncryptedLength one day (e.g. when a zip algorithm is implemented),
				// but for the moment, it is always chunkEncryptedLength
				$this->createHeader('chunk.$chunkId.blockLength', $chunkEncryptedLength);
			}
			$this->createHeader('chunk.$chunkId.encryptedMessageDigest', $encryptedMessageDigest);

			$chunk = new Chunk(array (
					'chunk_id' => $chunkId,
					'share_id' => $share->getId()
			));
			if (! $chunk->load() || $chunk->getChecksum() != $encryptedMessageDigest) {
				$chunk->setChecksum($encryptedMessageDigest);
				\OC_Log::write($this->api->getAppName(), 'Storing chunk digest: ' . $chunk->__toString(), \OC_Log::DEBUG);
				$chunk->save(); // TODO
			}
			return $encryptedChunk;
		} else if ($this->params('request') == 'getkey') {
			$checksums = $request->getProperties('chunk-message-digest.*');
			$corruptChunks = array ();
			for($i = 1; $i <= $chunkCount; $i ++) {
				$chunk = new Chunk(array (
						'chunk_id' => $i,
						'share_id' => $share->getId()
				));
				if (! $chunk->load() || ! $this->params('chunk-message-digest_$i') ||
						 (strtolower($this->params('chunk-message-digest_$i')) != $chunk->getChecksum())) {
					$corruptChunks [] = $i;
					\OC_Log::write($this->api->getAppName(), 'Validation of chunk $i failed', \OC_Log::WARN);
				} else {
					\OC_Log::write($this->api->getAppName(), 'Validation of chunk $i successful', \OC_Log::DEBUG);
				}
			}

			if (count($corruptChunks) == 0) {
				$this->createHeader('Content-Type', 'application/fidel-key-salt');
				$keyDigest = md5($key);
				$this->createHeader('Key-Digest', $keyDigest);
				\OC_Log::write($this->api->getAppName(), 'Setting header : Key-Digest: $keyDigest', \OC_Log::DEBUG);
				return pack('H*', $share->getSalt());
			} else {
				\OC_Log::write($this->api->getAppName(), 'Returning a list of corrupt IDs to download client', \OC_Log::DEBUG);
				$this->createHeader('Content-Type', 'application/fidel-chunk-ids');
				// pack() does not accept arrays
				$returnData = '';
				foreach ( $corruptChunks as $corruptChunk ) {
					$returnData .= pack('N', $corruptChunk); // Format as unsigned 32 bit big endian
				}
				return $returnData;
			}
		} else if ($this->params('request') == 'confirm') {
			$expectedHash = $share->getChecksum();
			if (strtolower($this->params('plain-file-hash')) == $expectedHash) {
				\OC_Log::write($this->api->getAppName(), "Finalized download of $fileName", \OC_Log::INFO);
				$share->setDownloadTime(date('Y-m-d H:i:s'));
				$share->save(false); // TODO
				$shareList = new ShareList();
				$shareList->load(false);
				$fileInUse = false;
				foreach ( $shareList->getShares() as $existingShare ) {
					if ($existingShare->getFile() == $fileName) {
						if (! $existingShare->getDownloadTime() && ! $existingShare->isObsolete()) {
							\OC_Log::write($this->api->getAppName(),
									"File $fileName is still in use by share " . $existingShare->__toString(), \OC_Log::DEBUG);
							$fileInUse = true;
							break;
						}
					}
				}
				// TODO Remove shares, if not used anymore
				/*
				 * if(!$fileInUse) { \OC_Log::write($this->api->getAppName(), "File $fileName is not used any more, deleting it", \OC_Log::DEBUG); $file = $share->getFile(); unlink($file); // TODO if(file_exists('$file.fidel.md5')) unlink('$file.fidel.md5'); if(file_exists('$file.lock.fidel.md5')) unlink('$file.lock.fidel.md5'); $userDir = preg_replace('#(' . DATA_ROOT . '/?user[0-9]{1,}).*$#', '\\1', $file); $this->logger->debug('Removing empty subdirs of $userDir'); removeEmptyDirs($userDir, $this->logger); }
				 */
			} else {
				\OC_Log::write($this->api->getAppName(),
						'Plain file hash for ' . $share->__toString() . '  is not correct!' .
								 ' Expected $expectedHash but received ' . $this->params('plain-file-hash'), \OC_Log::WARN);
			}
			// TODO: Handle Notification Email
			/*
			 * if($share->getNotificationEmail()) { $this->logger->debug('Notification e-mail requested, checking if this is last file for contact ' . $share->getContact()->__toString()); $lastFileForContact = true; foreach($shareList->getShares() as $existingShare) { if($existingShare->getContact()->equals($share->getContact())) { if(!$existingShare->getDownloadTime() && !$existingShare->isObsolete() && $existingShare->getNotificationEmail()) { $this->logger->debug('There is still an existing share ' . $existingShare->__toString() . ' for contact ' . $share->getContact()->__toString()); $lastFileForContact = false; break; } } } if($lastFileForContact) { $this->logger->debug('This was the last shared file with e-mail notification for contact ' . $share->getContact()->__toString() . ', sending e-mail notification'); $reply = $this->sendNotificationMail($share); if($reply != 'OK') { $this->logger->warn('Sending of notification email failed with reply $reply'); } else { $this->logger->info('Successfully sent e-mail notification to ' . $share->getNotificationEmail() . ' for contact ' . $share->getContact()->__toString()); } } }
			 */
		}
		return ''; // TODO
	}

	private function createHeader($name, $value) {
		header('$name: $value');
		\OC_Log::write($this->api->getAppName(), "Created header '$name' with value '$value'", \OC_Log::DEBUG);
	}


	/**
	 * Decrypt and extract an URL encoded client ID with stuffing
	 *
	 * @param string $encryptedContactId the encrypted client ID
	 * @param string $password the password used for encryption
	 * @throws SecurityException when decryption failed
	 * @return number the decrypted client ID
	 */
	private function processContactId($encryptedContactId, $password) {
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
					\OC_Log::write($this->api->getAppName(), 'Decrypted client_id ' . $decryptedContactId, \OC_Log::DEBUG);
					return $decryptedContactId;
				}
			}
		} catch(Exception $e) {
			// ignore (Exception is thrown later)
		}
		throw new SecurityException(ERROR_WRONG_CLIENT_ID);
	}
}
