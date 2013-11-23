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

$this->create('fidelapp_get_file_list', '/getfilelist')->action(
		function ($params) {
			App::main('PublicController', 'getFileList', $params, new DIContainer());
		});

