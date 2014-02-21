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

use \OCA\FidelApp\API;

/**
 * Response for twig templates.
 * We use our own version, because we want to use the extension .twig instead of .php
 */
class TwigResponse extends \OCA\AppFramework\Http\TemplateResponse {
	private $twig;

	/**
	 * Instantiates the Twig Template
	 * 
	 * @param API $api
	 *        	an api instance
	 * @param string $templateName
	 *        	the name of the twig template
	 * @param
	 *        	Twig_Environment an instance of the twig environment for rendering
	 */
	public function __construct(API $api, $templateName, $twig) {
		parent::__construct($api, $templateName . '.twig');
		$this->twig = $twig;
	}

	/**
	 * Returns the rendered result
	 * 
	 * @return string rendered output
	 */
	public function render() {
		return $this->twig->render($this->templateName, $this->params);
	}
}