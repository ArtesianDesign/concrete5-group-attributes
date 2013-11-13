<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

Loader::model('attribute/categories/group', 'group_attributes');
class GroupList extends DatabaseItemList {

	protected $attributeFilters = array();
	protected $sortByString = 'gName asc';
	protected $itemsPerPage = 10;
	protected $ignoreUserPermissions = false;
	protected $omitRequiredGroups = false;
	static $attributeClass = 'GroupAttributeKey';
	public $objectKey = 'gID';

	/* magic method for filtering by attributes. */
	public function __call($nm, $a) {
		if (substr($nm, 0, 8) == 'filterBy') {
			$attrib = Loader::helper('text')->uncamelcase(substr($nm, 8));
			if (count($a) == 2) {
				$this->filterByAttribute($attrib, $a[0], $a[1]);
			} else {
				$this->filterByAttribute($attrib, $a[0]);
			}
		}
	}

	/**
	 * The arguments here are for the legacy GroupList object, which we will use to immediately filter against the UserGroups table
	 * via the filterByUser() method
	 */
	function __construct($ui = false, $omitRequiredGroups = false, $ignoreUserPermissions = false) {
		if ($ui && $ui instanceof UserInfo) {
			$this->filterByUser($ui);
		}
		$this->objectKey = Group::getObjectKey();
		$this->searchTable = GroupAttributeKey::getIndexedSearchTable();
		$this->includeAllGroups($omitRequiredGroups);
		$this->ignoreUserPermissions($ignoreUserPermissions);
		$this->setBaseQuery();
		$this->sortBy($this->objectKey, "ASC");
	}

	public function includeAllGroups($include) {
		$this->omitRequiredGroups = $include;
	}

	public function ignoreUserPermissions($ignore) {
		$this->ignoreUserPermissions = $ignore;
	}

	protected function setBaseQuery() {
		$this->setQuery("SELECT g.{$this->objectKey} FROM Groups g ");
	}

	public function filterByUser($ui) {
		if ($ui instanceof UserInfo || $ui instanceof User) {
			$this->ui = $ui;
			$this->setupUserFilters();
			$this->filter(false, "ug.uID = " . $ui->getUserID());
		}
	}

	protected function setupUserFilters() {
		$this->addToQuery("LEFT JOIN UserGroups ug ON (ug.{$this->objectKey} = g.{$this->objectKey})");
	}

	public function filterByKeywords($keywords) {
		$db = Loader::db();
		$keywordsExact = $db->quote($keywords);
		$quoted_keywords = $db->quote("%{$keywords}%");
		$keys = GroupAttributeKey::getSearchableIndexedList();
		$attribute_search_string = '';
		foreach ($keys as $attribute_key) {
			$controller = $attribute_key->getController();
			$attribute_search_string .= " OR " . $controller->searchKeywords($keywords);
		}
		$this->filter(false, "(gName LIKE " . $quoted_keywords . " OR gDescription LIKE " . $quoted_keywords . $attribute_search_string . ")");
	}

	public function filterByAllowedPermission($pk) {
		$assignment = $pk->getMyAssignment();
		$permission = $assignment->getGroupsAllowedPermission();
		$gIDs = array('-1');
		if ($permission == 'C') {
			$gIDs = array_merge($assignment->getGroupsAllowedArray(), $gIDs);
			$this->filter($this->objectKey, $gIDs, 'in');
		}
	}

	public function get($itemsToGet = 100, $offset = 0) {
		$groups = array();
		$this->createQuery();
		$rows = parent::get($itemsToGet, intval($offset));
		foreach($rows as $row) {
			$group = Group::getByID($row[$this->objectKey]);
			if ($this->ui && $this->ui instanceof UserInfo) {
				$group->setPermissionsForObject($this->ui);
			}
			$groups[] = $group;
		}
		return $groups;
	}

	public function getTotal(){
		$this->createQuery();
		return parent::getTotal();
	}

	protected function createQuery(){
		if(!$this->queryCreated) {
			if ($this->omitRequiredGroups) {
				$this->filterByNonRequiredGroups();
			}
			if (!$this->user_info) {
				$this->setupAttributeFilters("LEFT JOIN {$this->searchTable} ON ({$this->searchTable}.{$this->objectKey} = g.{$this->objectKey})");
			}
			$this->queryCreated = 1;
		}
	}

	public function filterByNonRequiredGroups() {
		$this->filter(false, "g.{$this->objectKey} > " . ADMIN_GROUP_ID);
	}

	public function filterByIsExpirationEnabled($value) {
		$this->filter('gUserExpirationIsEnabled', intval($value), '=');
	}

	function getGroupList() {
		return $this->get(0);
	}

}

class Group extends Concrete5_Model_Group {

	protected $valuesTable = 'GroupAttributeValues';
	protected $searchTable = 'GroupSearchIndexAttributes';

	function __construct() {
		$this->objectKey = $this->getObjectKey();
	}

	public function setAttribute($ak, $value) {
		if (!is_object($ak)) {
			$ak = GroupAttributeKey::getByHandle($ak);
		}
		$ak->setAttribute($this, $value);
		$this->reindex();
	}

	public function getObjectKey() {
		return 'gID';
	}

	public function reindex() {
		$attribs = GroupAttributeKey::getAttributes($this->getGroupID(), 'getSearchIndexValue');
		$db = Loader::db();

		$db->Execute("DELETE FROM {$this->searchTable} WHERE {$this->objectKey} = ?", array($this->getGroupID()));
		$searchableAttributes = array($this->objectKey => $this->getGroupID());
		$rs = $db->Execute("SELECT * FROM {$this->searchTable} WHERE {$this->objectKey} = -1");
		AttributeKey::reindex($this->searchTable, $searchableAttributes, $attribs, $rs);
	}

	public function getAttribute($ak, $displayMode = false) {
		if (!is_object($ak)) {
			$ak = GroupAttributeKey::getByHandle($ak);
		}
		if (is_object($ak)) {
			$av = $this->getAttributeValueObject($ak);
			if (is_object($av)) {
				return $av->getValue($displayMode);
			}
		}
	}

	public function getAttributeField($ak) {
		if (!is_object($ak)) {
			$ak = GroupAttributeKey::getByHandle($ak);
		}
		$value = $this->getAttributeValueObject($ak);
		$ak->render('form', $value);
	}

	public function getAttributeValueObject($ak, $createIfNotFound = false) {
		$db = Loader::db();
		$av = false;
		$values = array($this->getGroupID(), $ak->getAttributeKeyID());
		$query = "SELECT avID FROM {$this->valuesTable} WHERE {$this->objectKey} = ? AND akID = ?";
		$avID = $db->GetOne($query, $values);
		if ($avID > 0) {
			$av = GroupAttributeValue::getByID($avID);
			if (is_object($av)) {
				$av->setGroup($this);
				$av->setAttributeKey($ak);
			}
		}

		if ($createIfNotFound) {
			$count = 0;

			// Is this avID in use ?
			if (is_object($av)) {
				$count = $db->GetOne("SELECT count(avID) FROM {$this->valuesTable} WHERE avID = ?", $av->getAttributeValueID());
			}

			if ((!is_object($av)) || ($count > 1)) {
				$av = $ak->addAttributeValue();
			}
		}

		return $av;
	}

	public function clearAttribute($ak) {
		$db = Loader::db();
		if (!is_object($ak)) {
			$ak = GroupAttributeKey::getByHandle($ak);
		}
		$cav = $this->getAttributeValueObject($ak);
		if (is_object($cav)) {
			$cav->delete();
		}
		$this->reindex();
	}

	public function delete() {

		parent::delete();

		$db = Loader::db();
		$r = $db->Execute("SELECT avID, akID FROM {$this->valuesTable} WHERE {$this->objectKey} = ?", array($this->{$this->objectKey}));
		while ($row = $r->FetchRow()) {
			$gak = GroupAttributeKey::getByID($row['akID']);
			$av = $this->getAttributeValueObject($gak);
			if (is_object($av)) {
				$av->delete();
			}
		}

		$r = $db->query("DELETE FROM {$this->searchTable} WHERE {$this->objectKey} = ?", array(intval($this->{$this->objectKey})));
	}

}
