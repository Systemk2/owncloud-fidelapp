<?php

namespace OCA\FidelApp\Controller;

\OC::$CLASSPATH ['OCA\FidelApp\InvalidConfigException'] = FIDELAPP_APPNAME . '/lib/exception.php';

use \OCA\AppFramework\Controller\Controller;
use \OCA\FidelApp\API;
use \OCA\FidelApp\FidelboxConfig;
use \OCA\FidelApp\TemplateParams;
use \OCA\FidelApp\Db\ContactShareItemMapper;
use \OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\InvalidConfigException;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\Db\ContactShareItem;

class PageController extends Controller {

	/**
	 *
	 * @var string the secret password known only by this Owncloud Server.
	 *      It is used to encrypt client ids in URLs
	 */
	private $password;
	private $fidelboxConfig;

	public function __construct(API $api, FidelboxConfig $fidelboxConfig, $request) {
		parent::__construct($api, $request);
		$this->fidelboxConfig = $fidelboxConfig;
	}

	/**
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
	public function passwords() {
		$mapper = new ContactShareItemMapper($this->api);
		$shares = $mapper->findByUser($this->api->getUserId());
		foreach ( $shares as &$item ) {
			$contactId = $item->getContactItem()->getId();
			if(!isset($shareItems[$contactId])) {
				$item->contactName = $this->makeContactName($item->getContactItem());
				$shareItems[$contactId] = &$item;
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
	 */
	public function shares() {
		$mapper = new ContactShareItemMapper($this->api);
		$shares = $mapper->findByUser($this->api->getUserId());
		foreach ( $shares as &$item ) {
			$item->contactName = $this->makeContactName($item->getContactItem());
			$item->fileName = trim($this->api->getPath($item->getShareItem()->getFileId()), DIRECTORY_SEPARATOR);
		}
		$params = array (
				'menu' => 'shares',
				'actionTemplate' => 'shares',
				'shares' => $shares
		);
		return $this->render($this->api->getAppName(), $params);
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

			$templateParams->add('accessType', $this->api->getAppValue('access_type'));
			if ($this->hasParam('selection')) {
				// One of the radio buttons is selected
				$templateParams->add('selection');
			} else {
				// No radio selected: determine state from saved configuration
				switch ($this->api->getAppValue('access_type')) {
					case ('FIXED_IP') :
					case ('DOMAIN_NAME') :
						$templateParams->set('selection', 'accessTypeDirect');
						break;
					case ('FIDELBOX_ACCOUNT') :
						$templateParams->set('selection', 'accessTypeFidelbox');
				}
			}

			$templateParams->add('domain', $this->api->getAppValue('domain_name'));
			$templateParams->add('fixedIp', $this->api->getAppValue('fixed_ip'));
			$templateParams->add('useSSL', $this->api->getAppValue('use_ssl'));
			$templateParams->add('fidelboxTempUser', uniqid('', true));
			$templateParams->add('fidelboxAccount', $this->api->getAppValue('fidelbox_account'));

			if ($this->hasParam('action')) {
				$this->api->setAppValue('use_ssl', $templateParams->get('useSSL'));
				$this->api->setAppValue('access_type', $templateParams->get('accessType'));
				if ($this->params('action') == 'saveDirectAccess') {
					if (! $templateParams->get('domain') && ! $templateParams->get('fixedIp')) {
						// TODO: Formal validation of IP and domain formats
						throw new \Exception($l->t('No IP or Domain Name specified'));
					}
					$this->api->setAppValue('domain_name', $templateParams->get('domain'));
					$this->api->setAppValue('fixed_ip', $templateParams->get('fixedIp'));
				} elseif ($this->params('action') == 'createFidelboxAccount') {
					$fidelboxAccount = $this->fidelboxConfig->createAccount($templateParams->get('fidelboxTempUser'),
							$this->params('captcha'));
					$this->api->setAppValue('fidelbox_account', $fidelboxAccount);
					$templateParams->add('wizard_step2', 'wizard_fidelbox');
				} elseif ($this->params('action') == 'deleteFidelboxAccount') {
					$this->fidelboxConfig->deleteAccount($this->api->getAppValue('fidelbox_account'));
					$this->api->setAppValue('fidelbox_account', null);
					if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT') {
						$this->api->setAppValue('access_type', null);
					}
					$templateParams->add('wizard_step2', 'wizard_fidelbox_createaccount');
				}
			} else {
				// No action... just check if selection does not match saved state any more and save it, if changed
				if ($this->api->getAppValue('access_type') && $this->hasParam('selection')) {
					if (($this->api->getAppValue('access_type') == 'FIXED_IP' ||
							 $this->api->getAppValue('access_type') == 'DOMAIN_NAME') &&
							 $this->params('selection') == 'accessTypeFidelbox') {
						// Stored value is fixed ip or domain name, but selection id fidelbox
						if ($this->api->getAppValue('fidelbox_account')) {
							$this->api->setAppValue('access_type', 'FIDELBOX_ACCOUNT');
						} else {
							$this->api->setAppValue('access_type', null);
						}
					}
					if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT' &&
							 $this->params('selection') != 'accessTypeFidelbox') {
						// Stored value is fidelbox, but current selection is different
						if ($this->api->getAppValue('fixed_ip')) {
							$this->api->setAppValue('access_type', 'FIXED_IP');
						} else if ($this->api->getAppValue('domain_name')) {
							$this->api->setAppValue('access_type', 'DOMAIN_NAME');
						} else {
							$this->api->setAppValue('access_type', null);
						}
					}
				}
			}

			if ($templateParams->get('selection') == 'accessTypeDirect') {
				$templateParams->add('wizard_step2', 'wizard_fixedipordomain');
			} elseif ($templateParams->get('selection') == 'accessTypeFidelbox') {
				if ($this->api->getAppValue('fidelbox_account')) {
					$templateParams->add('validFidelboxAccount',
							$this->fidelboxConfig->validateAccount($this->api->getAppValue('fidelbox_account')));
					$templateParams->add('fidelboxAccount', $this->api->getAppValue('fidelbox_account'));
					$templateParams->add('wizard_step2', 'wizard_fidelbox');
				} else {
					$templateParams->add(
							array (
									'wizard_step2' => 'wizard_fidelbox_createaccount',
									'urlFidelboxCaptcha' => $this->fidelboxConfig->createCaptchaURL(
											$templateParams->get('fidelboxTempUser'))
							));
				}
			}
			if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT') {
				$this->fidelboxConfig->startRegularIpUpdate();
			} else {
				$this->fidelboxConfig->stopRegularIpUpdate();
			}
		} catch(\Exception $e) {
			$templateParams->add('errors', array (
					$e->getMessage()
			));
			$templateParams->set('selection', null);
		}
		if ($this->hasParam('reload')) {
			// Subsequent call: Render page without header and footer
			return $this->render('fidelapp', $templateParams->getAll(), '');
		} else {
			// First call: Render page with header and footer
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
		try {
			$mapper = new ContactShareItemMapper($this->api);

			$itemSource = $this->params('data_item_source');
			$itemType = $this->params('data_item_type');
			$shareItems = $mapper->findByUserFile($this->api->getUserId(), $itemSource);

			foreach ( $shareItems as &$contactShareItem ) {
				$contactShareItem->downloadUrl = $this->generateUrl($contactShareItem->getContactItem());
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

	private function generateUrl(ContactItem $contact) {
		$url = $this->api->getAppValue('use_ssl') == 'true' ? 'https://' : 'http://';
		$clientId = PageController::makeClientId($contact->getId(), $this->getPassword());
		$localPathManual = $this->api->linkToRoute('fidelapp_authenticate_contact', array (
				'clientId' => $clientId
		));
		$localPathBase = $this->api->linkToRoute('fidelapp_index');

		switch ($this->api->getAppValue('access_type')) {
			case 'FIXED_IP' :
				$url .= $this->api->getAppValue('fixed_ip') . $localPathManual;
				break;
			case 'DOMAIN_NAME' :
				$url .= $this->api->getAppValue('domain_name') . $localPathManual;
				break;
			case 'FIDELBOX_ACCOUNT' :
				// TODO: Create correct redirect URL for download applet, including account ID
				$url = FIDELBOX_URL . '/fidelapp/download.php?contextRoot=' . urlencode($localPathBase) . '&accountHash=' .
						 md5($this->api->getAppValue('fidelbox_account')) . "&clientId=$clientId";
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
		// TODO: Check if mcrypt is available
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

	private function makeContactName(ContactItem $item) {
		$contactsappId = $item->getContactsappId();
		$email = $item->getEmail();
		if($contactsappId) {
			// TODO: Cache result (but how?)
			$contactName = $this->api->findContactNameById($contactsappId);
			if($contactName) {
				return $contactName . " <$email>";
			}
		}
		return $email;
	}
}
