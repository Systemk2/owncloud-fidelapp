<?php

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
	 * Set automatically to CURRENT_TIMESTAMP
	 */
	public $shareTime;
	/**
	 * When has the file been downloaded
	 */
	public $downloadTime;
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

	public function __construct() {
		$this->addType('contactId', 'integer');
		$this->addType('fileId', 'integer');
		$this->addType('nbChunks', 'integer');
	}
}
