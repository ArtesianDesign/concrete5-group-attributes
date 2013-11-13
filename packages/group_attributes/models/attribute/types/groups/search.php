<?php defined('C5_EXECUTE') or die(_('Access Denied.')); ?>
<div class="group-select-search group-select-search-<?php echo $akID; ?>">

	<select class="group-select" name="<?php echo $this->field('groups'); ?>[]" multiple="multiple" data-placeholder="Choose groups..">
		<option value="none"<?php if (in_array('none', $selected_groups)): ?> selected="selected"<?php endif; ?>>(<?php echo t('None'); ?>)</option>
		<?php foreach ($all_groups as $gID => $gName): ?>
		<option value="<?php echo $gID; ?>"<?php if (in_array($gID, $selected_groups)): ?> selected="selected"<?php endif; ?>>
			<?php echo trim($gName); ?>
		</option>
		<?php endforeach; ?>
	</select>

	<div class="group-search-type<?php if (count($selected_groups) > 1): ?> active<?php endif; ?>">
		<label class="radio inline">
			<input type="radio" name="<?php echo $this->field('type'); ?>" <?php if (!isset($type) || $type == 'ALL'): ?>checked="checked"<?php endif; ?> value="ALL"/>
			<?php echo t('Match all'); ?>
		</label>
		<label class="radio inline">
			<input type="radio" name="<?php echo $this->field('type'); ?>" <?php if ($type == 'ANY'): ?>checked="checked"<?php endif; ?>value="ANY"/>
			<?php echo t('Match any of the above'); ?>
		</label>
	</div>

</div>
<style>
.group-search-type { visibility: hidden; }
.group-search-type.active { visibility: visible; }
</style>
<script>
(function($){

	$(function () {

		var $group_search = $('.group-select-search-<?php echo $akID; ?>');
		$group_search.on('change.group-search', '.group-select', function() {
			var $select = $(this), values = $select.val();
			$group_search.find('.group-search-type').toggleClass('active', (values && values.length > 1));
		});
		$group_search.find('select').chosen();


	});

})(jQuery);
</script>
