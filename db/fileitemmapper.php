<?php

namespace OCA\FidelApp\Db;

use \OCA\FidelApp\API;

class FileItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_files'); // tablename is fidelapp_files
	}

	/**
	 * Get a share item with the given file for the given contact
	 *
	 * @param $fileId int
	 * @return \OCA\FidelApp\Db\FileItem
	 * @throws OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByFileId($fileId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `file_id` = ?';

		return $this->findEntity($sql, array (
				$fileId
		));
	}

}
