<?php

namespace OCA\FidelApp;

/**
 * Enhanced API, based on the AppFramework's API wrapper
 */
class API extends \OCA\AppFramework\Core\API {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct('fidelapp');
	}

	/**
	 * This function is used to search and find
	 * contacts within the users address books.
	 *
	 * In case $pattern is empty all contacts will be returned.
	 *
	 * @param string $pattern
	 *        	which should match within the $searchProperties
	 * @param array $searchProperties
	 *        	defines the properties within the query pattern should match
	 * @param array $options
	 *        	- for future use. One should always have options!
	 * @return array of contacts which are arrays of key-value-pairs
	 */
	public function search($pattern, $searchProperties = array(), $options = array()) {
		return \OCP\Contacts::search($pattern, $searchProperties, $options);
	}

	/**
	 * Get the path of a file by id
	 *
	 * Note that the resulting path is not guarantied to be unique for the id,
	 * multiple paths can point to the same file
	 *
	 * @param int $id
	 * @return string
	 */
	public function getPath($id) {
		return \OC\Files\Filesystem::getPath($id);
	}

	/**
	 * Configure the initial filesystem
	 *
	 * @param string $user
	 *        	[default = ''] the owncloud user. If no user is given,
	 *        	the one who is currently logged in is used
	 */
	public function setupFS($user) {
		\OC_Util::setupFS($user);
	}
}