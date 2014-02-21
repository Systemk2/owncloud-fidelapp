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

use \OCA\FidelApp\Db\ContactItem;
use \OCA\FidelApp\Db\ContactItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();
try {
	$api = new API();

	$contactId = $_POST ['contactId'];
	$password = trim($_POST ['password']);

	$mapper = new ContactItemMapper($api);

	$contactItem = $mapper->findById($contactId);

	$contactItem->setPassword($password);

	$mapper->save($contactItem);

	\OC_JSON::success(array (
			'contact' => $contactId,
			'password' => $password,
			'action' => 'PASSWORD_CHANGED'
	));
} catch(\Exception $e) {
	\OC_JSON::error(array (
			'message' => $e->getMessage()
	));
}