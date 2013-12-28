<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class ChunkItem extends Entity {
	// field id is set automatically by the parent class

	/**
	 * The download chunk Id
	 */
	public $chunkId;
	/**
	 * Reference to a share item
	 */
	public $shareId;
	/**
	 * The MD5 hash of the download chunk
	 */
	public $checksum;

	public function __construct() {
		$this->addType('chunkId', 'integer');
		$this->addType('shareId', 'integer');
	}
}
