<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['database'] = array (
	'expressionengine' => array (
		'hostname' => 'localhost',
		'username' => 'coach',
		'password' => 'wmFz3Ul8ik39KVNBipRO',
		'database' => 'adgt',
		'dbdriver' => 'mysqli',
		'dbprefix' => 'exp_',
		'pconnect' => FALSE
	),//
);
$config['db_port'] = '3308';

$config['debug'] = '1';
$config['log_threshold'] = 2;
$config['theme_folder_path'] = '/var/www/html/themes/';
$config['theme_folder_url'] = 'http://coach56:8888/themes/';
$config['site_url'] = 'http://coach56:8888/';
$config['cp_url'] = 'http://coach56:8888/lexscp.php';
$config['site_index'] = '';
$config['mail_protocol'] = 'smtp';
$config['smtp_server'] = 'strongmail2.biperf.com';
$config['smtp_port'] = '25';
$config['webmaster_email'] = 'no-reply@coachjourney.com';
$config['webmaster_name'] = 'Coach Journey';
$config['save_tmpl_files'] = 'y';
$config['user_session_ttl'] = '36000';

//$config['biw_proxy_host'] = 'appproxy.biperf.com';
//$config['biw_proxy_port'] = '8080';
//$config['elan_api_url'] = 'https://elan-054.ttnlearning.com';
//$config['elan_company_id'] = '9594727934592020';
//$config['elan_customer_key'] = '8FUYZYN3AOYAJ1QW6975EHASGB7XSATG';
//$config['elan_customer_subdomain'] = 'elan-054';

//$config['biw_proxy_host'] = 'appproxy.biperf.com';
//$config['biw_proxy_port'] = '8080';
$config['elan_api_url'] = 'https://coach.ttnlearning.com';
$config['elan_company_id'] = '3383353058459462';
$config['elan_customer_key'] = 'F31DU7B49GBRTK9EIATWICSE84X8BD3T';
$config['elan_customer_subdomain'] = 'coach';

// EOF