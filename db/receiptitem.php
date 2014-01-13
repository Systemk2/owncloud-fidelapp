<?php

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
	 * When has the file been downloaded
	 */
	public $downloadTime;
	/**
	 * BASIC or SECURE
	 */
	public $downloadType;
}


