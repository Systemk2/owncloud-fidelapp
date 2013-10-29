<?php
namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class ContactItem extends Entity {
	// field id is set automatically by the parent class
	public $userId; // The owncloud user id
	public $email;
	public $contactsappId; // A ContactItem
}
