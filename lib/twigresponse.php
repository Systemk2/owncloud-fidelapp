<?php

namespace OCA\FidelApp;

use \OCA\FidelApp\API;

/**
 * Response for twig templates.
 * We use our own version, because we want to use the extension .twig instead of .php
 */
class TwigResponse extends \OCA\AppFramework\Http\TemplateResponse {
	private $twig;

	/**
	 * Instantiates the Twig Template
	 * 
	 * @param API $api
	 *        	an api instance
	 * @param string $templateName
	 *        	the name of the twig template
	 * @param
	 *        	Twig_Environment an instance of the twig environment for rendering
	 */
	public function __construct(API $api, $templateName, $twig) {
		parent::__construct($api, $templateName . '.twig');
		$this->twig = $twig;
	}

	/**
	 * Returns the rendered result
	 * 
	 * @return string rendered output
	 */
	public function render() {
		return $this->twig->render($this->templateName, $this->params);
	}
}