<?php

/**
 * ownCloud - FidelApp (File Delivery App)
 *
 * @author Sebastian Kanzow
 * @copyright 2014 System k2 GmbH  info@systemk2.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\FidelApp;

use OCA\AppFramework\Http\Request;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\AppFramework\Utility\ControllerTestUtility;
use OCA\FidelApp\Controller\PageController;
use OCA\FidelApp\DependencyInjection\DIContainer;
use OCA\FidelApp\Db\ContactShareItem;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\Db\ShareItem;
use OCA\FidelApp\Db\ReceiptItem;

require_once (__DIR__ . "/../classloader.php");

require_once 'PHPUnit/Autoload.php';

class MockOC_L10N {

	public function t($string) {
		return $string;
	}
}

class PageControllerTest extends \PHPUnit_Framework_TestCase {

	// Fixture
	protected $mockApp;
	protected $mockContainer;
	protected $mockApi;
	protected $mockContactShareItemMapper;
	protected $mockContactManager;
	protected $mockReceiptItemMapper;
	protected $mockFidelboxConfig;

	// Class under Test
	protected $cutPageController;

	protected function setUp() {
		$this->mockApp = $this->getMock('OCA\FidelApp\AppInfo\App', array (
				'checkPrerequisites'
		));
		$this->mockApi = $this->getMock('OCA\FidelApp\API', get_class_methods('OCA\FidelApp\API'));
		$this->mockApi->expects($this->any())->method('registerFidelappException')->will($this->returnValue(null));
		$this->mockApi->expects($this->any())->method('getTrans')->will($this->returnValue(new \OCA\FidelApp\MockOC_L10N()));

		$this->mockContactShareItemMapper = $this->getMock('OCA\FidelApp\Db\ContactShareItemMapper',
				array (
						'findByUser'
				), array (
						$this->mockApi
				));
		$this->mockContactManager = $this->getMock('OCA\FidelApp\ContactManager', array (
				'makeContactName'
		), array (
				$this->mockApi
		));
		$this->mockReceiptItemMapper = $this->getMock('OCA\FidelApp\Db\ReceiptItemMapper', array (
				'findByUser'
		), array (
				$this->mockApi
		));
		$this->mockFidelboxConfig = $this->getMock('OCA\FidelApp\FidelboxConfig',
				get_class_methods('OCA\FidelApp\FidelboxConfig'), array (
						$this->mockApi
				));

		$this->mockContainer = new DIContainer();
		$this->mockContainer ['App'] = $this->mockApp;
		$this->mockContainer ['API'] = $this->mockApi;
		$this->mockContainer ['ContactShareItemMapper'] = $this->mockContactShareItemMapper;
		$this->mockContainer ['ContactManager'] = $this->mockContactManager;
		$this->mockContainer ['ReceiptItemMapper'] = $this->mockReceiptItemMapper;
		$this->mockContainer ['FidelboxConfig'] = $this->mockFidelboxConfig;

		$this->cutPageController = new PageController($this->mockContainer, new Request());
	}

	private function mockPageControllerMethods(array $methodsToMock) {
		$this->cutPageController = $this->getMock('OCA\FidelApp\Controller\PageController', $methodsToMock,
				array (
						$this->mockContainer,
						new Request()
				));
	}

	public function testFidelApp1() {
		// Simulate "no errors, no warnings"
		$this->mockApp->expects($this->once())->method('checkPrerequisites')->will(
				$this->returnValue(array (
						'errors' => array (),
						'warnings' => array ()
				)));

		// Simulate that the app has already been configured
		$this->mockApi->expects($this->exactly(2))->method('getAppValue')->will($this->returnValue(true));
		$this->mockPageControllerMethods(array (
				'shares',
				'appconfig'
		));
		$this->cutPageController->expects($this->once())->method('shares');

		// Run the test
		$this->cutPageController->fidelApp();
	}

	public function testFidelApp2() {
		// Simulate "no errors, no warnings"
		$this->mockApp->expects($this->once())->method('checkPrerequisites')->will(
				$this->returnValue(array (
						'errors' => array (),
						'warnings' => array ()
				)));

		// Simulate that the app has not yet been configured
		$this->mockApi->expects($this->once())->method('getAppValue')->with('fidelbox_account')->will($this->returnValue(null));

		$this->mockPageControllerMethods(array (
				'shares',
				'appconfig'
		));

		$this->cutPageController->expects($this->once())->method('appConfig');

		// Run the test
		$this->cutPageController->fidelApp();
	}

	public function testFidelApp3() {
		// Simulate "there are warnings"
		$returnValue = array (
				'warnings' => array (
						'message' => 'testdummy'
				)
		);
		$this->mockApp->expects($this->once())->method('checkPrerequisites')->will($this->returnValue($returnValue));

		$this->mockPageControllerMethods(array (
				'shares',
				'appConfig'
		));

		$this->cutPageController->expects($this->never())->method('shares');
		$this->cutPageController->expects($this->never())->method('appConfig');

		// Run the test
		$result = $this->cutPageController->fidelApp();
		$this->assertInstanceof('\OCA\AppFramework\Http\TemplateResponse', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);
		$expected = array_merge($returnValue);
		$expected ['errors'] = array ();
		$this->assertAttributeEquals($expected, 'params', $result);
	}

	private function setUp2MockContacts() {
		// User has shared two files
		$contactItem1 = new ContactItem();
		$contactItem1->setId(1);
		$shareItem1 = new ShareItem();
		$shareItem1->setFileId(1);
		$contactItem2 = new ContactItem();
		$contactItem2->setId(2);
		$shareItem2 = new ShareItem();
		$shareItem2->setFileId(2);

		$item1 = new ContactShareItem($contactItem1, $shareItem1);
		$item2 = new ContactShareItem($contactItem2, $shareItem2);
		$value = array (
				$item1,
				$item2
		);
		$this->mockContactShareItemMapper->expects($this->once())->method('findByUser')->will($this->returnValue($value));
		$valueMap = array (
				array (
						$contactItem1,
						'Jon Doe'
				),
				array (
						$contactItem2,
						'Jane Smith'
				)
		);
		$this->mockContactManager->expects($this->exactly(2))->method('makeContactName')->will($this->returnValueMap($valueMap));
		return array (
				clone $item1,
				clone $item2
		);
	}

	public function testPasswords() {
		$items = $this->setUp2MockContacts();
		// Run the test
		$result = $this->cutPageController->passwords();
		$this->assertInstanceof('\OCA\AppFramework\Http\TemplateResponse', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);

		$items [0]->contactName = 'Jon Doe';
		$items [1]->contactName = 'Jane Smith';

		$expectedResult = array (
				'menu' => 'passwords',
				'actionTemplate' => 'passwords',
				'shares' => array (
						1 => $items [0],
						2 => $items [1]
				)
		);
		$this->assertAttributeEquals($expectedResult, 'params', $result);
	}

	public function testShares() {
		$items = $this->setUp2MockContacts();

		$valueMap = array (
				array (
						$items [0]->getShareItem()->getFileId(),
						'/path1/file1'
				),
				array (
						$items [1]->getShareItem()->getFileId(),
						'/path2/file2'
				)
		);
		$this->mockApi->expects($this->exactly(2))->method('getPath')->will($this->returnValueMap($valueMap));

		$result = $this->cutPageController->shares();

		$items [0]->contactName = 'Jon Doe';
		$items [1]->contactName = 'Jane Smith';
		$items [0]->fileName = 'path1/file1';
		$items [1]->fileName = 'path2/file2';

		$expectedResult = array (
				'menu' => 'shares',
				'actionTemplate' => 'shares',
				'shares' => $items,
				'view' => 'activeshares'
		);
		$this->assertInstanceof('\OCA\AppFramework\Http\TemplateResponse', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);
		$this->assertAttributeEquals($expectedResult, 'params', $result);
	}

	public function testReceipts() {
		$receiptItem1 = new ReceiptItem();
		$receiptItem2 = new ReceiptItem();
		$value = array (
				$receiptItem1,
				$receiptItem2
		);
		$this->mockReceiptItemMapper->expects($this->once())->method('findByUser')->will($this->returnValue($value));

		$result = $this->cutPageController->receipts();
		$expectedResult = array (
				'menu' => 'shares',
				'actionTemplate' => 'shares',
				'receipts' => $value,
				'view' => 'receiptnotices'
		);
		$this->assertInstanceof('\OCA\AppFramework\Http\TemplateResponse', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);
		$this->assertAttributeEquals($expectedResult, 'params', $result);
	}

	public function testAppConfig1() {
		$this->mockApi->expects($this->any())->method('getAppValue')->will(
				$this->returnCallback(array (
						'\OCA\FidelApp\PageControllerTest',
						'returnFidelboxDefined'
				)));

		$this->mockFidelboxConfig->expects($this->once())->method('validateAccount')->will($this->returnValue(true));
		$this->mockFidelboxConfig->expects($this->once())->method('pingBack')->will($this->returnValue(true));

		// Run the test
		$result = $this->cutPageController->appConfig();

		$expectedResult = array (
				'menu' => 'appconfig',
				'actionTemplate' => 'appconfig',
				'useSSL' => true,
				'port' => '12345',
				'domain' => 'UNKNOWN',
				'fixedIp' => 'UNKNOWN',
				'accessType' => 'FIDELBOX_REDIRECT',
				'fidelboxConfig' => 'appconfig_fidelbox',
				'fidelboxAccount' => 'ABC123',
				'validFidelboxAccount' => true,
				'isReachable' => true,
				'showAccessTypeTemplate' => true
		);
		$this->assertInstanceof('\OCA\AppFramework\Http\TemplateResponse', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);
		$this->assertAttributeEquals($expectedResult, 'params', $result);
	}

	public function returnFidelboxDefined($argument) {
		switch ($argument) {
			case 'fidelbox_account' :
				return 'ABC123';
			case 'access_type' :
				return 'FIDELBOX_REDIRECT';
			case 'use_ssl' :
				return true;
			case 'port' :
				return '12345';
		}
		return 'UNKNOWN';
	}

	public function testAppConfig2() {
		$this->mockApi->expects($this->any())->method('getAppValue')->will(
				$this->returnCallback(array (
						'\OCA\FidelApp\PageControllerTest',
						'returnFidelboxNotDefined'
				)));

		$expectedResult = array (
				'menu' => 'appconfig',
				'actionTemplate' => 'appconfig',
				'useSSL' => 'UNKNOWN',
				'port' => 'UNKNOWN',
				'domain' => 'UNKNOWN',
				'fixedIp' => 'UNKNOWN',
				'accessType' => 'UNKNOWN',
				'fidelboxConfig' => 'appconfig_confirm_tos',
		);
		// Run the test
		$result = $this->cutPageController->appConfig();

		$this->assertAttributeEquals($expectedResult, 'params', $result);
	}

	public function returnFidelboxNotDefined($argument) {
		switch ($argument) {
			case 'fidelbox_account' :
				return null;
		}
		return 'UNKNOWN';
	}

	public function testAppConfig3() {
		$this->mockApi->expects($this->any())->method('getAppValue')->will(
				$this->returnCallback(array (
						'\OCA\FidelApp\PageControllerTest',
						'returnFidelboxDefined'
				)));

		// Run the test
		$result = $this->cutPageController->appConfigDisplayCaptcha();
		$this->assertAttributeContains('appconfig_fidelbox_createaccount', 'params', $result);
	}

	public function testAppConfig4() {
		$this->mockApi->expects($this->any())->method('getAppValue')->will(
				$this->returnCallback(array (
						'\OCA\FidelApp\PageControllerTest',
						'returnFidelboxDefined'
				)));
		$this->mockFidelboxConfig->expects($this->once())->method('createAccount')->will($this->returnValue('XYZ'));
		// Run the test
		$result = $this->cutPageController->appConfigCreateFidelboxAccount();
		$this->assertAttributeContains('XYZ', 'params', $result);
	}

	static function main() {
		$suite = new \PHPUnit_Framework_TestSuite(__CLASS__);
		\PHPUnit_TextUI_TestRunner::run($suite);
	}
}

if (! defined('PHPUnit_MAIN_METHOD')) {
	PageControllerTest::main();
}