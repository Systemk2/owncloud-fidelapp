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

// to execute without owncloud, we need our own classloader
spl_autoload_register(
		function ($className) {

			$relPath = false;

			if (strpos($className, 'OCA\\') === 0) {
				$path = strtolower(str_replace('\\', '/', substr($className, 3)) . '.php');
				// Do not use __DIR__, because it resolves symlinks
				if(strpos($_SERVER['argv'][1], DIRECTORY_SEPARATOR) == 0) {
					// an absolute path to the tests folder was given as an argument, so use this one
					$root = dirname($_SERVER['argv'][1]);
				} else {
					$root = dirname($_SERVER['PWD'] . DIRECTORY_SEPARATOR . $_SERVER['argv'][1]);
				}
				//die($root);
				$relPath = dirname($root) . $path;
				if (!file_exists($relPath)) {
					// If not found in the root of the app directory, insert '/lib' after app id and try again
					$parts = split(strtolower(FIDELAPP_APPNAME), $relPath);
					if(count($parts) == 2) {
						$relPath = $parts[0] . strtolower(FIDELAPP_APPNAME) . '/lib' . $parts[1];
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
