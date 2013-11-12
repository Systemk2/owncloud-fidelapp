<?php

namespace OCA\FidelApp\Controller;

use \OCA\AppFramework\Controller\Controller;
use \OCA\FidelApp\API;
use \OCA\FidelApp\Db\ContactShareItemMapper;

class PageController extends Controller {

	public function __construct($api, $request){
		parent::__construct($api, $request);
	}

	/**
	 * ATTENTION!!!
	 * The following comments turn off security checks
	 * Please look up their meaning in the documentation!
	 *
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function fidelApp(){
		return $this->render('fidelapp');
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function wizard(){
		$params = array('menu' => 'wizard', 'actionTemplate' => 'wizard_1');
		if($this->params('selection')) {
			$params['selection'] = $this->params('selection');
			$params['selection2'] = $this->params('selection2');
			$params['domainOrIp'] = $this->params('domainOrIp');
			$params['useSSL'] = $this->params('useSSL');
			if($this->params('selection') == 'accessTypeDirect') {
				$params['wizard_step2'] = 'wizard_2a';
			} else if($this->params('selection') == 'accessTypeFidelbox') {
				$params = array_merge($params, FidelboxSubscriptionController::createParams($this->api));
				$params['wizard_step2'] = 'wizard_2b';
			}
			return $this->render('fidelapp', $params, '');
		}
		return $this->render('fidelapp', $params);
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function createDropdown(){
		$mapper = new ContactShareItemMapper($this->api);

		$itemSource = $this->params('data_item_source');
		$itemType = $this->params('data_item_type');
		$shareItems = $mapper->findByUserFile($api->getUserId(), $itemSource);
		$response = $this->render('sharedropdown', array (
				'itemSource' => $itemSource,
				'itemType' => $itemType,
				'shareItems' => $shareItems
		), '');
		return $response;
	}
}
