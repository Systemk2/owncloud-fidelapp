<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;

class ShareItemMapper extends Mapper {

	public function __construct(API $api){
		parent::__construct($api, 'fidelapp_shares'); // tablename is fidelapp_shares
	}

	/**
	 * Get a share item with the given file for the given contact
	 *
	 * @param int $contactId        	
	 * @param int $fileId        	
	 * @return \OCA\FidelApp\Db\ShareItem
	 * @throws DoesNotExistException if the item does not exist
	 * @throws MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByContactFile($contactId, $fileId){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `contact_id` = ? AND `file_id` = ?';
		
		return $this->findEntity($sql, array (
				$contactId,
				$fileId 
		));
	}

	public function save(ShareItem $shareItem){
		$id = $shareItem->getId();
		if ($id === null) {
			$shareItem = $this->insert($shareItem);
		} else {
			$this->update($shareItem);
		}
		return $shareItem;
	}
}
