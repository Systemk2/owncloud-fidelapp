<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class ContactItem extends Entity {
	// field id is set automatically by the parent class
	/**
	 * The owncloud user id
	 */
	public $userId;
	public $email;
	public $password;
	public $contactsappId;
}
