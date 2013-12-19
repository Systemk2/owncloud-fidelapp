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

class PageController extends Controller {

	public function __construct($api, $request) {
		parent::__construct($api, $request);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function fidelApp() {
		return $this->render(FIDELAPP_APPNAME);
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
					$fidelboxAccount = $fidelboxConfig->createAccount($templateParams->get('fidelboxTempUser'),
							$this->params('captcha'));
					$this->api->setAppValue('fidelbox_account', $fidelboxAccount);
					$templateParams->add('wizard_step2', 'wizard_fidelbox');
				} elseif ($this->params('action') == 'deleteFidelboxAccount') {
					$fidelboxConfig->deleteAccount($this->api->getAppValue('fidelbox_account'));
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
							$fidelboxConfig->validateAccount($this->api->getAppValue('fidelbox_account')));
					$templateParams->add('fidelboxAccount', $this->api->getAppValue('fidelbox_account'));
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
			if ($this->api->getAppValue('access_type') == 'FIDELBOX_ACCOUNT') {
				$fidelboxConfig->startRegularIpUpdate();
			} else {
				$fidelboxConfig->stopRegularIpUpdate();
			}
		} catch(\Exception $e) {
			$templateParams->add('errors', array (
					$e->getMessage()
			));
			$templateParams->set('selection', null);
		}
		if ($this->hasParam('reload')) {
			// Subsequent call: Render page without header and footer
			return $this->render($this->api->getAppName(), $templateParams->getAll(), '');
		} else {
			// First call: Render page with header and footer
			return $this->render($this->api->getAppName(), $templateParams->getAll());
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

			array_walk($shareItems,
					function (&$contactShareItem, $key, $api) {
						$contactShareItem->downloadUrl = PageController::generateUrl($contactShareItem->getContactItem(), $api);
					}, $this->api);
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

	private static function generateUrl(ContactItem $contact,\OCA\FidelApp\API $api) {
		$url = $api->getAppValue('use_ssl') == 'true' ? 'https://' : 'http://';
		$localPath = $api->linkToRoute('fidelapp_authenticate_contact');
		switch ($api->getAppValue('access_type')) {
			case 'FIXED_IP' :
				$url .= $api->getAppValue('fixed_ip') . "$localPath?";
				break;
			case 'DOMAIN_NAME' :
				$url .= $api->getAppValue('domain_name') . "$localPath?";
				break;
			case 'FIDELBOX_ACCOUNT' :
				// TODO: Create correct redirect URL for download applet
				$url = FIDELBOX_URL . 'redirect.php?path=' . urlencode($localPath) . '&account=' .
						 $api->getAppValue('fidelbox_account') . '&';
				break;
			default :
				$l = $api->getTrans();
				throw new InvalidConfigException($l->t('Please configure access type in fidelapp first'));
		}
		$url .= 'id=' . $contact->getId();
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
}
