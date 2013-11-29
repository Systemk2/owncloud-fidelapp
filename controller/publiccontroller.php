<?php

namespace OCA\FidelApp\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\FidelApp\API;
use \OCA\FidelApp\Db\ContactItemMapper;
use \OCA\FidelApp\Db\ShareItemMapper;

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
			$id = $this->params('id');
			if ($id) {
				$contactMapper = new ContactItemMapper($this->api);
				try {
					$contact = $contactMapper->findById($id);
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
						'id' => $contact->getId()
				), '');
			} elseif ($contact->getPassword() != $password) {
				return $this->render('authenticate',
						array (
								'id' => $contact->getId(),
								'errors' => array (
										$l->t('Invalid password')
								)
						), '');
			} else {
				$_SESSION ['AUTHENTICATED_CONTACT'] = $contact;
				return $this->getFile();
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
	public function getFile() {
		$l = $this->api->getTrans();

		if (! isset($_SESSION ['AUTHENTICATED_CONTACT'])) {
			return $this->render('authenticate', array (
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
			return ($this->serveFile($shareItem->getFileId()));
		} else {
			$shareItems = $shareMapper->findByContact($contact->getId());

			foreach ( $shareItems as &$shareItem ) {
				$shareItem->fileName = $this->api->getPath($shareItem->getFileId());
			}
			return $this->render('filelist', array (
					'shareItems' => $shareItems
			), '');
		}
	}

	private function serveFile($fileId) {
		$filename = $this->api->getPath($fileId);
		if (! \OC\Files\Filesystem::file_exists($filename)) {
			unset($this->request->parameters ['shareId']);
			$this->getFile();
		}

		$ftype = \OC\Files\Filesystem::getMimeType($filename);

		header('Content-Type:' . $ftype);
		if (preg_match("/MSIE/", $_SERVER ["HTTP_USER_AGENT"])) {
			header('Content-Disposition: attachment; filename="' . rawurlencode(basename($filename)) . '"');
		} else {
			header(
					'Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode(basename($filename)) . '; filename="' .
							 rawurlencode(basename($filename)) . '"');
		}
		OCP\Response::disableCaching();
		header('Content-Length: ' . \OC\Files\Filesystem::filesize($filename));

		OC_Util::obEnd();
		// FIXME: Need to return a template!
		\OC\Files\Filesystem::readfile($filename);
	}
}