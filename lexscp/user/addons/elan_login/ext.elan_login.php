<?php

class elan_login_ext {

    var $name       = 'Elan Login (BIW)';
    var $version        = '1.0';
    var $description    = 'Member Login with ELAN system';
    var $settings_exist = 'n';
    var $docs_url       = ''; // 'https://ellislab.com/expressionengine/user-guide/';

    var $settings       = array();

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    public function __construct()
	{
        ee()->lang->loadfile('elan_login');
		//ee() =& get_instance();
        //settings = $settings;
    }

/**
 * Activate Extension
 *
 * This function enters the extension into the exp_extensions table
 *
 * @see https://ellislab.com/codeigniter/user-guide/database/index.html for
 * more information on the db class.
 *
 * @return void
 */
public function activate_extension()
	{
        $extensiondata = array(
            'class'     => __CLASS__,
            'method'    => 'elan_logout',
            'hook'      => 'member_member_logout',
            'settings'  => serialize(settings),
            'priority'  => 10,
            'version'   => version,
            'enabled'   => 'y'
        );
        ee()->db->insert('extensions', $extensiondata);
		
		$extensiondata = array(
            'class'     => __CLASS__,
            'method'    => 'elan_login',
            'hook'      => 'member_member_login_start',
            'settings'  => serialize(settings),
            'priority'  => 10,
            'version'   => version,
            'enabled'   => 'y'
        );
        ee()->db->insert('extensions', $extensiondata);
		
		$extensiondata = array(
            'class'     => __CLASS__,
            'method'    => 'elan_change_password',
            'hook'      => 'member_process_reset_password',
            'settings'  => serialize(settings),
            'priority'  => 10,
            'version'   => version,
            'enabled'   => 'y'
        );
        ee()->db->insert('extensions', $extensiondata);
    }

/**
 * Update Extension
 *
 * This function performs any necessary db updates when the extension
 * page is visited
 *
 * @return  mixed   void on update / false if none
 */
function update_extension($current = '')
{
    if ($current == '' OR $current == $this->version)
    {
        return FALSE;
    }

    if ($current < '1.0')
    {
        // Update to version 1.0
    }

    ee()->db->where('class', __CLASS__);
    ee()->db->update(
                'extensions',
                array('version' => $this->version)
    );
}
	/**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    public function disable_extension() {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');        
    }

	/**
	 * Extension Hook core_template_route
	 */
	public function elan_login()
	{
		$username = ee()->input->post('username');
		$password = ee()->input->post('password');
		$language_id = ee()->input->post('language_id');
		$language_abbr = ee()->input->post('language_abbr');
		
		$action = ee()->db->get_where('actions', array('class' => 'Elan_api', 'method' => 'elan_api_request' ));
        $action_id = $action->row('action_id');

		$url = ee()->config->item('site_url');
		$data = array('ACT' => $action_id, 'type' => 'login', 'username' => $username, 'password' => $password);

		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($curl);
		curl_close($curl);
		$json = json_decode($response, true);
		if($json['STATUS'] == 1 && isset($json['DATA'])){
			$user_id = $json['DATA']['USER_ID'];			
			$changePassword = $json['DATA']['FORCE_PASSWORD_CHANGE'];
			$language_id = $json['DATA']['LANGUAGE_ID'];
			//print("<br /> <br />");			
			
			ee()->input->set_cookie('elan_user_id', $user_id , 28800); // Set a cookie that expires in 8 hours 
			ee()->input->set_cookie('elan_username', $username , 28800); // Set a cookie that expires in 8 hours

			$member = ee()->db->get_where('members', array('username' => 'User'));
			$sess = new Auth_result($member->row());

			$sess->anon(FALSE);
			$sess->start_session();

			require_once(PATH_THIRD . 'elan_api/libraries/Elan_api_lib.php');
			$elan_api_lib = new Elan_api_lib();
			$allowed_languages = $elan_api_lib->get_allowed_languages();
			if( array_search($language_id, array_column($allowed_languages, 'id')) != false ) {
				$language_abbr = $allowed_languages[array_search($language_id, array_column($allowed_languages, 'id'))]['abbreviation'];
			} else {
				$language_id = 1;
				$language_abbr = 'en';
			}
			
			if( $changePassword == 1 ){
				//Set cookies for change password page.
				ee()->input->set_cookie('language_id', $language_id , 3600); // Set a cookie that expires in 8 hours
				ee()->input->set_cookie('language_abbr', $language_abbr , 3600); // Set a cookie that expires in 8 hours
				
				$current_url = ee()->functions->fetch_site_index();
				ee()->functions->redirect($current_url . 'member/edit_userpass');
			}
			else {				
				require_once(PATH_THIRD . 'transcribe/mod.transcribe.php');
				$transcribe = new Transcribe(); 
				$results = $transcribe->_set_current_language($language_abbr);
				$current_url = ee()->functions->fetch_site_index();
				ee()->functions->redirect($current_url);
			}
			exit();
		}
		else
		{
			ee()->input->set_cookie('elan_user_id', 1 , 28800); // Set a cookie that expires in 8 hours
			ee()->input->set_cookie('elan_username', 'superadmin' , 28800); // Set a cookie that 8 hours
		}
	}
	
	/**
	 * Extension Hook member_member_logout
	 */
    public function elan_logout()
	{
		ee()->input->delete_cookie('elan_user_id');
		ee()->input->delete_cookie('elan_username');
    }
	
	/**
	 * Extension Hook member_process_reset_password
	 */
    public function elan_change_password($data)
	{
		$user_id = ee()->input->cookie('elan_user_id');
		$password = ee()->input->post('password');
		$language_id = ee()->input->post('language_id');
		$language_abbr = ee()->input->post('language_abbr');
		$old_csrf = ee()->input->post('csrf_token');
 
 		require_once(PATH_THIRD . 'elan_api/libraries/Elan_api_lib.php');
		$obj = new Elan_api_lib();       
		if( !empty($password) ) {
				
			$force_password_change = 0;
			$obj->update_user_record($user_id, $force_password_change, $password, TRUE);
		}
		
		$allowed_languages = $obj->get_allowed_languages();
		if( array_search($language_id, array_column($allowed_languages, 'id')) != false ) {
			$language_abbr = $allowed_languages[array_search($language_id, array_column($allowed_languages, 'id'))]['abbreviation'];
		} else {
			$language_id = 1;
			$language_abbr = 'en';
		}

		//Send api call to change default language in Elan
		$elan = $obj->update_user_language_id($user_id, $language_id, true);

		require_once(PATH_THIRD . 'transcribe/mod.transcribe.php');
		$transcribe = new Transcribe(); 
		$results = $transcribe->_set_current_language($language_abbr);

		$current_url = ee()->functions->fetch_site_index();
		ee()->functions->redirect($current_url);
    }
}
// END CLASS

?>