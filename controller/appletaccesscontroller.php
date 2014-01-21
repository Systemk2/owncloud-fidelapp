<?php

namespace OCA\FidelApp\Controller;

\OC::$CLASSPATH ['OCA\FidelApp\FileNotFoundException'] = FIDELAPP_APPNAME . '/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\SecurityException'] = FIDELAPP_APPNAME . '/lib/exception.php';

define('CHUNK_SIZE', 1024 * 1024);

use OCA\AppFramework\Controller\Controller;
use OCA\FidelApp\Db\ShareItemMapper;
use OCA\FidelApp\SecurityException;
use OCA\FidelApp\AESEncryption;
use OCA\FidelApp\EncryptionHelper;
use OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\FidelApp\Db\FileItemMapper;
use OC\Files\Filesystem;
use OCA\FidelApp\Db\ChunkItemMapper;
use OCA\FidelApp\Db\ChunkItem;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\ReceiptItemMapper;
use OCA\FidelApp\Db\ReceiptItem;
use OCA\FidelApp\ContactManager;

class AppletAccessController extends Controller {

	public function __construct($api, $request) {
		parent::__construct($api, $request);
	}

	/**
	 * Get a list of file IDs for applet download
	 * <p>
	 * IDs of shared files for the contact identified by the encrypted client ID are sent back one ID per line
	 * </p>
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function getFilesForClient() {
		// TODO: Add brute force prevention
		// (e.g. http://stackoverflow.com/questions/15798918/what-is-the-best-method-to-prevent-a-brute-force-attack)
		try {
			$encryptionHelper = new EncryptionHelper($this->api);
			$decryptedContactId = $encryptionHelper->processContactId($this->params('client-id'));
			$shareItemMapper = new ShareItemMapper($this->api);
			$shareItems = $shareItemMapper->findByContact($decryptedContactId);
			// TODO: Exclude files where checksum is not yet calculated
			\OC_Util::obEnd();
			header('Content-Type: text/plain; charset=utf-8');
			foreach ( $shareItems as &$shareItem ) {
				echo $shareItem->getId() . "\n";
			}
			exit(0);
		} catch(\Exception $e) {
			return ($this->render('error', array (
					'errors' => array (
							$e->getMessage()
					)
			), ''));
		}
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function getChunk() {
		try {
			$chunkId = $this->params('chunk-id', 1);
			$submissionId = $this->params('submission-id');
			$contactShareItem = $this->getCurrentContactShareItem($submissionId);

			$userId = $contactShareItem->getContactItem()->getUserId();
			$this->api->setupFS($userId);
			$shareItem = $contactShareItem->getShareItem();
			$fileId = $shareItem->getFileId();
			$fileName = trim($this->api->getPath($fileId));
			$pass = $contactShareItem->getContactItem()->getPassword();
			$salt = $shareItem->getSalt();
			if (! $salt) {
				$salt = '';
				for($i = 0; $i < 7; $i ++) {
					$salt .= pack('C', mt_rand(0, 255));
				}
				$salt = bin2hex($salt);
				$shareItem->setSalt($salt);
				$shareItemMapper = new ShareItemMapper($this->api);
				$shareItemMapper->save($shareItem);
			}

			$key = $this->createKey($pass, $salt);

			if (! \OC\Files\Filesystem::file_exists($fileName)) { // TODO: Move to api
				throw new FileNotFoundException($fileName);
			}
			$fileLength = \OC\Files\Filesystem::filesize($fileName);
			$absolutePath = Filesystem::getLocalFile($fileName);
			if (! file_exists($absolutePath)) {
				throw new FileNotFoundException($absolutePath);
			}
			$chunkEncryptedLength = floor(CHUNK_SIZE / 16) * 16 + 16;
			$lastChunkPlainLength = $fileLength % CHUNK_SIZE;
			if ($lastChunkPlainLength) {
				$chunkCount = floor($fileLength / CHUNK_SIZE) + 1;
				$lastChunkEncryptedLength = floor($lastChunkPlainLength / 16) * 16 + 16;
			} else {
				$chunkCount = $fileLength / CHUNK_SIZE;
				$lastChunkEncryptedLength = $chunkEncryptedLength;
			}
			if ($shareItem->getNbChunks() != $chunkCount) {
				$shareItem->setNbChunks($chunkCount);
				$shareItemMapper = new ShareItemMapper($this->api);
				$shareItemMapper->save($shareItem);
			}
			$fileEncryptedLength = max($chunkCount - 1, 0) * $chunkEncryptedLength + $lastChunkEncryptedLength;
			$plainPos = ($chunkId - 1) * CHUNK_SIZE;
			$encryptedPos = ($chunkId - 1) * $chunkEncryptedLength;
			// The download client does not handle Initialization Vectors
			$iv = pack('H*', '00000000000000000000000000000000');
			$aesEncryption = new AESEncryption($key, $iv, CHUNK_SIZE);
			$encryptedChunk = $aesEncryption->encryptChunk($absolutePath, $chunkId);

			$encryptedMessageDigest = md5($encryptedChunk);

			// Global submission information
			$this->createHeader('Content-Type', 'text/plain; charset=UTF-8');
			$this->createHeader('id', $submissionId);
			$this->createHeader('fileName', urlencode(preg_replace('?.*/?', '', $fileName)));
			$this->createHeader('filePlainLength', $fileLength);
			$this->createHeader('fileEncryptedLength', $fileEncryptedLength);
			$this->createHeader('fileBlockLength', $fileEncryptedLength);
			$this->createHeader('chunkCount', $chunkCount);
			$this->createHeader('comment', ''); // TODO: Implement comment (?)
			try {
				$fileItemMapper = new FileItemMapper($this->api);
				$fileItem = $fileItemMapper->findByFileId($fileId);
				$checksum = $fileItem->getChecksum();
			} catch (DoesNotExistException $e ) {
				$checksum = null;
			}
			$this->createHeader('filePlainDigest', $checksum);

			// Individual chunk information
			$this->createHeader('chunkId', $chunkId);
			$this->createHeader("chunk.$chunkId.plainPos", $plainPos);
			$this->createHeader("chunk.$chunkId.encryptedPos", $encryptedPos);
			$this->createHeader("chunk.$chunkId.blockPos", $encryptedPos);
			if ($chunkId == $chunkCount) {
				$this->createHeader("chunk.$chunkId.plainLength", $lastChunkPlainLength);
				$this->createHeader("chunk.$chunkId.encryptedLength", $lastChunkEncryptedLength);
				$this->createHeader("chunk.$chunkId.blockLength", $lastChunkEncryptedLength);
			} else {
				$this->createHeader("chunk.$chunkId.plainLength", CHUNK_SIZE);
				$this->createHeader("chunk.$chunkId.encryptedLength", $chunkEncryptedLength);

				// blockLength is MAX(chunkEncryptedLength, plainLength)
				// It might differ from chunkEncryptedLength one day (e.g. when a zip algorithm is implemented),
				// but for the moment, it is always chunkEncryptedLength
				$this->createHeader("chunk.$chunkId.blockLength", $chunkEncryptedLength);
			}
			// $this->createHeader("chunk.$chunkId.encryptedMessageDigest", $encryptedMessageDigest);

			$chunkMapper = new ChunkItemMapper($this->api);
			try {
				$chunk = $chunkMapper->findByShareAndChunkId($shareItem->getId(), $chunkId);
			} catch(DoesNotExistException $e) {
				$chunk = new ChunkItem();
				$chunk->setChunkId($chunkId);
				$chunk->setShareId($shareItem->getId());
			}
			if ($chunk->getChecksum() != $encryptedMessageDigest) {
				$chunk->setChecksum($encryptedMessageDigest);
				$chunkMapper->save($chunk);
			}
			\OC_Util::obEnd();
			echo $encryptedChunk;
		} catch(\Exception $e) {
			\OC_Log::write($this->api->getAppName(), 'An error occurred in getChunk(): ' . $e->getMessage(), \OC_Log::ERROR);
		}
		exit(0);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function getKey() {
		try {

			\OC_Log::write($this->api->getAppName(), "Handling getkey request", \OC_Log::DEBUG);
			$contactShareItem = $this->getCurrentContactShareItem($this->params('submission-id'));
			$shareId = $contactShareItem->getShareItem()->getId();
			$chunkCount = $contactShareItem->getShareItem()->getNbChunks();
			$chunkMapper = new ChunkItemMapper($this->api);
			$corruptChunks = array ();
			for($i = 1; $i <= $chunkCount; $i ++) {
				try {
					$chunk = $chunkMapper->findByShareAndChunkId($shareId, $i);
					if (! $this->params("chunk-message-digest_$i")) {
						\OC_Log::write($this->api->getAppName(), "Validation of chunk $i failed, no chunk info sent by client",
								\OC_Log::WARN);
						$corruptChunks [] = $i;
					} elseif (strtolower($this->params("chunk-message-digest_$i") != $chunk->getChecksum())) {
						\OC_Log::write($this->api->getAppName(), "Validation of chunk $i failed, no chunk info sent by client",
								\OC_Log::WARN);
						$corruptChunks [] = $i;
					} else {
						\OC_Log::write($this->api->getAppName(), "Validation of chunk $i successful", \OC_Log::DEBUG);
					}
				} catch(DoesNotExistException $e) {
					\OC_Log::write($this->api->getAppName(), "Validation of chunk $i failed, chunk info is missing in DB",
							\OC_Log::WARN);
					$corruptChunks [] = $i;
				}
			}

			if ($chunkCount > 0 && count($corruptChunks) == 0) {
				\OC_Log::write($this->api->getAppName(), "No corrupt chunks, returning key", \OC_Log::DEBUG);
				$this->createHeader('Content-Type', 'application/fidel-key-salt');
				$key = $this->createKey($contactShareItem->getContactItem()->getPassword(),
						$contactShareItem->getShareItem()->getSalt());
				$keyDigest = md5($key);
				$this->createHeader('Key-Digest', $keyDigest);
				\OC_Log::write($this->api->getAppName(), "Setting header : Key-Digest: $keyDigest", \OC_Log::DEBUG);
				\OC_Util::obEnd();
				echo pack('H*', $contactShareItem->getShareItem()->getSalt());
				exit(0);
			}
			\OC_Log::write($this->api->getAppName(), 'Returning a list of corrupt IDs to download client', \OC_Log::DEBUG);
			$this->createHeader('Content-Type', 'application/fidel-chunk-ids');
			// pack() does not accept arrays
			$returnData = '';
			foreach ( $corruptChunks as $corruptChunk ) {
				$returnData .= pack('N', $corruptChunk); // Format as unsigned 32 bit big endian
			}
			\OC_Util::obEnd();
			echo $returnData;
		} catch(\Exception $e) {
			\OC_Log::write($this->api->getAppName(), 'An error occurred in getKey(): ' . $e->getMessage(), \OC_Log::ERROR);
		}

		exit(0);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function confirm() {
		try {
			$contactShareItem = $this->getCurrentContactShareItem($this->params('submission-id'));
			$share = $contactShareItem->getShareItem();
			$fileItemMapper = new FileItemMapper($this->api);
			$fileId = $share->getFileId();
			$fileItem = $fileItemMapper->findByFileId($fileId);
			$expectedHash = $fileItem->getChecksum();
			if (strtolower($this->params('plain-file-hash')) == $expectedHash) {
				\OC_Log::write($this->api->getAppName(), 'Finalized download of ' . $share->getFileId(), \OC_Log::INFO);
				$now = date('Y-m-d H:i:s');
				$share->setDownloadTime($now);
				$shareItemMapper = new ShareItemMapper($this->api);
				$shareItemMapper->save($share);
				$chunkItemMapper = new ChunkItemMapper($this->api);
				$chunkItemMapper->deleteByShareId($share->getId());
				$contactManager = new ContactManager($this->api);
				$receipt = new ReceiptItem();
				$receipt->setContactName($contactShareItem->getContactItem()->getEmail());
				$receipt->setDownloadTime($now);
				$userId = $contactShareItem->getContactItem()->getUserId();
				$this->api->setupFS($userId);
				$fileName = trim($this->api->getPath($fileId), DIRECTORY_SEPARATOR);
				$receipt->setFileName($fileName);
				$receipt->setUserId($userId);
				if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT') {
					$receipt->setDownloadType($share->getDownloadType());
				} else {
					$receipt->setDownloadType('BASIC');
				}
				$receiptItemMapper = new ReceiptItemMapper($this->api);
				$receiptItemMapper->save($receipt);
				\OC_Log::write($this->api->getAppName(), 'Wrote receipt', \OC_Log::DEBUG);
			} else {
				\OC_Log::write($this->api->getAppName(),
						'Plain file hash for ' . $share->getId() . "  is not correct!. Expected $expectedHash but received " .
								 $this->params('plain-file-hash'), \OC_Log::WARN);
			}
		} catch(\Exception $e) {
			\OC_Log::write($this->api->getAppName(), 'An error occurred in confirm(): ' . $e->getMessage(), \OC_Log::ERROR);
		}
		exit(0);
		// TODO: Handle Notification Email
		/*
		 * if($share->getNotificationEmail()) { $this->logger->debug('Notification e-mail requested, checking if this is last file for contact ' . $share->getContact()->__toString()); $lastFileForContact = true; foreach($shareList->getShares() as $existingShare) { if($existingShare->getContact()->equals($share->getContact())) { if(!$existingShare->getDownloadTime() && !$existingShare->isObsolete() && $existingShare->getNotificationEmail()) { $this->logger->debug('There is still an existing share ' . $existingShare->__toString() . ' for contact ' . $share->getContact()->__toString()); $lastFileForContact = false; break; } } } if($lastFileForContact) { $this->logger->debug('This was the last shared file with e-mail notification for contact ' . $share->getContact()->__toString() . ', sending e-mail notification'); $reply = $this->sendNotificationMail($share); if($reply != 'OK') { $this->logger->warn('Sending of notification email failed with reply $reply'); } else { $this->logger->info('Successfully sent e-mail notification to ' . $share->getNotificationEmail() . ' for contact ' . $share->getContact()->__toString()); } } }
		 */
	}

	private function getCurrentContactShareItem($submissionId) {
		$encryptionHelper = new EncryptionHelper($this->api);
		$contactId = $encryptionHelper->processContactId($this->params('client-id'));
		$mapper = new ContactShareItemMapper($this->api);
		return $mapper->findByShareId($submissionId);
	}

	private function createKey($pass, $salt) {
		if (count($pass) == 0)
			throw new SecurityException(ERROR_NO_PASSWORD_SET);
		if (count($salt) == 0) {
			throw new SecurityException(ERROR_NO_SALT_SET);
		}
		$pass = str_replace(' ', '/', $pass);
		while ( strlen($pass) < 12 ) // Stuff to at least 12 characters
			$pass .= $pass;
		$pass = substr($pass, 0, 12); // Cut to exactly 12 characters

		// Our password is composed of characters that can be used to represent a base64 - encoded
		                              // binary. Put differently: For the user it is a password, but for the system, it's a base64-code.
		                              // All we have to do is to make a binary key out of the base64 code
		$key = base64_decode($pass, true);
		if (! $key)
			throw new SecurityException(ERROR_INVALID_PASSWORD_SET);

			// Combine password and salt
		$key .= pack('H*', $salt);
		\OC_Log::write($this->api->getAppName(), 'Created key: ' . bin2hex($key), \OC_Log::DEBUG);
		return $key;
	}

	private function createHeader($name, $value) {
		header("$name: $value");
		\OC_Log::write($this->api->getAppName(), "Created header '$name' with value '$value'", \OC_Log::DEBUG);
	}

	private function getProperties($regexp) {
		$matches = array ();
		foreach ( $this->getParams() as $name => $value ) {
			if (preg_match('?' . $regexp . '?', $name))
				$matches [$name] = $value;
		}

		return $matches;
	}
}