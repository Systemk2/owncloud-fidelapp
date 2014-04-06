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
use \OCA\FidelApp\TemplateParams;
use \OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\InvalidConfigException;
use OCA\FidelApp\Db\ContactShareItem;
use OCA\FidelApp\DependencyInjection\DIContainer;
use OCA\FidelApp\API;

class PageController extends Controller {

	/**
	 *
	 * @var string the secret password known only by this Owncloud Server.
	 *      It is used to encrypt client ids in URLs
	 */
	private $password;

	// The following properties are set by Dependency Injection
	public $fidelboxConfig;
	private $app;
	private $contactManager;
	private $contactShareItemMapper;
	private $receiptItemMapper;

	public function __construct(DIContainer $diContainer, $request) {
		parent::__construct($diContainer ['API'], $request);
		$this->fidelboxConfig = $diContainer ['FidelboxConfig'];
		$this->app = $diContainer ['App'];
		$this->contactManager = $diContainer ['ContactManager'];
		$this->contactShareItemMapper = $diContainer ['ContactShareItemMapper'];
		$this->receiptItemMapper = $diContainer ['ReceiptItemMapper'];

		$this->api->registerFidelappException('InvalidConfigException');
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @throws \Exception
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function fidelApp() {
		$checkResult = $this->app->checkPrerequisites($this->api);
		if (count($checkResult ['warnings']) == 0 && count($checkResult ['errors']) == 0) {
			if ($this->api->getAppValue('access_type')) {
				return $this->shares();
			} else {
				return $this->wizard();
			}
		}
		return $this->render('fidelapp',
				array (
						'warnings' => $checkResult ['warnings'],
						'errors' => $checkResult ['errors']
				));
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @throws \Exception
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function passwords() {
		$shares = $this->contactShareItemMapper->findByUser($this->api->getUserId());
		$shareItems = array ();
		foreach ( $shares as &$item ) {
			$contactId = $item->getContactItem()->getId();
			if (! isset($shareItems [$contactId])) {
				$item->contactName = $this->contactManager->makeContactName($item->getContactItem());
				$shareItems [$contactId] = &$item;
			}
		}
		$params = array (
				'menu' => 'passwords',
				'actionTemplate' => 'passwords',
				'shares' => $shareItems
		);
		return $this->render('fidelapp', $params);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @throws \Exception
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function shares() {
		$shares = $this->contactShareItemMapper->findByUser($this->api->getUserId());
		foreach ( $shares as &$item ) {
			$item->contactName = $this->contactManager->makeContactName($item->getContactItem());
			$item->fileName = trim($this->api->getPath($item->getShareItem()->getFileId()), DIRECTORY_SEPARATOR);
		}
		$params = array (
				'menu' => 'shares',
				'actionTemplate' => 'shares',
				'shares' => $shares,
				'view' => 'activeshares',
				'isFidelbox' => ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT')
		);
		return $this->render('fidelapp', $params);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function receipts() {
		$receipts = $this->receiptItemMapper->findByUser($this->api->getUserId());
		$params = array (
				'menu' => 'shares',
				'actionTemplate' => 'shares',
				'receipts' => $receipts,
				'view' => 'receiptnotices'
		);
		return $this->render('fidelapp', $params);
	}

	/**
	 * @CSRFExemption
	 *
	 * @throws \Exception
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizard() {
		switch ($this->api->getAppValue('access_type')) {
			case 'FIDELBOX_ACCOUNT' :
				return $this->wizardFidelbox();
			case 'DOMAIN_NAME' :
				return $this->wizardDomainName();
			case 'FIXED_IP' :
				return $this->wizardFixedIp();
		}
		$params = array (
				'menu' => 'wizard',
				'actionTemplate' => 'wizard',
				'useSSL' => $this->api->getAppValue('use_ssl'),
				'port' => $this->api->getAppValue('port')
		);

		// Render page with header and footer
		return $this->render('fidelapp', $params);
	}

	/**
	 * Handle generic function (Exception handling etc.) for all wizard
	 * requests
	 *
	 * @param function $callbackFunction
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	private function doWizardAction($callbackFunction) {
		try {
			$params = array (
					'menu' => 'wizard',
					'actionTemplate' => 'wizard',
					'useSSL' => $this->api->getAppValue('use_ssl'),
					'port' => $this->api->getAppValue('port')
			);
			$callbackFunction($params, $this, $this->api);
		} catch(\Exception $e) {
			$params ['errors'] = array (
					$e->getMessage()
			);
		}
		if ($this->hasParam('ajax')) {
			// Ajax call: Render result without header and footer
			return $this->render('fidelapp', $params, '');
		} else {
			// Other call: Render page with header and footer
			return $this->render('fidelapp', $params);
		}
	}

	/**
	 * Display or set the "Fixed IP" configuration
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardFixedIp() {
		return $this->doWizardAction(
				function (&$params, PageController $context, API $api) {

					$params ['wizard_step2'] = 'wizard_fixedipordomain';
					$params ['accessType'] = 'FIXED_IP';
					if ($api->getAppValue('access_type') != 'FIXED_IP') {
						$api->setAppValue('access_type', 'FIXED_IP');
						$context->fidelboxConfig->stopRegularIpUpdate();
					}
					$currentIp = $api->getAppValue('fixed_ip');
					if ($context->hasParam('fixedIp')) {
						$params ['fixedIp'] = $context->params('fixedIp');
						if ($context->params('fixedIp') != $currentIp) {
							$api->setAppValue('fixed_ip', $context->params('fixedIp'));
						}
					} else {
						$params ['fixedIp'] = $currentIp;
					}
					$params ['domain'] = $api->getAppValue('domain_name');
				});
	}

	/**
	 * Display or set the "Domain Name" configuration
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardDomainName() {
		return $this->doWizardAction(
				function (&$params, PageController $context, API $api) {

					$params ['wizard_step2'] = 'wizard_fixedipordomain';
					$params ['accessType'] = 'DOMAIN_NAME';
					if ($api->getAppValue('access_type') != 'DOMAIN_NAME') {
						$api->setAppValue('access_type', 'DOMAIN_NAME');
						$context->fidelboxConfig->stopRegularIpUpdate();
					}
					$currentDomain = $api->getAppValue('domain_name');
					if ($context->hasParam('domain')) {
						$params ['domain'] = $context->params('domain');
						if ($context->params('domain') != $currentDomain) {
							$api->setAppValue('domain_name', $context->params('domain'));
						}
					} else {
						$params ['domain'] = $currentDomain;
					}
					$params ['fixedIp'] = $api->getAppValue('fixed_ip');
				});
	}

	/**
	 * Display or set the "Fidelbox" configuration
	 * Check if the Terms of Service (ToS) have been confirmed
	 * If this is the case, show fidelbox wizard, otherwise show ToS confirmation page
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardFidelbox() {
		if ($this->api->getAppValue('access_type') != 'FIDELBOX_ACCOUNT') {
			$this->api->setAppValue('access_type', 'FIDELBOX_ACCOUNT');
		}
		if (! $this->api->getAppValue('fidelbox_account') && ! $this->hasParam('captcha')) {
			return $this->doWizardAction(
					function (&$params, PageController $context, API $api) {
						$params ['wizard_step2'] = 'fidelbox_confirm_tos';
						$params ['accessType'] = 'FIDELBOX_ACCOUNT';
					});
		}
		return $this->wizardFidelboxTosConfirmed();
	}

	private function wizardFidelboxTosConfirmed() {

		return $this->doWizardAction(
				function (&$params, PageController $context, API $api) {
					$params ['wizard_step2'] = 'wizard_fidelbox_createaccount';
					$params ['accessType'] = 'FIDELBOX_ACCOUNT';
					$params ['fidelboxTempUser'] = uniqid('', true);
					$params ['urlFidelboxCaptcha'] = $context->fidelboxConfig->createCaptchaURL($params ['fidelboxTempUser']);

					if ($context->hasParam('captcha')) {
						$tempUser = $context->params('fidelboxTempUser');
						$captcha = $context->params('captcha');
						$fidelboxAccount = $context->fidelboxConfig->createAccount($tempUser, $captcha);
						$api->setAppValue('fidelbox_account', $fidelboxAccount);
					} else {
						$fidelboxAccount = $api->getAppValue('fidelbox_account');
					}

					if ($fidelboxAccount) {
						$params ['wizard_step2'] = 'wizard_fidelbox';
						$params ['fidelboxAccount'] = $fidelboxAccount;
						$params ['validFidelboxAccount'] = $context->fidelboxConfig->validateAccount($fidelboxAccount);
						if($params ['validFidelboxAccount']) {
							$context->fidelboxConfig->startRegularIpUpdate();
							$isReachable = false;
							try {
								$isReachable = $context->fidelboxConfig->pingBack();
							} catch(\Exception $e) {
								$params ['reachableFailedMsg'] = $e->getMessage();
							}
							$params ['isReachable'] = $isReachable;
						} else {
							$params ['isReachable'] = false;
							$params ['reachableFailedMsg'] = $this->api->getTrans()->t('Invalid fidelbox account');
						}
					}
				});
	}

	/**
	 * Set the Terms Of Service as accepted
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardFidelboxConfirmToS() {
		return $this->wizardFidelboxTosConfirmed();
	}

	/**
	 * Toggle SSL usage
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardSsl() {
		if ($this->params('useSSL') != $this->api->getAppValue('use_ssl')) {
			$this->api->setAppValue('use_ssl', $this->params('useSSL'));
		}
		return $this->wizard();
	}

	/**
	 * Change port settings
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardPort() {
		$currentPort = $this->api->getAppValue('port');
		$newPort = $this->params('port');
		if ($newPort == 'STANDARD_PORT') {
			$newPort = null;
		}
		if ($newPort != $currentPort) {
			$this->api->setAppValue('port', $newPort);
		}
		return $this->wizard();
	}

	/**
	 * Delete an existing fidelbox account
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function wizardDeleteFidelboxAccount() {
		try {
			$this->fidelboxConfig->deleteAccount($this->api->getAppValue('fidelbox_account'));
		} catch(\Exception $e) {
			// Safely ignore any problems with account deletion, just create a new one afterwards
		}
		$this->api->setAppValue('fidelbox_account', null);
		$this->api->setAppValue('access_type', null);
		$this->fidelboxConfig->stopRegularIpUpdate();
		return $this->wizardFidelbox();
	}

	/**
	 * Check if a request parameter exists
	 *
	 * @param string $param
	 * @throws \BadMethodCallException if the parameter is not of type <code>string</code>
	 * @return boolean <code>true</code> if a request parameter with the given name exists,
	 *         is not empty and not <code>null</code> and not the string 'n','u','l','l'
	 */
	public function hasParam($param) {
		if (gettype($param) != 'string')
			throw new \BadMethodCallException('hasParam expected parameter to be string, but ' . gettype($param) . ' given');

		$paramValue = $this->params($param);
		return ($paramValue && $paramValue != 'null');
	}

	/**
	 * Create the share dropdown in the file view
	 *
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 * @Ajax
	 */
	public function createDropdown() {
		try {

			$itemSource = $this->params('data_item_source');
			$itemType = $this->params('data_item_type');
			$shareItems = $this->contactShareItemMapper->findByUserFile($this->api->getUserId(), $itemSource);

			foreach ( $shareItems as &$contactShareItem ) {
				if($contactShareItem->getContactItem()->getPassword()) {
					$contactShareItem->downloadUrl = $this->generateUrl($contactShareItem);
					$contactShareItem->mailToLink = $this->generateMailToLink($contactShareItem);
				} else {
					$contactShareItem->downloadUrl = '';
					$contactShareItem->mailToLink = '';
				}
			}
			$response = $this->render('sharedropdown',
					array (
							'itemSource' => $itemSource,
							'itemType' => $itemType,
							'shareItems' => $shareItems,
							'fidelboxDownload' => $this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT'
					), '');
			return $response;
		} catch(\Exception $e) {
			\OC_JSON::error(array (
					'message' => $e->getMessage()
			));
			exit();
		}
	}

	private function generateUrl(ContactShareItem $contactShare) {
		$url = $this->api->getAppValue('use_ssl') == 'true' ? 'https://' : 'http://';
		$clientId = PageController::makeClientId($contactShare->getContactItem()->getId(), $this->getPassword());
		$localPathManual = $this->api->linkToRoute('fidelapp_authenticate_contact', array (
				'clientId' => $clientId
		));
		$localPathBase = $this->api->linkToRoute('fidelapp_index');
		$port = $this->api->getAppValue('port');
		if ($port) {
			$localPathManual = ":$port$localPathManual";
		}
		switch ($this->api->getAppValue('access_type')) {
			case 'FIXED_IP' :
				$url .= $this->api->getAppValue('fixed_ip') . $localPathManual;
				break;
			case 'DOMAIN_NAME' :
				$url .= $this->api->getAppValue('domain_name') . $localPathManual;
				break;
			case 'FIDELBOX_ACCOUNT' :
				if ($contactShare->getShareItem()->getDownloadType() == 'SECURE') {
					$url = FIDELAPP_FIDELBOX_URL . '/fidelapp/download.php?contextRoot=' . urlencode($localPathBase) . '&accountHash=' .
							 md5($this->api->getAppValue('fidelbox_account')) . "&clientId=$clientId";
				} else {
					$url = FIDELAPP_FIDELBOX_URL . '/fidelapp/redirect.php?path=' . urlencode($localPathManual) . '&accountHash=' .
							 md5($this->api->getAppValue('fidelbox_account'));
				}
				break;
			default :
				$l = $this->api->getTrans();
				throw new InvalidConfigException($l->t('Please configure access type in fidelapp first'));
		}
		return $url;
	}

	/**
	 * Create a 32 characters client id containing the id at a random position and padded with
	 * random digits.
	 * The resulting client id will be
	 * <code>&quot;&lt;position (2 digits)&gt;&lt;random stuffing&gt;&lt;id (4 digits)&gt;&lt;random stuffing&gt;&quot;</code>
	 *
	 * @param string $id
	 *        	the client ID to be encrypted
	 * @param string $password
	 *        	the password to encrypt the client ID
	 * @return string the encrypted and stuffed client ID
	 */
	public static function makeClientId($id, $password) {
		$id = str_pad($id, 4, '0', STR_PAD_LEFT);
		$position = mt_rand(2, 28);
		// Add position
		if ($position < 10) {
			$clientId = "0$position";
		} else {
			$clientId = "$position";
		}
		// Add random stuffing
		for($x = 2; $x < $position; $x ++) {
			$clientId .= mt_rand(0, 9);
		}
		// Add id
		$clientId .= "$id";
		// Add random stuffing
		for($x = $position + 4; $x < 32; $x ++) {
			$clientId .= mt_rand(0, 9);
		}
		// Encrypt using password
		$td = mcrypt_module_open('rijndael-128', '', 'ofb', '');
		mcrypt_generic_init($td, $password, '0000000000000000');
		$encrypted = mcrypt_generic($td, $clientId);
		mcrypt_generic_deinit($td);
		$clientId = bin2hex($encrypted);
		return $clientId;
	}

	private function getPassword() {
		$passwordBase64 = $this->api->getAppValue('secret');
		if ($passwordBase64) {
			return base64_decode($passwordBase64, true);
		} else {
			return $this->makePassword();
		}
	}

	private function makePassword() {
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_OFB, '');
		$maxKeySize = mcrypt_enc_get_key_size($td);
		$password = '';
		while ( strlen($password) < $maxKeySize ) {
			$password .= md5(uniqid('', true), true);
		}
		$passwordBase64 = base64_encode(substr($password, 0, $maxKeySize));
		$this->api->setAppValue('secret', $passwordBase64);
		\OC_Log::write($this->api->getAppName(), 'Generated new secret password', \OC_Log::INFO);
	}

	private function generateMailToLink(ContactShareItem $item) {
		$l = $this->api->getTrans();
		$passwordHint = $l->t('(Please remember to tell the recipient the appropriate password: %s)',
				$item->getContactItem()->getPassword());
		$mailto = 'mailto:' . $item->getContactItem()->getEmail() . '?body=' .
				 $this->escapeMailTo("$item->downloadUrl $passwordHint");
		return $mailto;
	}

	private function escapeMailTo($text) {
		$charArray = str_split($text);
		$escaped = '';
		foreach ( $charArray as $char ) {
			if ($char == '%')
				$escaped .= '%25';
			else if ($char == '"')
				$escaped .= '%22';
			else if ($char == '&')
				$escaped .= '%26';
			else if (ord($char) > 126) {
				$escaped .= '%' . bin2hex($char);
			} else
				$escaped .= $char;
		}
		return $escaped;
	}
}
