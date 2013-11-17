<?php defined('C5_EXECUTE') or die(_('Access Denied.'));

/***************************************************************************************************
 *     ______                          ___   __  __       _ __          __
 *    / ____/________  __  ______     /   | / /_/ /______(_) /_  __  __/ /____  _____
 *   / / __/ ___/ __ \/ / / / __ \   / /| |/ __/ __/ ___/ / __ \/ / / / __/ _ \/ ___/
 *  / /_/ / /  / /_/ / /_/ / /_/ /  / ___ / /_/ /_/ /  / / /_/ / /_/ / /_/  __(__  )
 *  \____/_/   \____/\__,_/ .___/  /_/  |_\__/\__/_/  /_/_.___/\__,_/\__/\___/____/
 *                       /_/
 * -----------------------------------------------------------------------------------------------
 *
 * Class GroupAttributesPackage
 * Package controller
 *
 * @package Group Attributes
 * @version 0.2.1.8
 * @author Andrew Householder <andrew@artesiandesigninc.com>
 */

class GroupAttributesPackage extends Package {

	/**
	 * Concrete5 required properties & methods
	 */
	protected $pkgHandle          = 'group_attributes';
	protected $appVersionRequired = '5.6';
	protected $pkgVersion         = '0.2.1.8';

	public function getPackageDescription() {
		return t('This extends the attribute system to include User Groups. Also included is a fully-featured GroupList model for searching Groups against attributes.');
	}

	public function getPackageName() {
		return t('Group Attributes');
	}

	/**
	 * Array of attribute type handles taht will be associated with our new category
	 */
	public $types_for_category = array(
		'text',
		'textarea',
		'number',
		'boolean',
		'rating',
		'select',
		'image_file',
		'address',
		'date_time'
	);

	/**
	 * Array of attribute key categories to associate our attribute types to
	 */
	public $categories_for_type = array(
		'file',
		'collection'
	);

	/**
	 * on_start method runs at the start of the app to extend the group object
	 *
	 * @return [void]
	 */
	public function on_start() {

		// Override the Group model to allow us to add attributes and create a fully-featured GroupList
		$environment = Environment::get();
		$environment->overrideCoreByPackage('models/groups.php', $this);

		// Load assets on the groups page only.
		$request = Request::get();
		$groups_edit_path = '/dashboard/users/groups';
		if (!strncmp($request->getRequestCollectionPath(), $groups_edit_path, strlen($groups_edit_path))) {
			$html = Loader::helper('html');
			$view = View::getInstance();
			$view->addFooterItem($html->javascript('groups_proxy.js', $this->pkgHandle));
		}

	}

	/**
	 * Package Install
	 *
	 * @return [void]
	 */
	public function install($options = null) {
		parent::install();
		$this->_configure();
	}

	/**
	 * Package Upgrade method
	 *
	 * @return [void]
	 */
	public function upgrade() {
		$this->_configure();
		$this->_refreshDB();
		parent::upgrade();
	}

	/**
	 * Package Uninstall
	 *
	 * @return [void]
	 */
	public function uninstall() {
		// for proper cleanup, we need to run a few things before uninstall
		$this->_removeSets();
		parent::uninstall();
		$this->_cleanup();
	}

	/**
	 * Our configure method that runs on install/upgrade to support our custom installations
	 *
	 * @return [void]
	 */
	private function _configure() {
		$this->_installAttributeCategory(); // attribute category
		$this->_installAttributeTypes(); // attribute types
		$this->_verifySinglePages(); // dashboard page
	}

	/**
	 * Run on package uninstall to cleanup any lingering data
	 *
	 * @return [void]
	 */
	private function _cleanup() {
		$this->_revertSinglePages(); // set the attributes title back
		$this->_removeDB(); // remove custom DB
	}

	/**
	 * Cleanup our attribute sets on uninstall
	 * 
	 * @return [void]
	 */
	private function _removeSets(){
		$sets = AttributeKeyCategory::getByHandle('group')->getAttributeSets();
		foreach ($sets as $set) {
			$set->delete();
		}
	}

	/**
	 * Install our attribute category model, as well as associating proper types
	 * 
	 * @return [void]
	 */
	private function _installAttributeCategory() {

		$group_akc = AttributeKeyCategory::getByHandle('group');
		if (!is_object($group_akc)) {
			$pkg = Package::getByHandle($this->pkgHandle);
			$group_akc = AttributeKeyCategory::add('group', AttributeKeyCategory::ASET_ALLOW_SINGLE, $pkg);
		}

		foreach ($this->types_for_category as $attribute_type) {
			$at = AttributeType::getByHandle($attribute_type);
			if (is_object($at) && !$this->_hasAttributeTypeAssociation($group_akc, $at)) {
				$group_akc->associateAttributeKeyType($at);
			}
		}

	}

	/**
	 * Install our attribute types and associate them to the proper categories
	 * 
	 * @return [void]
	 */
	public function _installAttributeTypes() {

		$groups_at = AttributeType::getByHandle('groups');
		if (!is_object($groups_at) || !intval($groups_at->getAttributeTypeID())) {
			$pkg = Package::getByHandle($this->pkgHandle);
			$groups_at = AttributeType::add('groups', t('Groups'), $pkg);
		}

		foreach ($this->categories_for_type as $category) {
			$akc = AttributeKeyCategory::getByHandle($category);
			if (!$this->_hasAttributeTypeAssociation($akc, $groups_at)) {
				$akc->associateAttributeKeyType($groups_at);
			}
		}

	}

	/**
	 * Verifies installation of our dashboard single pages
	 *
	 * @return [void]
	 */
	private function _verifySinglePages() {

		// Our own dashboard configuration page
		$pkg = Package::getByHandle($this->pkgHandle);

		// attribute creation
		$create_path = '/dashboard/users/group_attributes';
		$create_page = Page::getByPath($create_path);
		if (!$create_page instanceof Page || $create_page->isError()) {
			$create_page = SinglePage::add($create_path, $pkg);
		}
		$create_page->update(array(
			'cName'        => t('Group Attributes'),
			'cDescription' => t('Add and Edit Attributes for User Groups'))
		);
		$create_page->setAttribute('icon_dashboard', 'icon-cog');

		// attribute creation
		$edit_path = '/dashboard/users/groups/edit';
		$edit_page = Page::getByPath($edit_path);
		if (!$edit_page instanceof Page || $edit_page->isError()) {
			$edit_page = SinglePage::add($edit_path, $pkg);
		}
		$edit_page->update(array(
			'cName'        => t('Edit Group Attributes'),
			'cDescription' => t('Manage attribute values on Groups'))
		);
		$edit_page->setAttribute('icon_dashboard', 'icon-list');

		// Update the user attributes title for clarity's sake
		$user_attributes = Page::getByPath('/dashboard/users/attributes');
		if ($user_attributes instanceof Page && !$user_attributes->isError()) {
			$user_attributes->update(array(
				'cName' => t('User Attributes')
			));
		}
	}

	/**
	 * Verifies installation of our dashboard single pages
	 *
	 * @return [void]
	 */
	private function _revertSinglePages() {
		// Set the user attributes page title back since we do not need to differentiate anymore
		$user_attributes = Page::getByPath('/dashboard/users/attributes');
		if ($user_attributes instanceof Page && !$user_attributes->isError()) {
			$user_attributes->update(array(
				'cName' => t('Attributes')
			));
		}
	}

	/**
	 * Installs our DB XML
	 * @return [void]
	 */
	private function _installDB() {
		$dbxml = $this->getPackagePath() . '/db.xml';
		if (file_exists($dbxml)) {
			Package::getByHandle($this->pkgHandle)->installDB($dbxml);
		}
	}

	/**
	 * Refreshes DB.xml for our attribute type
	 * @return [void]
	 */
	private function _refreshDB() {
		$group_at = AttributeType::getByHandle('group');
		if (!is_object($group_at) || !intval($group_at->getAttributeTypeID())) {
			$group_at_db_path = $group_at->getAttributeTypeFilePath(FILENAME_ATTRIBUTE_DB);
			Package::installDB($group_at_db_path);
		}
	}

	/**
	 * Manually remove DB tables
	 * @return [void]
	 */
	private function _removeDB() {
		$db = Loader::db();
		$sql = "DROP TABLE IF EXISTS GroupAttributeKeys, GroupAttributeValues, GroupSearchIndexAttributes";
		$db->Execute($sql);
	}

	/**
	 * There's currently no check before associating an attribute type to a category, so it throws a SQL error
	 * I created a pull request for this, but presuming that never makes it in, we can still check.
	 * @param  [type] $attribute_category [description]
	 * @param  [type] $attribute_type     [description]
	 * @return [type]                     [description]
	 */
	public function _hasAttributeTypeAssociation($attribute_category, $attribute_type) {
		if (is_object($attribute_category) && is_object($attribute_type)) {
			if (method_exists($attribute_category, 'hasAttributeKeyTypeAssociated')) {
				$result = $attribute_category->hasAttributeKeyTypeAssociated($attribute_type);
			} else {
				$db = Loader::db();
				$query = "
					SELECT 
						atID
					FROM 
						AttributeTypeCategories
					WHERE
						atID = ?
							AND
						akCategoryID = ?";
				$values = array(
					$attribute_type->getAttributeTypeID(),
					$attribute_category->getAttributeKeyCategoryID()
				);
				$result = $db->getOne($query, $values);
			}

			return (boolean) $result;

		}

	}

}