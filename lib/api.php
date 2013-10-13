<?php
namespace OCA\FidelApp;

class API extends \OCA\AppFramework\Core\API {

	/**
	 * constructor
	 */
	public function __construct(){
		parent::__construct('fidelapp');
	}

		/**
		 * This function is used to search and find contacts within the users address books.
		 * In case $pattern is empty all contacts will be returned.
		 * 
		 * @param string $pattern which should match within the $searchProperties
		 * @param array $searchProperties defines the properties within the query pattern should match
		 * @param array $options - for future use. One should always have options!
		 * @return array of contacts which are arrays of key-value-pairs
		 */
	public function search($pattern, $searchProperties = array(), $options = array()) {
		return \OCP\Contacts::search($pattern, $searchProperties, $options);
	}

}