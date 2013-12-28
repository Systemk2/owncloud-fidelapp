<?php

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
}
