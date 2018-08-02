<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine ELAN API Module
 *
 * @package		ExpressionEngine
 * @subpackage	Third Party Modules
 * @category	Modules File
 * @author		BIW
 */
class Elan_api {

	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{		
		ee()->lang->loadfile('elan_api');
		ee()->load->library('elan_api_lib');
		ee()->load->library('logger');
	}

	/**
	 * Make Elan API request
	 */
	public function elan_api_request()
	{		
		error_reporting(0);
		$request_type = ee()->input->get_post('type', TRUE);
		
		$response = array();
		switch($request_type)
		{
			case 'login':
				$response = ee()->elan_api_lib->user_authentication();
				break;
			
			case 'tagsinfo':
				$response = ee()->elan_api_lib->get_tags_info();
				break;
			
			case 'userinfo':
				$response = ee()->elan_api_lib->get_single_user_info();
				break;

			case 'userinfowithattr':
				$response = ee()->elan_api_lib->get_single_user_info_with_custom_attr();
				break;		
				
			case 'updateuserrecord':
                $response = ee()->elan_api_lib->update_user_record();
                break;			

			case 'updateuserlanguageid':
                $response = ee()->elan_api_lib->update_user_language_id();
                break;

			case 'usersinfo':
				$response = ee()->elan_api_lib->get_multiple_user_info();
				break;

			case 'learninginfo':
				$response = ee()->elan_api_lib->get_multiple_objects_info();
				break;

			case 'usercoursedata':
				$response = ee()->elan_api_lib->get_user_course_data();
				break;
			
			case 'getmonthlyfocus':
				$response = ee()->elan_api_lib->get_monthly_focus();
				break;
			                
            case 'getmonthlyfocusitinerary':
                $response = ee()->elan_api_lib->get_monthly_focus_itinerary();
                break;

			case 'coursedetaildata':
				$response = ee()->elan_api_lib->get_course_detail_data();
				break;

			case 'coursedetaillink':
				$response = ee()->elan_api_lib->get_course_detail_link();
				break;

			case 'getdocumentdetail':
				$response = ee()->elan_api_lib->get_document_detail();
				break;
			
			case 'objectinfo':
				$response = ee()->elan_api_lib->get_object_info();
				break;
			
			case 'userobjectrecords':
				$response = ee()->elan_api_lib->get_user_object_records();
				break;

			case 'updatecreateuserrecord':
				$response = ee()->elan_api_lib->update_create_user_record();
				break;

			case 'createuserobjectrecord':
				$response = ee()->elan_api_lib->create_user_object_record();
				break;
			
			case 'updateuserobjectrecord':
				$response = ee()->elan_api_lib->update_user_object_record();
				break;			

			case 'getcoursevideo':
				$response = ee()->elan_api_lib->get_course_video();
				break;

			case 'getquizquestions':
				$response = ee()->elan_api_lib->get_quiz_questions();
				break;

			case 'getquizanswers':
				$response = ee()->elan_api_lib->get_quiz_answers();
				break;
				
			case 'submitquizanswers':
				$response = ee()->elan_api_lib->submit_quiz_answers();
				break;		
			
			case 'launchscorm':	
				$response = ee()->elan_api_lib->launch_scorm();
				break;

			case 'gettranslation':
				$response = ee()->elan_api_lib->get_translation();
				break;
			
			default:
				// Write log file
				$message = 'Invalid '.$request_type.' request!';
				$result = '{"STATUS": "false", "MESSAGE": "'.addslashes($message).'"}';
				$response = json_decode($result, true);
				log_message('error', $message.NBS.NBS.stripslashes('Class:'. __CLASS__ . ' Method: ' . __FUNCTION__));				
		}
		
		ee()->output->send_ajax_response($response);
	}

	/**
	 * Get Elan Objects
	 */
	public function get_elan_objects()
	{
		$output = array();
		$allowed_objects = array();
		
		if ( ee()->input->cookie('elan_user_id') != "" ) {
			$allowed_objects = ee()->elan_api_lib->get_allowed_objects();
		}
		
		$output[] = 'var elanObjects = ' . json_encode($allowed_objects) . ';';
		
		return implode("\n", $output);
	}
	
	/**
	 * Get Elan Languages
	 */
	public function get_elan_languages()
	{
		$output = array();
		$allowed_languages = array();		
		$allowed_languages = ee()->elan_api_lib->get_allowed_languages();		
		$output[] = 'var elanLanguages = ' . json_encode($allowed_languages) . ';';
		
		return implode("\n", $output);
	}
	
	/**
	 * Get Elan Tags
	 */
	public function get_elan_tags()
	{
		$output = array();
		$allowed_tags = array();
		
		if ( ee()->input->cookie('elan_user_id') != "" ) {
			$tags = ee()->elan_api_lib->get_tags_info();
			if( $tags['STATUS'] == 1 )
			{
				foreach($tags['DATA'] as $key => $value){
					$tag_name = $value['TAG_NAME'];
					$tag_key = preg_replace('/[^A-Za-z0-9]/', '', str_replace(' ', '', strtolower($tag_name)));
					$allowed_tags[$tag_key] = $tag_name;
				}
			}
		}
		
		$output[] = 'var elanTags = ' . json_encode($allowed_tags) . ';';
		
		return implode("\n", $output);
	}
	
	public function lex_categories_data()
	{
		$output = array();
		
		ee()->db->select('category_groups.group_id, category_groups.group_name');
		$groups = ee()->db->get('category_groups');
		$groups = $groups->result();
		
		foreach($groups as $key => $group) {
			$group_id = $group->group_id;
			$group_name = $group->group_name;
			$group_url_title = str_replace(' ', '', strtolower($group->group_name));

			$categories = array();
			$query = "SELECT A.cat_id, A.cat_url_title, A.cat_name, 
							IFNULL(B.cat_id, 0) AS parent_id, IFNULL(B.cat_url_title, '') AS parent_url_title, IFNULL(B.cat_name, '') AS parent_name, 
							IFNULL(A.cat_order, 0) AS unlockorder, IFNULL(C.field_id_2, '') AS color, 
							IFNULL(C.field_id_3, '') AS number_of_stops, IFNULL(C.field_id_4, '') AS minutes_to_finish
						FROM exp_categories AS A 
							LEFT JOIN exp_categories AS B ON B.cat_id = A.parent_id
							JOIN exp_category_field_data AS  C ON A.cat_id = C.cat_id
						WHERE A.group_id = " . $group_id . " ORDER BY A.parent_id, A.cat_order";						
			$categoriesQuery = ee()->db->query($query);			
			$categories = $categoriesQuery->result_array();

			$categories[] = array(
				'group_id' => $group_id,
				'group_url_title' => $group_url_title,
				'group_name' => $group_name
			);

			/*
			$categories['cat_id'] = '';
			$categories['cat_url_title'] = '';
			$categories['cat_name'] = '';			
			$categories['parent_id'],
			$categories['parent_url_title'],
			$categories['parent_name'],
			$categories['unlockorder'],
			$categories['color'],
			$categories['number_of_stops'],
			$categories['minutes_to_finish']
			*/
			
			$output[] = 'var '.$group_url_title.' = ' . json_encode($categories) . ';';
		}
		
		return implode("\n", $output);
	}
	
}
// END CLASS

/* End of file mod.elan_api.php */
/* Location: ./lexscp/user/addons/elan_api/mod.elan_api.php */