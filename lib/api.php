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


namespace OCA\FidelApp;

use OCP\BackgroundJob;
use OCA\Contacts\VCard;
use Sabre\VObject\Reader;

/**
 * Enhanced API, based on the AppFramework's API wrapper
 */
class API extends \OCA\AppFramework\Core\API {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(FIDELAPP_APPNAME);
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
		// The API is not active -> nothing to do
		if (! \OCP\Contacts::isEnabled()) {
			$msg = 'Contact app is not enabled';
			\OCP\Util::writeLog($this->getAppName(), $msg, \OCP\Util::WARN);
			return array ();
		}

		return \OCP\Contacts::search($pattern, $searchProperties, $options);
	}

	public function findContactsByNameOrEmail($term) {
		// Search in username and e-Mail
		$result = $this->search($term, array (
				'FN',
				'EMAIL'
		));

		$contacts = array ();
		foreach ( $result as $r ) {
			$id = $r ['id'];
			$fn = $r ['FN'];

			if (isset($r ['EMAIL'])) {
				$email = $r ['EMAIL'];
				// loop through all email addresses of this contact
				foreach ( $email as $e ) {
					$displayName = $fn . " <$e>";
					$contacts [] = array (
							'label' => $displayName,
							'value' => $e
					);
				}
			}
		}
		return $contacts;
	}

	public function findContactsappIdByEmail($email) {
		$result = $this->search($email, array (
				'EMAIL'
		));

		foreach ( $result as $r ) {
			if (isset($r ['EMAIL'])) {
				$emails = $r ['EMAIL'];
				// loop through all email addresses of this contact
				foreach ( $emails as $e ) {
					if ($e == $email) {
						return $r ['id'];
					}
				}
			}
		}
		return null;
	}

	public function findContactNameById($contactsappId) {
		$return = VCard::find($contactsappId);
		if(isset ($return['fullname'])) {
			return $return ['fullname'];
		}
		return null;
	}

	public function findEMailAddressesByContactsappId($contactsappId) {
		$contactData = VCard::find($contactsappId);
		if(!$contactData) {
			return array();
		}
		$vCardData = $contactData['carddata'];
		$vCard = Reader::read($vCardData);
		$emails = array();
		foreach ($vCard->children() as $property) {
			if(is_a($property, '\Sabre\VObject\Property')) {
				if($property->name == 'EMAIL') {
					$emails[] = $property->value;
				}
			}
		}
		return $emails;
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

	/**
	 * Queues a task
	 *
	 * @param $class string
	 *        	class name
	 * @param $method string
	 *        	[default = 'run'] method name
	 * @param $parameters string
	 *        	[default = ''] all useful data as text
	 * @return id of task
	 */
	public function addQueuedTask($class, $method = 'run', $parameters = '') {
		return BackgroundJob::addQueuedTask($this->getAppName(), $class, $method, $parameters);
	}

	/**
	 * Deletes a queued task
	 *
	 * @param $id int
	 * @return true
	 */
	public function deleteQueuedTask($id) {
		return BackgroundJob::deleteQueuedTask($id);
	}

	/**
	 * Register an exception class in lib/exception with OC's classloader
	 * @param string $exceptionClassName
	 */
	public function registerFidelappException($exceptionClassName) {
		\OC::$CLASSPATH ["OCA\\FidelApp\\$exceptionClassName"] = FIDELAPP_APPNAME . '/lib/exception.php';
	}
}