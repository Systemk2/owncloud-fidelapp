<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;

class ContactItemMapper extends Mapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_contacts'); // tablename is fidelapp_contacts
	}

	/**
	 * Get a contact item with the given email for the given user
	 *
	 * @param int $userId
	 * @param string $email
	 * @return \OCA\FidelApp\Db\ContactItem
	 * @throws DoesNotExistException if the item does not exist
	 * @throws MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByUserEmail($userId, $email) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ? AND `email` = ?';

		return $this->findEntity($sql, array (
				$userId,
				$email
		));
	}

	public function save(ContactItem $contactItem) {
		$id = $contactItem->getId();
		if ($id === null) {
			$contactItem = $this->insert($contactItem);
		} else {
			$this->update($contactItem);
		}
		return $contactItem;
	}
}
