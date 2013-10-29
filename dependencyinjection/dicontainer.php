<?php

namespace OCA\FidelApp\DependencyInjection;

use \OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;
use \OCA\FidelApp\Controller\PageController;
use \OCA\FidelApp\API;
use \OCA\FidelApp\TwigMiddleware;

class DIContainer extends BaseContainer {
	public function __construct() {
		parent::__construct ( 'fidelapp' );

		// use this to specify the template directory
		$this ['TwigTemplateDirectory'] = __DIR__ . '/../templates';

		$this ['API'] = $this->share ( function ($c) {
			return new API ();
		} );

		$this ['PageController'] = function ($c) {
			return new PageController ( $c ['API'], $c ['Request'] );
		};

		$this ['TwigMiddleware'] = $this->share ( function ($c) {
			return new TwigMiddleware ( $c ['API'], $c ['Twig'] );
		} );
	}
}