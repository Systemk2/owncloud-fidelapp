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

use OCA\FidelApp\Db\ContactShareItem;
use OCA\FidelApp\Db\ContactItem;
use OCA\FidelApp\Db\ShareItem;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();


try {
	$api = new API();

	$userId = $api->getUserId();
	$email = htmlspecialchars_decode($_POST ['shareWith']);
	$fileId = $_POST ['itemSource'];
	$file = $_POST ['file'];


	$contactItem = new ContactItem();
	$contactItem->setUserId($userId);
	$contactItem->setEmail($email);
	$contactsappId = $api->findContactsappIdByEmail($email);
	if ($contactsappId) {
		$contactItem->setContactsappId($contactsappId);
	}

	$shareHelper = new ShareHelper($api);
	$shareHelper->share($fileId, $contactItem);

	\OC_JSON::success();
} catch(\Exception $e) {
	\OC_JSON::error($e->getMessage());
}

