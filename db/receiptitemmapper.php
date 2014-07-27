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

class ReceiptItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_receipts'); // tablename is fidelapp_receipts
	}

	/**
	 * Get a receipt items for the given user
	 *
	 * @param $userId string the Owncloud user
	 * @return array \OCA\FidelApp\Db\ReceiptItem
	 */
	public function findByUser($userId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ? ORDER BY download_time DESC';

		return $this->findEntities($sql, array (
				$userId
		));
	}

	/**
	 * Get receipt items for the given share id
	 *
	 * @param $shareId int the shareitem Id
	 * @return array \OCA\FidelApp\Db\ReceiptItem
	 */
	public function findByShareId($shareId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `share_id` = ? ORDER BY download_time DESC';

		return $this->findEntities($sql, array (
				$shareId
		));
	}

}
