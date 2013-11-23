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
	public function getFileList() {
		$l = $this->api->getTrans();

		$email = $this->params('email');
		if (! $email) {
			return $this->render('error', array (
					'errors' => array (
							$l->t('Invalid email')
					)
			), '');
		}
		$userId = $this->params('userId');
		if (! $userId) {
			return $this->render('error', array (
					'errors' => array (
							$l->t('Invalid userId')
					)
			), '');
		}
		$contactMapper = new ContactItemMapper($this->api);
		try {
			$contact = $contactMapper->findByUserEmail($userId, $email);
		} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
			return $this->render('filelist', array (
					'shareItems' => array ()
			), '');
		} catch(\Exception $e) {
			return $this->render('error', array (
					'errors' => array (
							$e->getMessage()
					)
			), '');
		}
		$password = $this->params('password');
		if (! $password || $contact->getPassword() != $password) {
			return $this->render('error', array (
					'errors' => array (
							$l->t('Invalid password')
					)
			), '');
		}
		\OC_Util::setupFS($userId);
		$shareMapper = new ShareItemMapper($this->api);
		$shareItems = $shareMapper->findByContact($contact->getId());
		$fileList = array();
		foreach ($shareItems as $shareItem) {
			$fileList[$shareItem->getFileId()] = $this->api->getPath($shareItem->getFileId());
		}
		return $this->render('filelist', array ('shareItems' => $fileList), '');
	}
}
