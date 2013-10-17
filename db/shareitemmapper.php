<?php
namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;
use \OCA\FidelApp\API;

class ShareItemMapper extends Mapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_shares'); // tablename is fidelapp_shares
	}

	public function findByUserFileEmail($userId, $fileId, $email){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' .
				'WHERE `user_id` = ? AND `file_id` = ? AND `email` = ?';

		$shareItems = $this->findEntities($sql, array($userId, $fileId, $email));

		return $shareItems;
	}

	public function findByUserFile($userId, $fileId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' .
				'WHERE `user_id` = ? AND `file_id` = ?' ;
		
		$shareItems = $this->findEntities($sql, array($userId, $fileId));
		
		return $shareItems;
	}

	public function save($shareItem) {
		$id = $shareItem->getId();
		if($id === null){
			$this->insert($shareItem);
		} else {
			$this->update($shareItem);
		}
	}
}
