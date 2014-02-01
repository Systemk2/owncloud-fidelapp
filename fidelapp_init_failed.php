<?php
// Check if we are a user
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled(FIDELAPP_APPNAME);

OCP\App::setActiveNavigationEntry( FIDELAPP_APPNAME );
OCP\Util::addStyle(FIDELAPP_APPNAME,  'fidelapp_style');

$tmpl = new OCP\Template( FIDELAPP_APPNAME, 'init_failed', 'user' );
$tmpl->printPage();
