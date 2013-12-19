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

$this->create('fidelapp_authenticate_contact', '/authenticate')->action(
		function ($params) {
			App::main('PublicController', 'authenticateContact', $params, new DIContainer());
		});

$this->create('fidelapp_filelist_for_applet', '/getfilesforclient/{client_id}')->action(
		function ($params) {
			App::main('AppletAccessController', 'getFilesForClient', $params, new DIContainer());
		});



