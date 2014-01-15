<?php

namespace OCA\FidelApp;

use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\ContactItemMapper;
use OCA\FidelApp\Db\ShareItemMapper;
use OCA\FidelApp\Db\FileItemMapper;
use OCA\FidelApp\Db\ContactItem;

class ContactManager {
	protected $api;

	public function __construct(API $api) {
		$this->api = $api;
	}

	/**
	 * Remove all contact data from the fidelapp tables
	 * This includes
	 * <ul>
	 * <li>The contact data itself</li>
	 * <li>Active shares for this contact</li>
	 * <li>File checksum information for files that are not shared anymore</li>
	 * </ul>
	 * Receipt notices for downloaded files for the contact are not removed
	 *
	 * @param string $contactId
	 * @return <code>true</code>
	 * @throws OCA\AppFramework\Db\DoesNotExistException if the contact does not exist
	 */
	public function removeContact($contactId) {
		$mapper = new ContactItemMapper($this->api);
		$shareMapper = new ShareItemMapper($this->api);
		$fileMapper = new FileItemMapper($this->api);

		$contactItem = $mapper->findById($contactId);

		// Cleanup: Remove shares that belong to the deleted contact
		$shares = $shareMapper->findByContact($contactId);
		foreach ( $shares as $share ) {
			$fileId = $share->getFileId();
			$shareMapper->delete($share);

			if (count($shareMapper->findByFileId($fileId)) == 0) {
				// File is not shared anymore, clean up checksum table too
				try {
					$fileItem = $fileMapper->findByFileId($fileId);
					$fileMapper->delete($fileItem);
				} catch(DoesNotExistException $e) {
					// The checksum information has already been deleted? Ignore....
				}
			}
		}
		// Finally delete the contact itself
		$mapper->delete($contactItem);
		return true;
	}

	/**
	 * Create a display name for the given contact item.
	 * If the contact is a contactsapp contact, the display name is "Contactname <email-address>",
	 * otherwise it's just "email-address"
	 *
	 * @param ContactItem $item
	 * @return string the display name
	 */
	public function makeContactName(ContactItem $item) {
		$contactsappId = $item->getContactsappId();
		$email = $item->getEmail();
		if ($contactsappId) {
			// TODO: Cache result (but how?)
			$contactName = $this->api->findContactNameById($contactsappId);
			if ($contactName) {
				return $contactName . " <$email>";
			}
		}
		return $email;
	}
}