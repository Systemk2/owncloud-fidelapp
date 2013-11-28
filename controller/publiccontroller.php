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
		unset($_SESSION['AUTHENTICATED_CONTACT']);

		try {
			$contact = $this->loadContact($id = $this->params('id'));
			if (! $contact) {
				return $this->render('authenticate', array (
						'errors' => array (
								$l->t('Invalid id')
						)
				), '');
			}
		} catch(\Exception $e) {
			return $this->render('authenticate', array (
					'errors' => array (
							$e->getMessage()
					)
			), '');
		}
		$password = $this->params('password');
		if (! $password) {
			return $this->render('authenticate', array (
					'id' => $contact->getId()
			), '');
		} elseif ($contact->getPassword() != $password) {
			return $this->render('authenticate', array (
					'id' => $contact->getId(),
					'errors' => array (
							$l->t('Invalid password')
					)
			), '');
		} else {
			$_SESSION['AUTHENTICATED_CONTACT'] = $contact->getId();
			return $this->getFileList();
		}
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @IsLoggedInExemption
	 */
	public function getFileList() {
		$l = $this->api->getTrans();

		$id = $_SESSION['AUTHENTICATED_CONTACT'];
		if (! $id) {
			return $this->render('authenticate', array (
					'errors' => array (
							$l->t('Not authenticated')
					)
			), '');
		}
		$contact = $this->loadContact($id);
		$this->api->setupFS($contact->getUserId());
		$shareMapper = new ShareItemMapper($this->api);
		$shareItems = $shareMapper->findByContact($contact->getId());
		$fileList = array ();
		foreach ( $shareItems as $shareItem ) {
			$fileList [$shareItem->getFileId()] = $this->api->getPath($shareItem->getFileId());
		}
		return $this->render('filelist', array (
				'shareItems' => $fileList
		), '');
	}

	private function loadContact($id) {
		if (! $id) {
			return false;
		}
		$contactMapper = new ContactItemMapper($this->api);
		try {
			$contact = $contactMapper->findById($id);
		} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
			return false;
		}
		return $contact;
	}
}
