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

	$checkResult = \OCA\Fidelapp\App::checkPrerequisites();
?>
<div id="fidelapp_errors">
	<h1><?php p($l->t('Your current settings do not meet the basic requirements for the FidelApp')); ?></h1>
	<div class="fidelapp_error_messages">
		<h2><?php p($l->t('The following blocking problems where detected:')); ?></h2>
		<ul>
			<?php foreach ( $checkResult ['errors'] as $error ) { ?>
			<li>
				<?php p($error); ?>
			</li>
			<?php } ?>
		</ul>
	</div>
<?php
if (count($checkResult ['warnings']) > 0) { ?>
	<div class="fidelapp_warning_messages">
		<h2><?php p($l->t('The following problems are not blocking, but they should be addressed in ' .
								 ' order to get the best out of the FidelApp:') ); ?></h2>
		<ul>
			<?php foreach ( $checkResult ['warnings'] as $warning ) { ?>
				<li>
					<?php p($warning); ?>
				</li>
				<?php } ?>
			</ul>
	</div>
<?php }?>
</div>

