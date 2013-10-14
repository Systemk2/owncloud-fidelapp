<?php
namespace \OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class ShareItem extends Entity {
	// field id is set automatically by the parent class
	public $userId;
	public $email;
	public $contactId;
	public $fileId;
	public $shareTime; // Set automatically to CURRENT_TIMESTAMP
	public $downloadTime;
	public $shareNotification;
	public $salt;
	public $checksum;
	public $notificationEmail;
}

