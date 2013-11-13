<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

$tp = new TaskPermission();
if (!$tp->canAccessUserSearch()) { die(t('You have no access to users.')); }

Loader::model('attribute/categories/group', 'group_attributes');
$keys_array = (array) $_REQUEST['akID'];
GroupAttributeKey::updateAttributesDisplayOrder($keys_array);