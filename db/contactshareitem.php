<?php

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

	public function getContactItem() {
		return $this->contactItem;
	}

	public function getShareItem() {
		return $this->shareItem;
	}
}