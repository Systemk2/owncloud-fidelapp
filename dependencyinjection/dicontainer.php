<?php

namespace OCA\FidelApp\DependencyInjection;

use \OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;
use \OCA\FidelApp\Controller\PageController;
use \OCA\FidelApp\Controller\PublicController;
use \OCA\FidelApp\API;
use \OCA\FidelApp\TwigMiddleware;
use OCA\FidelApp\Controller\AppletAccessController;
use OCA\FidelApp\EncryptionHelper;
use OCA\FidelApp\FidelboxConfig;
use OCA\FidelApp\App;
use OCA\FidelApp\ContactManager;
use OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\FidelApp\Db\ReceiptItemMapper;

class DIContainer extends BaseContainer {

	public function __construct() {
		parent::__construct(FIDELAPP_APPNAME);

		// use this to specify the template directory
		$this ['TwigTemplateDirectory'] = __DIR__ . '/../templates';

		$this ['App'] = $this->share(function ($c) {
			return new App();
		});

		$this ['API'] = $this->share(function ($c) {
			return new API();
		});

		$this ['ContactManager'] = $this->share(function ($c) {
			return new ContactManager($c ['API']);
		});

		$this ['ContactShareItemMapper'] = $this->share(function ($c) {
			return new ContactShareItemMapper($c ['API']);
		});

		$this ['ReceiptItemMapper'] = $this->share(function ($c) {
			return new ReceiptItemMapper($c ['API']);
		});
		$this ['EncryptionHelper'] = $this->share(function ($c) {
			return new EncryptionHelper();
		});

		$this ['FidelboxConfig'] = $this->share(function ($c) {
			return new FidelboxConfig($c ['API']);
		});

		$this ['PageController'] = $this->share(function ($c) {
			return new PageController($c, $c ['Request']);
		});

		$this ['PublicController'] = $this->share(function ($c) {
			return new PublicController($c ['API'], $c ['Request']);
		});

		$this ['AppletAccessController'] = $this->share(
				function ($c) {
					return new AppletAccessController($c ['API'], $c ['Request']);
				});

		$this ['TwigMiddleware'] = $this->share(function ($c) {
			return new TwigMiddleware($c ['API'], $c ['Twig']);
		});
	}
}