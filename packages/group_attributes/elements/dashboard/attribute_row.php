<?php defined('C5_EXECUTE') or die(_('Access Denied.'));
/**
 * Attribute Value Row Element
 * @requires $ak, $group, $assignment
 */
$vo = $group->getAttributeValueObject($ak);
$value = '';
if (is_object($vo)) {
	$value = $vo->getValue('displaySanitized', 'display');
}
if ($value == '') {
	$text = '<div class="ccm-attribute-field-none">' . t('None') . '</div>';
} else {
	$text = $value;
}
?>

<?php if ($ak->isAttributeKeyEditable()): ?>

	<?php $type = $ak->getAttributeType(); ?>
	<tr class="ccm-attribute-editable-field">
		<td width="250" style="vertical-align:middle;">
			<a style="font-weight:bold; line-height:18px;" href="javascript:void(0)">
				<?php echo $ak->getAttributeKeyDisplayHandle(); ?>
			</a>
		</td>
		<td class="ccm-attribute-editable-field-central" style="vertical-align:middle;">
			<div class="ccm-attribute-editable-field-text">
				<?php echo $text; ?>
			</div>
			<form method="post" style="margin-bottom:0;" action="<?php echo View::url('/dashboard/users/groups/edit', 'edit_attribute'); ?>">
				<input type="hidden" name="gakID" value="<?php echo $ak->getAttributeKeyID(); ?>" />
				<input type="hidden" name="gID" value="<?php echo $group->getGroupID(); ?>" />
				<input type="hidden" name="task" value="update_extended_attribute" />
				<div class="ccm-attribute-editable-field-form ccm-attribute-editable-field-type-<?php echo strtolower($type->getAttributeTypeHandle()); ?>">
					<?php echo $ak->render('form', $vo, true); ?>
				</div>
			</form>
		</td>
		<td class="ccm-attribute-editable-field-save" style="vertical-align:middle; text-align:center;" width="30">
			<a href="javascript:void(0)">
				<img src="<?php echo ASSETS_URL_IMAGES; ?>/icons/edit_small.png" width="16" height="16" class="ccm-attribute-editable-field-save-button" />
			</a>
			<a href="javascript:void(0)">
				<img src="<?php echo ASSETS_URL_IMAGES; ?>/icons/close.png" width="16" height="16" class="ccm-attribute-editable-field-clear-button" />
			</a>
			<img src="<?php echo ASSETS_URL_IMAGES; ?>/throbber_white_16.gif" width="16" height="16" class="ccm-attribute-editable-field-loading" />
		</td>
	</tr>

<?php else: ?>
	<tr>
		<th width="250"><?php echo $ak->getAttributeKeyDisplayHandle(); ?></th>
		<td class="ccm-attribute-editable-field-central" colspan="2"><?php echo $text; ?></td>
	</tr>
<?php endif; ?>