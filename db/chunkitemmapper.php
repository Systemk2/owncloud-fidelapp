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

use \OCA\FidelApp\API;

class ChunkItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_chunks'); // tablename is fidelapp_chunks
	}

	/**
	 * Get all chunk items for the given share
	 *
	 * @param $shareId int
	 * @return array of \OCA\FidelApp\Db\ChunkItem
	 */
	public function findByShareId($shareId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `share_id` = ?';

		return $this->findEntities($sql, array (
				$shareId
		));
	}

	/**
	 * Get chunk item with given chunk id for the given share
	 *
	 * @param $shareId int
	 * @return \OCA\FidelApp\Db\ChunkItem
	 * @throws OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByShareAndChunkId($shareId, $chunkId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `share_id` = ? AND `chunk_id` = ?';

		return $this->findEntity($sql, array (
				$shareId,
				$chunkId
		));
	}

	/**
	 * Delete all chunk information for the given share Id
	 *
	 * @param integer $shareId
	 */
	public function deleteByShareId($shareId) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` ' . 'WHERE `share_id` = ?';
		$this->execute($sql, array($shareId));
	}
}
