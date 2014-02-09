<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
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

// require_once __DIR__ . '/../../appframework/3rdparty/SimplePie/autoloader.php';

// to execute without owncloud, we need to create our own classloader
spl_autoload_register(
		function ($className) {

			$relPath = false;

			if (strpos($className, 'OCA\\') === 0) {
				$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
				$relPath = __DIR__ . '/../..' . $path;
				if (!file_exists($relPath)) {
					// If not found in the root of the app directory, insert '/lib' after app id and try again
					$parts = split(strtolower(FIDELAPP_APPNAME), $relPath);
					if(count($parts) == 3) {
						$relPath = $parts[0] . strtolower(FIDELAPP_APPNAME) . '/lib' . $parts[2];
					}
				}
			}

			if ($relPath) {
				if (file_exists($relPath)) {
					require_once $relPath;
				} else {
					die("FATAL: Class $className could not be loaded, because file does not exist: $relPath\n");
				}
			}
		});
