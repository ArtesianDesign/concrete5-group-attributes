<?php defined('C5_EXECUTE') or die(_('Access Denied.')); ?>

<?php $form_action = (isset($form_action)) ? $form_action : 'add'; ?>
<?php if ($this->controller->getTask() == 'select_type' || $this->controller->getTask() == 'add' || $this->controller->getTask() == 'edit'): ?>

	<?php echo $concrete_dashboard->getDashboardPaneHeaderWrapper(t('Edit Attribute'), false, false, false)?>
	<form method="post" action="<?php echo $this->action($form_action)?>" id="ccm-attribute-key-form">
		<?php Loader::element('attribute/type_form_required', array('category' => $category, 'type' => $type, 'key' => $key)); ?>
	</form>

<?php else: ?>

	<?php echo $concrete_dashboard->getDashboardPaneHeaderWrapper(t('Group Attributes'), false, false, false)?>
	<?php Loader::element('dashboard/attributes_table', array('category' => $category, 'attribs'=> GroupAttributeKey::getList(), 'editURL' => $view_path)); ?>
	<div class="ccm-pane-body ccm-pane-body-footer" style="margin-top: -25px">
		<form method="get" class="form-stacked inline-form-fix" action="<?php echo $this->action($form_action); ?>" id="ccm-attribute-type-form">
			<div class="clearfix">
				<?php echo $form->label('atID', t('Add Attribute'))?>
				<div class="input">
					<?php echo $form->select('atID', $types)?>
					<?php echo $form->submit('submit', t('Add'))?>
				</div>
			</div>
		</form>
	</div>

<?php endif; ?>
<?php echo $concrete_dashboard->getDashboardPaneFooterWrapper(false);?>

<script type="text/javascript">
$(function() {
	$('div.ccm-attributes-list').sortable({
		handle: 'img.ccm-attribute-icon',
		cursor: 'move',
		opacity: 0.5,
		stop: function() {
			var ualist = $(this).sortable('serialize');
			$.post('<?php echo $group_update_url; ?>', ualist, function(r){});
		}
	});
});

</script>

<style type="text/css">
div.ccm-attributes-list img.ccm-attribute-icon:hover {cursor: move}
</style>