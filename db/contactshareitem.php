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

use OCA\FidelApp\Db\ContactItem;

/**
 * Links a contact entity to a share entity
 *
 * @author Sebastian Kanzow
 *
 */
class ContactShareItem {
	private $contactItem;
	private $shareItem;
	private $id;

	public function __construct(ContactItem $contactItem, ShareItem $shareItem) {
		$this->contactItem = $contactItem;
		$this->shareItem = $shareItem;
		$this->id = $contactItem->getId() . '_' . $shareItem->getId();
	}

	/**
	 * @return ContactItem
	 */
	public function getContactItem() {
		return $this->contactItem;
	}

	/**
	 * @return ShareItem
	 */
	public function getShareItem() {
		return $this->shareItem;
	}
}