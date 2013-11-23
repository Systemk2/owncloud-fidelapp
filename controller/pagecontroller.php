<?php

namespace OCA\FidelApp\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\FidelApp\API;
use \OCA\FidelApp\FidelboxConfig;
use \OCA\FidelApp\TemplateParams;
use \OCA\FidelApp\Db\ConfigItem;
use \OCA\FidelApp\Db\ConfigItemMapper;
use \OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\AppFramework\Db\Entity;

class PageController extends Controller {

	public function __construct($api, $request) {
		parent::__construct($api, $request);
	}

	/**
	 * ATTENTION!!!
	 * The following comments turn off security checks
	 * Please look up their meaning in the documentation!
	 *
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function fidelApp() {
		return $this->render('fidelapp');
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function wizard() {
		$l = $this->api->getTrans();

		$templateParams = new TemplateParams($this->getParams(),
				array (
						'menu' => 'wizard',
						'actionTemplate' => 'wizard'
				));

		try {
			$fidelboxConfig = new FidelboxConfig($this->api);

			$mapper = new ConfigItemMapper($this->api);
			try {
				$entity = $mapper->findByUser($this->api->getUserId());
			} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
				$entity = new ConfigItem();
				$entity->setUserId($this->api->getUserId());
			}

			$templateParams->add('accessType', $entity->getAccessType());
			if ($this->hasParam('selection')) {
				// One of the radio buttons is selected
				$templateParams->add('selection');
			} else {
				// No radio selected: determine state from saved configuration
				switch ($entity->getAccessType()) {
					case ('FIXED_IP') :
					case ('DOMAIN_NAME') :
						$templateParams->set('selection', 'accessTypeDirect');
						break;
					case ('FIDELBOX_ACCOUNT') :
						$templateParams->set('selection', 'accessTypeFidelbox');
				}
			}

			$templateParams->add('accessType', $entity->getAccessType());
			$templateParams->add('domain', $entity->getDomainName());
			$templateParams->add('fixedIp', $entity->getFixedIp());
			$templateParams->add('useSSL', $entity->getUseSsl());
			$templateParams->add('fidelboxTempUser', uniqid('', true));
			$templateParams->add('fidelboxAccount', $entity->getFidelboxAccount());

			if ($this->hasParam('action')) {
				$entity->setUseSsl($templateParams->get('useSSL'));
				$entity->setAccessType($templateParams->get('accessType'));
				if ($this->params('action') == 'saveDirectAccess') {
					$entity->setDomainName($templateParams->get('domain'));
					$entity->setFixedIp($templateParams->get('fixedIp'));
					if (! $entity->getDomainName() && ! $entity->getFixedIp()) {
						throw new \Exception($l->t('No IP or Domain Name specified'));
					}
				} elseif ($this->params('action') == 'createFidelboxAccount') {
					$userId = $fidelboxConfig->createAccount($templateParams->get('fidelboxTempUser'), $this->params('captcha'));
					$entity->setFidelboxAccount($userId);
					$templateParams->add('wizard_step2', 'wizard_fidelbox');
				} elseif ($this->params('action') == 'deleteFidelboxAccount') {
					$fidelboxConfig->deleteAccount($entity->getFidelboxAccount());
					$entity->setFidelboxAccount(null);
					if ($entity->getAccessType() == 'FIDELBOX_ACCOUNT') {
						$entity->setAccessType(null);
					}
					$templateParams->add('wizard_step2', 'wizard_fidelbox_createaccount');
				}
				$mapper->save($entity);
			} else {
				// No action... just check if selection does not match saved state any more and save it, if changed
				if ($entity->getAccessType() && $this->hasParam('selection')) {
					if (($entity->getAccessType() == 'FIXED_IP' || $entity->getAccessType() == 'DOMAIN_NAME') &&
							 $this->params('selection') == 'accessTypeFidelbox') {
						if ($entity->getFidelboxAccount()) {
							$entity->setAccessType('FIDELBOX_ACCOUNT');
						} else {
							$entity->setAccessType(null);
						}
						$mapper->save($entity);
					}
					if ($entity->getAccessType() == 'FIDELBOX_ACCOUNT' && $this->params('selection') != 'accessTypeFidelbox') {
						if ($entity->getFixedIp()) {
							$entity->setAccessType('FIXED_IP');
						} else if ($entity->getDomainName()) {
							$entity->setAccessType('DOMAIN_NAME');
						} else {
							$entity->setAccessType(null);
						}
						$mapper->save($entity);
					}
				}
			}

			if ($templateParams->get('selection') == 'accessTypeDirect') {
				$templateParams->add('wizard_step2', 'wizard_fixedipordomain');
			} elseif ($templateParams->get('selection') == 'accessTypeFidelbox') {
				if ($entity->getFidelboxAccount()) {
					$templateParams->add('validFidelboxAccount', $fidelboxConfig->validateAccount($entity->getFidelboxAccount()));
					$templateParams->add('fidelboxAccount', $entity->getFidelboxAccount());
					$templateParams->add('wizard_step2', 'wizard_fidelbox');
				} else {
					$templateParams->add(
							array (
									'wizard_step2' => 'wizard_fidelbox_createaccount',
									'urlFidelboxCaptcha' => $fidelboxConfig->createCaptchaURL(
											$templateParams->get('fidelboxTempUser'))
							));
				}
			}
		} catch(\Exception $e) {
			$templateParams->add('errors', array (
					$e->getMessage()
			));
			$templateParams->set('selection', null);
		}

		if ($this->hasParam('reload')) {
			// Render page without header and footer
			return $this->render('fidelapp', $templateParams->getAll(), '');
		} else {
			// Render page with header and footer
			return $this->render('fidelapp', $templateParams->getAll());
		}
	}

	private function hasParam($param) {
		if (gettype($param) != 'string')
			throw new \BadMethodCallException('hasParam expected parameter to be string, but ' . gettype($param) . ' given');

		$paramValue = $this->params($param);
		return ($paramValue && $paramValue != 'null');
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function createDropdown() {
		$mapper = new ContactShareItemMapper($this->api);

		$itemSource = $this->params('data_item_source');
		$itemType = $this->params('data_item_type');
		$shareItems = $mapper->findByUserFile($this->api->getUserId(), $itemSource);
		$response = $this->render('sharedropdown',
				array (
						'itemSource' => $itemSource,
						'itemType' => $itemType,
						'shareItems' => $shareItems
				), '');
		return $response;
	}
}
