<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

Loader::model('attribute/categories/group', 'group_attributes');

/**
 * Class DashboardUsersGroupAttributesController
 *
 * @package Group Attributes
 * @author Andrew Householder <concrete5@aghouseh.com>
 */

class DashboardUsersGroupAttributesController extends Controller {

	public $helpers = array('form', 'concrete/dashboard');
	private $view_path = '/dashboard/users/group_attributes';

	public function __construct() {
		parent::__construct();
		$otypes = AttributeType::getList('group');
		$types = array();
		foreach($otypes as $at) {
			$types[$at->getAttributeTypeID()] = $at->getAttributeTypeName();
		}
		$this->set('types', $types);
		$this->set('view_path', $this->view_path);
	}

	public function delete($akID, $token = null){
		try {
			$ak = GroupAttributeKey::getByID($akID);

			if(!($ak instanceof GroupAttributeKey)) {
				throw new Exception(t('Invalid attribute ID.'));
			}

			$valt = Loader::helper('validation/token');
			if (!$valt->validate('delete_attribute', $token)) {
				throw new Exception($valt->getErrorMessage());
			}

			$ak->delete();

			$this->redirect($this->view_path, 'attribute_deleted');
		} catch (Exception $e) {
			$this->set('error', $e);
		}
	}

	public function on_start() {
		$this->set('group_update_url', Loader::helper('concrete/urls')->getToolsURL('group_attributes_update', 'group_attributes'));
		$this->set('category', AttributeKeyCategory::getByHandle('group'));
	}

	public function activate($akID, $token = null) {
		try {
			$ak = GroupAttributeKey::getByID($akID);

			if(!($ak instanceof GroupAttributeKey)) {
				throw new Exception(t('Invalid attribute ID.'));
			}

			$valt = Loader::helper('validation/token');
			if (!$valt->validate('attribute_activate', $token)) {
				throw new Exception($valt->getErrorMessage());
			}

			$ak->activate();

			$this->redirect($this->view_path, 'edit', $akID);

		} catch (Exception $e) {
			$this->set('error', $e);
		}
	}

	public function deactivate($akID, $token = null) {
			$ak = GroupAttributeKey::getByID($akID);

			if(!($ak instanceof GroupAttributeKey)) {
				throw new Exception(t('Invalid attribute ID.'));
			}

			$valt = Loader::helper('validation/token');
			if (!$valt->validate('attribute_deactivate', $token)) {
				throw new Exception($valt->getErrorMessage());
			}

			$ak->deactivate();

			$this->redirect($this->view_path, 'edit', $akID);
	}

	public function view() {
		$this->set('form_action', 'select_type');
		$this->set('attribs', GroupAttributeKey::getList());
	}

	public function select_type() {
		$atID = $this->request('atID');
		$at = AttributeType::getByID($atID);
		$this->set('form_action', 'add');
		if(isset($at->atID) && $at->atID > 0) {
			$this->set('type', $at);
		} else {
			throw new Exception(t('Invalid Attribute Type.'));
		}
	}

	public function add() {
		$this->select_type();
		$type = $this->get('type');
		$cnt = $type->getController();
		$e = $cnt->validateKey($this->post());
		if ($e->has()) {
			$this->set('error', $e);
		} else {
			$type = AttributeType::getByID($this->post('atID'));
			$ak = GroupAttributeKey::add($type, $this->post());
			$this->redirect($this->view_path, 'attribute_created');
		}
	}

	public function edit($akID = 0) {
		if ($this->post('akID')) {
			$akID = $this->post('akID');
		}
		$key = GroupAttributeKey::getByID(intval($akID));
		if (!is_object($key) || $key->isAttributeKeyInternal()) {
			$this->redirect($this->view_path);
		}
		$type = $key->getAttributeType();
		$this->set('key', $key);
		$this->set('type', $type);
		$this->set('form_action', 'edit');

		if ($this->isPost()) {
			$cnt = $type->getController();
			$cnt->setAttributeKey($key);
			$e = $cnt->validateKey($this->post());
			if ($e->has()) {
				$this->set('error', $e);
			} else {
				$key->update($this->post());
				$this->redirect($this->view_path, 'attribute_updated');
			}
		}
	}

	public function attribute_deleted() {
		$this->set('form_action', 'select_type');
		$this->set('message', t('Group Attribute Deleted.'));
	}

	public function attribute_created() {
		$this->set('form_action', 'select_type');
		$this->set('message', t('Group Attribute Created.'));
	}

	public function attribute_updated() {
		$this->set('form_action', 'select_type');
		$this->set('message', t('Group Attribute Updated.'));
	}

}
