<?php

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

$this->create('fidelapp_wizard', '/wizard')->action(
		function ($params) {
			AppFrameworkApp::main('PageController', 'wizard', $params, new DIContainer());
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




