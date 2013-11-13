<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

Loader::model('attribute/categories/group', 'group_attributes');
class DashboardUsersGroupsEditController extends DashboardBaseController {

	public $helpers = array('html', 'text', 'form', 'form/date_time', 'validation/token', 'concrete/dashboard', 'concrete/interface');

	public function on_start(){
		$u = new User();
		$this->error = Loader::helper('validation/error');
		$this->set('u', $u);
		$this->set('uo', UserInfo::getByID($u->getUserID()));
		$this->set('attribs', GroupAttributeKey::getList(true));
	}

	public function view($gID = null) {
		if (!$gID) {
			$this->redirect('dashboard/users/groups');
		}
		$group = Group::getByID(intval($gID));
		if ($group instanceof Group) {
			$this->set('gID', $gID);
			$this->set('group', $group);
		} else {
			$this->error->add('Group not found.');
		}
	}

	public function edit_attribute() {
		$group = Group::getByID($_POST['gID']);
		$u = new User();

		$akID = $_REQUEST['gakID'];
		$ak = GroupAttributeKey::get($akID);

		if ($_POST['task'] == 'update_extended_attribute') {
			$ak->saveAttributeForm($group);
			$val = $group->getAttributeValueObject($ak);
			print $val->getValue('displaySanitized','display');
			exit;
		}

		if ($_POST['task'] == 'clear_extended_attribute') {
			$group->clearAttribute($ak);
			$val = $group->getAttributeValueObject($ak);
			print '<div class="ccm-attribute-field-none">' . t('None') . '</div>';
			exit;
		}
	}
}