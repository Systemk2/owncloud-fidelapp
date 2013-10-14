<?php
namespace \OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Mapper;

class ShareItemMapper extends Mapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_shares'); // tablename isfidelapp_shares
	}

	public function findByUserFileEmail($userId, $fileId, $email){
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' .
				'WHERE `user_id` = ? AND `file_id` = ? AND `email` = ?';

		$row = $this->execute($sql, array($userId, $fileId, $email));
		$shareItem = new ShareItem();
		$shareItem->fromRow($row);

		return $shareItem;
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
