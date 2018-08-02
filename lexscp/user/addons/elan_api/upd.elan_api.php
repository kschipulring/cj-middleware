<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine ELAN API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Third Party Modules
 * @category	Modules Update File
 * @author		BIW
 */
class Elan_api_upd {

	var $version = '1.1';
	var $module_name = 'Elan_api';
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	function __construct()
	{
		ee()->load->dbforge();
	}

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */
	function install()
	{
		//Install Module
		$moduledata = array(
			'module_name' => $this->module_name,
			'module_version' => $this->version,
			'has_cp_backend' => 'n',
			'has_publish_fields' => 'n'
		);

		ee()->db->insert('modules', $moduledata);

		// Install to 1.1
		$this->create_actions();
		
		// Install to 1.2		

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall()
	{
		//Uninstall Module
		ee()->db->where('class', $this->module_name);
		ee()->db->delete('actions');

		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => $this->module_name));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', $this->module_name);
		ee()->db->delete('modules');

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */
	function update($current = '')
	{		
		if (version_compare($current, $this->version, '='))
		{
			// up to date
			return FALSE;
		}

		// Update to 1.1
		if (version_compare($current, '1.1', '<'))
		{
			$this->create_actions();
		}

		// Update to 1.2
		if (version_compare($current, '1.2', '<'))
		{
			
		}

		// update version number
		return TRUE;
	}
	
	function create_actions()
	{
		$actiondata = array(
			'class' => $this->module_name,
			'method' => 'elan_api_request',
			'csrf_exempt' => '1'
		);

		ee()->db->insert('actions', $actiondata);
	}
}
// END CLASS

/* End of file upd.elan_api.php */
/* Location: ./lexscp/user/addons/elan_api/upd.elan_api.php */