<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['database'] = array (
	'expressionengine' => array (
		'hostname' => 'localhost',
		'username' => 'root',
		'password' => 'wmFz3Ul8ik39KVNBipRO',
		'database' => 'adgt',
		'dbdriver' => 'mysqli',
		'dbprefix' => 'exp_',
		'pconnect' => FALSE
	),
);

$config['debug'] = '0';
$config['log_threshold'] = 1;
$config['theme_folder_path'] = '/var/www/html/themes/';
$config['theme_folder_url'] = 'http://ec2-34-228-71-87.compute-1.amazonaws.com/themes/';
$config['site_url'] = 'http://ec2-34-228-71-87.compute-1.amazonaws.com/';
$config['cp_url'] = 'http://ec2-34-228-71-87.compute-1.amazonaws.com/lexscp.php';
$config['site_index'] = '';
$config['mail_protocol'] = 'smtp';
$config['smtp_server'] = 'strongmail2.biperf.com';
$config['smtp_port'] = '25';
$config['webmaster_email'] = 'no-reply@coachjourney.com';
$config['webmaster_name'] = 'Coach Journey';
$config['email_crlf'] = '\r\n';
$config['email_newline'] = '\r\n';
$config['save_tmpl_files'] = 'y';
$config['user_session_ttl'] = '3600';

$config['biw_proxy_host'] = '';
$config['biw_proxy_port'] = '';
$config['elan_api_url'] = 'https://coach.ttnlearning.com';
$config['elan_company_id'] = '3383353058459462';
$config['elan_customer_key'] = 'F31DU7B49GBRTK9EIATWICSE84X8BD3T';
$config['elan_customer_subdomain'] = 'coach';

// EOF