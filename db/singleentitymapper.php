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
			$updatedFields = $entity->getUpdatedFields();
			// dont update the id field
			unset($updatedFields['id']);

			if (count($updatedFields)) {
				$this->update($entity);
			}
		}
		return $entity;
	}
}