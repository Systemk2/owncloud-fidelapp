<?php

namespace OCA\FidelApp\Db;

use \OCA\FidelApp\API;

class ContactItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_contacts'); // tablename is fidelapp_contacts
	}

	/**
	 * Get a contact item with the given email for the given user
	 *
	 * @param int $userId
	 * @param string $email
	 * @return \OCA\FidelApp\Db\ContactItem
	 * @throws \OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws \OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByUserEmail($userId, $email) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ? AND `email` = ?';

		return $this->findEntity($sql, array (
				$userId,
				$email
		));
	}

	public function findByContactsappId($contactId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `contactsapp_id` = ?';

		return $this->findEntities($sql, array (
				$contactId
		));
	}
}
