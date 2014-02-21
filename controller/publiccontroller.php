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


namespace OCA\FidelApp\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\FidelApp\API;
use \OCA\FidelApp\Db\ContactItemMapper;
use \OCA\FidelApp\Db\ShareItemMapper;
use OCA\FidelApp\EncryptionHelper;
use OCA\FidelApp\Db\ReceiptItemMapper;
use OCA\FidelApp\Db\ReceiptItem;
use OCA\FidelApp\Db\ShareItem;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\WrongDownloadTypeException;

\OC::$CLASSPATH ['OCA\FidelApp\WrongDownloadTypeException'] = FIDELAPP_APPNAME . '/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\FileNotFoundException'] = FIDELAPP_APPNAME . '/lib/exception.php';

class PublicController extends Controller {

	public function __construct($api, $request) {
		parent::__construct($api, $request);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function authenticateContact() {
		// TODO: Add brute force prevention
		// (e.g. http://stackoverflow.com/questions/15798918/what-is-the-best-method-to-prevent-a-brute-force-attack)
		$l = $this->api->getTrans();
		unset($_SESSION ['AUTHENTICATED_CONTACT']);

		try {
			$contact = null;
			$passwordBase64 = $this->api->getAppValue('secret');
			if (! $passwordBase64) {
				throw new SecurityException(ERROR_NO_SECRET_KEY);
			}
			$password = base64_decode($passwordBase64, true);
			$id = $this->params('clientId');
			if ($id) {
				$encryptionHelper = new EncryptionHelper($this->api);
				$decryptedContactId = $encryptionHelper->processContactId($id, $password);

				$contactMapper = new ContactItemMapper($this->api);
				try {
					$contact = $contactMapper->findById($decryptedContactId);
				} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
					// Do nothing here, the error will be emitted by "if (! $contact)" below
				}
			}
			if (! $contact) {
				return $this->render('error', array (
						'errors' => array (
								$l->t('Invalid id')
						)
				), '');
			}
			$password = $this->params('password');
			if (! $password) {
				return $this->render('authenticate', array (
						'clientId' => $id
				), '');
			} elseif ($contact->getPassword() != $password) {
				return $this->render('authenticate',
						array (
								'clientId' => $id,
								'errors' => array (
										$l->t('Invalid password')
								)
						), '');
			} else {
				$_SESSION ['AUTHENTICATED_CONTACT'] = $contact;
				return $this->getFileList();
			}
		} catch(\Exception $e) {
			return $this->render('authenticate', array (
					'errors' => array (
							$e->getMessage()
					)
			), '');
		}
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function getFileList() {
		try {
			$l = $this->api->getTrans();

			if (! isset($_SESSION ['AUTHENTICATED_CONTACT'])) {
				return $this->render('authenticate',
						array (
								'errors' => array (
										$l->t('Not authenticated')
								)
						), '');
			}
			$contact = $_SESSION ['AUTHENTICATED_CONTACT'];
			$shareMapper = new ShareItemMapper($this->api);

			$this->api->setupFS($contact->getUserId());
			if ($this->params('shareId')) {
				$shareItem = $shareMapper->findById($this->params('shareId'));
				$filename = $this->api->getPath($shareItem->getFileId());
				if (\OC\Files\Filesystem::file_exists($filename)) {
					return ($this->serveFile($shareItem, $contact));
				}
			}
			$shareItems = $shareMapper->findByContact($contact->getId());

			foreach ( $shareItems as &$shareItem ) {
				$filename = $this->api->getPath($shareItem->getFileId());
				if (\OC\Files\Filesystem::file_exists($filename) && $filename != '') {
					$shareItem->fileName = trim($filename, DIRECTORY_SEPARATOR);
					if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT' && $shareItem->getDownloadType() != 'BASIC') {
						$shareItem->fileName .= ' (' . $l->t('Can only be downloaded with Download Manager') . ')';
						$shareItem->downloadable = false;
					} else {
						$shareItem->downloadable = true;
					}
				} else {
					$shareItem->fileName = null;
					$shareItem->downloadable = false;
				}
			}
			return $this->render('filelist', array (
					'shareItems' => $shareItems
			), '');
		} catch(\Exception $e) {
			return $this->render('error', array (
					'errors' => array (
							'message' => $e->getMessage()
					)
			), '');
		}
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function pingback() {
		\OC_JSON::success(array (
				'pingback' => 'ok'
		));
		exit(0);
	}

	private function serveFile(ShareItem $shareItem, ContactItem $contactItem) {
		$filename = trim($this->api->getPath($shareItem->getFileId()), DIRECTORY_SEPARATOR);
		if($filename == '') {
			throw new FileNotFoundException($filename);
		}
		if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT' && $shareItem->getDownloadType() != 'BASIC') {
			throw new WrongDownloadTypeException($filename);
		}

		$receiptItem = new ReceiptItem();
		$receiptItem->setContactName($contactItem->getEmail());
		$receiptItem->setDownloadTime(date('Y-m-d H:i:s'));
		$receiptItem->setDownloadType('BASIC');
		$receiptItem->setFileName(filename);
		$receiptItem->setUserId($contactItem->getUserId());
		$receiptItemMapper = new ReceiptItemMapper($this->api);
		$receiptItemMapper->save($receiptItem);

		$ftype = \OC\Files\Filesystem::getMimeType($filename);

		header('Content-Type:' . $ftype);
		if (preg_match("/MSIE/", $_SERVER ["HTTP_USER_AGENT"])) {
			header('Content-Disposition: attachment; filename="' . rawurlencode(basename($filename)) . '"');
		} else {
			header(
					'Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode(basename($filename)) . '; filename="' .
							 rawurlencode(basename($filename)) . '"');
		}
		\OCP\Response::disableCaching();
		header('Content-Length: ' . \OC\Files\Filesystem::filesize($filename));

		\OC_Util::obEnd();
		\OC\Files\Filesystem::readfile($filename);
	}
}
