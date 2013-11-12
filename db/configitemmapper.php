<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;

class ConfigItemMapper extends Mapper {

	public function __construct(API $api){
		parent::__construct($api, 'fidelapp_config'); // tablename is fidelapp_config
	}

	/**
	 * Get a config item for the given user
	 *
	 * @param int $userId
	 * @return \OCA\FidelApp\Db\ConfigItem
	 * @throws DoesNotExistException if the item does not exist
	 * @throws MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByUser($userId){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ?';

		return $this->findEntity($sql, array (
				$userId
		));
	}

	public function save(ConfigItem $configItem){
		$id = $configItem->getId();
		if ($id === null) {
			$configItem = $this->insert($configItem);
		} else {
			$this->update($configItem);
		}
		return $configItem;
	}
}
