<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

if (!PermissionKey::getByHandle('assign_user_groups')->validate($uo)) {
	throw new Exception(t('Access Denied.'));
} ?>

<?php echo $concrete_dashboard->getDashboardPaneHeaderWrapper(t('Edit Group'), t('Edit Group settings.')); ?>

<?php if ($error->has()): ?>
	<?php $error->output(); ?>
<?php else: ?>

	<script>
	function editAttrVal (attId, cancel) {
		$('#attUnknownWrap'+attId).toggle(cancel);
		$('#attEditWrap'+attId).toggle(!cancel);
		$('#attValChanged'+attId).val((!cancel) ? attId : 0);
	}
	</script>

<form method="post"  class="form-horizontal" id="ccm-group-form" action="<?php echo $this->url('/dashboard/users/groups/update_group'); ?>">

   <?php echo $validation_token->output('update_group_' . $gID); ?>
   <?php echo $form->hidden('gID', $gID); ?>

	<fieldset>

		<div class="control-group">
			<?php echo $form->label('gName', t('Name')); ?>
			<div class="controls">
				<input type="text" name="gName" class="span6" value="<?php echo $text->entities($group->getGroupName()); ?>" />
			</div>
		</div>

		<div class="control-group">
			<?php echo $form->label('gDescription', t('Description')); ?>
			<div class="controls">
				<textarea name="gDescription" rows="6" class="span6"><?php echo $text->entities($group->getGroupDescription()); ?></textarea>
			</div>
		</div>

	</fieldset>

	<fieldset>

		<legend><?php echo t("Group Expiration Options"); ?></legend>

		<div class="control-group">
			<div class="controls">
				<label class="checkbox">
					<?php echo $form->checkbox('gUserExpirationIsEnabled', 1, $group->isGroupExpirationEnabled()); ?>
					<span><?php echo t('Automatically remove users from this group'); ?></span>
				</label>
			</div>
			<div class="controls" style="padding-left: 18px">
			<?php
			echo $form->select('gUserExpirationMethod', array(
				'SET_TIME' => t('at a specific date and time'),
				'INTERVAL' => t('once a certain amount of time has passed')
			), $group->getGroupExpirationMethod(), array('disabled' => true)); ?>
			</div>
		</div>

		<div id="gUserExpirationSetTimeOptions" style="display: none">
			<div class="control-group">
				<?php echo $form->label('gUserExpirationSetDateTime', t('Expiration Date')); ?>
				<div class="controls">
					<?php echo $form_date_time->datetime('gUserExpirationSetDateTime', $group->getGroupExpirationDateTime()); ?>
				</div>
			</div>
		</div>

		<div id="gUserExpirationIntervalOptions" style="display: none">
			<div class="control-group">
				<label><?php echo t('Accounts expire after'); ?></label>
				<div class="controls">
					<table class="table table-condensed" style="width: auto">
						<tr>
							<?php
							$days    = $group->getGroupExpirationIntervalDays();
							$hours   = $group->getGroupExpirationIntervalHours();
							$minutes = $group->getGroupExpirationIntervalMinutes();
							$style   = 'width: 60px';
							?>
							<td valign="top"><strong><?php echo t('Days'); ?></strong><br/>
							<?php echo $form->text('gUserExpirationIntervalDays', $days, array('style' => $style, 'class' => 'span1')); ?>
							</td>
							<td valign="top"><strong><?php echo t('Hours'); ?></strong><br/>
							<?php echo $form->text('gUserExpirationIntervalHours', $hours, array('style' => $style, 'class' => 'span1')); ?>
							</td>
							<td valign="top"><strong><?php echo t('Minutes'); ?></strong><br/>
							<?php echo $form->text('gUserExpirationIntervalMinutes', $minutes, array('style' => $style, 'class' => 'span1')); ?>
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<div id="gUserExpirationAction" style="display: none">
			<div class="control-group">
				<?php echo $form->label('gUserExpirationAction', t('Expiration Action')); ?>
				<div class="controls">
					<?php echo $form->select('gUserExpirationAction', array(
						'REMOVE'            => t('Remove the user from this group'),
						'DEACTIVATE'        => t('Deactivate the user account'),
						'REMOVE_DEACTIVATE' => t('Remove the user from the group and deactivate the account')
					), $group->getGroupExpirationAction()); ?>
				</div>
			</div>
		</div>
	</fieldset>

	<div class="well">
		<?php echo $concrete_interface->button(t('Cancel'), $this->url('/dashboard/users/groups'), 'left'); ?>
		<div class="btn-group pull-right">
			<?php if ($u->isSuperUser()): ?>
			<a href="<?php echo $this->url('/dashboard/users/groups', 'delete', $gID, $validation_token->generate('delete_group_' . $gID)); ?>" data-ccm-confirm="<?php echo t('Are you sure you want to delete this group?'); ?>" class="btn btn-danger group-delete">
				<?php echo t('Delete'); ?>
			</a>
			<?php endif; ?>
			<button type="submit" class="btn btn-primary"><?php echo t('Update'); ?></button>
		</div>
	</div>

</form>

<?php if (count($attribs)): ?>
<fieldset>
	<legend><?php echo t('Group Attributes'); ?></legend>
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="table table-striped">
		<tbody>
			<?php foreach($attribs as $ak): //if ($pk->validate($ak)): ?>
				<?php Loader::packageElement('dashboard/attribute_row', 'group_attributes', array('ak' => $ak, 'group' => $group, 'assignment' => $assignment)); ?>
			<?php /*endif;*/ endforeach; ?>
		</tbody>
	</table>
</fieldset>
<?php endif; ?>

<script type="text/javascript">
ccm_checkGroupExpirationOptions = function() {
	var sel = $("select[name=gUserExpirationMethod]");
	var cb = $("input[name=gUserExpirationIsEnabled]");
	if (cb.prop('checked')) {
		sel.attr('disabled', false);
		switch(sel.val()) {
			case 'SET_TIME':
				$("#gUserExpirationSetTimeOptions").show();
				$("#gUserExpirationIntervalOptions").hide();
				break;
			case 'INTERVAL':
				$("#gUserExpirationSetTimeOptions").hide();
				$("#gUserExpirationIntervalOptions").show();
				break;
		}
		$("#gUserExpirationAction").show();
	} else {
		sel.attr('disabled', true);
		$("#gUserExpirationSetTimeOptions").hide();
		$("#gUserExpirationIntervalOptions").hide();
		$("#gUserExpirationAction").hide();
	}
}

$(function() {
	$("input[name=gUserExpirationIsEnabled]").click(ccm_checkGroupExpirationOptions);
	$("select[name=gUserExpirationMethod]").change(ccm_checkGroupExpirationOptions);
	ccm_checkGroupExpirationOptions();
});
</script>
<script type="text/javascript">

ccm_activateEditableProperties = function() {
	$("tr.ccm-attribute-editable-field").each(function() {
		var trow = $(this);
		$(this).find('a').click(function() {
			trow.find('.ccm-attribute-editable-field-text').hide();
			trow.find('.ccm-attribute-editable-field-clear-button').hide();
			trow.find('.ccm-attribute-editable-field-form').show();
			trow.find('.ccm-attribute-editable-field-save-button').show();
		});

		trow.find('form').submit(function() {
			ccm_submitEditableProperty(trow);
			return false;
		});

		trow.find('.ccm-attribute-editable-field-save-button').parent().click(function() {
			trow.find('form input[name=task]').val('update_extended_attribute');
			ccm_submitEditableProperty(trow);
		});

		trow.find('.ccm-attribute-editable-field-clear-button').parent().unbind();
		trow.find('.ccm-attribute-editable-field-clear-button').parent().click(function() {
			trow.find('form input[name=task]').val('clear_extended_attribute');
			ccm_submitEditableProperty(trow);
			return false;
		});

	});
}

ccm_submitEditableProperty = function(trow) {
	trow.find('.ccm-attribute-editable-field-save-button').hide();
	trow.find('.ccm-attribute-editable-field-clear-button').hide();
	trow.find('.ccm-attribute-editable-field-loading').show();
	try {
		tinyMCE.triggerSave(true, true);
	} catch(e) { }

	trow.find('form').ajaxSubmit(function(resp) {
		// resp is new HTML to display in the div
		trow.find('.ccm-attribute-editable-field-loading').hide();
		trow.find('.ccm-attribute-editable-field-save-button').show();
		trow.find('.ccm-attribute-editable-field-text').html(resp);
		trow.find('.ccm-attribute-editable-field-form').hide();
		trow.find('.ccm-attribute-editable-field-save-button').hide();
		trow.find('.ccm-attribute-editable-field-text').show();
		trow.find('.ccm-attribute-editable-field-clear-button').show();
		trow.find('td').show('highlight', {
			color: '#FFF9BB'
		});

	});
}

$(function() {
	ccm_activateEditableProperties();
	$('#ccm-group-form').on('click', '[data-ccm-confirm]', function (event) {
		if (!confirm($(this).data('ccm-confirm'))) {
			event.preventDefault();
			return false;
		}
	});
});

</script>


<?php endif; ?>
<?php echo $concrete_dashboard->getDashboardPaneFooterWrapper(); ?>
