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


namespace OCA\FidelApp\DependencyInjection;

use \OCA\AppFramework\DependencyInjection\DIContainer as BaseContainer;
use \OCA\FidelApp\Controller\PageController;
use \OCA\FidelApp\Controller\PublicController;
use \OCA\FidelApp\API;
use \OCA\FidelApp\TwigMiddleware;
use OCA\FidelApp\Controller\AppletAccessController;
use OCA\FidelApp\EncryptionHelper;
use OCA\FidelApp\FidelboxConfig;
use OCA\FidelApp\App;
use OCA\FidelApp\ContactManager;
use OCA\FidelApp\Db\ContactShareItemMapper;
use OCA\FidelApp\Db\ReceiptItemMapper;

class DIContainer extends BaseContainer {

	public function __construct() {
		parent::__construct(FIDELAPP_APPNAME);

		// use this to specify the template directory
		$this ['TwigTemplateDirectory'] = __DIR__ . '/../templates';

		$this ['App'] = $this->share(function ($c) {
			return new App();
		});

		$this ['API'] = $this->share(function ($c) {
			return new API();
		});

		$this ['ContactManager'] = $this->share(function ($c) {
			return new ContactManager($c ['API']);
		});

		$this ['ContactShareItemMapper'] = $this->share(function ($c) {
			return new ContactShareItemMapper($c ['API']);
		});

		$this ['ReceiptItemMapper'] = $this->share(function ($c) {
			return new ReceiptItemMapper($c ['API']);
		});
		$this ['EncryptionHelper'] = $this->share(function ($c) {
			return new EncryptionHelper();
		});

		$this ['FidelboxConfig'] = $this->share(function ($c) {
			return new FidelboxConfig($c ['API']);
		});

		$this ['PageController'] = $this->share(function ($c) {
			return new PageController($c, $c ['Request']);
		});

		$this ['PublicController'] = $this->share(function ($c) {
			return new PublicController($c ['API'], $c ['Request']);
		});

		$this ['AppletAccessController'] = $this->share(
				function ($c) {
					return new AppletAccessController($c ['API'], $c ['Request']);
				});

		$this ['TwigMiddleware'] = $this->share(function ($c) {
			return new TwigMiddleware($c ['API'], $c ['Twig']);
		});
	}
}