<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;
use OCA\FidelApp\Db\ContactShareItem;

class ContactShareItemMapper extends Mapper {
	protected $api;
	protected $contactItemMapper;
	protected $shareItemMapper;

	public function __construct(API $api){
		$this->api = $api;
		$this->contactItemMapper = new ContactItemMapper($api);
		$this->shareItemMapper = new ShareItemMapper($api);
	}

	public function findByUserFile($userId, $fileId){
		$sql = 'SELECT * FROM `' . $this->shareItemMapper->getTableName() . '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ? AND S.`file_id` = ?';

		$contactShareItems = $this->findEntities($sql, array (
				$userId,
				$fileId
		));

		return $contactShareItems;
	}

	public function findByUserFileEmail($userId, $fileId, $email){
		$sql = 'SELECT * FROM `' . $this->shareItemMapper->getTableName() . '` S, `' . $this->contactItemMapper->getTableName() . '` C WHERE C.`id` = S.`contact_id` AND C.`user_id` = ? AND S.`file_id` = ? AND C.`email` = ? ';

		$contactShareItems = $this->findEntities($sql, array (
				$userId,
				$fileId,
				$email
		));

		return $contactShareItems;
	}

	public function save(ContactShareItem $item){
		$contactItem = $item->getContactItem();
		$shareItem = $item->getShareItem();

		$contactId = $contactItem->getId();
		if ($contactId === null) {
			// Check if the same contact / email exists in share items
			try {
				$contactItemInDb = $this->contactItemMapper->findByUserEmail($contactItem->userId, $contactItem->email);
				$contactItem->setId($contactItemInDb->getId());
			} catch ( \OCA\AppFramework\Db\DoesNotExistException $e ) {
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
			} catch ( \OCA\AppFramework\Db\DoesNotExistException $e ) {
				// Ignore
			}
		}

		$shareItem->setContactId($contactItem->getId());
		$this->shareItemMapper->save($shareItem);
	}

	protected function mapRowToEntity($row){
		$contactItemProperties = array ();
		$shareItemProperties = array ();

		$contactItem = new ContactItem();
		$shareItem = new ShareItem();

		foreach ( $row as $key => $value ) {
			$property = $contactItem->columnToProperty($key);
			if ($property !== null) {
				$contactItemProperties [$key] = $value;
			}
			$property = $shareItem->columnToProperty($key);
			if ($property !== null) {
				$shareItemProperties [$key] = $value;
			}
		}

		$contactItem->fromRow($contactItemProperties);
		$shareItem->fromRow($shareItemProperties);

		return new ContactShareItem($contactItem, $shareItem);
	}
}
