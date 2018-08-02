<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');



class Transcribe_import_content {
	
	public static $name         = 'Transcribe Import Content';
	public static $version      = '1.0';
	public static $author       = 'BIW';
	public static $author_url   = 'http://www.biworldwide.com/';
	public static $description  = 'Transcribe Import Content';
	public static $typography   = FALSE;
	
	public $return_data = "";

    public function __construct()
    {
		$site_id = 1;
		$file_name = 'Translated_'.date('Ymd').'.csv';
		$file_path = PATH_THIRD . 'transcribe_import_content/files/' . $file_name;
		
		if( ($handle = fopen($file_path, "r")) !== FALSE )
		{
			ee()->db->truncate('transcribe_languages');
			ee()->db->truncate('transcribe_variables');
			ee()->db->truncate('transcribe_translations');
			ee()->db->truncate('transcribe_variables_languages');
			
			require_once(PATH_THIRD . 'elan_api/libraries/Elan_api_lib.php');
			$elan_api_lib = new Elan_api_lib();
			$all_languages = $elan_api_lib->get_all_languages();
					
			foreach( $all_languages as $key => $language )
			{
				// exp_transcribe_languages - id, name, abbreviation, force_prefix, enabled
				$transcribe_languages = array();
				$transcribe_languages['id'] = $language['id'];
				$transcribe_languages['name'] = $language['name'];
				$transcribe_languages['abbreviation'] = $language['abbreviation'];
				$transcribe_languages['force_prefix'] = '0';
				$transcribe_languages['enabled'] = '1';
				ee()->db->insert('transcribe_languages', $transcribe_languages);
			}
			
			$columns_order = array(
				'variable'
			);

			$query = ee()->db->get('transcribe_languages');
			
			$languages = array();
			if ($query->num_rows() > 0) {
				foreach($query->result() as $row)
				{
					$languages[] = $row->id;
					$columns_order[] = 'lang'.$row->id;
				}
			}
			
			$rownumber = 0;
			while( ($rowdata = fgetcsv($handle, 10000, ",")) !== FALSE )
			{
				$rownumber++;
				$total_columns = count($rowdata);
				if( $rownumber > 1 && $total_columns == count($columns_order) )
				{
					$column = 0;
					
					//exp_transcribe_variables - id, name
					if( !empty($rowdata[$column]) )
					{
						$transcribe_variables['name'] = $rowdata[$column];
						ee()->db->insert('transcribe_variables', $transcribe_variables);
						$variable_id = ee()->db->insert_id();

						foreach( $languages as $key => $language_id )
						{
							$column++;
							
							//exp_transcribe_translations - id, content, variable_id
							$transcribe_translations['variable_id'] = $variable_id;
							$transcribe_translations['content'] = $rowdata[$column];
							ee()->db->insert('transcribe_translations', $transcribe_translations);
							$translation_id = ee()->db->insert_id();

							//exp_transcribe_variables_languages - id, variable_id, translation_id, language_id, site_id
							$transcribe_variables_languages['site_id'] = $site_id;
							$transcribe_variables_languages['variable_id'] = $variable_id;
							$transcribe_variables_languages['language_id'] = $language_id;						
							$transcribe_variables_languages['translation_id'] = $translation_id;
							ee()->db->insert('transcribe_variables_languages', $transcribe_variables_languages);
						}
					}
				}
			}
			
			echo "File has been imported successfully.";		
		}
		else 
		{
			echo "File not found.";
		}
    }
	
	/**
	* Usage
	*
	* This function describes how the plugin is used.
	*
	* @access  public
	* @return  string
	*/
	public static function usage()
	{
		ob_start();  ?>
		
		The Transcribe Import Content Plugin simply imports a
		list of content of your site.

		{exp:transcribe_import_content}

		This is an incredibly simple Plugin.

		<?php
		$buffer = ob_get_contents();
		ob_end_clean();

		return $buffer;
	}
}

/* End of file pi.transcribe_import_content.php */
/* Location: ./system/user/addons/transcribe_import_content/pi.transcribe_import_content.php */