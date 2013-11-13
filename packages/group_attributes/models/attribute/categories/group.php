<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

/**
 * Contains the file attribute key and value objects.
 * @package Pages
 * @author Andrew Embler <andrew@concrete5.org>
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
/**
 * An object that represents metadata added to files.
 * @author Andrew Embler <andrew@concrete5.org>
 * @package Pages
 * @category Concrete
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 *
 */
class GroupAttributeKey extends AttributeKey {

	public function getIndexedSearchTable() {
		return 'GroupSearchIndexAttributes';
	}

	protected $searchIndexFieldDefinition = 'gID I(11) UNSIGNED NOTNULL DEFAULT 0 PRIMARY';

	/**
	 * Returns an attribute value list of attributes and values (duh) which a collection version can store
	 * against its object.
	 * @return AttributeValueList
	 */
	public function getAttributes($gID, $method = 'getValue') {
		$db = Loader::db();
		$values = $db->GetAll('SELECT akID, avID FROM GroupAttributeValues WHERE gID = ?', array($gID));
		$avl = new AttributeValueList();
		foreach($values as $val) {
			$ak = GroupAttributeKey::getByID($val['akID']);
			if (is_object($ak)) {
				$value = $ak->getAttributeValue($val['avID'], $method);
				$avl->addAttributeValue($ak, $value);
			}
		}
		return $avl;
	}

	public function getAttributeValue($avID, $method = 'getValue') {
		$av = GroupAttributeValue::getByID($avID);
		if (is_object($av)) {
			$av->setAttributeKey($this);
			return $av->{$method}();
		}
	}

	public static function getByID($akID) {
		$ak = new GroupAttributeKey();
		$ak->load($akID);
		if ($ak->getAttributeKeyID() > 0) {
			return $ak;
		}
	}

	public static function getByHandle($akHandle) {
		$db = Loader::db();
		$query = "
			SELECT
				ak.akID
			FROM
				AttributeKeys ak
			INNER JOIN
				AttributeKeyCategories akc ON ak.akCategoryID = akc.akCategoryID
			WHERE 
				ak.akHandle = ?
					AND
				akc.akCategoryHandle = ?";
		$values = array($akHandle, 'group');
		$akID = $db->GetOne($query, $values);
		if ($akID > 0) {
			$ak = GroupAttributeKey::getByID($akID);
			return $ak;
		} else {
			 // else we check to see if it's listed in the initial registry
			 $ia = FileTypeList::getImporterAttribute($akHandle);
			 if (is_object($ia)) {
				// we create this attribute and return it.
				$at = AttributeType::getByHandle($ia->akType);
				$args = array(
					'akHandle' => $akHandle,
					'akName' => $ia->akName,
					'akIsSearchable' => 1,
					'akIsAutoCreated' => 1,
					'akIsEditable' => $ia->akIsEditable
				);
				return GroupAttributeKey::add($at, $args);
			 }
		}
	}

	public function sortListByDisplayOrder($a, $b) {
		if ($a->getAttributeKeyDisplayOrder() == $b->getAttributeKeyDisplayOrder()) {
			return 0;
		} else {
			return ($a->getAttributeKeyDisplayOrder() < $b->getAttributeKeyDisplayOrder()) ? -1 : 1;
		}
	}

	public static function getList() {
		$list = parent::getList('group');
		usort($list, array('GroupAttributeKey', 'sortListByDisplayOrder'));
		return $list;
	}

	public static function getSearchableList() {
		return parent::getList('group', array('akIsSearchable' => 1));
	}

	public static function getSearchableIndexedList() {
		return parent::getList('group', array('akIsSearchableIndexed' => 1));
	}

	public function load($akID) {
		parent::load($akID);
		$db = Loader::db();
		$query = "
			SELECT 
				gakProfileDisplay, gakMemberListDisplay, gakProfileEdit, displayOrder, gakIsActive
			FROM 
				GroupAttributeKeys 
			WHERE 
				akID = ?";
		$row = $db->GetRow($query, $akID);
		$this->setPropertiesFromArray($row);
	}

	public function get($akID) {
		return GroupAttributeKey::getByID($akID);
	}

	protected function saveAttribute($group, $value = false) {
		// We check a gID/cvID/akID combo, and if that particular combination has an attribute value ID that
		// is NOT in use anywhere else on the same gID, cvID, akID combo, we use it (so we reuse IDs)
		// otherwise generate new IDs
		$av = $group->getAttributeValueObject($this, true);
		parent::saveAttribute($av, $value);
		$db = Loader::db();
		$db->Replace('GroupAttributeValues', array(
			'gID'  => $group->getGroupID(),
			'akID' => $this->getAttributeKeyID(),
			'avID' => $av->getAttributeValueID()
		), array('gID', 'akID'));
		unset($av);
		unset($group);
	}

	public function add($at, $args, $pkg = false) {
		$ak = parent::add('group', $at, $args, $pkg);

		$args = self::sanitizeKeyOptions($args);

		$db = Loader::db();
		$query = "
			SELECT
				max(displayOrder)
			FROM
				GroupAttributeKeys";
		$displayOrder = $db->GetOne($query);
		$args['displayOrder'] = ($displayOrder) ? $displayOrder : 0;
		$args['displayOrder']++;

		self::saveKeyOptions($ak, $args);

		$nak = new GroupAttributeKey();
		$nak->load($ak->getAttributeKeyID());
		return $nak;
	}

	public function update($args) {
		$ak = parent::update($args);
		$args = self::sanitizeKeyOptions($args);
		self::saveKeyOptions($ak, $args, 'UPDATE');
	}

	protected function saveKeyOptions($ak, $args, $mode = 'INSERT') {
		$args['akID'] = $ak->getAttributeKeyID();
		$where = ($mode == 'UPDATE') ? 'akID = ' . $args['akID'] : false;
		$db = Loader::db();
		return $db->AutoExecute('GroupAttributeKeys', $args, $mode, $where);
	}

	protected function sanitizeKeyOptions($args) {
		$settings = array();
		$options = array('gakProfileDisplay', 'gakMemberListDisplay', 'gakProfileEdit');
		foreach ($options as $option) {
			$settings[$option] = ($args[$option] == 1) ? $args[$option] : 0;
		}
		$settings['gakIsActive'] = (isset($args['uakIsActive']) && (!$args['uakIsActive'])) ? 0 : 1;
		return $settings;
	}


	public static function getColumnHeaderList() {
		return parent::getList('group', array('akIsColumnHeader' => 1));
	}

	public function delete() {
		parent::delete();
		$db = Loader::db();
		$db->Execute('DELETE FROM GroupAttributeKeys WHERE akID = ?', array($this->getAttributeKeyID()));
		$r = $db->Execute('SELECT avID from GroupAttributeValues WHERE akID = ?', array($this->getAttributeKeyID()));
		while ($row = $r->FetchRow()) {
			$db->Execute('DELETE FROM AttributeValues WHERE avID = ?', array($row['avID']));
		}
		$db->Execute('DELETE FROM GroupAttributeValues WHERE akID = ?', array($this->getAttributeKeyID()));
	}

	public function getAttributeKeyDisplayOrder() {
		return $this->displayOrder;
	}

	function updateAttributesDisplayOrder($keys_array) {
		$db = Loader::db();
		for ($index = 0; $index < count($keys_array); $index++) {
			$akID = $keys_array[$index];
			$group_attribute_key = GroupAttributeKey::getByID($akID);
			$group_attribute_key->refreshCache();
			$values = array($index, $akID);
			$db->query('UPDATE GroupAttributeKeys SET displayOrder = ? WHERE akID = ?', $values);
		}
	}

	public function activate() {
		$db = Loader::db();
		$this->refreshCache();
		$db->Execute('UPDATE GroupAttributeKeys SET gakIsActive = 1 WHERE akID = ?', array($this->akID));
	}

	public function deactivate() {
		$db = Loader::db();
		$this->refreshCache();
		$db->Execute('UPDATE GroupAttributeKeys SET gakIsActive = 0 WHERE akID = ?', array($this->akID));
	}

	public static function getPublicProfileList() {
		$tattribs = self::getList();
		$attribs = array();
		foreach($tattribs as $uak) {
			if ((!$uak->isAttributeKeyDisplayedOnProfile()) || (!$uak->isAttributeKeyActive())) {
				continue;
			}
			$attribs[] = $uak;
		}
		unset($tattribs);
		return $attribs;
	}

	public static function getMemberListList() {
		$tattribs = self::getList();
		$attribs = array();
		foreach($tattribs as $uak) {
			if ((!$uak->isAttributeKeyDisplayedOnMemberList()) || (!$uak->isAttributeKeyActive())) {
				continue;
			}
			$attribs[] = $uak;
		}
		unset($tattribs);
		return $attribs;
	}

	public static function getEditableInProfileList() {
		$tattribs = self::getList();
		$attribs = array();
		foreach($tattribs as $uak) {
			if ((!$uak->isAttributeKeyEditableOnProfile()) || (!$uak->isAttributeKeyActive())) {
				continue;
			}
			$attribs[] = $uak;
		}
		unset($tattribs);
		return $attribs;
	}



	public function isAttributeKeyDisplayedOnProfile() {
		return $this->gakProfileDisplay;
	}

	public function isAttributeKeyEditableOnProfile() {
		return $this->gakProfileEdit;
	}

	public function isAttributeKeyDisplayedOnMemberList() {
		return $this->gakMemberListDisplay;
	}

	public function isAttributeKeyActive() {
		return $this->gakIsActive;
	}

}

class GroupAttributeValue extends AttributeValue {

	public function setGroup($group) {
		$this->group = $group;
	}

	public static function getByID($avID) {
		$gav = new GroupAttributeValue();
		$gav->load($avID);
		if ($gav->getAttributeValueID() == $avID) {
			return $gav;
		}
	}

	public function delete() {
		$db = Loader::db();
		$db->Execute("DELETE FROM GroupAttributeValues WHERE gID = ? AND akID = ? AND avID = ?", array(
			$this->group->getGroupID(),
			$this->attributeKey->getAttributeKeyID(),
			$this->getAttributeValueID()
		));

		// Before we run delete() on the parent object, we make sure that attribute value isn't being referenced in the table anywhere else
		$num = $db->GetOne("SELECT count(avID) FROM GroupAttributeValues WHERE avID = ?", array($this->getAttributeValueID()));
		if ($num < 1) {
			parent::delete();
		}
	}
}