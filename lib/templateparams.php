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
	 *        	if of type <code>array</code>, the parameters are simply added to the template parameters list,
	 *        	when of type <code>string</code> and different from "null" (four characters: 'n', 'u', 'l', 'l'),
	 *        	the request parameter with the corresponding key is added as a new template parameter
	 * @param unknown $defaultValue [default = NULL] will be set, if no matching request param was found
	 *
	 * @throws \BadMethodCallException when the parameter is neither an <code>array</code> nor a <code>string</code>
	 */
	public function add($paramsToAdd, $defaultValue = null) {
		if (is_array($paramsToAdd)) {
			$this->twigParams = array_merge($this->twigParams, $paramsToAdd);
		} elseif (gettype($paramsToAdd) == 'string') {
			if (isset($this->requestParams [$paramsToAdd]) && $this->requestParams [$paramsToAdd] != 'null') {
				$this->twigParams [$paramsToAdd] = $this->requestParams [$paramsToAdd];
			} else {
				$this->twigParams [$paramsToAdd] = $defaultValue;
			}
		} else {
			throw new \BadMethodCallException('$paramsToAdd: Expected either array or string, but got ' . gettype($paramsToAdd));
		}
	}

	/**
	 * Unconditionally set the template param to the given value
	 *
	 * @param string $param
	 * @param unknown $value
	 *
	 * @throws \BadMethodCallException when the param is not given or not a string
	 */
	public function set($param, $value) {
		if (! $param || ! (gettype($param) == 'string')) {
			throw new \BadMethodCallException("set: Wrong parameter [$param]");
		}
		$this->twigParams[$param] = $value;
	}
	/**
	 * @param string $param
	 * @return the param value or NULL
	 *
	 * @throws \BadMethodCallException when the param is not given or not a string
	 */
	public function get($param) {
		if (! $param || ! (gettype($param) == 'string')) {
			throw new \BadMethodCallException("set: Wrong parameter [$param]");
		}
		if(isset($this->twigParams[$param])) {
			return $this->twigParams[$param];
		}
		return null;
	}
	/**
	 *
	 * @return array the template parameters
	 */
	public function getAll() {
		return $this->twigParams;
	}
}