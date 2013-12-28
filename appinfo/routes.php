<?php

namespace OCA\FidelApp;

use \OCA\AppFramework\App;
use \OCA\FidelApp\DependencyInjection\DIContainer;

$this->create('fidelapp_create_dropdown', '/dropdown')->action(
		function ($params) {
			App::main('PageController', 'createDropdown', $params, new DIContainer());
		});

$this->create('fidelapp_index', '/')->action(
		function ($params) {
			App::main('PageController', 'fidelApp', $params, new DIContainer());
		});

$this->create('fidelapp_wizard', '/wizard')->action(
		function ($params) {
			App::main('PageController', 'wizard', $params, new DIContainer());
		});

$this->create('fidelapp_get_file', '/download')->action(
		function ($params) {
			App::main('PublicController', 'getFileList', $params, new DIContainer());
		});

$this->create('fidelapp_authenticate_contact', '/authenticate/{clientId}')->action(
		function ($params) {
			App::main('PublicController', 'authenticateContact', $params, new DIContainer());
		});

$this->create('fidelapp_filelist_for_applet', '/getfilesforclient')->action(
		function ($params) {
			App::main('AppletAccessController', 'getFilesForClient', $params, new DIContainer());
		});

$this->create('fidelapp_applet_request_getchunk', '/getchunk')->action(
		function ($params) {
			App::main('AppletAccessController', 'getChunk', $params, new DIContainer());
		});

$this->create('fidelapp_applet_request_getkey', '/getkey')->action(
		function ($params) {
			App::main('AppletAccessController', 'getKey', $params, new DIContainer());
		});

$this->create('fidelapp_applet_request_confirm', '/confirm')->action(
		function ($params) {
			App::main('AppletAccessController', 'confirm', $params, new DIContainer());
		});




