<?php

namespace OCA\FidelApp\Db;

use \OCA\FidelApp\API;

class ShareItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_shares'); // tablename is fidelapp_shares
	}

	/**
	 * Get a share item with the given file for the given contact
	 *
	 * @param int $contactId
	 * @param int $fileId
	 * @return \OCA\FidelApp\Db\ShareItem
	 * @throws OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByContactFile($contactId, $fileId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `contact_id` = ? AND `file_id` = ?';

		return $this->findEntity($sql, array (
				$contactId,
				$fileId
		));
	}

	public function findByContact($contactId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `contact_id` = ?';

		return $this->findEntities($sql, array (
				$contactId
		));
	}
}
