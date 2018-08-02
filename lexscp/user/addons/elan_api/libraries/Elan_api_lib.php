<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ExpressionEngine ELAN API Module Library
 *
 * @package		ExpressionEngine
 * @subpackage	Third Party Modules
 * @category	Modules Library File
 * @author		BIW
 */
class Elan_api_lib {
	
	private $default_options = NULL;
	private $allowed_methods = array('login');
	private $allowed_objects = array(
									'course' => array('id' => 1, 'name' => 'Course'),
									'class' => array('id' => 2, 'name' => 'Class'),
									'link' => array('id' => 3, 'name' => 'Link'),
									'survey' => array('id' => 5, 'name' => 'Survey'),
									'quiz' => array('id' => 6, 'name' => 'Quiz'),
									'success_track' => array('id' => 8, 'name' => 'Success Track'),
									'curriculum' => array('id' => 9, 'name' => 'Curriculum'),
									'document' => array('id' => 20, 'name' => 'Document'),
									'scorm' => array('id' => 21, 'name' => 'SCORM')
								);

	private $translation = array();

								
	private $allowed_languages = array(
									array('id' => 1, 'name' => 'English', 'tag' => 'English', 'abbreviation' => 'en')
								);

	//maximum cache age
	protected $maxCacheMinutes = 15;
	protected $currentDateTime;
	protected $datePattern = "Y-m-d H:i:s";
	protected $datePatternDBinsertPattern = "M, d Y H:i:s";
	protected $datePatternDBinsertFormatPattern = "%M, %d %Y %H:%i:%s";

	//mySQL table names for the cache system.  Depends on the $config dbprefix variable. Populated in the construct magic method
	protected $table_user_object_records = "";


	/**
	 * Constructor
	 *
	 * @access	public
	 */
	public function __construct()
	{
		ee()->lang->loadfile('elan_api');
		ee()->load->library('logger');
		
		$this->default_options['debug'] = DEBUG;
		$this->default_options['log_path'] = SYSPATH . 'user/logs/';
		$this->default_options['proxy_host'] = ee()->config->item('biw_proxy_host');
		$this->default_options['proxy_port'] = ee()->config->item('biw_proxy_port');
		$this->default_options['elan_api_url'] = ee()->config->item('elan_api_url');
		$this->default_options['elan_customer_key'] = ee()->config->item('elan_customer_key');
		$this->default_options['elan_company_id'] = ee()->config->item('elan_company_id');
		$this->default_options['call_path_prefix'] = '/rest/' . ee()->config->item('elan_customer_subdomain') . '/';
		//$this->default_options['call_path_prefix'] = '/assets/roast.php?sd=' . ee()->config->item('elan_customer_subdomain') . '&se=';
		
		//if( ee()->session->userdata['member_id'] != '0' )
		//{
		$this->allowed_methods = array('login', 'tags', 'user', 'record', 'object', 'launchscorm', 'form', 'formoptions');
		//}

		//for comparisons
		$this->currentDateTime = gmdate($this->datePatternDBinsertPattern);

		//minutes to seconds
		$this->seconds_to_cache = $this->maxCacheMinutes * 60;

		//primarily for browser cache death time
		$this->maxCacheDateTimeForward = gmdate($this->datePattern, time() + $this->seconds_to_cache);

		//also for comparisons, but for the earlier part of the comparison
		//$this->maxCacheDateTimeBackward = gmdate($this->datePattern, time() - $this->seconds_to_cache);
		$this->maxCacheDateTimeBackward = gmdate($this->datePatternDBinsertPattern, time() - $this->seconds_to_cache);

		//database table prefix
		$this->dbp = ee()->config->item('database')["expressionengine"]["dbprefix"];

		//special database tables used by this plugin
		//$this->table_user_object_records = $this->dbp . "temp_monthly_focus";
		$this->table_user_info = $this->dbp . "temp_user_info";
		$this->table_user_object_records = $this->dbp . "temp_user_object_records";
		$this->table_multiple_objects_info = $this->dbp . "temp_multiple_objects_info";
		$this->table_group_info = $this->dbp . "temp_group_info";
		$this->table_tags = $this->dbp . "temp_tags";

		//the user id
		$this->user_id = ee()->input->get_post('user_id', true);

		//the group id
		$this->group_id = ee()->input->get_post('group_id', TRUE);

		//damn, lets do this the hard way with mysql, if possible
		if( !is_numeric($this->group_id) ){
			$this->get_set_group_id();
		}

		//echo '$this->group_id = ' . $this->group_id;die();

		//force browser to cache this XHR request
		/*header("Expires: {$this->maxCacheDateTimeForward}");
		header("Pragma: cache");
		header("Cache-Control: max-age={$this->seconds_to_cache}");*/


		
		$query = "SELECT C.field_id_5 AS id, A.cat_name AS name, C.field_id_6 AS tag, A.cat_url_title AS abbreviation, C.field_id_8 AS month_tag, C.field_id_9 AS year_tag
				FROM exp_categories AS A
					JOIN {$this->dbp}category_field_data AS C ON C.group_id = A.group_id AND A.cat_id = C.cat_id
				WHERE A.group_id = 3 AND C.field_id_7 = 'Active'
				ORDER BY A.cat_order";						
		$categoriesQuery = ee()->db->query($query);
		$this->allowed_languages = $categoriesQuery->result_array();
	}

	//used to determine whether an array is a numeric array, or a hash style array with string keys.  0 for only numeric, 1 for with at least one string key in the given array.
	protected function has_string_keys(array $array) {
		return count(array_filter(array_keys($array), 'is_string')) > 0;
	}

	//the group id seems very unreliable in this class.  This is why more brute force is needed to make sure it is populated
	protected function get_set_group_id(){
		if( !is_numeric($this->group_id) ){
			$this->group_id = ee()->input->get_post('group_id', TRUE);
		}

		//if the expressions engine default techniques have still failed to somehow populate the group id, try to call it from the database
		if( !is_numeric($this->group_id) ){
			$temp_uid = is_numeric($this->group_id) ? $this->user_id : 0;

			//ok, lets do this the hard way
			$query = "SELECT group_id AS g FROM `{$this->table_user_info}` WHERE USER_ID = {$temp_uid} LIMIT 0,1";						
			$gidQuery = ee()->db->query($query);

			$this->group_id = count( $gidQuery->result_array() ) > 0 ? $gidQuery->result_array()[0]["g"] : 0;
		}

		//if there is still a failure of some sort, just give it the default group id
		if( $this->group_id < 1 ){
			$this->group_id = 1;
		}

		return $this->group_id;
	}

	private function get_translation_array($language_id = 1)
	{

		// $variable = ee()->input->get_post('variable', TRUE);
		// $language_id = ee()->input->get_post('language_id', TRUE);
		//'$this->dbp' replaces 'exp_' in this query for consistency with rest of application

		$query = "SELECT a.name, c.content from {$this->dbp}transcribe_variables as a 
					left join exp_transcribe_variables_languages as b on a.id = b.variable_id
					left join exp_transcribe_translations as c on b.translation_id = c.id
					where b.language_id = $language_id";

		// return $query;

		$results = ee()->db->query($query);

		return $results->result_array();
	}

	public function get_translation($word = '', $language_id = 1, $use_variables = false)
	{
		if(!$use_variables) {
			$word = ee()->input->get_post('word', TRUE);
			$language_id = ee()->input->get_post('language_id', TRUE);
		}

		if (count($this->translation) == 0) {
			$this->translation = $this->get_translation_array($language_id , true);
		}

		foreach($this->translation as $translation){
			if($translation['name'] == $word) {
				return $translation['content'];
			}
		}

		return '';
	}
	
	/**
	 * Get allowed objects
	 */
	public function get_allowed_objects($key = '')
	{
		$info = $this->allowed_objects;
		
		if( !empty($key) && isset($this->allowed_objects[$key]) )
		{
			$info = $this->allowed_objects[$key];
		}
		
		return $info;
	}
	
	/**
	 * Get allowed languages
	 */
	public function get_allowed_languages($key = '')
	{
		$info = $this->allowed_languages;
		
		if( !empty($key) && isset($this->allowed_languages[$key]) )
		{
			$info = $this->allowed_languages[$key];
		}
		
		return $info;
	}
	
	/**
	 * Get all languages
	 */
	public function get_all_languages()
	{
		$query = "SELECT C.field_id_5 AS id, A.cat_name AS name, C.field_id_6 AS tag, A.cat_url_title AS abbreviation, C.field_id_8 AS month_tag, C.field_id_9 AS year_tag
				FROM {$this->dbp}categories AS A
					JOIN {$this->dbp}category_field_data AS C ON C.group_id = A.group_id AND A.cat_id = C.cat_id
				WHERE A.group_id = 3
				ORDER BY A.cat_order";						
		$categoriesQuery = ee()->db->query($query);
		$all_languages = $categoriesQuery->result_array();
		
		return $all_languages;
	}
	
	/**
	 * API Request
	 */
	public function curl_request($options = array())
	{
		$protocol = 'hmac';		
		$request_method = 'GET';
		$call_method = '';
		$call_path = '';
		$request_url = '';
		$valid_request = false;
		$result = '{}';

		if(isset($options['call_method']) && !empty($options['call_method']))
		{
			$call_method = $options['call_method'];
		}
		
		if( in_array($call_method, $this->allowed_methods) )
		{
			if( isset($options['request_method'])
				&& isset($options['call_path_prefix'])
				&& isset($options['call_path'])
				&& isset($options['elan_api_url'])
				&& isset($options['elan_customer_key'])
				&& isset($options['elan_company_id']) )
			{
				$request_method = strtoupper($options['request_method']);
				$call_path = $options['call_path_prefix'] . $options['call_path'];
				$request_url = $options['elan_api_url'];
				$customer_key = $options['elan_customer_key'];
				$company_id = $options['elan_company_id'];						
				$valid_request = true;
			}			
		}
		
		// API request URL with call path
		$request_url .= $call_path;
		//$request_url .= $call_path . "&type=" . $_GET["type"];
		
		// Replace with logged in username
		$username = '';
		/*
		if( ee()->session->userdata['member_id'] != '0' )
		{
			$username = 'superadmin';
			if( ee()->session->userdata['group_id'] != '1' ) {
				$username = ee()->session->userdata['username'];
			}
		}
		*/
		$username = ee()->input->cookie('elan_username');
		
		if($valid_request)
		{
			// create a new cURL resource
			$ch = curl_init();

			// proxy
			if(isset($options['proxy_host']) && !empty($options['proxy_host']))
			{
				curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
				curl_setopt($ch, CURLOPT_PROXY, $options['proxy_host']);
			}
			if(isset($options['proxy_port']) && !empty($options['proxy_port']))
			{
				curl_setopt($ch, CURLOPT_PROXYPORT, $options['proxy_port']);
			}			

			// debug
			if( isset($options['debug']) && $options['debug'] )
			{
				curl_setopt($ch, CURLOPT_VERBOSE, true);

				// An alternative location to output errors to instead of STDERR.
				$fp1 = fopen($options['log_path'].'curl_'.date('Ymd').'_stderr.log', "a+");
				curl_setopt($ch, CURLOPT_STDERR, $fp1);

				// The file that the header part of the request is written to.
				$fp2 = fopen($options['log_path'].'curl_'.date('Ymd').'_writeheader.log', "a+");
				curl_setopt($ch, CURLOPT_WRITEHEADER, $fp2);
			}
			
			// request parameters
			$parameters = '';			
			if(isset($options['parameters']) && is_array($options['parameters']))
			{
				$parameters = $options['parameters'];
				
				if( empty($username) && isset($parameters['username']) ) {
					$username = $parameters['username'];
				}
			}
			
			if( $request_method == 'POST') //POST
			{
				$fields_string = '';
				if( is_array($parameters) )
				{
					$fields_string = json_encode($parameters);
				}				
				
				if( !empty($fields_string) )
				{
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
				}
			}
			else if( $request_method == 'PUT') //PUT
			{
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');	
				$fields_string = '';
				if( is_array($parameters) )
				{
					$fields_string = json_encode($parameters);
				}				
				
				if( !empty($fields_string) )
				{
					curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
				}				
			}
			else //GET, DELETE
			{
				$fields_string = '';
				if( is_array($parameters) )
				{
					foreach($parameters as $key => $value)
					{ 
						$fields_string .= $key.'='.urlencode($value).'&';
					}
					rtrim($fields_string, '&');
				}
			
				if( !empty($fields_string) )
				{
					$request_url = $request_url . '?' . $fields_string;
					//$request_url = $request_url . '&' . $fields_string;
				}
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}

			
			// headers
			$current_timestamp = strtotime("now");
			$timestamp = gmdate('YmdH:i:s', $current_timestamp);

			/*
			echo 'request_method: ' . $request_method;
			echo 'call_path: ' . $call_path;
			echo 'timestamp: ' . $timestamp;
			echo 'username: ' . $username;
			echo 'customer_key: ' . $customer_key;			
			echo "$request_method+$call_path+$timestamp+$username";
			*/
			
			$digest = base64_encode(hash_hmac("sha256", "$request_method+$call_path+$timestamp+$username", $customer_key));
			$authentication_header = $protocol.' '.$username.':['.$digest.'] '.$company_id;
			
			$headers = array(
				'Content-Type: application/json',
				'Authentication: '. $authentication_header,					
				'Date: ' . gmdate('Y-m-d H:i:s', $current_timestamp)
			);
			
			// print_r($headers);
			// print_r($request_url);
			// print_r($fields_string);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			
			// set URL and other appropriate options
			curl_setopt($ch, CURLOPT_URL, $request_url);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FAILONERROR, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLINFO_HEADER_OUT, false);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);


			// grab URL response and pass it
			$result = curl_exec($ch);
			
			//if( isset($options['debug']) && $options['debug'] )
			//{
				// Write log file
				$message = $call_method.' Elan API request.';
				$this->_log_message('error', $message, $options, $request_url, $headers);
			//}
			
			// Check if any error occurred
			if(curl_errno($ch))
			{
				// Write log file
				$message = 'Elan API Curl error: ' . curl_error($ch);
				$result = '{"STATUS": false, "MESSAGE": "'.addslashes($message).'"}';
				$this->_log_message('error', $message, $options, $request_url, $headers);				
			}

			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			// close cURL resource, and free up system resources
			curl_close($ch);

			//echo $httpcode;
			if($httpcode != 200 && $httpcode != 201)
			{
				// Write log file
				$message = 'Failed '.$call_method.' Elan API request. HTTP CODE: '.$httpcode;
				$result = '{"STATUS": false, "MESSAGE": "'.addslashes($message).'"}';
				$this->_log_message('error', $message, $options, $request_url, $headers);				
			}
		}
		else
		{
			// Write log file
			$message = 'Invalid '.$call_method.' Elan API request.';
			$result = '{"STATUS": false, "MESSAGE": "'.addslashes($message).'"}';			
			//$this->_log_message('error', $message, $options, $request_url, $headers);
		}
		
		$response_data = json_decode($result, true);

		return $response_data;
	}


    //take curl results and put them into the database
	protected function curl_2_db($dbTable, $curlData, $options=false, $colArr=false){

		//will just contain the column names
		$columnArr = array();
		$columnTickArr = array();

		if( $colArr == false ){
			//get the columns from the db table
			$sql = "SHOW COLUMNS FROM $dbTable";

			$columnResults = ee()->db->query($sql);

			//the first array is just the regular column names.  The second is almost the same, but with backtick characters on each side of a name.  This is for db insertion purposes.
			foreach( $columnResults->result_array() as $obj ){
				$columnArr[] = $obj["Field"];
				$columnTickArr[] = "`" . $obj["Field"] . "`";
			}
		}else{
			//allows for possibility of feeder array of predefined columns
			foreach( $colArr as $name ){
				$columnArr[] = $name;
				$columnTickArr[] = "`" . $name . "`";
			}
		}


		//will contain the values
		$insertArr = array();
		$insertArrCrushed = array();

		//initial string for insert (actually replace, as it is much more flexible for whenever an older version of the same data is present)
		$baseSql = "REPLACE INTO `" . $dbTable . "` (" . implode(",", $columnTickArr) . ")";


		//sometimes the curl results will have only a single returned object, so it will then not be a numerical array.  If so, then make one.
		if( $this->has_string_keys($curlData) == 1 ){
			$curlDataLooper = array();

			$curlDataLooper[] = $curlData;
		}else{
			$curlDataLooper = $curlData;
		}

		$i=0;

		//loop through all the records of the json
		foreach($curlDataLooper as $val) {
			$insertArr[$i] = array();
			
			//loop through the column names
			foreach($columnArr as $col){

				if( $col != "insert_time_gmt" ){
					if(!empty($val[$col]) ){

						//normal, when both the table has this column AND this member from the curl array has this property as well.
						$insertArr[$i][$col] = "'" . ee()->db->escape_str( $val[$col] ) . "'";
					}else if( $options != false && array_key_exists($col, $options) ){

						//if options were fed into this function, and the options have a column not populated by anything in the CURL feed, then try to populate it from the options array.
						$insertArr[$i][$col] = "'" . ee()->db->escape_str( $options[$col] ) . "'";
					}else{
						$insertArr[$i][$col] = "''";
					}
				}
			}

			//the insert_time_gmt column is in every temp table used for this plugin
			$insertArr[$i]["insert_time_gmt"] = "'" . $this->currentDateTime . "'";

			//a single line for each row to be inserted
			$insertArrCrushed[] = "(" . implode(",", $insertArr[$i]) . ")";

			$i++;
		}//end foreach

		//we do this because we only want to make mysql insert 100 records at a time, but certainly not just one at a time.
		$insertMultiDarr = array_chunk($insertArrCrushed, 100);

		for( $i=0; $i<count($insertMultiDarr); $i++){

			//a complete statement
			$sql = $baseSql . " VALUES" . implode(",", $insertMultiDarr[$i] );

			//execute the batch insert
			ee()->db->query($sql);
		}
	}

    public function db_populate_if_not_current( $table, $options, $condition="1" ){
		//$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

		//inspect whether there are sufficiently young records on the database.  If not, do a curl request to the Elan REST API.
		$query = "SELECT x.insert_time_gmt AS i FROM `{$table}` x
		WHERE {$condition}
		AND STR_TO_DATE(x.insert_time_gmt, '{$this->datePatternDBinsertFormatPattern}')
		 > STR_TO_DATE('{$this->maxCacheDateTimeBackward}', '{$this->datePatternDBinsertFormatPattern}')
		ORDER BY x.`insert_time_gmt` DESC LIMIT 1";
		
		$relevantTimeResults = ee()->db->query($query);


		//do the time inspection for this user
		if( count($relevantTimeResults->result_array()) < 1 ||
			is_null( $relevantTimeResults->result_array()[0]["i"] )
			|| $relevantTimeResults->num_rows == 0 ){
			$curl_records = $this->curl_request($options);

			//var_dump( $curl_records );

			//place the data from the curl request on the database
			$this->curl_2_db($table, $curl_records["DATA"], $options);
		}
	}

	//for combining arrays, but all values are to be unique
	protected function array_unique_merge($array_a, $array_b) {
		$union_array = array_merge($array_a, $array_b);

		return array_unique( $union_array );
	}
	
	/**
	 * Log message
	 */
	private function _log_message($type, $message, $options, $request_url, $headers)
	{
		// Write log file
		foreach($options as $key => $value)
		{
			if( is_array($value) )
			{
				$value = http_build_query($value);
			}		
			$message .= NL . $key . ' => ' . $value;
		}
		$message .= NL . $request_url;
		$message .= NL . implode(NL, $headers);
		$message .= NL;
		log_message($type, $message);
	}
	
	/**
	 * Set default value
	 */
	public function set_default_value(&$array, $key, $data)
	{
		$element = $data['element'];
		$defaultvalue = $data['defaultvalue'];

		if(!isset($array[$element]) || empty($array[$element]))
		{
			$array[$element] = $defaultvalue;
		}

		return $array;
	}
	
	/**
	 * User Authentication
	 */
	public function user_authentication()
	{
		$username = ee()->input->get_post('username', TRUE);
		$password = ee()->input->get_post('password', TRUE);
		
		$options = $this->default_options;
		$options['request_method'] = 'POST';
		$options['call_method'] = 'login';		
		$options['call_path'] = 'login';
		$options['parameters'] = array(
			'username' => $username,
			'password' => $password
		);
		
		$userinfo = $this->curl_request($options);
		
		return $userinfo;		
	}
	
	/**
	 * Get Tags Info
	 */
	public function get_tags_info()
	{
		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'tags';		
		$options['call_path'] = 'tags';
		$options['parameters'] = array(
			'user_id' => ''
		);
		
		//$tagsinfo = $this->curl_request($options);

		$this->db_populate_if_not_current( $this->table_tags, $options);

		$query = "SELECT x.OWNER_GROUP_ID, x.TAG_ID, x.TAG_TYPE, x.TAG_NAME
		FROM {$this->table_tags} x
		WHERE STR_TO_DATE(x.insert_time_gmt,
		'{$this->datePatternDBinsertFormatPattern}') > '{$this->maxCacheDateTimeBackward}'";

		$tagResults = ee()->db->query($query);

		$tagsinfo["DATA"] = $tagResults->result_array();

		$tagsinfo["STATUS"] = ( count($tagsinfo["DATA"] > 0) )? true : false;

		return $tagsinfo;		
	}	
	
	/**
	 * Get Single User Info
	 */
	public function get_single_user_info()
	{
		//$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;
		
		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'user';		
		$options['call_path'] = 'user/'.$user_id;
		
		//$userinfo = $this->curl_request($options);


		$this->db_populate_if_not_current( $this->table_user_info, $options,
			"x.USER_ID={$user_id}" );

		$cols = "x.*";

		//get the results from the database
		$query = "SELECT {$cols} FROM `{$this->table_user_info}` x
		WHERE STR_TO_DATE(x.insert_time_gmt,
		'{$this->datePatternDBinsertFormatPattern}') > '{$this->maxCacheDateTimeBackward}'";


		$userinfoResults = ee()->db->query($query);

		$userinfo = array();

		$tempUserinfo = $userinfoResults->result_array();
		$userinfo["DATA"] = $tempUserinfo[0];
		
		return $userinfo;
	}

    /**
     * Get Single User Info With Attributes
     */
    public function get_single_user_info_with_custom_attr()
    {
		//$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

        $object_type = ee()->input->get_post('object_type', true);

        //Get initial user info
        $userinfo = $this->get_single_user_info();

        // return $userinfo;

        //Get all of the Curriculm Objects with Monthly Focus tags
		/*
        $tags = "Monthly Focus Main";
        $tags = 'MONTHLY FOCUS';
        $tags = '';
        $objects = $this->get_multiple_objects_info('', 21, 'Active', $tags, '', true);

        if(isset($objects['DATA'])) {
        	$objects_length = count($objects['DATA']);
        } else {
        	$objects_length = 0;
        }

        // return $objects;
        //Get all of the user objects
        $user_objects = $this->get_user_object_records($user_id, '', '', true);
        // return $user_objects;
        //Cycle throw objects to determine how many are complete and which need a user object record created
        $objects_completed = 0;
        $score = 0;
        //return $objects['DATA'];
        if ($objects_length > 0) {
	        foreach ($objects['DATA'] as $object) {
	            $found = false;
	            if(isset($user_objects['DATA'])) {
		            foreach ($user_objects['DATA'] as $user_object) {
		                if ($user_object['OBJECT_ID'] == $object['OBJECT_ID']) {
		                    if ($user_object['STATUS'] == 'Complete') {		               
		                        $objects_completed ++;
		                        $score += $user_object['SCORE'];
		                        break;
		                    }
		                }
		            }
		        }	

	            //If a user record was not found create one
	            // if (!$found) {
	            //     $this->create_user_object_record($user_id, $object['OBJECT_ID'], '', '', 'Started', '', true);
	            // }
	        }
	    }
		
 
        //Set the custom attributes.  Still need to create rules for ML_RANK
        $custom_attributes = array(
                              "COURSES_COMPLETED" => $objects_completed,
                              "TOTAL_COURSES"=> $objects_length,
                              "TOTAL_SCORES"=> $score,
							  "ML_RANK_MAP" => "mlapprentice", // mlapprentice or mlambassador etc.
                              "ML_RANK"=>"ML Apprentice",
							  "ML_RANK_SINCE"=>"9/10/2016"

        );
        $userinfo['OBJECT_RECORDS'] = $user_objects;
		*/
		
		$user_language_id = 1; // Default language is English
		if( isset($userinfo['DATA']['LANGUAGE_ID']) ) {			
			if( array_search($userinfo['DATA']['LANGUAGE_ID'], array_column($this->allowed_languages, 'id')) != false ) {
				$user_language_id = $userinfo['DATA']['LANGUAGE_ID'];
			}
		}
		$current_month_tag = $this->allowed_languages[array_search($user_language_id, array_column($this->allowed_languages, 'id'))]['month_tag'];
        $current_year_tag = $this->allowed_languages[array_search($user_language_id, array_column($this->allowed_languages, 'id'))]['year_tag'];
		
		//Set the custom attributes.  Still need to create rules for ML_RANK
        $custom_attributes = array(
			"ML_RANK_MAP" => "mlapprentice", // mlapprentice or mlambassador etc.
			"ML_RANK" => "ML Apprentice",
			"ML_RANK_SINCE" => "9/10/2016",
			"CURRENT_MONTH_TAG" => $current_month_tag,
			"CURRENT_YEAR_TAG" => $current_year_tag,
        );
        
        $userinfo['DATA']['CUSTOM_ATTRIBUTES'] = $custom_attributes;

        return $userinfo;
    }

    public function get_user_course_data()
    {
        //$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

        //Get all of the Curriculm Objects with Monthly Focus Main, Current Month and Current Year Tags.  NOTE: parameter 4 (for column 'TAGS') is modified here, because it was incorrectly formatted before.
        $objects = $this->get_multiple_objects_info('', '', 'Active', 'MONTHLY FOCUS' . "," .Date("F") . "," . Date("Y"), '', true);

        $course_data = array();

        //Setting the order of Monthly Focus page.  Space in front is intentional as that is how they are returned from the api.
        $order = array(' Row 1', ' Row 2', ' Row 3', ' Row 4');
        foreach ($order as $ord) {
            for ($column=1;$column<=3;$column++) {
                foreach ($objects['DATA'] as $object) {
                    
                    //create an array of the tags
                    $tags = explode(",", $object['TAGS']);
                    
                    //Check to see if this object should be in this row and column
                    if (in_array($ord, $tags) && in_array(' Item '.$column, $tags)) {

                        // $course_data[$ord][$column] = $tmp_array;
                        if (in_array(' PRODUCT FOCUS', $tags)) {
                            $key = "product focus";
                        } elseif (in_array(' SERVICE FOCUS', $tags)) {
                            $key = "service focus";
                        } elseif (in_array(' MANAGER FOCUS', $tags)) {
                            $key = "manager focus";
						} elseif (in_array(' SEASONAL FOCUS', $tags)) {
                            $key = "seasonal focus";
                        } elseif (in_array(' PRODUCT LIBRARY', $tags)) {
                            $key = "product library";
                        } elseif (in_array(' LAST MONTH ARCHIVE', $tags)) {
                            $key = "last month archive";
                        } elseif (in_array(' SPOTTED', $tags)) {
                            $key = "spotted";
                        } elseif (in_array(' INSTAGRAM', $tags)) {
                            $key = "instagram";
                        } else {
                            $key = "other";
                        }

                        //Check to see record status
                        $record_status = $this->get_or_create_user_record($user_id, $object['OBJECT_ID'], '');
                        //return $record_status;
                        $record_id = $record_status['RECORD_ID'];


                        $tmp_array = array(
                            "course_title" => $object['OBJECT_NAME'],
                              "course_image" => $object['OBJECT_IMAGE'],
                              "course_summary" => $object['OBJECT_DESCR'],
                              "course_completion" => array("is_complete" => "false"),
                              "course_id" => $object['OBJECT_ID'],
                              "course_head" => $key,
                              "course_children" => $object['CHILD_ID_LIST']);

                        if($key == "instagram") {
                            $tmp_array["link"] = "https://www.instagram.com/coach/"; 
                        }

                        //$course_data[$ord][] = $tmp_array;
                        $course_data[' Row 1'][] = $tmp_array;
                    }
                }
            }
        }
        return $course_data;
    }

    public function get_monthly_focus()
    {
    	$start = microtime(true);
    	
		$user_id = $this->user_id;
		$group_id = $this->get_set_group_id();

    	$userinfo = $this->get_single_user_info();
		$user_language_id = 1; // Default language is English
		if( isset($userinfo['DATA']['LANGUAGE_ID']) ) {		
			if( array_search($userinfo['DATA']['LANGUAGE_ID'], array_column($this->allowed_languages, 'id')) != false ) {
				$user_language_id = $userinfo['DATA']['LANGUAGE_ID'];
			}
		}

		//the results object initiated
    	$results = array();

    	// return $object_records;
    	$monthly_focus = array();

        $total_assessments = 0;
        $passed_assessments = 0;
        $total_percentage = 0;
        $objects_completed = 0;
        $total_objects = 0;
        
		$current_language_tr = $this->allowed_languages[array_search($user_language_id, array_column($this->allowed_languages, 'id'))]['tag'];
		$current_month_tag_tr = $this->allowed_languages[array_search($user_language_id, array_column($this->allowed_languages, 'id'))]['month_tag'];
		$current_year_tag_tr = $this->allowed_languages[array_search($user_language_id, array_column($this->allowed_languages, 'id'))]['year_tag'];

		$current_language = ' '.$current_language_tr;
		$current_month_tag = ' '.$current_month_tag_tr;
		$current_year_tag = ' '.$current_year_tag_tr;
		//$current_language = ' '.$this->allowed_languages[array_search(1, array_column($this->allowed_languages, 'id'))]['name'];


    	$objects = $this->get_multiple_objects_info($group_id, '', 'Active', '',
    	$current_language_tr, true, $current_year_tag_tr, $current_month_tag_tr, "", 1);


/*
    	SELECT y.STATUS, y.SCORE, x.* FROM `exp_temp_multiple_objects_info` x RIGHT JOIN `exp_temp_user_object_records` y ON x.OBJECT_ID = y.OBJECT_ID WHERE x.OBJECT_TYPE_ID = 21 AND x.OWNER_GROUP_ID = 1 AND y.USER_ID = 11016 AND y.STATUS = 'Complete' 
*/

    	/*
    	we just need the total summed score from all the Scorm courses that this
    	user took, how many courses the user took, and how many courses completed.
    	*/
    	$object_records = $this->get_user_object_records($user_id, '', '', true,
    	"SUM( CASE WHEN x.USER_ID={$user_id} then x.SCORE ELSE 0 END ) as score,
    	SUM( 1 ) as total_courses,
SUM( CASE WHEN x.STATUS = 'Complete' AND x.USER_ID={$user_id} then 1 ELSE 0 END ) as courses_completed",
    	" AND y.OBJECT_TYPE_ID = 21",
    	"RIGHT JOIN {$this->table_multiple_objects_info} y ON y.OBJECT_ID = x.OBJECT_ID");

    	//get Scorm Course information for scoring
		$total_courses = 0;
		$courses_completed = 0;
		$score = 0;

        if (isset($object_records['DATA']) && count($object_records['DATA']) > 0
         && is_array($object_records['DATA'][0]) ) {

			$total_courses = $object_records['DATA'][0]["total_courses"];
			$courses_completed = $object_records['DATA'][0]["courses_completed"];
			$score = $object_records['DATA'][0]["score"];
        }


        if (isset($objects['DATA'])) {

	        /*
	        all the conditions are now done upstream in the
	        MySQL stored procedure 'get_user_object_records'
	        */
	        $results['objects'] = $objects['DATA'];
	    }

    	//Setting the order of Monthly Focus page.  Space in front is intentional as that is how they are returned from the api.
        $order = array(' Row 1', ' Row 2', ' Row 3', ' Row 4');
        //foreach ($order as $ord) {
            //for ($column=1;$column<=3;$column++) {
            	//$count = 0;
				
				//if( isset($results['objects']) ) {
					foreach ($results['objects'] as $object) {
                    
						//create an array of the tags
						$tags = explode(",", $object['TAGS']);
						
						//Check to see if this object should be in this row and column
						//NOTE: ALL records now fit these requirements from the query
						//if (in_array($ord, $tags) && in_array(' Item '.$column, $tags) && in_array($current_month_tag, $tags) && in_array($current_year_tag, $tags) && in_array($current_language, $tags)) {
							// $course_data[$ord][$column] = $tmp_array;
							if (in_array(' PRODUCT FOCUS', $tags)) {
								$key = 'product_focus';
							} elseif (in_array(' SERVICE FOCUS', $tags)) {
								$key = 'service_focus';
							} elseif (in_array(' MANAGER FOCUS', $tags)) {
								$key = 'manager_focus';
							} elseif (in_array(' SEASONAL FOCUS', $tags)) {
								$key = 'seasonal_focus';
							} elseif (in_array(' PRODUCT LIBRARY', $tags)) {
								$key = 'product_library';
							} elseif (in_array(' LAST MONTH ARCHIVE', $tags)) {
								$key = 'archive';
							} elseif (in_array(' SPOTTED', $tags)) {
								$key = 'spotted';
							} elseif (in_array(' INSTAGRAM', $tags)) {
								$key = 'instagram';
							} else {
								$key = 'other';
							}

							$children = explode(',', $object['CHILD_ID_LIST']);
							//$user_object_record['STATUS'] = '';
							//$user_object_record['RECORD_ID'] = '';

							/*
							if( isset($object_records['DATA']) ) {

								if(in_array($object['OBJECT_ID'], array_column($object_records['DATA'], 'OBJECT_ID'))) {
									$temp_object_record = array_search($object['OBJECT_ID'], array_column($object_records['DATA'], 'OBJECT_ID'));  //echo '$temp_object_record = '; var_dump($temp_object_record);
									$user_object_record = $object_records['DATA'][$temp_object_record];  //echo '$user_object_record = '; var_dump($user_object_record);
								} else {
									$user_object_record = array('STATUS' => '');
								}
							}
							*/


							//Oh, SOO much simpler!  Do it for the children!
							$children_array = json_decode( "[" . $object['CHILDREN'] . "]", true );

							//echo "<pre>" . $object['CHILDREN'] . "\n\n\n\n";

							//var_dump( $children_array );


							//$count2 = 0;
							$disabled = '';
							$course_percent = '';
							//$children_array = array();
							$course_no_quiz = true; 


							foreach($children_array as $child) {
								//$highest_score = 0;

								$is_link = "";
								$link_path = "";
								$is_video = "";
								$is_scorm = "";
								$video_url = "";
								$is_quiz = "";
								$quiz_percent = 0;
								$is_complete = $child['is_objective_complete'];
								$total_objects ++;


								//$child["type"]

								if($child["type"] == 'Link') {
									$is_link = "true";
									$link_path = $objects['DATA'][$temp_object]['LINK_PATH'];
									$link_path = urldecode($link_path);
									if(strpos($link_path, 'stylegame.coach.com')) {
										$link_path .= "&userid=".$user_id."&date=".date("Y-m"); 
									}
									//$link_path = str_replace("https://coach.ttnlearning.com/content/links", "/assets/coachjourney", $link_path);
								} else if($child["type"] == 'SCORM') {
								   $is_scorm = "true";
									
								} else if ($child["type"] == 'Course') {
									$step_num = trim($child['video_steps']);
									// $video_info = $this->get_course_video(trim($child), $step_num, $parent_object_id, true);
									// foreach ($video_info['DATA']['MEDIA_INFO']['encodings'] as $video_encodings){
									// 	if ($video_encodings['width'] == 900 && $video_encodings['container_type'] == 'Mp4') {
											$is_video = "true";
										// 	$video_url = $video_encodings['file_url'];
										// }
									// }
									//return $video_info;
								} else if ($child["type"] == 'Quiz') {
									$course_no_quiz = '';
									$is_quiz = "true";
									$total_assessments ++;
									$quiz_percent = $child["quiz_percent"];
									if ($child['is_objective_complete'] != 'Passed') {
										$is_complete = '';
									} else {
										$course_percent = $child['score'].'%';
										$total_percentage += $child['score'];
										$passed_assessments ++;
									}								
								}
							}//end foreach($children_array as $child)


							/*foreach($children as $child) {
								$temp_object = array_search(trim($child), array_column($objects['DATA'], 'OBJECT_ID'));
								//var_dump($temp_object);
								//$children_array[] = $objects['DATA'][$temp_object];
								if ($temp_object) {
									$child_user_object_record['STATUS'] = '';
									$child_user_object_record['RECORD_ID'] = '';
									$highest_score = 0;
									// var_dump($object_records['DATA']);
									// var_dump($user_object_record['RECORD_ID']);
									if( isset($object_records['DATA']) ) {
										foreach ($object_records['DATA'] as $object_record) {
											if(isset($user_object_record['RECORD_ID'])) {
												if($object_record['OBJECT_ID'] == $objects['DATA'][$temp_object]['OBJECT_ID'] && $object_record['PARENT_RECORD_ID'] == $user_object_record['RECORD_ID'])
												{

													//Quiz attempt with Failed status does not get consider as complete
													if( $object_record['STATUS'] != 'Failed' ) {
														if($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'Quiz') {
															if ($object_record['SCORE'] > $highest_score) {
																// print_r($object_record);
																$child_user_object_record = $object_record;
																$highest_score = $object_record['SCORE'];
															}
														} else {
															$child_user_object_record = $object_record;
															break;
														}
													}										
												}
											}
										}
									}

									$is_link = "";
									$link_path = "";
									$is_video = "";
									$is_scorm = "";
									$video_url = "";
									$is_quiz = "";
									$quiz_percent = 0;
									$is_complete = $child_user_object_record['STATUS'];
									$total_objects ++;
									

									if($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'Link') {
										$is_link = "true";
										$link_path = $objects['DATA'][$temp_object]['LINK_PATH'];
										$link_path = urldecode($link_path);
										if(strpos($link_path, 'stylegame.coach.com')) {
											$link_path .= "&userid=".$user_id."&date=".date("Y-m"); 
										}
										//$link_path = str_replace("https://coach.ttnlearning.com/content/links", "/assets/coachjourney", $link_path);
									} else if($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'SCORM') {
									   $is_scorm = "true";
										
									} else if ($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'Course') {
										$step_num = trim($objects['DATA'][$temp_object]['VIDEO_STEPS']);
										// $video_info = $this->get_course_video(trim($child), $step_num, $parent_object_id, true);
										// foreach ($video_info['DATA']['MEDIA_INFO']['encodings'] as $video_encodings){
										// 	if ($video_encodings['width'] == 900 && $video_encodings['container_type'] == 'Mp4') {
												$is_video = "true";
											// 	$video_url = $video_encodings['file_url'];
											// }
										// }
										//return $video_info;
									} else if ($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'Quiz') {
										$course_no_quiz = '';
										$is_quiz = "true";
										$total_assessments ++;
										$quiz_percent = $objects['DATA'][$temp_object]['PASSING_PERCENT'];
										if ($child_user_object_record['STATUS'] != 'Passed') {
											$is_complete = '';
										} else {
											$course_percent = $child_user_object_record['SCORE'].'%';
											$total_percentage += $child_user_object_record['SCORE'];
											$passed_assessments ++;
										}								
									}


									$this_child_data = array('title' => $objects['DATA'][$temp_object]['OBJECT_NAME'],
														'type' => $objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'],
														'is_link' => $is_link,
														'is_objective_complete' => $is_complete,
														'object_id' => $objects['DATA'][$temp_object]['OBJECT_ID'],
														'parent_object_id' => $object['OBJECT_ID'],
														'link_path' => $link_path,
														'is_video' => $is_video,
														'is_scorm' => $is_scorm,
														'disabled' => $disabled,
														'record_id' => $child_user_object_record['RECORD_ID'],
														'is_quiz' => $is_quiz,
														'quiz_percent' => $quiz_percent);

									if($child_user_object_record['STATUS'] != 'Complete' && $child_user_object_record['STATUS'] != 'Passed' ) {
										$disabled = " disabled";
									} else {
										$objects_completed ++;
									}
								
									$children_array[] = $this_child_data;
								}
								
								$count2 ++;
							}
							$count ++;
							*/


							//determined by adding up all the children of every main object
							//$total_objects += count($children_array);


							//find out how many courses were completed / passed
							$counts = array_count_values( array_column($children_array, "is_objective_complete") );
							//$passed_assessments += $counts["Complete"] + $counts["Passed"];

							$objects_completed += $counts["Complete"] + $counts["Passed"];

							//var_dump( $counts );


							//adding up all the percentages of all courses taken
							//$total_percentage += array_sum( array_column($children_array, "quiz_percent") );


							//get the score from the highest score of the child array
							/*if( max( array_column($children_array, "quiz_percent") ) > 0 ){
								$course_percent = max( array_column($children_array, "quiz_percent") ) .'%';
							}*/

							/*
							foreach( $children_array as $k=>$v ){
								switch ($v["type"]) {
									case 'Link':
										
										//$children_array[$k]["link_path"] = "elephant-rape.com";

										if(strpos($children_array[$k]["link_path"], 'stylegame.coach.com')) {
											$children_array[$k]["link_path"] .= "&userid=".$user_id."&date=".date("Y-m"); 
										}
									break;
									case 'SCORM':
										
									break;
									case 'Course':
										
									break;
									case 'Quiz':
										/*if( is_numeric($v['SCORE']) ){
											$course_percent = $v['SCORE'].'%';
										} else {
											$course_percent = '';
										}*/

/*
										$total_percentage += $v['SCORE'];

										if ($v['is_objective_complete'] == 'Passed' ) {
											$course_percent = $v['SCORE'].'%';
											//$total_percentage += $child_user_object_record['SCORE'];
											//$passed_assessments ++;

											//$total_percentage += $v['SCORE'];
										} else {
											$course_percent = '';
										}	
									break;
								}
							}*/


							//count the number of quizzes for this user from the children array
							/*foreach( array_column($children_array, "is_quiz") as $iq ){
								if( $iq == true){
									$total_assessments ++;

									$course_no_quiz = '';
								}
							}*/

							//Check to see if course is complete
							$complete = '';
							//if ($user_object_record['STATUS'] == 'Complete') {
							if ($object['STATUS'] == 'Complete') {
								$complete = true;

								//
								if( $course_no_quiz !== true ){
									$course_percent = "100%";
								}
							}

							$tmp_array = array(
								"course_title" => $object['OBJECT_NAME'],
								  "course_image" => $object['OBJECT_IMAGE'],
								  "course_summary" => $object['OBJECT_DESCR'],
								  "course_status" => $object['STATUS'],
								  "course_completion" => $complete,
								  "course_id" => $object['OBJECT_ID'],                              
								  "course_children" => $object['CHILD_ID_LIST'],
								  "course_percent" => $course_percent,
								  "course_no_quiz" => $course_no_quiz,
								  'children' => $children_array);

							// instagram link
							if(strtolower($key) == "instagram") {
								$tmp_array["link"] = "https://www.instagram.com/coach/"; 
							}
							
							// course_title
							if($key == "archive" || $key == "spotted" || $key == "instagram") {
								$tmp_array["course_title"] = ""; 
							}
							
							// course_head
							$course_head = $this->get_translation($key, $user_language_id, true);                        
							$tmp_array["course_head"] = $course_head;
							
							//$monthly_focus[$ord][] = $tmp_array;
							//$monthly_focus[" Row 1"][] = $tmp_array;
							$monthly_focus[ " " . $object['ROW'] ][] = $tmp_array;
						//}//end if
					}				
				//} //end if( isset($results['objects']) )
			//} //end for ($column=1;$column<=3;$column++)
        //} //end foreach ($order as $ord)

		//Fix for Division by Zero error
		$assessment_avg_percentage = 0;
		if( $passed_assessments > 0 ) {
			$assessment_avg_percentage = round($total_percentage/$passed_assessments);
		}
		
        $custom_attributes = array(
                              "COURSES_COMPLETED" => $courses_completed,
                              "TOTAL_COURSES"=> $total_courses,
                              "TOTAL_SCORES"=> $score,
                              "OBJECTS_COMPLETED" => $objects_completed, 
                              "TOTAL_OBJECTS" => $total_objects, 
                              "TOTAL_ASSESSMENTS" => $total_assessments, 
                              "ASSESSMENTS_COMPLETED" => $passed_assessments, 
                              "ASSESSMENT_TOTAL_SCORE" => $total_percentage, 
                              "ASSESSMENT_AVE_PERCENTAGE" => $assessment_avg_percentage, 
                              "ML_RANK"=>"ML Apprentice",
							  "ML_RANK_SINCE"=>"9/10/2016"

        );

        $monthly_focus['DATA'] = $userinfo['DATA'];
        $monthly_focus['DATA']['CUSTOM_ATTRIBUTES'] = $custom_attributes;
        $translations = $this->get_translation_array($user_language_id);
        $translation_array = array();
        foreach($translations as $translation) {
        	$translation_array[$translation['name']] = $translation['content'];
        }
        $monthly_focus['translations'] = $translation_array;


        //echo 'count($monthly_focus[" Row 1"]) = ' . count($monthly_focus[" Row 1"]);

        return $monthly_focus;

    	$end = microtime(true);
    	//echo $end - $start;
    }
	
	    
     public function get_monthly_focus_itinerary()
    {
		
        $start = microtime(true);
		$user_id = $this->user_id;

        $userinfo = $this->get_single_user_info();		
		$user_language_id = 1; // Default language is English
		if( isset($userinfo['DATA']['LANGUAGE_ID']) ) {			
			if( array_search($userinfo['DATA']['LANGUAGE_ID'], array_column($this->allowed_languages, 'id')) != false ) {
				$user_language_id = $userinfo['DATA']['LANGUAGE_ID'];
			}
		}
		//var_dump($user_language_id);
        $results = array();
        $objects = $this->get_multiple_objects_info('','','Active','','',true);
        // return $objects;
        $object_records = $this->get_user_object_records($user_id, '', '', true);

        // return $object_records;
        $monthly_focus = array();

		
		//$months = array(' January',' February',' March',' April',' May',' June',' July',' August',' September',' October',' November',' December');
		$months = array();
		array_push($months, ' '.date('F')) ;
			for ($i=1; $i<=13; $i++ ){
				array_push($months, ' '.date('F', strtotime("+$i months"))) ;
			}
	    $months = array_reverse($months);
		//print "<pre>";print_r($months);
		
		$current_language = ' '.$this->allowed_languages[array_search($user_language_id, array_column($this->allowed_languages, 'id'))]['tag'];
		
		foreach ($months as $month){
			$total_assessments = 0;
			$passed_assessments = 0;
			$total_percentage = 0;
			$objects_completed = 0;
			$total_objects = 0;
			//$current_year_tag = ' 2017';
		
        	// return $objects;
        	//Check each object for Monthly Focus
            foreach ($objects['DATA'] as $object) {
                if(strpos($object['TAGS'], 'Monthly Focus Main') && strpos($object['TAGS'], $month) && strpos($object['TAGS'], $current_language)){
                    $results['objects'][] = $object;
                }
            }

        
        
        //Setting the order of Monthly Focus page.  Space in front is intentional as that is how they are returned from the api.
		if (isset($results['objects'])) {
                foreach ($results['objects'] as $object) {
                    //create an array of the tags
                    $tags = explode(",", $object['TAGS']);
                    
                    //Check to see if this object should be in this row and column
                    if (in_array($month, $tags) && isset($object)) {
                        $children = explode(',', $object['CHILD_ID_LIST']);
                        $user_object_record['STATUS'] = '';
                        $user_object_record['RECORD_ID'] = '';
                        if( isset($object_records['DATA']) ) {

                            if(in_array($object['OBJECT_ID'], array_column($object_records['DATA'], 'OBJECT_ID'))) {
                                $temp_object_record = array_search($object['OBJECT_ID'], array_column($object_records['DATA'], 'OBJECT_ID'));
                                $user_object_record = $object_records['DATA'][$temp_object_record];
                            } else {
                                $user_object_record = array('STATUS' => '');
                            }
                        }
                        $count2 = 0;
                        $disabled = '';
                        $course_percent = '';
						
                        $children_array = array();
                        $course_no_quiz = true; 
                        foreach($children as $child) {
                            $temp_object = array_search(trim($child), array_column($objects['DATA'], 'OBJECT_ID'));
                            //var_dump($temp_object);
                            //$children_array[] = $objects['DATA'][$temp_object];
                            if ($temp_object) {
                                $child_user_object_record['STATUS'] = '';
                                $child_user_object_record['RECORD_ID'] = '';
                                $highest_score = 0;
                                // var_dump($object_records['DATA']);
                                // var_dump($user_object_record['RECORD_ID']);
                                if( isset($object_records['DATA']) ) {
                                    foreach ($object_records['DATA'] as $object_record) {
                                        if(isset($user_object_record['RECORD_ID'])) {
                                            if($object_record['OBJECT_ID'] == $objects['DATA'][$temp_object]['OBJECT_ID'] && $object_record['PARENT_RECORD_ID'] == $user_object_record['RECORD_ID'])
                                            {

                                                //Quiz attempt with Failed status does not get consider as complete
                                                if( $object_record['STATUS'] != 'Failed' ) {
                                                    if($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'Quiz') {
                                                        if ($object_record['SCORE'] > $highest_score) {
                                                            // print_r($object_record);
                                                            $child_user_object_record = $object_record;
                                                            $highest_score = $object_record['SCORE'];
                                                        }
                                                    } else {
                                                        $child_user_object_record = $object_record;
                                                        break;
                                                    }
                                                    
                                                    
                                                }                                        
                                            }
                                        }
                                    }
                                }

                                $is_quiz = "";
                                $quiz_percent = 0;
                                $is_complete = $child_user_object_record['STATUS'];
                                $total_objects ++;
                                

                                if ($objects['DATA'][$temp_object]['OBJECT_TYPE_NAME'] == 'Quiz') {
                                    $course_no_quiz = '';
                                    $is_quiz = "true";
                                    $total_assessments ++;
                                    $quiz_percent = $objects['DATA'][$temp_object]['PASSING_PERCENT'];
                                    if ($child_user_object_record['STATUS'] != 'Passed') {
                                        $is_complete = '';
                                    } else {
                                        $course_percent = $child_user_object_record['SCORE'].'%';
                                        $total_percentage += $child_user_object_record['SCORE'];
                                        $passed_assessments ++;
                                    }                                
                                }

                                if($child_user_object_record['STATUS'] != 'Complete' && $child_user_object_record['STATUS'] != 'Passed' ) {
                                    $disabled = " disabled";
                                } else {
                                    $objects_completed ++;
                                }
                            
                                //$children_array[] = $this_child_data;
                            }
                        }

                        //Check to see if course is complete
                        $complete = '';
                        if ($user_object_record['STATUS'] == 'Complete') {
                            $complete = true;
                        }

                        //$monthly_focus[$month][] = $tmp_array;

                    }
               
				}
			}
			//Fix for Division by Zero error
				$assessment_avg_percentage = 0;
				$month = strtoupper(trim($month));
				if( $passed_assessments > 0 ) {
					$assessment_avg_percentage = round($total_percentage/$passed_assessments);
				}
			
				$custom_attributes = array(
									  "OBJECTS_COMPLETED" => $objects_completed, 
									  "TOTAL_OBJECTS" => $total_objects, 
									  "TOTAL_ASSESSMENTS" => $total_assessments, 
									  "ASSESSMENTS_COMPLETED" => $passed_assessments, 
									  "ASSESSMENT_TOTAL_SCORE" => $total_percentage, 
									  "ASSESSMENT_AVG_PERCENTAGE" => $assessment_avg_percentage
				);
			$monthly_focus['DATA'][$month] = $custom_attributes;
		}
		return $monthly_focus;
    }

    public function get_course_detail_data()
    {
    	$start = microtime(true);
        //$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

        $parent_object_id = ee()->input->get_post('object_id', true);
        $parent_children = ee()->input->get_post('children', true);
                
        //Set initial course detail from parent data
        $child_data = array('courseSummary' => '', 'learningObjectives' => array());
        
        //If parent has child then get data for each child.
        if ($parent_children != '') {
            $children = explode(',', $parent_children);
            
            $disabled = "";
            foreach ($children as $child) {

                $child_object = $this->get_object_info(trim($child), true);
                $is_link = "";
                $link_path = "";
                $is_video = "";
                $video_url = "";
                $is_quiz = "";
                $quiz_percent = 0;

                if($child_object['DATA']['OBJECT_TYPE_NAME'] == 'Link') {
                    $is_link = "true";
                    $link_path = $child_object['DATA']['LINK_PATH'];
                    $link_path = str_replace("https://coach.ttnlearning.com/content/links", "/assets/coachjourney", $link_path);
                } else if ($child_object['DATA']['OBJECT_TYPE_NAME'] == 'Course') {
                	$step_num = trim($child_object['DATA']['VIDEO_STEPS']);
                	// $video_info = $this->get_course_video(trim($child), $step_num, $parent_object_id, true);
                	// foreach ($video_info['DATA']['MEDIA_INFO']['encodings'] as $video_encodings){
                	// 	if ($video_encodings['width'] == 900 && $video_encodings['container_type'] == 'Mp4') {
                			$is_video = "true";
                		// 	$video_url = $video_encodings['file_url'];
                		// }
                	// }
                	//return $video_info;
                } else if ($child_object['DATA']['OBJECT_TYPE_NAME'] == 'Quiz') {
                	$is_quiz = "true";
                	$quiz_percent = $child_object['DATA']['PASSING_PERCENT'];
                }

                //Check for user record for this object.  If not found create it.
                $user_record = $this->get_or_create_user_record($user_id, $child_object['DATA']['OBJECT_ID'], $parent_object_id, false);
                
                //return $user_record;
                $completed = "";
                if($user_record['STATUS'] == 'Complete') {
                    $completed = "true";
                }                

                $this_child_data = array('title' => $child_object['DATA']['OBJECT_NAME'],
                                    'type' => $child_object['DATA']['OBJECT_TYPE_NAME'],
                                    'is_link' => $is_link,
                                    'is_objective_complete' => $completed,
                                    'object_id' => $child_object['DATA']['OBJECT_ID'],
                                    'parent_object_id' => $parent_object_id,
                                    'link_path' => $link_path,
                                    'is_video' => $is_video,
                                    'disabled' => $disabled,
                                    'record_id' => $user_record['RECORD_ID'],
                                    'is_quiz' => $is_quiz,
                                    'quiz_percent' => $quiz_percent);

                //If this child object is not complete then set next child to disabled from being clicked.
                if($completed == "") {
                	$disabled = " disabled";
                }

                //var_dump($this_child_data);
                $child_data['learningObjectives'][] = $this_child_data;
            }
        }
        $end = microtime(true);
        //echo $end - $start;
        return $child_data;
    }

	public function get_quiz_questions()
	{
		//$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

        $object_id = ee()->input->get_post('object_id', true);
        $parent_object_id = ee()->input->get_post('parent_object_id', true);
		
		//Get additional information pertaining only to quizzes and surveys that is sent on the Elan backend
		$options = $this->default_options;
        $options['request_method'] = 'GET';
        $options['call_method'] = 'formoptions';
        $options['call_path'] = 'formoptions/'.$object_id;
        $options['parameters'] = array(
            'parent_object_id' => $parent_object_id
        );
        $quiz_options = $this->curl_request($options);
		$quiz_options_data = array();
		$max_retakes = '';
		if( isset($quiz_options['DATA']) ) {
			$quiz_options_data = $quiz_options['DATA'];
			$max_retakes = $quiz_options_data['MAX_RETAKES'];
		}
		
		$record_object = $this->get_or_create_user_record($user_id, $object_id, $parent_object_id, true, 'Started', $max_retakes);
        $record_object_id = $record_object['RECORD_ID'];

		if( !empty($max_retakes) && $record_object['TOTAL_ATTEMPTS'] >= $max_retakes ) {
			$quiz_info['STATUS'] = true;
			$quiz_info['MESSAGE'] = 'Maximum allowed attempts reached.';
			$quiz_info['DATA'] = array();		
		} else {
			$options = $this->default_options;
			$options['request_method'] = 'GET';
			$options['call_method'] = 'form';
			$options['call_path'] = 'form/'.$object_id;
			$options['parameters'] = array(
				'parent_object_id' => $parent_object_id
			);
			$quiz_info = $this->curl_request($options);
		}
		
		$quiz_info['quiz_options'] = $quiz_options_data;					
        $quiz_info['record_id'] = $record_object_id;

        return $quiz_info;    
   
	}

	public function submit_quiz_answers()
	{
        $record_id = ee()->input->get_post('record_id', true);
        $user_ans_no = ee()->input->get_post('user_ans_no', true);
        $user_ans_list = ee()->input->get_post('user_ans_list', true);
        $user_ans_text = ee()->input->get_post('user_ans_text', true);
        $user_ans_textarea = ee()->input->get_post('user_ans_textarea', true);
        $question_num = ee()->input->get_post('question_id', true);

        $options = $this->default_options;
        $options['request_method'] = 'POST';
        $options['call_method'] = 'object';
        $options['call_path'] = 'formanswers/'.$record_id;
        $options['parameters'] = array(
        	array(
	        	'ques_no' => $question_num,
	            'user_ans_no' => $user_ans_no,
	            'user_ans_list' => $user_ans_list,
	            'user_ans_text' => $user_ans_text,
	            'user_ans_textarea' => $user_ans_textarea,
	        )
        );
        //echo json_encode($options['parameters']);

        $quiz_info = $this->curl_request($options);  

        return $quiz_info;    
    }
	
	public function get_quiz_answers()
	{
        $object_id = ee()->input->get_post('object_id', true);
		//$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

		$record_id = ee()->input->get_post('record_id', true);
		
        $options = $this->default_options;
        $options['request_method'] = 'GET';
        $options['call_method'] = 'object';
        $options['call_path'] = 'formanswers/'.$object_id.'/'.$user_id.'/'.$record_id;

		$userquizinfo = $this->curl_request($options);
		return $userquizinfo;
    }

    public function update_create_user_record($user_id = 0, $object_id = 0, $parent_object_id = 0, $status = 'Started', $use_variables = false)
	{
        if (!$use_variables) {
            //$user_id = ee()->input->get_post('user_id', TRUE);
			$user_id = $this->user_id;

            $object_id = ee()->input->get_post('object_id', true);
            $parent_object_id = ee()->input->get_post('parent_object_id', true);
            $status = ee()->input->get_post('status', true);
        }    		

        //Check or create user object record.  
        $record = $this->get_or_create_user_record($user_id, $object_id, $parent_object_id, true, $status);
        
        //If status is not what was passed then update it.
        if($record['STATUS'] != $status) {
        	$record_id = $record['RECORD_ID'];
        	$record = $this->update_user_object_record($record_id, $status, true);
        }

        return $record;
    }

    private function get_or_create_user_record($user_id, $object_id, $parent_object_id, $create_record = true, $status = 'Started', $max_allowed_attempts = '')
    {
        //Check for user record for this object.  If not found create it.
        $user_record = $this->get_user_object_records($user_id, $object_id, $parent_object_id, TRUE);

		$record_id = '';
		$status = '';
		$total_attempts = 0;
		
		if( $user_record['STATUS'] != false && is_array($user_record['DATA']) ) {			
			$total_attempts = count($user_record['DATA']);			
			foreach($user_record['DATA'] as $record) {
				if( $record['STATUS'] != 'Failed' && $record['STATUS'] != 'Passed' ) {
					$status = $record['STATUS']; 
					$record_id = $record['RECORD_ID'];
					break;
				}
			}
		}
		
        if( empty($record_id) && $create_record )
		{
			if( empty($max_allowed_attempts) || $total_attempts < $max_allowed_attempts ) {
				$status = 'Started';
				$new_record = $this->create_user_object_record($user_id, $object_id, $parent_object_id, '', 'Started', '', TRUE);
				$record_id = $new_record['DATA']['RECORD_ID'];
			}
		}

        $results = array('RECORD_ID' => $record_id, 'STATUS' => $status, 'TOTAL_ATTEMPTS' => $total_attempts);

        return $results;
    }

    public function get_course_detail_link()
    {
        //$user_id = ee()->input->get_post('user_id', TRUE);
		$user_id = $this->user_id;

        $parent_object_id = ee()->input->get_post('parent_object_id', true);
        $object_id = ee()->input->get_post('object_id', true);

        //get or create a record_id.
        $record_id_data = $this->get_or_create_user_record($user_id, $object_id, $parent_object_id);
        //return $record_id_data;
        $record_id = $record_id_data['RECORD_ID']['DATA'][0]['RECORD_ID'];

        $data = $this->get_document_detail($object_id, $record_id, true);

        return $data;
        
    }

    public function get_document_detail($object_id = '', $record_id = '', $use_variables = FALSE)
    {
        if (!$use_variables) {
            $object_id = ee()->input->get_post('object_id', true);
            $record_id = ee()->input->get_post('record_id', true);
        }

        $options = $this->default_options;
        $options['request_method'] = 'GET';
        $options['call_method'] = 'object';
        $options['call_path'] = 'getdoc/'.$object_id.'/'.$record_id;
        
        //$objectsinfo = $this->curl_request($options);
        
        
        return $this->update_user_object_record($record_id, 'Complete', true);
        //return $objectsinfo;
    }

    public function get_course_video($object_id = '', $step_num = '', $parent_object_id = '', $use_variables = false)
    {
         if (!$use_variables) {
            $object_id = ee()->input->get_post('object_id', true);
            $step_num = ee()->input->get_post('step_num', true);
            $parent_object_id = ee()->input->get_post('parent_object_id', true);
        }

        $options = $this->default_options;
        $options['request_method'] = 'GET';
        $options['call_method'] = 'object';
        $options['call_path'] = 'launchvideo/'.$object_id.'/'.$step_num;
        $options['parameters'] = array(
            'parent_object_id' => $parent_object_id
        );

        $video_info = $this->curl_request($options);
        
        return $video_info;   	
    }
	
	/**
	     *  Update user record
     */
        public function update_user_record($user_id = '', $force_password_change = '', $password = '' , $use_variables = false)
    {
		if (!$use_variables) {
			//$user_id = ee()->input->get_post('user_id', TRUE);
			$user_id = $this->user_id;

			$change_password = ee()->input->get_post('force_password_change', TRUE);
			$password = ee()->input->get_post('password', TRUE);
		}
		
        $options = $this->default_options;
        $options['request_method'] = 'PUT';
        $options['call_method'] = 'user';
        $options['call_path'] = 'user/'.$user_id;
        $options['parameters'] = array(
            'force_password_change' => $change_password,
            'password' => $password
        );

        $response = $this->curl_request($options);

        return $response;
    }

    	/**
	     *  Update user record
     */
       public function update_user_language_id($user_id = '', $language_id = 1, $use_variables = false)
    {
    	if (!$use_variables) {
			//$user_id = ee()->input->get_post('user_id', TRUE);
			$user_id = $this->user_id;

	        $language_id = ee()->input->get_post('language_id', TRUE);
		}
		
        $options = $this->default_options;
        $options['request_method'] = 'PUT';
        $options['call_method'] = 'user';
        $options['call_path'] = 'user/'.$user_id;
        $options['parameters'] = array(
            'language_id' => $language_id
        );

        $response = $this->curl_request($options);

        return $response;
    }
    
    /**
	 * Get Multiple User Info
	 */
	public function get_multiple_user_info()
	{
		//$group_id = ee()->input->get_post('group_id', TRUE);
		$group_id = $this->get_set_group_id();
		$primary_yn = ee()->input->get_post('primary_yn', TRUE);
		$role_id = ee()->input->get_post('role_id', TRUE);
		$gmt_offset = ee()->input->get_post('gmt_offset', TRUE);
		$status = ee()->input->get_post('status', TRUE);
		
		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'user';
		$options['call_path'] = 'user';		
		$options['parameters'] = array(
			'group_id' => $group_id,
			'primary_yn' => $primary_yn,
			'role_id' => $role_id,
			'gmt_offset' => $gmt_offset,
			'status' => $status
		);
		
		//$usersinfo = $this->curl_request($options);


		$conditionalExtras = "";

		$tags_l = strtolower(trim($tags));

		if( strlen($group_id) > 0 || $group_id != 0 ){
			$conditionalExtras .= " AND x.group_id='{$group_id}'";
		}

		if( strlen($primary_yn) > 0 || $primary_yn != 0 ){
			$conditionalExtras .= " AND x.primary_yn='{$primary_yn}'";
		}

		if( strlen($role_id) > 0 || $role_id != 0 ){
			$conditionalExtras .= " AND x.role_id='{$role_id}'";
		}

		if( strlen($gmt_offset) > 0 || $gmt_offset != 0 ){
			$conditionalExtras .= " AND x.GMT_OFFSET='{$gmt_offset}'";
		}

		if( strlen($status) > 0 ){
			$conditionalExtras .= " AND x.STATUS='{$status}'";
		}


		$this->db_populate_if_not_current( $this->table_user_info, $options,
			"1 {$conditionalExtras}" );

		$cols = "x.*";

		//get the results from the database
		$query = "SELECT {$cols} FROM `{$this->table_user_info}` x
		WHERE STR_TO_DATE(x.insert_time_gmt,
		'{$this->datePatternDBinsertFormatPattern}') > '{$this->maxCacheDateTimeBackward}' {$conditionalExtras}";


		$userinfoResults = ee()->db->query($query);

		$userinfo = array();

		$tempUserinfo = $userinfoResults->result_array();
		$userinfo["DATA"] = $tempUserinfo[0];
		
		return $usersinfo;
	}
	
	/**
	 * Get All Learning Objects Info
	 */
    public function get_multiple_objects_info($group_id = '', $object_type_id = '',
		$status = '', $tags = '', $language_aux_field_value = '', $use_variables = false,
		$year = "", $month = "", $conditionalExtras = "", $use_children = false)
    {

        if (!$use_variables) {
            $group_id = $this->get_set_group_id();

            $object_type_id = ee()->input->get_post('object_type_id', true);
            $status = ee()->input->get_post('status', true);
            $tags = ee()->input->get_post('tags', true);
            $language_aux_field_value = ee()->input->get_post('language', true);
			if(!$status) {
				$status = 'Active';
			}

			$year = date("Y");
			$month = date("F");
        }

        //$object_type_id = 21;

        //custom tags picked
		$tags_l = strtolower(trim($tags));

		if( strlen($tags) > 0 ){

			if( strstr($tags_l, "," ) ){
				//if there are multiple tags, comma delimited
				$tagArr = explode(",", $tags_l );

				foreach($tagArr as $tag){
					$conditionalExtras .= " AND x.LOWER(TAGS) LIKE \"%" . trim($tag) . "%\"";
				}

			}else{
				//if there is only one meaningful tag
				$conditionalExtras .= " AND x.LOWER(TAGS) LIKE \"%{$tags_l}%\"";
			}
		} 

		if( $object_type_id > 0 || strlen($object_type_id) > 0 ){
			$conditionalExtras .= " AND x.OBJECT_TYPE_ID=\"{$object_type_id}\"";
		}

		if( $group_id > 0 || strlen($group_id) > 0 ){
			$conditionalExtras .= " AND x.OWNER_GROUP_ID=\"{$group_id}\"";
		}


		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'object';		
		$options['call_path'] = 'object';		
		$options['parameters'] = array(
			'group_id' => $group_id,
			'object_type_id' => $object_type_id,
			'status' => $status,
			'tags' => $tags,
			'language_aux_field' => $language_aux_field_value
		);


		//populate the datbase if needed
		$this->db_populate_if_not_current( $this->table_multiple_objects_info,
			$options, "x.OBJECT_STATUS='{$status}' {$conditionalExtras} " );

		//rely on a new MySQL stored procedure.  Way, way faster than an equivalent php fed query
		$query = "CALL exp_get_multiple_objects_info('{$this->dbp}',
		'{$this->maxCacheDateTimeBackward}', '{$year}', '". strtolower($month) .
		"', '". strtolower($language_aux_field_value) . "', '" . $conditionalExtras .
		"', " . intval($use_children) . ")"; //die($query);

		//execute the query, get the results
		$currentRecordsResults = ee()->db->query($query);

		//this is the object which will be returned
		$objectsinfo = array();
		$objectsinfo["DATA"] = $currentRecordsResults->result_array();
		
		return $objectsinfo;
	}
	
	/**
	 * Get Learning Object Info
	 */
    public function get_object_info($object_id = '', $use_variables = false)
    {
        if (!$use_variables) {
            $object_id = ee()->input->get_post('object_id', true);
        }
		
		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'object';		
		$options['call_path'] = 'object/'.$object_id;
		
		$objectinfo = $this->curl_request($options);
		
		return $objectinfo;
	}
	
	/**
	 *  Get user CLO data
	 */
    public function get_user_object_records($user_id = '', $object_id = '',
    $parent_object_id = '', $use_variables = false, $selCols = "",
    $conditionalExtras = "", $joinWith = "")
    {
        if (!$use_variables) {
            //$user_id = ee()->input->get_post('user_id', TRUE);
			$user_id = $this->user_id;
			
            $object_id = ee()->input->get_post('object_id', TRUE);
            $parent_object_id = ee()->input->get_post('parent_object_id', TRUE);
        }

		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'record';
		$options['call_path'] = 'record/'.$user_id;
		$options['parameters'] = array(
			'object_id' => $object_id,
			'parent_object_id' => $parent_object_id
		);


		/*
		ALL columns right below, but we are not currently using. Just listed here for reference.
		SCORE, COMPLETED_TS, USER_ID, BATCH_ID, STATUS, OBJECT_ID, ENTRY_NO, PASS_YN, GRANDFATHER_ID, PARENT_RECORD_ID, RECORD_ID, VM_ATTENDEE_ID, TOTAL_SECS_TRACKED, START_TS
		*/
		//OBJECT_ID, PARENT_RECORD_ID, RECORD_ID, SCORE, STATUS
		if( trim($selCols) == "" ){
			$selCols = "x.OBJECT_ID, x.PARENT_RECORD_ID, x.RECORD_ID, x.SCORE, x.STATUS";
		}

		if( trim($object_id) != '' ){
			$conditionalExtras .= " AND x.OBJECT_ID = '{$object_id}'";
		}

		if( trim($parent_object_id) != '' ){
			$conditionalExtras .= " AND x.PARENT_RECORD_ID = '{$parent_object_id}'";
		}

		//$user_record = $this->curl_request($options);

		$this->db_populate_if_not_current( $this->table_user_object_records,
			$options, "x.USER_ID={$user_id}" );


		$whereTime = "STR_TO_DATE(x.insert_time_gmt,
		'{$this->datePatternDBinsertFormatPattern}') >
		STR_TO_DATE('{$this->maxCacheDateTimeBackward}', 
		'{$this->datePatternDBinsertFormatPattern}')";


		if( strlen(trim($joinWith)) > 0){
			$whereTime = "(" . $whereTime . " OR STR_TO_DATE(y.insert_time_gmt,
		'{$this->datePatternDBinsertFormatPattern}') >
		STR_TO_DATE('{$this->maxCacheDateTimeBackward}', 
		'{$this->datePatternDBinsertFormatPattern}'))";
		}

		//get the results from the database
		$query = "SELECT {$selCols} FROM `{$this->table_user_object_records}` x
		{$joinWith} WHERE {$whereTime}
		 {$conditionalExtras}"; //die($query);


		$currentRecordsResults = ee()->db->query($query);

		$user_record = array();

		$user_record["DATA"] = $currentRecordsResults->result_array();

		return $user_record;
	}
	
	/**
	 *  Create user object record
	 */
    public function create_user_object_record($user_id = '', $object_id = '', $parent_object_id = '', $entry_no = '', $status = '', $batch_id = '', $use_variables = false)
    {
        if (!$use_variables) {
			//$user_id = ee()->input->get_post('user_id', TRUE);
			$user_id = $this->user_id;
            $object_id = ee()->input->get_post('object_id', true);
            $parent_object_id = ee()->input->get_post('parent_object_id', true);
            $entry_no = ee()->input->get_post('entry_no', true);
            $status = ee()->input->get_post('status', true);
            $batch_id = ee()->input->get_post('batch_id', true);
        }

		$options = $this->default_options;
		$options['request_method'] = 'POST';
		$options['call_method'] = 'record';
		$options['call_path'] = 'record';
		$options['parameters'] = array(
			'user_id' => $user_id,
			'object_id' => $object_id,
			'parent_object_id' => $parent_object_id,
			'entry_no' => $entry_no,
			'status' => $status,
			'batch_id' => $batch_id
		);

		$response = $this->curl_request($options);

		return $response;
	}
	
	/**
	 *  Update user object record
	 */
    public function update_user_object_record($record_id = '', $status = '', $use_variables = false)
    {

        if(!$use_variables) {
            $record_id = ee()->input->get_post('record_id', true);
            $status = ee()->input->get_post('status', true);
            $score = ee()->input->get_post('score', TRUE);
        }

		$options = $this->default_options;
		$options['request_method'] = 'PUT';
		$options['call_method'] = 'record';
		$options['call_path'] = 'record/'.$record_id;
		$options['parameters'] = array(
			'status' => $status,
			'score' => $score
		);
		//var_dump($options);

		$response = $this->curl_request($options);

		return $response;
	}
	
	/**
	 *  Launch scorm
	 */
	public function launch_scorm()
	{
		$object_id = ee()->input->get_post('object_id', TRUE);
		$record_id = ee()->input->get_post('record_id', TRUE);

		$options = $this->default_options;
		$options['request_method'] = 'GET';
		$options['call_method'] = 'launchscorm';
		$options['call_path'] = 'launchscorm/'.$object_id.'/'.$record_id;		

		$response = $this->curl_request($options);
		/*
		$response['MESSAGE'] = '';
		$response['STATUS'] = true;
		$response['DATA']['LAUNCH_URL'] = 'https://wwwpprd.coachjourney.com';
		switch( $object_id ) {
			case 1248:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/758-570e618b4b65a/pre/index.html';
				break;
			case 1256:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/760-570e62da86891/pre/index.html#topic=home';
				break;
			case 1252:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/742-570515057c3a6/pre/index.html';
				break;
			case 1253:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/763-570e63ea05a19/pre/index.html';
				break;
			case 1259:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/743-5705151cc9eff/pre/index.html';
				break;
			case 1261:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/751-5706ec990d145/pre/index.html';
				break;
			case 1260:
				$response['DATA']['LAUNCH_URL'] = 'https://s3.amazonaws.com/chameleonprod/clients/39/courses/744-5705152c1dead/pre/index.html';
				break;				
		}
		*/
		
		return $response;
	}
}
// END CLASS