<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');



class Transcribe_tab {

	private $EE;

	public function __construct()
	{

	}

	public function display( $channel_id, $entry_id = '' )
	{
		$settings = array();
		$selected = null;
		$relationship_id = ee()->input->get('relationship') ? ee()->input->get('relationship') : uniqid();

		ee()->lang->loadfile('transcribe');

		// get available languages
		$languages = ee()->db->get('transcribe_languages');
		$languages = $languages->result();

		$languages_dropdown = array();

		foreach( $languages as $language )
		{
			$languages_dropdown[$language->id] = $language->name;
		}

		// get language of current entry
		if( !empty($entry_id) )
		{
			ee()->db->where('entry_id', $entry_id);
			$entry_language = ee()->db->get('transcribe_entries_languages', 1);
			$entry_language = $entry_language->row();

			if( !empty($entry_language) )
			{
				$selected = $entry_language->language_id;
				$relationship_id = $entry_language->relationship_id;
			}
		}
		else
		{
			if( ee()->input->get('language') )
			{
				$selected = ee()->input->get('language');
			}
			else
			{
				$language_id = ee()->db->get_where('transcribe_settings', array('site_id' => ee()->config->config['site_id']), 1)->row('language_id');
				$selected = $language_id;
			}
		}

		ee()->session->set_cache('transcribe', 'relationship_id', $relationship_id);


		// the following check is in place since there is a bug in the core tab save function.
		if(empty($_POST['transcribe__transcribe_language']))
		{
			$settings['transcribe_language'] = array(
			'field_id' => 'transcribe_language',
			'field_label' => lang('transcribe_language_field_label'),
			'field_required' => 'n',
			'field_data' => $selected,
			'field_list_items' => $languages_dropdown,
			'field_fmt' => '',
			'field_instructions' => '',
			'field_show_fmt' => 'n',
			'field_pre_populate' => 'n',
			'field_text_direction' => 'ltr',
			'field_type' => 'select',
			'field_visibility' => 'y',
			);
		}


		// get the related entries for the channel
		$relations = $this->get_entries($channel_id);

		// dropdown to edit/create a related entry for a language
		if( !empty($entry_id) )
		{
			// if we have an entry id.. we want to show what it's related to
			$html_output = array();

			// get currently defined relationships
			$relationships_result = ee()->db->get_where('transcribe_entries_languages', array('relationship_id' => $relationship_id));
			$relationships = $relationships_result->result();

			$existing_translations = array();
			foreach( $relationships as $key => $relationship )
				$existing_translations[$key] = $relationship->language_id;

			// loop over the languages and add a link to the related language if it's present... link to create if not.
			foreach( $languages as $language )
			{
				// determine entry_id, if any
				$key = array_search($language->id, $existing_translations);
				$language_entry_id = ($key === FALSE) ? '' : $relationships[$key]->entry_id;

				// generate edit/create link
				$link = (empty($language_entry_id) ? ee('CP/URL')->make('publish/create/'.$channel_id.'/', array('relationship' => $relationship_id, 'language' => $language->id, 'eid' => $entry_id)) : ee('CP/URL', 'publish/edit/entry/'.$language_entry_id));

				$html_output[] = '<h2><a href="'.$link.'" style="text-decoration:none;">'.$language->name.(empty($language_entry_id) ? ' (does not exist)' : '').'</a></h2>';
				unset($link);
			}

			// set the field
			$settings['transcribe_related_entries'] = array(
				'field_id' => 'transcribe_related_entries',
				'field_label' => lang('transcribe_related_entries_field_label'),
				'field_required' => 'n',
				'field_instructions' => lang('transcribe_related_entries_field_instructions').'<br><br><h3>'.lang('transcribe_entry_translations').'</h3>'.implode('', $html_output),
				'field_type' => 'select',
				'field_list_items' => lang('transcribe_already_related'),
				'field_maxl' => 10000,
				// 'string_override' => implode('', $html_output),
				'field_visibility' => 'y',
			);

			// if we don't have any relationships lets show the dropdown.
			if($relationships_result->num_rows() == 1)
			{
				$settings['transcribe_related_entries']['field_list_items'] = $relations;
			}
		}
		else
		{

			// we done have a new entry.... so we want to send out the normal field
			$settings['transcribe_related_entries'] = array(
				'field_id' => 'transcribe_related_entries',
				'field_label' => lang('transcribe_related_entries_field_label'),
				'field_required' => 'n',
				'field_instructions' => lang('transcribe_related_entries_field_instructions').'<br><br><h3>'.lang('transcribe_entry_translations').'</h3>',
				'field_type' => 'select',
				'field_list_items' => $relations,
				'field_data' => '',
				'field_maxl' => 10000,
				'string_override' => '',
				'field_visibility' => 'y',
			);
		}

		//Relate existing entries here only if it's not being related from another entry
		// only show options for the same channel

		$entry_relationship = ee()->input->get('relationship', TRUE);
		$entry_langauge = ee()->input->get('language', TRUE);

		// are we relating this one from another entry?  If so we don't display
		if( !empty($entry_relationship) && !empty($entry_langauge) )
		{
			$relations = $this->get_entries($channel_id);

			// get the entry id we're relating to
			$eid = ee()->input->get_post('eid');

			if(ee()->input->get_post('eid') && !empty($relationship_id))
			{
				$settings['transcribe_related_entries'] = array(
						'field_id' => 'transcribe_related_entries',
						'field_label' => lang('transcribe_related_entries_field_label'),
						'field_required' => 'n',
						'field_instructions' => lang('transcribe_related_entries_field_instructions'),
						'field_type' => 'select',
						'field_list_items' => $relations,
						// 'string_override' => '',
						'field_visibility' => 'y',
						'field_text_direction' => 'ltr',
						'field_data' => $eid.'__'.$relationship_id,
						);
			}
			else
			{
				// need to set the field data below if it's defined in the URL get param.

				$settings['transcribe_related_entries'] = array(
							'field_id' => 'transcribe_related_entries',
							'field_label' => lang('transcribe_related_entries_field_label'),
							'field_required' => 'n',
							'field_instructions' => lang('transcribe_related_entries_field_instructions'),
							'field_type' => 'select',
							'field_list_items' => $relations,
							'string_override' => '',
							'field_visibility' => 'y',
							'field_text_direction' => 'ltr',
				);

			}



			// $settings['transcribe_related_entries'] = array(
			// 	'field_id' => 'transcribe_related_entries',
			// 	'field_label' => lang('transcribe_relate_entry'),
			// 	'field_type' => 'select',
			// 	'field_required' => 'n',

			// 	'field_list_items' => $relations,
			// 	'field_data' => '',
			// 	'field_fmt' => '',
			// 	'field_instructions' => '',
			// 	'field_show_fmt' => 'n',
			// 	'field_pre_populate' => 'n',
			// 	'field_text_direction' => 'ltr',

			// 	'field_visibility' => 'y',
			// );
		}

		// check the config to see if we have Transcribe URI enabled

		$transcribe_uri_enabled = ee()->config->item('transcribe_uri_facade');

		if( !empty($transcribe_uri_enabled) )
		{
			$uri = '';
			if( empty($entry_id) )
				$entry_id = ee()->input->get('entry_id', TRUE);
			if( !empty($entry_id) )
			{
				$query = ee()->db->select('uri')
										->from('transcribe_uris')
										->where('entry_id', $entry_id)->get();
				if( $query->num_rows() == 1 )
					$uri = $query->row('uri');

			}

			$settings['transcribe_uri'] = array(
				'field_id' => 'transcribe_uri',
				'field_label' => lang('transcribe_uri'),
				'field_required' => 'n',
				'field_data' => $uri,
				'field_list_items' => '',
				'field_fmt' => '',
				'field_instructions' => '',
				'field_show_fmt' => 'n',
				'field_pre_populate' => 'n',
				'field_text_direction' => 'ltr',
				'field_type' => 'text',
				'field_visibility' => 'y',
				'field_maxl' => 100,
			);
		}

		foreach ($settings as $k => $v)
		{
			ee()->api_channel_fields->set_settings($k, $v);
		}

		return $settings;
	}

	/**
	 * Gets the entries in this channel so you can relate this entry to another one.
	 *
	 * @method get_entries
	 * @param  int $channel_id Channel id to get entries for
	 * @return array formatted for select box
	 */
	public function get_entries($channel_id)
	{
		$relate_entries = array();
			$entries = ee()->db
				->select('ct.title, te.entry_id, tl.abbreviation, te.relationship_id')
				->from('transcribe_entries_languages AS te')
				->join('channel_titles AS ct', 'ct.entry_id = te.entry_id')
				->join('transcribe_languages AS tl', 'tl.id = te.language_id')
				->where('ct.channel_id ', $channel_id)
				->order_by('ct.title')
				->limit(1000)
				->get();

			$relations['none'] = lang('transcribe_none');

			if( empty($entry_id) )
				$entry_id = ee()->input->get('entry_id', TRUE);

			foreach($entries->result() as $row)
					if($row->entry_id != $entry_id)
						$relations[$row->entry_id.'__'.$row->relationship_id] = $row->title;

		return $relations;
	}

	/**
	 * Validate tab data... please note this doesn't currently work since theres a bug in the EE core.
	 *
	 * @method validate
	 * @param  object $entry entry data
	 * @param  array? $values values for our tab
	 * @return True if we have errors FALSE if there are no errors.
	 */
	public function validate( $entry, $values )
	{
		// if all is good we return FALSE
		$errors = FALSE;

		$data = $this->get_post_data($entry);

		if(!empty($data['transcribe_related_entries']) )
		{
			$channel_id = ee()->input->get('channel_id', TRUE);

			if( $data['transcribe_related_entries'] !='none' && !empty($data['language_id']) )
			{
				$id_rel = explode('__', $data['transcribe_related_entries']);

				$entries = ee()->db
					->select('te.entry_id, te.language_id, te.relationship_id')
					->from('transcribe_entries_languages AS te')
					->join('channel_titles AS ct', 'ct.entry_id = te.entry_id')
					->where('te.relationship_id ', $id_rel['1'])
					->get();

				foreach($entries->result() as $row)
				{
					if($row->language_id == $data['language_id'])
					{
						$link = array();
						$link[] = BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$channel_id;
						$link[] = (empty($row->entry_id) ? '' : AMP.'entry_id='.$row->entry_id);
						$link[] = 'relationship='.$row->relationship_id;
						$link[] = 'language='.$row->language_id;
						$link = implode(AMP, $link);

						$entry_exists = lang('transcribe_entry_exists_for_lang');
						$entry_exists .= ' <a href="'.$link.'">'.lang('transcribe_view_entry').'</a>';

						$errors = array($entry_exists => 'transcribe_related_entries');
					}
				}
			}
		}

		return $errors;
	}
	/**
	 * Saves tab data to the DB
	 *
	 * @method save
	 * @param  object $entry passed in from EE
	 * @param  array? $values passed in from EE
	 * @return nothing just save our tab data
	 */
	public function save( $entry, $values)
	{
		$data = $this->get_post_data($entry);

		// determine if we are updating or inserting data
		$exists = ee()->db->get_where('transcribe_entries_languages', array('entry_id' => $entry->entry_id), 1);
		$exists = $exists->row();

		if( empty($exists) )
		{
			ee()->db->insert('transcribe_entries_languages', $data);
		}
		else
		{
			ee()->db->where('entry_id', $entry->entry_id);
			ee()->db->update('transcribe_entries_languages', $data);
		}

		// is Transcribe uri enabled turned on?
		$transcribe_uri_enabled = ee()->config->item('transcribe_uri_facade');

		if( !empty($transcribe_uri_enabled) )
		{
			// is a Transcribe URI defined?
			if( !empty($values['transcribe_uri']) )
			{
				// check to see if we have an entry for this entry id already
				$uri_exists = ee()->db->get_where('transcribe_uris', array('entry_id' => $entry->entry_id), 1);
				$uri_exists = $uri_exists->row();

				// if so we'll go ahead and add it to the db.
				$uri_data['entry_id'] = $entry->entry_id;
				$uri_data['uri'] = $values['transcribe_uri'];

				if( empty($uri_exists) )
				{
					ee()->db->insert('transcribe_uris', $uri_data);
				}
				else
				{
					ee()->db->where('entry_id', $entry->entry_id);
					ee()->db->update('transcribe_uris', $uri_data);
				}
			}
		}
	}

	/**
	 * This functino gets the data needed from the post vars since there is a bug in the EE tab values passed in
	 *
	 * @method get_post_data
	 * @return array of data
	 */
	private function get_post_data($entry)
	{
		$data['entry_id'] = $entry->entry_id;
		$data['language_id'] = ee()->input->post('transcribe__transcribe_language');

		$transcribe_relate_entry = ee()->input->post('transcribe__transcribe_related_entries');


		// get the default we're already related line
		$already_related = lang('transcribe_already_related');

		// check the relationship selected in the publish area
		if( ee()->input->post('transcribe__transcribe_related_entries') )
		{

			//checking if we are relating the entry to a new set of entrie(s)
			// set the hey we alredy have this language here.
			if( $transcribe_relate_entry !='none' && !empty($data['language_id']) && $transcribe_relate_entry != $already_related)
			{
				$id_rel = explode('__', $transcribe_relate_entry);
				$data['relationship_id'] = $id_rel['1'];
			}
		}

		// are we already related to a set of entries?
		if( $transcribe_relate_entry  == $already_related)
		{
			$result = ee()->db->get_where('transcribe_entries_languages', array('entry_id' => $data['entry_id']));

			if($result->num_rows() > 0)
			{
				$row = $result->row();
				$data['relationship_id'] = $row->relationship_id;
			}
		}


		// if relationship_id is empty we need to set it here
		if(empty($data['relationship_id']))
			$data['relationship_id'] = uniqid();

		return $data;
	}

	public function delete( $params )
	{
		ee()->db->where_in('entry_id', $params);
		ee()->db->delete('transcribe_entries_languages');
	}
}

/* End of File: tab.transcribe.php */
