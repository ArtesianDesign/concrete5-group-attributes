<?php defined('C5_EXECUTE') or die('Access Denied.');
$token = (isset($token)) ? $token : rand(1, 99999);
?>
<div class="group-select">
	<select class="group-select-<?php echo $token; ?>" name="<?php echo $name; ?>[]" <?php if ($akAllowMultipleValues): ?>multiple="multiple"<?php endif; ?> data-placeholder="Choose groups..">
		<?php if (!$akAllowMultipleValues): ?><option value="0"<?php if (!$value): ?> selected="selected"<?php endif; ?>><?php echo t('None'); ?></option><?php endif; ?>
		<?php foreach ($all_groups as $gID => $gName): ?>
		<option value="<?php echo $gID; ?>"<?php if (in_array($gID, $value)): ?> selected="selected"<?php endif; ?>><?php echo trim($gName); ?></option>
		<?php endforeach; ?>
	</select>
</div>

<script>
$(function(){
	$('.group-select-<?php echo $token; ?>').chosen();
});
</script>
