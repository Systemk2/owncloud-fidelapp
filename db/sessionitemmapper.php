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

class SessionItemMapper extends SingleEntityMapper {

	public function __construct(API $api) {
		parent::__construct($api, 'fidelapp_public_session'); // tablename is fidelapp_public_session
	}

	/**
	 * Get session item with given session token
	 *
	 * @param $sessionToken string
	 * @return \OCA\FidelApp\Db\SessionItem
	 * @throws \OCA\AppFramework\Db\DoesNotExistException if the item does not exist
	 * @throws \OCA\AppFramework\Db\MultipleObjectsReturnedException if more than one item exists
	 */
	public function findBySessionToken($sessionToken) {
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` ' . 'WHERE `session_token` = ?';

		return $this->findEntity($sql, array (
				$sessionToken
		));
	}

	/**
	 * Delete all session information older than the given timestamp
	 *
	 * @param $timestamp \DateTime
	 */
	public function deleteOlderThan(\DateTime $timestamp) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` ' . 'WHERE `timestamp` < ?';
		$this->execute($sql, array (
				$timestamp->format('Y-m-d H:i:s')
		));
	}

	/**
	 * Delete all session information for the goiven contact
	 *
	 * @param $contactId integer
	 */
	public function deletebyContactId($contactId) {
		$sql = 'DELETE FROM `' . $this->getTableName() . '` ' . 'WHERE `contact_id` = ?';
		$this->execute($sql, array (
				$contactId
		));
	}
}
