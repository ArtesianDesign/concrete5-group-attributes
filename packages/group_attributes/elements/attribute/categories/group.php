<?php defined('C5_EXECUTE') or die(_('Access Denied.'));
if (is_object($key)) {
	$gakProfileDisplay       = $key->isAttributeKeyDisplayedOnProfile();
	$gakMemberListDisplay    = $key->isAttributeKeyDisplayedOnMemberList();
	$gakProfileEdit          = $key->isAttributeKeyEditableOnProfile();
	$gakIsActive             = $key->isAttributeKeyActive();
}
?>
<?php  $form = Loader::helper('form'); ?>
<fieldset class="form-horizontal">

	<legend><?php echo t('Group Attribute Options'); ?></legend>

	<div class="control-group">
		<label class="control-label"><?php echo t('Public Display'); ?></label>
		<div class="controls">
			<ul class="inputs-list">
				<li>
					<label>
						<?php echo $form->checkbox('gakProfileDisplay', 1, (boolean) $gakProfileDisplay); ?>
						<span><?php echo t('Displayed in Public Profile.');?></span>
					</label>
				</li>
				<li>
					<label>
						<?php echo $form->checkbox('gakMemberListDisplay', 1, (boolean) $gakMemberListDisplay); ?>
						<span><?php echo t('Displayed on Member List.');?></span>
					</label>
				</li>
			</ul>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label"><?php echo t('Edit Mode'); ?></label>
		<div class="controls">
			<ul class="inputs-list">
				<li>
					<label>
						<?php echo $form->checkbox('gakProfileEdit', 1, (boolean) $gakProfileEdit); ?>
						<span><?php echo t('Editable in Profile.');?></span>
					</label>
				</li>
			</ul>
		</div>
	</div>

</fieldset>