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
		$templateParams = new TemplateParams($this->getParams(),
				array (
						'menu' => 'wizard',
						'actionTemplate' => 'wizard'
				));

		$mapper = new ConfigItemMapper($this->api);
		try {
			$entity = $mapper->findByUser($this->api->getUserId());
		} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
			$entity = new ConfigItem();
			$entity->setUserId($this->api->getUserId());
		}

		if (! $this->hasParam('selection')) {
			if ($entity->getDomainName() || $entity->getFixedIp()) {
				$templateParams->add(
						array (
								'selection' => 'accessTypeDirect',
								'useSSL' => $entity->getUseSsl()
						));
			} elseif ($entity->getFidelboxUser()) {
				$templateParams->add(
						array (
								'selection' => 'accessTypeFidelbox',
								'useSSL' => $entity->getUseSsl()
						));
			}
			// Render page with header and footer
			return $this->render('fidelapp', $templateParams->getAll());
		}

		$entity->setUseSsl($this->params('useSSL'));

		$templateParams->add('selection');
		$templateParams->add('useSSL');

		if ($this->params('selection') == 'accessTypeDirect') {
			if ($this->manageDirectAccess($entity, $templateParams)) {
				$mapper->save($entity);
			}
		} elseif ($this->params('selection') == 'accessTypeFidelbox') {
			if ($this->manageFidelboxConfig($entity, $templateParams)) {
				$mapper->save($entity);
			}
		}

		// Render page without header and footer
		return $this->render('fidelapp', $templateParams->getAll(), '');
	}

	private function manageDirectAccess(ConfigItem & $entity, TemplateParams & $templateParams) {
		$domainOrIp = $this->params('domainOrIp');
		if ($this->params('selection2') == 'fixedIp') {
			$entity->setFixedIp($domainOrIp);
			$entity->setDomainName(null);
			$templateParams->add(array (
					'fixedIp' => $domainOrIp
			));
		} elseif ($this->params('selection2') == 'domainName') {
			$entity->setDomainName($this->params('domainOrIp'));
			$entity->setFixedIp(null);
			$templateParams->add(array (
					'domainName' => $domainOrIp
			));
		} else {
			$templateParams->add(
					array (
							'domainName' => $entity->getDomainName(),
							'fixedIp' => $entity->getFixedIp()
					));
		}
		$entity->setFidelboxUser(null);

		$templateParams->add(array (
				'wizard_step2' => 'wizard_fixedipordomain'
		));
		return true;
	}

	private function manageFidelboxConfig(ConfigItem & $entity, TemplateParams & $templateParams) {
		$fidelboxConfig = new FidelboxConfig($this->api);
		// Temporary fidelbox captcha id
		$tempUserId = null;
		if ($this->hasParam('fidelboxUser')) {
			$tempUserId = $this->params('fidelboxUser');
		} else {
			// Create a new random user id
			$tempUserId = uniqid('', true);
		}
		$templateParams->add(
				array (
						'wizard_step2' => 'wizard_fidelbox',
						'urlFidelboxCaptcha' => $fidelboxConfig->createCaptchaURL($tempUserId),
						'fidelboxUser' => $tempUserId
				));

		try {
			$userId = $fidelboxConfig->createAccount($tempUserId, $this->params('captcha'));
			$entity->setDomainName(null);
			$entity->setFixedIp(null);
			$entity->setFidelboxUser($userId);
		} catch(\Exception $e) {
			$templateParams->add(array (
					'errors' => array (
							$e->getMessage()
					)
			));
			return false;
		}
		return true;
	}

	private function hasParam($param) {
		if (gettype($param) != 'string')
			throw new \BadMethodCallException('hasParam expected parameter to be string, but ' . gettype($param) . ' given');

		$paramValue = $this->params($param);
		return ($paramValue && $paramValue != 'null');
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function createDropdown() {
		$mapper = new ContactShareItemMapper($this->api);

		$itemSource = $this->params('data_item_source');
		$itemType = $this->params('data_item_type');
		$shareItems = $mapper->findByUserFile($api->getUserId(), $itemSource);
		$response = $this->render('sharedropdown',
				array (
						'itemSource' => $itemSource,
						'itemType' => $itemType,
						'shareItems' => $shareItems
				), '');
		return $response;
	}
}
