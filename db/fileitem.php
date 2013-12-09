<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class FileItem extends Entity {
	// field id is set automatically by the parent class
	public $id; // Reference to entry in fidelapp_contacts
	public $fileId; // Reference to entry in filecache
	public $checksum; // MD5 hash of the file
	public $calculationInProgress; // Set to true, while calculation is in progress
}
