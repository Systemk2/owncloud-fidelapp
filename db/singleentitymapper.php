<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;
use OCA\AppFramework\Db\Entity;
use OCA\AppFramework\Db\DoesNotExistException;

class SingleEntityMapper extends Mapper {

	/**
	 * Returns an entity from the db and throws exceptions when there are more or less
	 * results
	 *
	 * @param int $id
	 *        	the Id of the entity to be retrieved
	 * @return \OCA\AppFramework\Db\Entity
	 * @throws OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exist
	 */
	public function findById($id) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `id` = ?';

		return $this->findEntity($sql, array (
				$id
		));
	}

	public function save(Entity $entity) {
		$id = $entity->getId();
		if ($id === null) {
			$entity = $this->insert($entity);
		} else {
			$this->update($entity);
		}
		return $entity;
	}
}