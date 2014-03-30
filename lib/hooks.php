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

use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\ContactItemMapper;
use OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\FidelApp\Db\FileItemMapper;
use OCA\FidelApp\Db\ShareItemMapper;

class Hooks {

	/**
	 * Deletes all shares and contacts of a deleted user
	 *
	 * @param
	 *        	parameters array containing 'uid' => userId from postDeleteUser-Hook
	 * @return <code>true</code>
	 */
	public static function deleteUser(array $parameters) {
		// TODO: Test!!!
		$deletedUser = $parameters ['uid'];
		$mapper = new ContactShareItemMapper($api);
		$mapper->deleteAllForUser($deletedUser);
		return true;
	}

	/**
	 * Deletes all shares shares for a deleted contactapp contact
	 *
	 * @param
	 *        	$parameters array containing an id from \OCA\Contacts::pre_deleteContact - Hook
	 * @return <code>true</code>
	 */
	/*
	 * \OC_Hook::emit('\OCA\Contacts', 'pre_deleteContact, array('id' => $id))
	 */
	public static function deleteContact(array $parameters) {
		$contactId = $parameters ['id'];
		$api = new API();

		$contactMapper = new ContactItemMapper($api);

		$contacts = $contactMapper->findByContactsappId($contactId);
		if (count($contacts) == 0) {
			return true;
		}
		$contactManager = new ContactManager($api);
		$contactManager->removeContact($contacts[0]->getId());
		return true;
	}

	/**
	 * When a contactapp contact has been updated, check if the shared e-Mail addressis still there
	 *
	 * @param $parameters array
	 *        	containing a 'contactid' from \OCA\Contacts::post_updateContact - Hook
	 * @return <code>true</code>
	 */
	/* \OC_Hook::emit('\OCA\Contacts', 'post_updateContact', 'backend' => $this->name,
					'addressBookId' => $addressbookid,
					'contactId' => $id,
					'contact' => $contact,
					'carddav' => $isCardDAV);
	*/
	public static function updateEmail($parameters) {
		$contactsappId = $parameters['contactId'];
		$api = new API();
		$contactMapper = new ContactItemMapper($api);
		$contacts = $contactMapper->findByContactsappId($contactsappId);
		if (count($contacts) == 0) {
			return true;
		}
		foreach ( $contacts as $contact ) {
			$emails = $api->findEMailAddressesByContactsappId($contactsappId);
			$found = false;
			foreach ( $emails as $email ) {
				if ($contact->getEmail() == $email) {
					$found = true;
					break;
				}
			}
			if (! $found) {
				// E-mail for contact does not exist anymore
				// remove Contact Id from contact
				$contact->setContactsappId(null);
				$contactMapper->save($contact);
			}
		}
		return true;
	}

	/**
	 * Remove active shares when a file is moved to the thrash bin
	 *
	 * @param array $params contains the file name with key 'filePath'
	 */
	public static function moveFileToTrash(array $params) {
		$path = $params['filePath'];
		Hooks::removeSharesForDeletedFiles($path);
	}

	/**
	 * Remove active shares when a file is deleted
	 *
	 * @param array $params contains the file name with key 'path'
	 */
	public static function deleteFile(array $params) {
		$path = $params['path'];
		Hooks::removeSharesForDeletedFiles($path);
	}

	private static function removeSharesForDeletedFiles($path) {
		$api = new API();
		$contactShareItemMapper = new ContactShareItemMapper($api);
		$fileItemMapper = new FileItemMapper($api);
		$shareItemMapper = new ShareItemMapper($api);
		$contactShareItems = $contactShareItemMapper->findByUser($api->getUserId());

		foreach($contactShareItems as $contactShareItem) {
			$fileId = $contactShareItem->getShareItem()->getFileId();
			$fileName = $api->getPath($fileId);
			if (! \OC\Files\Filesystem::file_exists($fileName) || $fileName == $path) { // TODO: Move to api
				try {
					$fileItem = $fileItemMapper->findByFileId($fileId);
					$fileItemMapper->delete($fileItem);
				} catch ( DoesNotExistException $e) {
					// Ignore non-existent files
				}
				$shareItemMapper->delete($contactShareItem->getShareItem());
			}
		}

	}
}