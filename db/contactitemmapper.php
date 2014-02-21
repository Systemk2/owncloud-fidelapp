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

class ContactItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_contacts'); // tablename is fidelapp_contacts
	}

	/**
	 * Get a contact item with the given email for the given user
	 *
	 * @param int $userId
	 * @param string $email
	 * @return \OCA\FidelApp\Db\ContactItem
	 * @throws \OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws \OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findByUserEmail($userId, $email) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `user_id` = ? AND `email` = ?';

		return $this->findEntity($sql, array (
				$userId,
				$email
		));
	}

	public function findByContactsappId($contactId) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `contactsapp_id` = ?';

		return $this->findEntities($sql, array (
				$contactId
		));
	}
}
