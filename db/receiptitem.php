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

class ReceiptItem extends Entity {
	// field id is set automatically by the parent class

	/**
	 * The Owncloud user
	 */
	public $userId;
	/**
	 * Receiver contact name and e-mail address
	 */
	public $contactName;
	/**
	 * The name of the file
	 */
	public $fileName;
	/**
	 * The reference the share which was used to create this receipt
	 * (not a FK reference, because the share might be deleted)
	 */
	public $shareId;
	/**
	 * When has the download has been started
	 */
	public $downloadStarted;
	/**
	 * When has the file been downloaded
	 */
	public $downloadTime;
	/**
	 * BASIC or SECURE
	 */
	public $downloadType;
}


