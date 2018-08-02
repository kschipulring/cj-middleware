<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine ELAN API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Third Party Modules
 * @category	Modules CP File
 * @author		BIW
 */
class Elan_api_mcp {

	public $name = 'Elan_api';
	
	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		// Load it all
		ee()->load->helper('form');
		ee()->load->library('elan_api_lib');
	}

}
// END CLASS

/* End of file mcp.elan_api.php */
/* Location: ./lexscp/user/addons/elan_api/mcp.elan_api.php */