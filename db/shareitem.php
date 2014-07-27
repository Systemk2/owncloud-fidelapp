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

use \OCA\AppFramework\Db\Entity;

class ShareItem extends Entity {
	// field id is set automatically by the parent class
	/**
	 * Reference to entry in fidelapp_contacts
	 */
	public $contactId;
	/**
	 * Reference to entry in fidelapp_files
	 */
	public $fileId;
	/**
	 * When the file has been shared
	 */
	public $shareTime;
	/**
	 * Boolean indicating if the receiver has been notified that files are there to be downloaded
	 */
	public $shareNotification;
	/**
	 * The second part of the password, which is transmitted to the downloader only after checksum verfication.
	 */
	public $salt;
	/**
	 * The notification e-mail for successful download
	 */
	public $notificationEmail;
	/**
	 * BASIC or SECURE
	 */
	public $downloadType;
	/**
	 * The number of encrypted chunks
	 */
	public $nbChunks;

	/**
	 * share id if this file was shared indirectly by sharing a directory
	 */
	public $parentShareId;

	/**
	 * boolean true if this a directory
	 */
	public $isDir;

	public function __construct() {
		$this->addType('contactId', 'integer');
		$this->addType('fileId', 'integer');
		$this->addType('shareNotification', 'boolean');
		$this->addType('nbChunks', 'integer');
		$this->addType('parentShareId', 'integer');
		$this->addType('isDir', 'boolean');
	}
}
