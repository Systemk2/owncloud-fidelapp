<?php

namespace OCA\FidelApp;

/**
 * Container class for managing mapping between request parameters and twig parameters
 */
class TemplateParams {
	private $requestParams;
	private $twigParams;

	/**
	 * Construct a new TemplateParams object
	 *
	 * @param array $requestParams
	 *        	the GET and POST parameters
	 * @param array $twigParams
	 *        	[default = array()] the initial template parameters
	 */
	public function __construct(array $requestParams, array $twigParams = array()) {
		$this->requestParams = $requestParams;
		$this->twigParams = $twigParams;
	}

	/**
	 * Add parameters to the template parameters list
	 *
	 * @param unknown $paramsToAdd
	 *        	if of type <code>array</code>, the parameters are simply added to the template parameters list, when of type <code>string</code>, the request parameter with the corresponding key is added as a new template parameter
	 *
	 *        @throws \BadMethodCallException when the parameter is neither an <code>array</code> nor a <code>string</code>
	 */
	public function add($paramsToAdd) {
		if (is_array($paramsToAdd)) {
			$this->twigParams = array_merge($this->twigParams, $paramsToAdd);
		} elseif(gettype($paramsToAdd) == 'string') {
			if(isset($this->requestParams [$paramsToAdd]) && $this->requestParams [$paramsToAdd] != 'null') {
				$this->twigParams [$paramsToAdd] = $this->requestParams [$paramsToAdd];
			}
		} else {
			throw new \BadMethodCallException('$paramsToAdd: Expected either array or string, but got ' . gettype($paramsToAdd));
		}
	}

	/**
	 * @return array the template parameters
	 */
	public function getAll() {
		return $this->twigParams;
	}
}