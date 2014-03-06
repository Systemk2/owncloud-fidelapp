<?php
/**
* ownCloud - FidelApp (File Delivery App)
*
* @author Sebastian Kanzow
* @copyright 2014 System k2 GmbH info@systemk2.de
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library. If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCA\FidelApp;

use OCA\FidelApp\Db\ReceiptItemMapper;

\OC_JSON::checkLoggedIn();
\OCP\JSON::callCheck();
\OC_App::loadApps();

try {
	$api = new API();
	$api->registerFidelappException('WrongParameterException');
	$sourceId = $_POST ['sourceId'];
	if(! preg_match('/[0-9]+$/', $sourceId, $idArray)) {
		throw new WrongParameterException('sourceId', $sourceId);
	}
	$receiptItemMapper = new ReceiptItemMapper($api);
	$receiptItem = $receiptItemMapper->findById($idArray[0]);
	$receiptItemMapper->delete($receiptItem);
	\OC_JSON::success();
} catch(\Exception $e) {
	\OC_JSON::error(array('message' => $e->getMessage()));
}