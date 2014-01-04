<?php

namespace OCA\FidelApp;

use OCA\AppFramework\Http\Request;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\AppFramework\Utility\ControllerTestUtility;

require_once (__DIR__ . "/../classloader.php");

class ItemControllerTest extends ControllerTestUtility {

	public function testSetSystemValue() {
		$post = array (
				'somesetting' => 'this is a test'
		);
		$request = new Request(array (), $post);

		// create an api mock object
		$api = $this->getAPIMock();

		// expects to be called once with the method
		// setAppValue('somesetting', 'this is a test')
		$api->expects($this->once())->method('setAppValue')->with($this->equalTo('somesetting'), $this->equalTo('this is a test'));

		// we want to return the appname yourapp when this method
		// is being called
		$api->expects($this->any())->method('getAppName')->will($this->returnValue('yourapp'));

		$controller = new ItemController($api, $request, null);
		$response = $controller->setAppValue(null);

		// check if the correct parameters of the json response are set
		$this->assertEquals($post, $response->getParams());
	}
}