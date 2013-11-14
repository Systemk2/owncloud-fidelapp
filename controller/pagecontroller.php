<?php

namespace OCA\FidelApp\Controller;

\OC::$CLASSPATH ['OCA\FidelApp\CaptchaNotMatchException'] = 'fidelapp/lib/exception.php';
\OC::$CLASSPATH ['OCA\FidelApp\PasswordNotGoodException'] = 'fidelapp/lib/exception.php';

use \OCA\AppFramework\Controller\Controller;
use \OCA\FidelApp\API;
use \OCA\FidelApp\TemplateParams;
use \OCA\FidelApp\Db\ConfigItemMapper;
use \OCA\FidelApp\Db\ContactShareItemMapper;

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
						'actionTemplate' => 'wizard_1'
				));
		if ($this->hasParam('selection')) {
			$templateParams->add('selection');
			$templateParams->add('selection2');
			$templateParams->add('useSSL');
			if ($this->params('selection') == 'accessTypeDirect') {
				$templateParams->add('domainOrIp');
				$templateParams->add(array (
						'wizard_step2' => 'wizard_2a'
				));
			} elseif ($this->params('selection') == 'accessTypeFidelbox') {
				$savedPassword = false;
				if ($this->hasParam('password')) {
					$templateParams->add('password');
					try {
						$savedPassword = $this->savePassword($this->params('captcha'), $this->params('password'));
					} catch ( \Exception $e ) {
						$templateParams->add(array (
								'errors' => array (
										$e->getMessage()
								)
						));
					}
				}
				$templateParams->add(array (
						'wizard_step2' => 'wizard_2b'
				));

				if (! $savedPassword) {
					$templateParams->add(array (
							'urlFidelboxCaptcha' => $this->createCaptchaURL()
					));
				}
			}
			// Render page without header and footer
			return $this->render('fidelapp', $templateParams->getAll(), '');
		}
		// Render page with header and footer
		return $this->render('fidelapp', $templateParams->getAll());
	}

	private function createCaptchaURL() {
		$mapper = new ConfigItemMapper($this->api);
		try {
			$entity = $mapper->findByUser($this->api->getUserId());
		} catch ( \OCA\AppFramework\Db\DoesNotExistException $e ) {
			$entity = new ConfigItem();
			$entity->setUserId($this->api->getUserId());
			// Create a new random user id
			$entity->setFidelboxUser(uniqid('', true));
			$mapper->save($entity);
		}
		return FIDELBOX_URL . '/fidelapp/captcha.php?userId=' . urlencode($entity->getFidelboxUser()) . '&token=' . uniqid();
	}

	public function savePassword($captcha, $password) {
		$l = $this->api->getTrans();
		if (strlen($password) < 6) {
			throw new \OCA\FidelApp\PasswordNotGoodException(
					$l->t('Password needs to be at least $1 characters long', array (
							'$1' => 6
					)));
		}
		$captchaNotMatchException = new \OCA\FidelApp\CaptchaNotMatchException($l->t('Wrong captcha'));
		if (strlen($captcha) != 6) {
			throw $captchaNotMatchException;
		}

		$mapper = new ConfigItemMapper($this->api);
		$entity = $mapper->findByUser($this->api->getUserId());

		// set a sensible timeout of 10 sec to stay responsive even if the fidelbox server is down.
		$ctx = stream_context_create(array (
				'http' => array (
						'timeout' => 10
				)
		));
		$json = @file_get_contents(
				FIDELBOX_URL . '/fidelapp/setpassword.php?userId=' . $entity->getFidelboxUser() .  '&captcha=' . urlencode($captcha) . '&password=' . urlencode($password),
				false, $ctx);
		if (! $json) {
			throw new \RuntimeException(FIDELBOX_URL . $l->t(' did not return any result'));
		}
		$return = json_decode($json, true);
		if ($return == null) {
			throw new \RuntimeException(FIDELBOX_URL . $l->t(' did return an unparsable  result: ') . $json);
		}
		if ($return ['status'] != 'success') {
			if (! isset($return ['message'])) {
				throw new \RuntimeException($l->t('Unexpected error while trying to save password on ' . FIDELBOX_URL));
			}
			if ($return ['message'] == 'Captcha did not match') {
				throw $captchaNotMatchException;
			} else {
				throw new \RuntimeException(
						$l->t(
								'The following error occurred while trying to save password on ' . FIDELBOX_URL . ': ' .
										 $return ['message']));
			}
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
