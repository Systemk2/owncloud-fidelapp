<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class FileItem extends Entity {
	// field id is set automatically by the parent class

	/**
	 * Reference to entry in filecache
	 */
	public $fileId;
	/**
	 * MD5 hash of the file
	 */
	public $checksum;
	/**
	 * Set to true, while calculation is in progress
	 */
	public $calculationInProgress;

	public function __construct() {
		$this->addType('fileId', 'integer');
	}
}
