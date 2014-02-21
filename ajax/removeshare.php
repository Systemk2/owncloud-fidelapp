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

use OCA\FidelApp\Db\ShareItemMapper;
use OCA\AppFramework\Db\DoesNotExistException;
use OCA\FidelApp\Db\FileItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();

try {
	$api = new API();

	$shareId = $_POST ['shareId'];

	$mapper = new ShareItemMapper($api);
	$shareItem = $mapper->findById($shareId);
	$fileId = $shareItem->getFileId();
	$mapper->delete($shareItem);

	if (count($mapper->findByFileId($fileId)) == 0) {
		// File is not shared anymore, clean up checksum table too
		$fileMapper = new FileItemMapper($api);
		try {
			$fileItem = $fileMapper->findByFileId($fileId);
			$fileMapper->delete($fileItem);
		} catch(DoesNotExistException $e) {
			// The checksum information has already been deleted? Ignore....
		}
	}
	\OC_JSON::success();
} catch(Exception $e) {
	\OC_JSON::error($e->getMessage());
}

