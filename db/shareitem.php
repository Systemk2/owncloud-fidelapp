<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class ShareItem extends Entity {
	// field id is set automatically by the parent class
	public $contactId; // Reference to entry in fidelapp_contacts
	public $fileId; // Reference to entry in fidelapp_files
	public $shareTime; // Set automatically to CURRENT_TIMESTAMP
	public $downloadTime;
	public $shareNotification;
	public $salt;
	public $notificationEmail;
	public $downloadType; // BASIC or SECURE

	public function __construct() {
		$this->addType('contactId', 'integer');
		$this->addType('fileId', 'integer');
	}
}
