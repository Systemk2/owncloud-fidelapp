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

require_once (__DIR__ . "/../classloader.php");

class PageControllerTest extends ControllerTestUtility {

	// Fixture
	protected $testApp;
	protected $testContainer;
	protected $testApi;

	// Class under Test
	protected $testPageController;

	protected function setUp() {
		$this->testApp = $this->getMock('OCA\FidelApp\AppInfo\App', array('checkPrerequisites'));
		$this->testApi = $this->getAPIMock('OCA\FidelApp\API', array());
		$this->testApi->expects($this->any())->method('registerFidelappException')->will($this->returnValue(null));

		$this->testContainer = new DIContainer();
		$this->testContainer ['App'] = $this->testApp;
		$this->testContainer ['API'] = $this->testApi;

		$this->testPageController = $this->getMock('OCA\FidelApp\Controller\PageController',
				array (
						'shares',
						'wizard',
				), array (
						$this->testContainer,
						new Request()
				));
	}

	public function testRouteFidelApp1() {
		// Simulate "no errors, no warnings"
		$this->testApp->expects($this->once())->method('checkPrerequisites')->will(
				$this->returnValue(array (
						'errors' => array (),
						'warnings' => array ()
				)));

		// Simulate that the app has already been configured
		$this->testApi->expects($this->once())->method('getAppValue')->with('access_type')->will($this->returnValue(true));
		$this->testPageController->expects($this->once())->method('shares');

		// Run the test
		$this->testPageController->fidelApp();
	}

	public function testRouteFidelApp2() {
		// Simulate "no errors, no warnings"
		$this->testApp->expects($this->once())->method('checkPrerequisites')->will(
				$this->returnValue(array (
						'errors' => array (),
						'warnings' => array ()
				)));

		// Simulate that the app has not yet been configured
		$this->testApi->expects($this->once())->method('getAppValue')->with('access_type')->will($this->returnValue(null));
		$this->testPageController->expects($this->once())->method('wizard');

		// Run the test
		$this->testPageController->fidelApp();
	}

	public function testRouteFidelApp3() {
		// Simulate "there are warnings"
		$returnValue = array (
						'warnings' => array ('message' => 'testdummy')
				);
		$this->testApp->expects($this->once())->method('checkPrerequisites')->will(
				$this->returnValue($returnValue));

		$this->testPageController->expects($this->never())->method('shares');
		$this->testPageController->expects($this->never())->method('wizard');

		// Run the test
		$result = $this->testPageController->fidelApp();
		$this->assertInstanceof('\OCA\AppFramework\Http\TemplateResponse', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);
		$this->assertAttributeEquals('fidelapp', 'templateName', $result);
		$this->assertAttributeEquals($returnValue, 'params', $result);
	}
}