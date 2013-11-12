<?php

namespace OCA\FidelApp\Controller;

use OCA\FidelApp\API;
use \OCA\FidelApp\Db\ConfigItem;
use \OCA\FidelApp\Db\ConfigItemMapper;

class FidelboxSubscriptionController {

	public static function createParams(API $api) {
		$mapper = new ConfigItemMapper($api);
		try {
			$entity = $mapper->findByUser($api->getUserId());
		} catch ( \OCA\AppFramework\Db\DoesNotExistException $e ) {
			$entity = new ConfigItem();
			$entity->setUserId($api->getUserId());
			// Create a new random user id
			$entity->setFidelboxUser(uniqid('', true));
			$mapper->save($entity);
		}
		$params = array (
				'urlFidelboxCaptcha' => FIDELBOX_URL . '/fidelapp/captcha.php?userId=' . urlencode($entity->getUserId())
		);
		return $params;
	}

	public function fidelboxWizard() {
		// set a sensible timeout of 10 sec to stay responsive even if the update server is down.
		$ctx = stream_context_create(array (
				'http' => array (
						'timeout' => 10
				)
		));
		$result = @file_get_contents(FIDELBOX_URL, false, $ctx);
	}
}