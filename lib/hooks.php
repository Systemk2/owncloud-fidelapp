<?php

namespace OCA\FidelApp;

use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\ContactItemMapper;
use OCA\FidelApp\Db\ContactShareItemMapper;

class Hooks {

	// TODO: system test for all methods
	/**
	 * Deletes all shares and contacts of a deleted user
	 *
	 * @param
	 *        	parameters array containing 'uid' => userId from postDeleteUser-Hook
	 * @return <code>true</code>
	 */
	public static function deleteUser(array $parameters) {
		$deletedUser = $parameters ['uid'];
		$mapper = new ContactShareItemMapper($api);
		$mapper->deleteAllForUser($deletedUser);
		return true;
	}

	/**
	 * Deletes all shares shares for a deleted contactapp contact
	 *
	 * @param
	 *        	parameters array containing a vCard id from \OCA\Contacts\VCard::pre_deleteVCard - Hook
	 * @return <code>true</code>
	 */
	/*
	 * \OC_Hook::emit('\OCA\Contacts\VCard', 'pre_deleteVCard', array('aid' => null, 'id' => $id, 'uri' => null)
	 */
	public static function deleteContact(array $parameters) {
		$contactId = $parameters ['id'];
		$api = new API();

		$contactMapper = new ContactItemMapper($api);
		try {
			$contact = $contactMapper->findByContactsappId($contactId);
			$contactManager = new ContactManager($api);
			$contactManager->removeContact($contact->getId());
		} catch(DoesNotExistException $e) {
			// No corresponding fidelapp contact, nothing to do
		}
		return true;
	}

	/**
	 * When a contactapp contact has been updated, check if the shared e-Mail addressis still there
	 *
	 * @param
	 *        	$id integer containing  a vCard id from \OCA\Contacts\VCard::post_updateVCard  - Hook
	 * @return <code>true</code>
	 */
	// \OC_Hook::emit('\OCA\Contacts\VCard', 'post_updateVCard', $id);
	public static function updateEmail($contactsappId) {
		$api = new API();
		$contactMapper = new ContactItemMapper($api);
		$contacts = $contactMapper->findByContactsappId($contactsappId);
		if (count($contacts) == 0) {
			return true;
		}
		foreach ( $contacts as $contact ) {
			$emails = $api->findEMailAddressesByContactsappId($contactsappId);
			$found = false;
			foreach ($emails as $email) {
				foreach($contacts as $contact) {
					if($contact>getEmail() == $email) {
						$found = true;
						break;
					}
				}
				if($found) {
					break;
				}
			}
			if(!$found) {
				// E-mail for contact does not exist anymore
				if(count($emails) > 0) {
					// Just set it to the first new e-mail
					$contact->setEmail($emails[0]);
					$contactMapper->save($contact);
				}
			}
		}
		return true;
	}
}