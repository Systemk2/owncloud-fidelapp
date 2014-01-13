<?php

namespace OCA\FidelApp\Db;

use \OCA\FidelApp\API;

class ReceiptItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_receipts'); // tablename is fidelapp_receipts
	}

	/**
	 * Get a receipt items for the given user
	 *
	 * @param $userId string the Owncloud user
	 * @return array \OCA\FidelApp\Db\ReceiptItem
	 */
	public function findByUser($userId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ?';

		return $this->findEntities($sql, array (
				$userId
		));
	}

}
