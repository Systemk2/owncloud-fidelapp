<?php

namespace OCA\FidelApp\Db;

use \OCA\AppFramework\Db\Entity;

class ConfigItem extends Entity {
	// field id is set automatically by the parent class
	public $userId; // The owncloud user id
	public $accessType; // FIXED_IP, DOMAIN_NAME, FIDELBOX_ACCOUNT or null
	public $fixedIp; // Optional field: fixed ip
	public $domainName; // Optional field: domain name
	public $fidelboxAccount; // Optional field: fidelbox.de account id
	public $useSsl; // Should SSL be used to contact fidelapp from Internet?
}
