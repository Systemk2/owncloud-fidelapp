<?php

namespace OCA\FidelApp\Controller;

use \OCA\AppFramework\Controller\Controller;


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
	 * ATTENTION!!!
	 * The following comments turn off security checks
	 * Please look up their meaning in the documentation!
	 *
	 * @CSRFExemption
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 */
	public function createDropdown() {
		$itemSource = $this->params('data_item_source');
		$itemType = $this->params('data_item_type');
		$response =  $this->render('sharedropdown', array( 'itemSource' => $itemSource, 'itemType' => $itemType), '');
		return $response;
		
	}
}