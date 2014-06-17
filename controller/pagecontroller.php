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
			if ($this->api->getAppValue('fidelbox_account') && $this->api->getAppValue('access_type')) {
				return $this->shares();
			} else {
				return $this->appConfig();
			}
		}
		return $this->render('fidelapp',
				array (
						'warnings' => isset($checkResult ['warnings']) ? $checkResult ['warnings'] : array (),
						'errors' => isset($checkResult ['errors']) ? $checkResult ['errors'] : array ()
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
		$contactShareItems = array ();
		foreach ( $shares as $item ) {
			// Ignore files that are implicitly shared through folders
			if (! $item->getShareItem()->getParentShareId()) {
				$item->contactName = $this->contactManager->makeContactName($item->getContactItem());
				$item->fileName = trim($this->api->getPath($item->getShareItem()->getFileId()), DIRECTORY_SEPARATOR);
				if ($item->getShareItem()->getIsDir()) {
					$numberOfFilesInDir = count($this->api->readDir($item->fileName));
					$item->fileName .= ' ' . $this->api->getTrans()->t('(Directory, %s files shared)',
							array (
									$numberOfFilesInDir
							));
				}
				$contactShareItems [] = $item;
			}
		}
		$params = array (
				'menu' => 'shares',
				'actionTemplate' => 'shares',
				'shares' => $contactShareItems,
				'view' => 'activeshares'
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

	/*
	 * @CSRFExemption @throws \Exception @return \OCA\AppFramework\Http\TemplateResponse public function appConfigAccess() { switch ($this->api->getAppValue('access_type')) { case 'FIDELBOX_REDIRECT' : return $this->appConfigRedirect(); case 'DOMAIN_NAME' : return $this->appConfigDomainName(); case 'FIXED_IP' : return $this->appConfigFixedIp(); } $params = array ( 'menu' => 'appconfig', 'actionTemplate' => 'appconfig_access', 'useSSL' => $this->api->getAppValue('use_ssl'), 'port' => $this->api->getAppValue('port') ); // Render page with header and footer return $this->render('fidelapp', $params); }
	 */

	/**
	 * Handle generic function (Exception handling etc.) for all appconfig
	 * requests
	 *
	 * @param function $callbackFunction,
	 *        	may be <code>null</code>
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	private function doAppConfigAction($callbackFunction) {
		try {
			$params = array (
					'menu' => 'appconfig',
					'actionTemplate' => 'appconfig',
					'useSSL' => $this->api->getAppValue('use_ssl'),
					'port' => $this->api->getAppValue('port'),
					'domain' => $this->api->getAppValue('domain_name'),
					'fixedIp' => $this->api->getAppValue('fixed_ip'),
					'accessType' => $this->api->getAppValue('access_type'),
					'fidelboxConfig' => 'appconfig_fidelbox'
			);
			if ($callbackFunction) {
				$callbackFunction($params, $this, $this->api);
			}
			$fidelboxAccount = $this->api->getAppValue('fidelbox_account');

			if ($fidelboxAccount) {
				$params ['fidelboxAccount'] = $fidelboxAccount;
				$params ['validFidelboxAccount'] = $this->fidelboxConfig->validateAccount($fidelboxAccount);
				if ($params ['validFidelboxAccount']) {
					$isReachable = false;
					try {
						$isReachable = $this->fidelboxConfig->pingBack();
					} catch(\Exception $e) {
						$params ['reachableFailedMsg'] = $e->getMessage();
					}
					$params ['isReachable'] = $isReachable;
					$params ['showAccessTypeTemplate'] = true;
				} else {
					$params ['isReachable'] = false;
					$params ['reachableFailedMsg'] = $this->api->getTrans()->t('Invalid fidelbox account');
				}
			}
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
	 * Set the "Fixed IP" configuration
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigFixedIp() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {

					$params ['showAccessTypeTemplate'] = true;
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
							$context->fidelboxConfig->updateIp();
						}
					} else {
						$params ['fixedIp'] = $currentIp;
					}
					$params ['domain'] = $api->getAppValue('domain_name');
				});
	}

	/**
	 * Set the "Domain Name" configuration
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigDomainName() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {

					$params ['showAccessTypeTemplate'] = true;
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
							$context->fidelboxConfig->updateIp();
						}
					} else {
						$params ['domain'] = $currentDomain;
					}
					$params ['fixedIp'] = $api->getAppValue('fixed_ip');
				});
	}

	/**
	 * Set the "Fidelbox redirect" configuration
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigRedirect() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {
					$params ['showAccessTypeTemplate'] = true;
					$params ['accessType'] = 'FIDELBOX_REDIRECT';
					if ($api->getAppValue('access_type') != 'FIDELBOX_REDIRECT') {
						$api->setAppValue('access_type', 'FIDELBOX_REDIRECT');
						$context->fidelboxConfig->startRegularIpUpdate();
					}
				});
	}

	/**
	 * Check if the Terms of Service (ToS) need to be confirmed (no fidelbox account present)
	 * If this is the case, show ToS confirmation page, otherwise go to fidelbox appconfig
	 * @CSRFExemption
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfig() {
		if (! $this->api->getAppValue('fidelbox_account')) {
			return $this->doAppConfigAction(
					function (&$params, PageController $context, API $api) {
						$params ['fidelboxConfig'] = 'appconfig_confirm_tos';
					});
		}
		return $this->doAppConfigAction(null);
	}

	/**
	 * Generate and display the "fidelbox" account creation page withits capcha
	 * @CSRFExemption
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigDisplayCaptcha() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {
					$params ['fidelboxConfig'] = 'appconfig_fidelbox_createaccount';
					$params ['fidelboxTempUser'] = uniqid('', true);
					$params ['urlFidelboxCaptcha'] = $context->fidelboxConfig->createCaptchaURL($params ['fidelboxTempUser']);
				});
	}

	/**
	 * Display or create a "fidelbox" account
	 * @CSRFExemption
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigCreateFidelboxAccount() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {
					$tempUser = $context->params('fidelboxTempUser');
					$captcha = $context->params('fidelboxCaptcha');
					$fidelboxAccount = $context->fidelboxConfig->createAccount($tempUser, $captcha);
					$params ['validFidelboxAccount'] = $context->fidelboxConfig->validateAccount($fidelboxAccount);
					$params ['fidelboxAccount'] = $fidelboxAccount;
					$api->setAppValue('fidelbox_account', $fidelboxAccount);
				});
	}

	/**
	 * Toggle SSL usage
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigSsl() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {
					if ($params ['useSSL'] != $context->params('useSSL')) {
						$api->setAppValue('use_ssl', $context->params('useSSL'));
						$params ['useSSL'] = $context->params('useSSL');
						$context->fidelboxConfig->updateIp();
					}
				});
	}

	/**
	 * Change port settings
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigPort() {
		return $this->doAppConfigAction(
				function (&$params, PageController $context, API $api) {
					$newPort = $context->params('port');
					if ($newPort == 'STANDARD_PORT') {
						$newPort = null;
					}
					if ($newPort != $params ['port']) {
						$api->setAppValue('port', $newPort);
						$params ['port'] = $newPort;
						$context->fidelboxConfig->updateIp();
					}
				});
	}

	/**
	 * Delete an existing fidelbox account
	 * @Ajax
	 *
	 * @return \OCA\AppFramework\Http\TemplateResponse
	 */
	public function appConfigDeleteFidelboxAccount() {
		try {
			$this->fidelboxConfig->deleteAccount($this->api->getAppValue('fidelbox_account'));
		} catch(\Exception $e) {
			// Safely ignore any problems with account deletion, just create a new one afterwards
		}
		$this->api->setAppValue('fidelbox_account', null);
		$this->api->setAppValue('access_type', null);
		$this->fidelboxConfig->stopRegularIpUpdate();
		return $this->appConfig();
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
				if ($contactShareItem->getContactItem()->getPassword()) {
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
							'shareItems' => $shareItems
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
		$accessType = $this->api->getAppValue('access_type');
		if ($contactShare->getShareItem()->getDownloadType() == 'SECURE') {
			$fidelboxAccount = $this->api->getAppValue('fidelbox_account');
			if (! $fidelboxAccount) {
				$l = $this->api->getTrans();
				throw new InvalidConfigException($l->t('Please create a fidelbox.de account first'));
			}
			$url = FIDELAPP_FIDELBOX_URL . '/fidelapp/download.php?contextRoot=' . urlencode($localPathBase) . '&accountHash=' .
					 md5($fidelboxAccount) . "&clientId=$clientId";
			// TODO: Implement
			if ($accessType == 'FIXED_IP') {
				$ip = $this->api->getAppValue('fixed_ip');
				$port = $this->api->getAppValue('port');
				$url .= "&ip=$ip&port=$port";
			} else if ($accessType == 'DOMAIN_NAME') {
				$domain = $this->api->getAppValue('domain_name');
				$port = $this->api->getAppValue('port');
				$url .= "&domain=$domain&port=$port";
			}
		} else {
			switch ($accessType) {
				case 'FIXED_IP' :
					$url .= $this->api->getAppValue('fixed_ip') . $localPathManual;
					break;
				case 'DOMAIN_NAME' :
					$url .= $this->api->getAppValue('domain_name') . $localPathManual;
					break;
				case 'FIDELBOX_REDIRECT' :
					$url = FIDELAPP_FIDELBOX_URL . '/fidelapp/redirect.php?path=' . urlencode($localPathManual) . '&accountHash=' .
							 md5($this->api->getAppValue('fidelbox_account'));
					break;
				default :
					$l = $this->api->getTrans();
					throw new InvalidConfigException($l->t('Please configure the fidelapp first'));
			}
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
