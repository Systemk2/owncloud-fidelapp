<?php

namespace OCA\FidelApp\Db;

use \OCA\FidelApp\API;

class ConfigItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_config'); // tablename is fidelapp_config
	}

	/**
	 * Get a config item for the given user
	 *
	 * @param string $userId
	 *
	 * @return \OCA\FidelApp\Db\ConfigItem
	 *
	 * @throws \OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws \OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByUser($userId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ?';

		return $this->findEntity($sql, array (
				$userId
		));
	}
}
