<?php defined('C5_EXECUTE') or die('Access Denied.');

class GroupsAttributeTypeController extends Concrete5_Controller_AttributeType_Default {

	protected $searchIndexFieldDefinition = 'X2 NULL';
	protected $atTable = 'atGroups';
	protected $atSettings = 'atGroupsSettings';
	protected $helpers = array('form', 'html');

	public function getValue() {
		$this->load();
		$db = Loader::db();
		$data = $db->getOne("SELECT value FROM {$this->atTable} WHERE avID = ?", array($this->getAttributeValueID()));
		return Loader::helper('json')->decode($data);
	}

	public function getSearchIndexValue() {
		$value = $this->getValue();
		return ',' . implode(',', $value) . ',';
	}

	public function getGroupValue() {
		$data = $this->getValue();
		$groups = array();
		foreach ($data as $gID) {
			$group = Group::getByID($gID);
			if ($group instanceof Group) {
				$groups[$gID] = $group;
			}
		}
		// this will provide a cleaner output for the returned groups if we only have one
		// if (!$this->akAllowMultipleValues && count($groups) == 1) {
		// 	return array_shift($groups);
		// } else {
			return $groups;
		//}
	}

	public function getDisplayValue() {
		$groups = $this->getGroupValue();
		if (count($groups) > 1) {
			$group_names = array();
			foreach ($groups as $group) {
				var_dump($group);
				$group_names[$group->getGroupID()] = $group->getGroupName();
			}
			return implode('<br/>', $group_names);
		} else {
			return $groups->getGroupName();
		}
	}

	protected function load(){
		$ak = $this->getAttributeKey();
		if (!is_object($ak)) {
			return false;
		}
		$db = Loader::db();
		$row = $db->GetRow("SELECT * FROM {$this->atSettings} WHERE akID = ?", $ak->getAttributeKeyID());
		foreach($row as $property => $value) {
			$this->{$property} = $value;
		}
		$this->set('akAllowMultipleValues', $this->akAllowMultipleValues);
		$this->set('akShowDefaultGroups', $this->akShowDefaultGroups);
	}

	public function form() {
		$this->load();
		$this->set('value', $this->getValue());
		$this->set('name', $this->field('value'));
		$this->set('all_groups', $this->getGroupsArray());
	}

	public function type_form(){
		$this->load();
	}

	public function search() {
		$this->load();
		$data = $this->getSearchData();
		$this->setFromArray($data);
	}

	private function setFromArray($data) {
		foreach ($data as $var => $value) {
			$this->set($var, $value);
		}
	}

	public function getSearchData(){
		$data = array();
		$data['akID'] = $this->getAttributeKey()->getAttributeKeyID();
		$data['all_groups'] = $this->getGroupsArray();
		$data['type'] = $this->request('type');
		$selected_groups = $this->request('groups');
		if (!is_array($selected_groups)) {
			$selected_groups = array();
		}
		$data['selected_groups'] = $selected_groups;
		return $data;
	}

	public static function getSearchString($arg, $add_quotes = false) {
		$id = ($arg instanceof Group) ? $arg->getGroupID() : intval($arg);
		$string = '%,' . $id . ',%';
		return ($add_quotes) ? "'" . $string . "'" : $string;
	}

	public function searchForm($itemList, $groups = null) {
		$groups = (is_null($groups)) ? $this->request('groups') : $groups;
		if (is_array($groups)) {
			$akHandle = $this->attributeKey->getAttributeKeyHandle();

			// if we've selected NONE in the array, we just filter by NO group attribution
			if (in_array('none', $groups)) {
				$itemList->filter(false, "(ak_{$akHandle} IS NULL)");

			// otherwise just iterate the array like normal
			} else {
				switch($this->request('type')) {
					case('ANY'):
						$filter = array();
						foreach ($groups as $gID) {
							$filter[] = 'ak_' . $akHandle . ' LIKE ' . $this->getSearchString($gID, true);
						}
						$itemList->filter(false, '(' . implode(' OR ', $filter) . ')');
						break;
					case('ALL'):
					default:
						foreach ($groups as $gID) {
							$itemList->filterByAttribute($akHandle,	$this->getSearchString($gID), 'LIKE');
						}
				}
			}
		}
		return $itemList;
	}

	public function deleteKey() {
		$db = Loader::db();
		$db->Execute("DELETE FROM {$this->atSettings} WHERE akID = ?", array($this->getAttributeKey()->getAttributeKeyID()));

		$arr = $this->attributeKey->getAttributeValueIDList();
		foreach($arr as $id) {
			$db->Execute("DELETE FROM {$this->atTable} WHERE avID = ?", array($id));
		}
	}

	public function duplicateKey($newAK) {
		$this->load();
		$db = Loader::db();
		$db->Execute("INSERT INTO {$this->atSettings} (akID, akAllowMultipleValues, akShowDefaultGroups) VALUES (?, ?)", array($newAK->getAttributeKeyID(), $this->akAllowMultipleValues, $this->akShowDefaultGroups));
	}

	public function saveKey($data) {
		$ak = $this->getAttributeKey();
		$db = Loader::db();

		$akAllowMultipleValues = (isset($data['akAllowMultipleValues'])) ? $data['akAllowMultipleValues'] : 0;
		$akShowDefaultGroups = (isset($data['akShowDefaultGroups'])) ? $data['akShowDefaultGroups'] : 0;

		$db->Replace($this->atSettings, array(
			'akID'                  => $ak->getAttributeKeyID(),
			'akAllowMultipleValues' => $akAllowMultipleValues,
			'akShowDefaultGroups'   => $akShowDefaultGroups
		), array('akID'), true);
	}

	public function saveValue($data = null) {
		$db = Loader::db();
		$group_ids = (array) $data;
		array_walk_recursive($group_ids, create_function('&$a', '$a = intval($a);'));
		$db->Replace($this->atTable, array(
			'avID'  => $this->getAttributeValueID(),
			'value' => Loader::helper('json')->encode($group_ids)
		), 'avID', true);
	}

	public function saveForm($data) {
		$this->saveValue($data['value']);
	}

	public function getGroupsArray() {
		$groups = $this->getGroups();
		$groups_array = array();
		foreach ($groups as $group) {
			$groups_array[$group->getGroupID()] = $group->getGroupName();
		}
		return $groups_array;
	}

	public function getGroups(){
		$group_list = new GroupList();
		if (!$this->akShowDefaultGroups) {
			$group_list->filterByNonRequiredGroups();
		}
		return $group_list->get();
	}

}