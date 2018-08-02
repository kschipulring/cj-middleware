<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

class Transcribe_upd {

	public $version = '2.1.0';

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->EE->load->dbforge();
	}

	public function install()
	{
		// install module
		$this->EE->db->insert('modules', array(
			'module_name' => 'Transcribe',
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'y'
		));

		// create action
		$this->EE->db->insert('actions', array(
			'class' => 'Transcribe',
			'method' => 'language_switcher',
		));

		// table: transcribe_settings
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("site_id INT(11) NOT NULL DEFAULT '1'");
		$this->EE->dbforge->add_field("language_id INT(11) NOT NULL DEFAULT '1'");
		$this->EE->dbforge->add_field("force_prefix INT(1) NOT NULL DEFAULT '1'");
		$this->EE->dbforge->add_field("enable_transcribe ENUM('1','0') NOT NULL DEFAULT '1'");
		$this->EE->dbforge->create_table('transcribe_settings');

		// table: transcribe_languages
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("name VARCHAR(50) NOT NULL DEFAULT ''");
		$this->EE->dbforge->add_field("abbreviation VARCHAR(20) NOT NULL DEFAULT ''");
		$this->EE->dbforge->add_field("force_prefix ENUM('1','0') NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("enabled ENUM('1','0') NOT NULL DEFAULT '1'");
		$this->EE->dbforge->create_table('transcribe_languages');

		// table: transcribe_variables
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("name VARCHAR(50) NOT NULL DEFAULT ''");
		$this->EE->dbforge->create_table('transcribe_variables');

		// table: transcribe_translations
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("content TEXT NOT NULL");
		$this->EE->dbforge->add_field("variable_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->create_table('transcribe_translations');

		// table: transcribe_variables_languages
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("variable_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("translation_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("language_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("site_id INT(11) NOT NULL DEFAULT '1'");
		$this->EE->dbforge->create_table('transcribe_variables_languages');

		// table: transcribe_entries_languages
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("language_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("entry_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("relationship_id VARCHAR(50) DEFAULT ''");
	 	$this->EE->dbforge->create_table('transcribe_entries_languages');

		// table: transcribe_templates_languages
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("content VARCHAR(100) NOT NULL DEFAULT ''");
		$this->EE->dbforge->add_field("template_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("language_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("site_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->create_table('transcribe_templates_languages');

		// table: transcribe_template_groups_languages
		$this->EE->dbforge->add_field("id");
		$this->EE->dbforge->add_field("content VARCHAR(100) NOT NULL DEFAULT ''");
		$this->EE->dbforge->add_field("template_group_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("language_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("site_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->create_table('transcribe_template_groups_languages');

		// table: transcribe_template_groups_languages
		$this->EE->dbforge->add_field("entry_id INT(11) NOT NULL DEFAULT '0'");
		$this->EE->dbforge->add_field("uri VARCHAR(100) NOT NULL DEFAULT ''");
		$this->EE->dbforge->create_table('transcribe_uris');

		// add indexes.
		$this->EE->db->query("CREATE INDEX `variable_id` ON ".$this->EE->db->dbprefix('transcribe_variables_languages')." (variable_id)");
		$this->EE->db->query("CREATE INDEX `translation_id` ON ".$this->EE->db->dbprefix('transcribe_variables_languages')." (translation_id)");
		$this->EE->db->query("CREATE INDEX `language_id` ON ".$this->EE->db->dbprefix('transcribe_variables_languages')." (language_id)");
		$this->EE->db->query("CREATE INDEX `language_id` ON ".$this->EE->db->dbprefix('transcribe_entries_languages')." (language_id)");
		$this->EE->db->query("CREATE INDEX `relationship_id` ON ".$this->EE->db->dbprefix('transcribe_entries_languages')." (relationship_id)");
		$this->EE->db->query("CREATE INDEX `variable_id` ON ".$this->EE->db->dbprefix('transcribe_translations')." (variable_id)");
		$this->EE->db->query("CREATE INDEX `abbreviation` ON ".$this->EE->db->dbprefix('transcribe_languages')." (abbreviation)");
		$this->EE->db->query("CREATE INDEX `entry_id` ON ".$this->EE->db->dbprefix('transcribe_uris')." (entry_id)");
		$this->EE->db->query("CREATE INDEX `uri` ON ".$this->EE->db->dbprefix('transcribe_uris')." (uri)");

		// add the publish page tab
		$this->EE->load->library('layout');
		$this->EE->layout->add_layout_tabs($this->tabs(), 'transcribe');

		return TRUE;
	}

	public function update( $current = '' )
	{
		if($current == $this->version) { return FALSE; }

		if( version_compare($current, '1.0.3', '<') )
		{
			$fields = array('enable_transcribe' => array('type' => 'ENUM', 'constraint' => '"1","0"', 'default' => '1'));
			$this->EE->dbforge->add_column('transcribe_settings', $fields);
		}

		if( version_compare($current, '1.0.5', '<') )
		{
			if( !$this->EE->db->field_exists('enable_transcribe', 'transcribe_settings') )
			{
				$fields = array('enable_transcribe' => array('type' => 'ENUM', 'constraint' => '"1","0"', 'default' => '1'));
				$this->EE->dbforge->add_column('transcribe_settings', $fields);
			}

			$this->EE->load->library('layout');
			$this->EE->layout->delete_layout_tabs($this->tabs());
			$this->EE->layout->add_layout_tabs($this->tabs(), 'transcribe');
		}

		if( version_compare($current, '1.0.7.1', '<') )
		{
			// Need to add coloum for inject prefix onto the transcribe_languages table.
			// Need to look at the settings on a per site basis and see if the language segment is injected.

			if( !$this->EE->db->field_exists('force_prefix', 'transcribe_languages') )
			{
				$fields = array('force_prefix' => array('type' => 'ENUM', 'constraint' => '"1","0"', 'default' => '0'));
				$this->EE->dbforge->add_column('transcribe_languages', $fields);
			}

			// grab current settings and restore thm after we update the col.
			$this->EE->db->select('id, force_prefix');
			$site_settings = $this->EE->db->get('transcribe_settings');
			$site_settings = $site_settings->result_array();

			$fields = array('force_prefix' => array('name' => 'force_prefix','type' => 'INT','constraint' => 1,'default' => 1));

			$this->EE->dbforge->modify_column('transcribe_settings', $fields);

			$this->EE->db->update_batch('transcribe_settings', $site_settings, 'id');
		}
		if( version_compare($current, '1.0.7.3', '<') )
		{

			$this->EE->db->query("ALTER TABLE `".$this->EE->db->dbprefix('transcribe_languages')."` MODIFY abbreviation varchar(20);");
		}

		if( version_compare($current, '1.0.7.4', '<') )
		{
			// add indexes.
			$this->EE->db->query("CREATE INDEX `variable_id` ON ".$this->EE->db->dbprefix('transcribe_variables_languages')." (variable_id)");
			$this->EE->db->query("CREATE INDEX `translation_id` ON ".$this->EE->db->dbprefix('transcribe_variables_languages')." (translation_id)");
			$this->EE->db->query("CREATE INDEX `language_id` ON ".$this->EE->db->dbprefix('transcribe_variables_languages')." (language_id)");
		}

		if( version_compare($current, '1.0.7.5', '<') )
		{
			// add indexes.
			$this->EE->db->query("CREATE INDEX `language_id` ON ".$this->EE->db->dbprefix('transcribe_entries_languages')." (language_id)");
			$this->EE->db->query("CREATE INDEX `relationship_id` ON ".$this->EE->db->dbprefix('transcribe_entries_languages')." (relationship_id)");
			$this->EE->db->query("CREATE INDEX `variable_id` ON ".$this->EE->db->dbprefix('transcribe_translations')." (variable_id)");
			$this->EE->db->query("CREATE INDEX `abbreviation` ON ".$this->EE->db->dbprefix('transcribe_languages')." (abbreviation)");
		}

		if( version_compare($current, '1.5.1.1', '<') )
		{
			// table: transcribe_template_groups_languages
			$this->EE->dbforge->add_field("entry_id INT(11) NOT NULL DEFAULT '0'");
			$this->EE->dbforge->add_field("uri VARCHAR(100) NOT NULL DEFAULT ''");
			$this->EE->dbforge->create_table('transcribe_uris');
			$this->EE->db->query("CREATE INDEX `entry_id` ON ".$this->EE->db->dbprefix('transcribe_uris')." (entry_id)");
			$this->EE->db->query("CREATE INDEX `uri` ON ".$this->EE->db->dbprefix('transcribe_uris')." (uri)");
		}

		if( version_compare($current, '1.6', '<') )
		{
			// table: transcribe_template_groups_languages
			if( !$this->EE->db->field_exists('enabled', 'transcribe_languages') )
			{
				$fields = array('enabled' => array('type' => 'ENUM', 'constraint' => '"1","0"', 'default' => '1'));
				$this->EE->dbforge->add_column('transcribe_languages', $fields);
			}
		}

		return TRUE;
	}

	public function uninstall()
	{
		$this->EE->db->delete('modules', array('module_name' => 'Transcribe'));
		$this->EE->db->delete('actions', array('class' => 'Transcribe'));

		$this->EE->dbforge->drop_table('transcribe_settings');
		$this->EE->dbforge->drop_table('transcribe_languages');
		$this->EE->dbforge->drop_table('transcribe_variables');
		$this->EE->dbforge->drop_table('transcribe_translations');
		$this->EE->dbforge->drop_table('transcribe_variables_languages');
		$this->EE->dbforge->drop_table('transcribe_entries_languages');
		$this->EE->dbforge->drop_table('transcribe_templates_languages');
		$this->EE->dbforge->drop_table('transcribe_template_groups_languages');
		$this->EE->dbforge->drop_table('transcribe_uris');

		// remove the publish page tab
		$this->EE->load->library('layout');
		$this->EE->layout->delete_layout_tabs($this->tabs());

		return TRUE;
	}

	public function tabs()
	{
		return array('transcribe' => array(
			'transcribe_language' => array(
				'visible' => TRUE,
				'collapse' => FALSE,
				'htmlbuttons' => TRUE,
				'width' => '100%',
			),
			'transcribe_related_entries' => array(
				'visible' => TRUE,
				'collapse' => FALSE,
				'htmlbuttons' => TRUE,
				'width' => '100%',
			),
		));
	}

}

/* End of File: upd.transcribe.php */
