<?php

namespace OCA\FidelApp\Controller;

use \OCA\AppFramework\Controller\Controller;

use \OCA\FidelApp\API;
use \OCA\FidelApp\Db\ShareItemMapper;


class PageController extends Controller {


	public function __construct($api, $request) {
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
	public function index() {
		return $this->render('main', array(
				'msg' => 'Hello World'
		));
	}

	/**
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function createDropdown() {
		$api = new API();
		$mapper = new ShareItemMapper($api);
		
		$itemSource = $this->params('data_item_source');
		$itemType = $this->params('data_item_type');
		$shareItems = $mapper->findByUserFile($api->getUserId(), $itemSource); 
		$response =  $this->render('sharedropdown', array( 'itemSource' => $itemSource, 'itemType' => $itemType, 'shareItems' => $shareItems), '');
		return $response;
		
	}
}