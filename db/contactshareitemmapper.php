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
namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;
use OCA\FidelApp\Db\ContactShareItem;

class ContactShareItemMapper extends Mapper {
	protected $api;
	protected $contactItemMapper;
	protected $shareItemMapper;

	public function __construct(API $api) {
		$this->api = $api;
		$this->contactItemMapper = new ContactItemMapper($api);
		$this->shareItemMapper = new ShareItemMapper($api);
	}

	/**
	 * Find all ContactShareItems for the given Owncloud user and file
	 *
	 * @param string $userId
	 * @param int $fileId
	 *
	 * @return array OCA\FidelApp\Db\ContactShareItem\ContactShareItem
	 */
	public function findByUserFile($userId, $fileId) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() .
				 '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ? AND S.`file_id` = ?';

		// $sql = 'SELECT * FROM `' . $this->shareItemMapper->getTableName() . '` S, `' . $this->contactItemMapper->getTableName() .
		// '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ? AND S.`file_id` = ?';

		$contactShareItems = $this->findEntities($sql, array (
				$userId,
				$fileId
		));

		return $contactShareItems;
	}

	/**
	 * Find all ContactShareItems for the given Owncloud user, file and email
	 *
	 * @param string $userId
	 * @param int $fileId
	 * @param string $email
	 *
	 * @return array of OCA\FidelApp\Db\ContactShareItem
	 */
	public function findByUserFileEmail($userId, $fileId, $email) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() .
				 '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ? AND S.`file_id` = ? AND C.`email` = ? ';

		$contactShareItems = $this->findEntities($sql, array (
				$userId,
				$fileId,
				$email
		));

		return $contactShareItems;
	}

	public function findByContact($contactId) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND S.`contact_id` = ?';

		$contactShareItems = $this->findEntities($sql, array (
				$contactId
		));

		return $contactShareItems;
	}

	/**
	 * Get all contact share items for the given user id
	 *
	 * @param string $userId
	 * @param boolean $findOnlyDirectoryShares
	 *        	[optional] if set to <code>true</code>, only directory shares will be returned
	 * @return array of OCA\FidelApp\Db\ContactShareItem
	 */
	public function findByUser($userId, $findOnlyDirectoryShares = false) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ?';
		if($findOnlyDirectoryShares) {
			$sql .= ' AND S.`is_dir` = 1';
		}

		$contactShareItems = $this->findEntities($sql, array (
				$userId
		));

		return $contactShareItems;
	}

	/**
	 * Get the contact share item with the given share id
	 *
	 * @param integer $shareId
	 * @return OCA\FidelApp\Db\ContactShareItem
	 * @throws OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByShareId($shareId) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND S.`id` = ?';

		$contactShareItem = $this->findEntity($sql, array (
				$shareId
		));

		return $contactShareItem;
	}

	/**
	 * Remove all contacts, shares, receipts, checksums and chunks for the given user id
	 * This is called when a user is deleted from ownCloud
	 *
	 * @param string $userId
	 *        	TODO: Test it!
	 */
	public function deleteAllForUser($userId) {
		// Delete chunks
		$chunkItemMapper = new ChunkItemMapper($this->api);
		$sql = 'DELETE FROM `' . $chunkItemMapper->getTableName() . '` WHERE `share_id` IN (SELECT S.`id` from `' .
				 $this->shareItemMapper->getTableName() . '` S, `' . $this->contactItemMapper->getTableName() .
				 '` C WHERE S.`contact_id` = C.`id` AND C.`user_id` = ? )';
		$this->execute($sql, array (
				$userId
		));

		// Delete file checksums
		$fileItemMapper = new FileItemMapper($this->api);
		$sql = 'DELETE FROM `' . $fileItemMapper->getTableName() . '` WHERE `file_id` IN (SELECT S.`file_id` from `' .
				 $this->shareItemMapper->getTableName() . '` S, `' . $this->contactItemMapper->getTableName() .
				 '` C WHERE S.`contact_id` = C.`id` AND C.`user_id` = ? )';
		$this->execute($sql, array (
				$userId
		));

		// Delete shares
		$sql = 'DELETE FROM `' . $this->shareItemMapper->getTableName() . '` WHERE `contact_id` IN (SELECT `id` from `' .
				 $this->contactItemMapper->getTableName() . '` WHERE `user_id` = ?)';
		$this->execute($sql, array (
				$userId
		));

		// Delete download receipts
		$receiptItemMapper = new ReceiptItemMapper($this->api);
		$sql = 'DELETE FROM `' . $receiptItemMapper->getTableName() . '` WHERE `user_id` = ?';
		$this->execute($sql, array (
				$userId
		));

		// Delete contacts
		$sql = 'DELETE FROM `' . $this->contactItemMapper->getTableName() . '` WHERE `user_id` = ?';
		$this->execute($sql, array (
				$userId
		));
	}

	public function save(ContactShareItem $item) {
		$contactItem = $item->getContactItem();
		$shareItem = $item->getShareItem();

		$contactId = $contactItem->getId();
		if ($contactId === null) {
			// Check if the same contact / email exists in share items
			try {
				$contactItemInDb = $this->contactItemMapper->findByUserEmail($contactItem->userId, $contactItem->email);
				$contactItem->setId($contactItemInDb->getId());
			} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
				// Ignore
			}
		}
		$contactItem = $this->contactItemMapper->save($contactItem);

		$shareId = $shareItem->getId();
		if ($shareId === null) {
			// Check if the file is already shared for the contact
			try {
				$shareItemInDb = $this->shareItemMapper->findByContactFile($contactItem->userId, $shareItem->fileId);
				$shareItem->setId($shareItemInDb->getId());
			} catch(\OCA\AppFramework\Db\DoesNotExistException $e) {
				// Ignore
			}
		}

		$shareItem->setContactId($contactItem->getId());
		$shareItem = $this->shareItemMapper->save($shareItem);
		// Update IDs from Database
		$item->getContactItem()->setId($contactItem->getId());
		$item->getShareItem()->setId($shareItem->getId());
		return $item;
	}

	protected function mapRowToEntity($row) {
		$contactItemProperties = array ();
		$shareItemProperties = array ();

		$contactItem = new ContactItem();
		$shareItem = new ShareItem();

		foreach ( $row as $key => $value ) {
			if ($key == 'id') {
				$shareItemProperties ['id'] = $value;
			} else {
				$property = $contactItem->columnToProperty($key);
				if (property_exists($contactItem, $property)) {
					$contactItemProperties [$key] = $value;
				}
				if (property_exists($shareItem, $property) !== null) {
					$shareItemProperties [$key] = $value;
				}
			}
			if ($key == 'contact_id') {
				$contactItemProperties ['id'] = $value;
			}
		}

		$contactItem->fromRow($contactItemProperties);
		$shareItem->fromRow($shareItemProperties);

		return new ContactShareItem($contactItem, $shareItem);
	}
}
