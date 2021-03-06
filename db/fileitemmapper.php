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
