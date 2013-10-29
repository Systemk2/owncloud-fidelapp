<?php

namespace OCA\FidelApp;

use \OCA\AppFramework\App;
use \OCA\FidelApp\DependencyInjection\DIContainer;

$this->create('fidelapp_index', '/')->action(
    function($params){
        // call the index method on the class PageController
        App::main('PageController', 'index', $params, new DIContainer());
    }
);

$this->create('fidelapp_create_dropdown', '/dropdown')->action(
		function($params){
	// call the createDropdown method on the class PageController
	App::main('PageController', 'createDropdown', $params, new DIContainer());
}
);
