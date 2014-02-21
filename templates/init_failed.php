<?php
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

