<?php

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
	 * @return array of OCA\FidelApp\Db\ContactShareItem\ContactShareItem
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

	public function findByUserContact($userId, $contactId) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() .
				 '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ? AND S.`contact_id` = ?';

		$contactShareItems = $this->findEntities($sql, array (
				$userId,
				$contactId
		));

		return $contactShareItems;
	}

	public function findByUser($userId) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ?';

		$contactShareItems = $this->findEntities($sql, array (
				$userId
		));

		return $contactShareItems;
	}

	public function findByShareId($shareId) {
		$sql = 'SELECT S.*, C.user_id, C.email, C.password, C.contactsapp_id FROM `' . $this->shareItemMapper->getTableName() .
				 '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND S.`id` = ?';

		$contactShareItem = $this->findEntity($sql, array (
				$shareId
		));

		return $contactShareItem;
	}

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
		$this->shareItemMapper->save($shareItem);
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
