<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

$akAllowMultipleValues = (!isset($akAllowMultipleValues)) ? 1 : $akAllowMultipleValues;
$akShowDefaultGroups = (!isset($akShowDefaultGroups)) ? 0 : $akShowDefaultGroups;
?>
<fieldset class="form-horizontal">
	<legend><?php echo t('Display Options')?></legend>

	<div class="control-group">
		<label class="control-label"></label>
		<div class="controls">
			<ul class="inputs-list">
				<li>
					<label class="checkbox">
						<?php echo $form->checkbox('akAllowMultipleValues', 1, $akAllowMultipleValues); ?>
						<?php echo t('Allow multiple groups to be chosen'); ?>
					</label>
				</li>
				<li>
					<label class="checkbox">
						<?php echo $form->checkbox('akShowDefaultGroups', 1, $akShowDefaultGroups); ?>
						<?php echo t('Show system groups'); ?>
						<small class="help-inline muted">
							<?php echo t('This will allow the selection of the Guest and Registered Users groups'); ?>
						</small>
					</label>
				</li>
			</ul>
		</div>
	</div>

</fieldset>