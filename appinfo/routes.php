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

use \OCA\AppFramework\App as AppFrameworkApp;
use \OCA\FidelApp\DependencyInjection\DIContainer;

$this->create('fidelapp_create_dropdown', '/dropdown')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'createDropdown', $params, new DIContainer());
		});

$this->create('fidelapp_index', '/')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'fidelApp', $params, new DIContainer());
		});
/*
$this->create('fidelapp_appconfig_access', '/appConfigAccess')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigAccess', $params, new DIContainer());
		});
*/

$this->create('fidelapp_appconfig_fixed_ip', '/appConfigFixedIp')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigFixedIp', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_domain_name', '/appConfigDomainName')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigDomainName', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig', '/appConfig')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfig', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_display_captcha', '/appConfigDisplayCaptcha')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigDisplayCaptcha', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_create_fidelbox_account', '/appConfigCreateFidelboxAccount')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigCreateFidelboxAccount', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_redirect', '/appConfigRedirect')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigRedirect', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_ssl', '/appConfigSsl')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigSsl', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_port', '/appConfigPort')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigPort', $params, new DIContainer());
		});

$this->create('fidelapp_appconfig_delete_fidelbox_account', '/appConfigDeleteFidelboxAccount')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'appConfigDeleteFidelboxAccount', $params, new DIContainer());
		});

$this->create('fidelapp_shares', '/shares')->action(
		function ($params) {
			$params['view'] = 'activeshares';
			AppFrameworkApp::main('PageController', 'shares', $params, new DIContainer());
		});

$this->create('fidelapp_receipts', '/receipts')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'receipts', $params, new DIContainer());
		});

$this->create('fidelapp_passwords', '/passwords')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'passwords', $params, new DIContainer());
		});

$this->create('fidelapp_get_file', '/download')->action(
		function ($params) {
			AppFrameworkApp::main('PublicController', 'getFileList', $params, new DIContainer());
		});

$this->create('fidelapp_authenticate_contact', '/authenticate/{clientId}')->action(
		function ($params) {
			AppFrameworkApp::main('PublicController', 'authenticateContact', $params, new DIContainer());
		});

$this->create('pingback', '/pingback')->action(
		function ($params) {
			AppFrameworkApp::main('PublicController', 'pingback', $params, new DIContainer());
		});

$this->create('fidelapp_filelist_for_applet', '/getfilesforclient')->action(
		function ($params) {
			AppFrameworkApp::main('AppletAccessController', 'getFilesForClient', $params, new DIContainer());
		});

$this->create('fidelapp_applet_request_getchunk', '/getchunk')->action(
		function ($params) {
			AppFrameworkApp::main('AppletAccessController', 'getChunk', $params, new DIContainer());
		});

$this->create('fidelapp_applet_request_getkey', '/getkey')->action(
		function ($params) {
			AppFrameworkApp::main('AppletAccessController', 'getKey', $params, new DIContainer());
		});

$this->create('fidelapp_applet_request_confirm', '/confirm')->action(
		function ($params) {
			AppFrameworkApp::main('AppletAccessController', 'confirm', $params, new DIContainer());
		});




