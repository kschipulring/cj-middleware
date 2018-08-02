<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'transcribe/mod.transcribe.php';
require_once PATH_THIRD . 'transcribe/libraries/Cache.php';
use Transcribe\Cache;

class Transcribe_ext {

	public $name = 'Transcribe';
	public $version = '2.1.0';
	public $description = '';
	public $settings_exist = 'n';
	public $docs_url = '';
	public $settings = array();

	public $entry_sql;

	private $EE;
	private $site_id;

	public function __construct( $settings='' )
	{

		$this->transcribe = new Transcribe();

		$this->settings = $settings;
		$this->site_id = ee()->config->config['site_id'];
	}

	public function activate_extension()
	{
		$hooks['channel_entries_query_result'] = 'transcribe_channel_entries_query_results';
		$hooks['channel_entries_tagdata'] = 'transcribe_channel_entries_tagdata';

		$hooks['sessions_start'] = 'transcribe_session_start';
		$hooks['cp_menu_array'] = 'transcribe_cp_menu_array';
		$hooks['edit_entries_additional_where'] = 'transcribe_edit_entries_additional_where';

		// added for 280
		$hooks['channel_search_modify_search_query'] = 'transcribe_channel_search_modify_search_query';

		foreach ($hooks as $hook => $ext_method)
		{
			ee()->db->insert('extensions', array(
				'class'		=> __CLASS__,
				'method'	=> $ext_method,
				'hook'		=> $hook,
				'settings'	=> serialize($this->settings),
				'priority'	=> 9,
				'version'	=> $this->version,
				'enabled'	=> 'y'
			));
		}
	}

	/**
	 * Extension at channel_entries_query_results hook
	 *
	 * Receives a query result of entries which we then modify to pull the
	 * proper translation of the query result if they exist. If nothing exists
	 * we return the query results with no change to what was passed into the
	 * function.
	 */
	public function transcribe_channel_entries_query_results( $object, $query_results )
	{
		$disable_transcribe = ee()->TMPL->fetch_param('transcribe');
		if( !empty($disable_transcribe) AND $disable_transcribe == 'disable' ) return $query_results;

		if (REQ == 'CP') return FALSE;

		// is transcribe enabled for this site?
		$settings = $this->transcribe->_get_settings();
		if( empty($settings->enable_transcribe) ) return $query_results;

		// is_search boolean
		$is_search = FALSE;

		// get currently selected language
		if(!empty(ee()->config->_global_vars['transcribe:lang_id']))
			$config_lang_id = ee()->config->_global_vars['transcribe:lang_id'];

		if(empty($config_lang_id))
		{
			$language_id = $this->transcribe->_set_current_language();
			$language_id = $language_id['id'];
		}
		else
		{
			$language_id = $config_lang_id;
		}

		$search_id = ee()->uri->query_string;

		$pattern = '/\/.+/';
		$replacement = '';
		$search_id = preg_replace($pattern, $replacement, $search_id);

		// set the current page for use later
		$cur_page = ee()->pagination->cur_page;
		$current_offset = $object->pagination->offset;
		$per_page = $object->pagination->per_page;


		ee()->db->select('el.id', 1);
		ee()->db->from('transcribe_entries_languages as el');
		ee()->db->where_not_in('language_id ', 0);
		$query = ee()->db->get();

		if( $query->num_rows() == 0 )
			return $query_results;

		if( empty($this->entry_sql) AND !empty($object->sql) )
		{
			ee()->session->set_cache('transcribe', 'entries_sql', $object->sql);
		}

		$logged_queries = array_reverse(ee('Database')->getLog()->getQueries());

		// $logged_queries is an array of arrays we need 0 (the query) from each array
		foreach($logged_queries as $query_array)
			$current_queries[] = $query_array[0];

		// retrieve all entry_id's without the query limit (limit results after grabbing the translations)
		$select_entries_sql = NULL;

		foreach( $current_queries as $query )
		{
			// check for relationship
			if( strpos($query, 'SELECT rel_id, rel_parent_id, rel_child_id, rel_type, rel_data') !== FALSE )
			{
				return $query_results;
			}
			elseif( strpos($query, 'SELECT `rel_id`, `rel_parent_id`, `rel_child_id`, `rel_type`, `reverse_rel_data`' ) !== FALSE )
			{
				// we have a reverse relationship
				return $query_results;
			}
			elseif( (strpos($query, 'SELECT t.entry_id FROM ' . ee()->db->dbprefix('channel_titles') . ' AS t') !== FALSE) OR (strpos($query, 'SELECT DISTINCT(t.entry_id) FROM ' . ee()->db->dbprefix('channel_titles') . ' AS t') !== FALSE) )
			{

				// we have regular results
				// save LIMIT for use later
				preg_match('/LIMIT ([0-9,\s]+)$/', $query, $matches);

				if( !empty($matches) )
				{
					$limit = $matches[1];
					$values = explode(',', $matches[1]);
					$limit = array(
						'offset' => trim($values[0]),
						'limit' => trim($values[1]),
					);
				}

				// remove limit from query
				$select_entries_sql = preg_replace('/LIMIT [0-9,\s]+$/', '', $query);

				// save ORDER BY for later use
				preg_match('/ORDER BY (.*)$/', $select_entries_sql, $matches);
				if( !empty($matches) )
					$order_by = $matches[1];

				if(!empty($select_entries_sql))
				{
					$all_entry_ids = ee()->db->query($select_entries_sql);
					$all_entry_ids = $all_entry_ids->result_array();
				}

				break;
			}
		}

		if( empty($all_entry_ids))
		{
			$query_results = $this->rewrite_channel_and_comment_urls($query_results, $language_id);
			return $query_results;
		}

		// cache all entry id's for later use
		$original_entry_ids = array();
		foreach( $all_entry_ids as $row )
			$original_entry_ids[] = $row['entry_id'];

		$entry_ids_hardcoded= ee()->TMPL->fetch_param('entry_id');


		// get languages to be added in
		$add_langs = $this->_get_add_in_langs();
		$all_languages = $this->_get_all_languages();

		// get entry id's for all entries in this result set with the current language id
		ee()->db->select('el.entry_id, el.language_id, el.relationship_id');
		ee()->db->from('transcribe_entries_languages AS tel');
		ee()->db->join('transcribe_entries_languages AS el', 'el.relationship_id = tel.relationship_id', 'INNER');
		ee()->db->join('channel_titles as t', 't.entry_id = el.entry_id');
		ee()->db->join('channels as w', 'w.channel_id = t.channel_id', 'left');
		ee()->db->join('channel_data as wd', 'wd.entry_id = t.entry_id', 'left');
		ee()->db->join('members as m', 'm.member_id = t.author_id', 'left');
		ee()->db->join('member_data as md', 'md.member_id = m.member_id', 'left');

		// if entry ids are passed...pull in statuses here
		if( !empty($entry_ids_hardcoded) )
		{
			if( ($channel_status = ee()->TMPL->fetch_param('status')) !== FALSE )
			{
				// have to use the following string replace and not str to lower since custom statuses can have upper case chars
				$status = str_replace(array('Open', 'Closed'), array('open', 'closed'), $channel_status);
				$and_or = ee()->functions->sql_andor_string($status, 't.status');

				// first characters are always AND.. remove them
				$status_where = str_replace(substr($and_or, 0, 3), '', $and_or);

				ee()->db->where($status_where, NULL, FALSE);
			}
			else
			{
				ee()->db->where('t.status', 'open');
			}

		}

		ee()->db->where_in('tel.entry_id', $original_entry_ids);


		// adding multiple langage entry ids here.
		if(empty($all_languages))
		{
			// standard operation is just the current language
			ee()->db->where('el.language_id', $language_id);
		}
		else
		{
			// we want to go ahead and add additional languages
			// first this is first... add the current language
			$lang_ids[] = $language_id;

			// loop over the add in langauges and add them to an array to be used in where in.
			foreach($add_langs as $add_in_lang)
			{
				if(!empty($all_languages[$add_in_lang]) && $all_languages[$add_in_lang]->id != $language_id)
				{
					$lang_ids[] = $all_languages[$add_in_lang]->id;
				}
			}

			// lang_ids is all languages we want to add in.
			ee()->db->where_in('el.language_id', $lang_ids);
			// this SQL statement should remove related entries from the added in language from the results as well... which is intended.
		}

		// end adding in multiple languages
		if( !empty($order_by) )
		{
			ee()->db->order_by($order_by, '', FALSE);
		}

		if( !empty($limit) )
		{
			// added this due to a limit issue where we didn't
			ee()->db->distinct();
			ee()->db->limit($limit['limit'], $limit['offset']);
		}

		$new_ids = ee()->db->get();
		$new_ids = $new_ids->result_array();

		// go ahead and save this query for pagination later if we are running multiple languages
		if(!empty($all_languages))
		{
			$entries_query = ee()->db->last_query();

			if(version_compare(APP_VER, '2.8.0', '>='))
			{
				$app_pos = strpos($entries_query, "#APP/");
				$ext_pos = strpos($entries_query, "#ext.transcribe.php");

				if ($app_pos > 0)
				{
					$entries_query = substr($entries_query, 0, $app_pos);
				}
				elseif ($ext_pos > 0)
				{
					$entries_query = substr($entries_query, 0, $ext_pos);
				}
			}

			// save to cache so we can use it later
			Cache::set(array('entries_query'), $entries_query);
		}

		// loop over the data and organize it in entry id key relationship
		foreach($new_ids as $row)
		{
			$new_ids_relationship[$row['entry_id']] = $row['relationship_id'];
		}

		if(!empty($new_ids_relationship))
		{
			// since by default the enry ids for our current language are going to be first from the query...
			//  and array_unique preserves the first keys (entry_ids) we'll filter out the duplicate non primary language ones with an array_unique
			//  in the event that more the one language is suppose to be added in, we will filter out entries related in the langauges in the order
			// the are passsed in as params to the channel_entries loop
			$new_ids_relationship = array_unique($new_ids_relationship);

			// flip them into our entry_ids array and re index the values
			$entry_ids = array_values(array_flip($new_ids_relationship));
		}
		else
		{
			// build a new array of entry id's to re-run the channel entries query with the current language
			$entry_ids = array();
		}

		$entry_ids = array_unique($entry_ids);


		if( empty($entry_ids_hardcoded) )
			$entry_ids = array_intersect($entry_ids, $original_entry_ids);

		// if there are no entries to select, return an empty result
		if( empty($entry_ids) )
		{
			// wow why was this so hard to do
			$object->return_data = ee()->TMPL->no_results();

			return array();
		}

		// modify the original query to get the correct entries for the current language
		$sql_split = preg_split('/IN \([0-9,]+\)/', ee()->session->cache['transcribe']['entries_sql']);
		$sql = $sql_split[0] . 'IN (' . implode(',', $entry_ids) . ')' . $sql_split[1];

		if( $is_search )
		{
			$sql .= ' '. $search_limit;
			$sql= str_replace('MDBMPREFIX', ee()->db->dbprefix, $sql);
		}

		$results = ee()->db->query($sql);

		if( !empty($object->enable['categories']) )
		{
			// rebuild category data.
			$object->query = $results;
			$object->fetch_categories();
		}

		$results = $results->result_array();

		$results = $this->rewrite_channel_and_comment_urls($results, $language_id);

		// pagination check
		if(!empty($object->enable['pagination']) && $object->enable['pagination'] == true )
		{
			// standard operation... just the currnet language
			if(empty($all_languages))
			{
				ee()->db->select('COUNT(entry_id) AS count');
				ee()->db->from('transcribe_entries_languages');
				ee()->db->where_in('entry_id', $original_entry_ids);
				ee()->db->where('language_id', $language_id);
				$entries_count = ee()->db->get();
				$entries_count = $entries_count->row();
			}
			else
			{
				// we have more then one langauge active here... we need to run the results generation query and remove the limit
				// limit is always set with pagination...
				$entries_query = Cache::get(array('entries_query'));

				preg_match('/LIMIT ([0-9,\s]+)$/', $entries_query, $matches);

				if(!empty($matches))
				{
					$values = explode(',', $matches[1]);
					$entries_query = str_replace($matches['0'], '', $entries_query);
				}

				$entries_count = ee()->db->query($entries_query);

				$entries_count->count = $entries_count->num_rows;

				// ok, were going to be adding in entires from more then one language.... go ahead and add it in here.
				// ee()->db->where_in('language_id', $lang_ids);
			}

			// reset pagination links
			$object->pagination->page_links = NULL;
			$url = ee()->functions->fetch_site_index(1);

			// setup pagination for EE 2.4.0+
			$object->pagination->absolute_results = $entries_count->count;
			$object->absolute_results = $entries_count->count;
			$object->pagination->total_rows = $entries_count->count;

			$object->pagination->total_items = $entries_count->count;

			//the following is to add support for next and prev linking
			// do we have more then 0?

			if( !empty($object->pagination->per_page) && (!empty($object->pagination->total_rows) || !empty($object->pagination->total_items)) )
			{

				$entries_limit = ee()->TMPL->fetch_param('limit');
				if(!empty($entries_limit))
				{
					$object->pagination->per_page = $entries_limit;
				}

				if(!empty($entries_limit))
				{
					$object->pagination->total_pages = intval(floor($object->pagination->total_items / $entries_limit));
				}
			}
			else
			{
				$object->pagination->total_pages = 1;
			}

			$current_page = $object->pagination->current_page;

			// finally we build the pagination
			if(!empty($entries_limit))
			{
				// this is where we need to rest the page links
				$reflector = new ReflectionClass($object->pagination);
				// get the protected var
				$_page_links = $reflector->getProperty('_page_links');
				// change scope so we can change it
				$_page_links->setAccessible(true);
				// change it
				$_page_links->setValue($object->pagination, '');

				$_page_array = $reflector->getProperty('_page_array');
				$_page_array->setAccessible(true);
				$_page_array->setValue($object->pagination, array());

				$object->pagination->build($entries_count->count, $entries_limit);
			}

			// reset vars
			$object->pagination->current_page = $current_page;
			$object->pagination->cur_page = $cur_page;
			$object->pagination->offset = $current_offset;
			$object->pagination->per_page = $per_page;

			//we run into an issue with EE adding an extra next page link based on logic thats already executed. This needs to be at the bottom of the pagination data
			if($object->pagination->total_pages == $object->pagination->current_page)
				$object->pagination->page_next = '';

		}

		$language = $this->transcribe->_set_current_language();


		// do we have facades enabled?
		$transcribe_uri_enabled = ee()->config->item('transcribe_uri_facade');

		if( !empty($transcribe_uri_enabled) && !empty($entry_ids) )
		{
			$query = ee()->db->select('uri, entry_id')
									->from('transcribe_uris')
									->where_in('entry_id', implode(',', $entry_ids))->get();

			foreach($results as $key => $result_row)
			{
				$result[$key]['transcribe_uri'] = FALSE;
				foreach($query->result() as $row)
				{
					if($row->entry_id == $result_row['entry_id'])
					{
						$results[$key]['transcribe_uri'] = $row->uri;
					}
				}
			}
		}
		if(!empty($add_langs))
		{
			$lang_pages = array();
			// get current site_pages
			$site_pages_live = ee()->config->item('site_pages');

			// get lang data in format needed
			$langs_by_id = $this->_get_all_languages(TRUE);

			$entry_langauges = $this->transcribe->_get_language($entry_ids);

			// format this array so we don't loop over it once for every entry
			foreach($entry_langauges as $entry_lang_details)
				$entry_langs_by_entry_id[$entry_lang_details['entry_id']] = $entry_lang_details;

			// add the entry specific lang data
			foreach($results as $key => $result_row)
			{
				$results[$key]['transcribe_entry_lang_abbr'] = $langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->abbreviation;
				$results[$key]['transcribe_entry_lang_name'] = $langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->name;
				$results[$key]['transcribe_entry_lang_id'] = $langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->id;

				// if we have an entry in a different language we need to go aehad and get the proper page_url for it
				if($language['id'] != $langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->id)
				{
					if(empty($lang_pages[$langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->id]))
					{
						$lang_pages[$langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->id] =
						$this->get_site_pages_for_lang($langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]);
					}

					$entry_pages_data = $lang_pages[$langs_by_id[$entry_langs_by_entry_id[$result_row['entry_id']]['language_id']]->id];

					// get returned data and output the uri for the right langauge
					if(!empty($entry_pages_data[$this->site_id]['uris'][$result_row['entry_id']]))
					{
						$results[$key]['transcribe_entry_url'] = $entry_pages_data[$this->site_id]['uris'][$result_row['entry_id']];
					}
				}
				else
				{
					// default language
					if(!empty($site_pages_live[$this->site_id]['uris'][$result_row['entry_id']]))
					{
						$results[$key]['transcribe_entry_url'] = $site_pages_live[$this->site_id]['uris'][$result_row['entry_id']];
					}
				}
			}

		}
		return $results;
	}

	/*
	 * this hook was added in ee 280 for us to modify the search query.
	 * Returns modified query
	*/
	public function transcribe_channel_search_modify_search_query($query, $hash)
	{
		$settings = $this->transcribe->_get_settings();
		if( empty($settings->enable_transcribe) ) return $query;

		// take the query and break it on the WHER
		$query_parts = explode('WHERE', $query);

		if(!empty($query_parts) && count($query_parts) == 2)
		{
			// get currently selected language
			$segments = explode('/', ee()->uri->uri_string);
			// if the first segment is an abbreviation for a language, set as active language
			$is_abbreviation = ee()->db->get_where('transcribe_languages', array('abbreviation' => $segments[0]));
			$is_abbreviation = $is_abbreviation->row();

			if(!empty($is_abbreviation->abbreviation))
			{
				$language_id = $this->transcribe->_set_current_language($is_abbreviation->abbreviation);
			}
			else
			{
				$language_id = $this->transcribe->_set_current_language();
			}

			$query = $query_parts[0].' LEFT JOIN exp_transcribe_entries_languages ON exp_channel_titles.entry_id = exp_transcribe_entries_languages.entry_id WHERE exp_transcribe_entries_languages.language_id = '.$language_id['id'].' AND '.$query_parts[1];
		}
		return $query;
	}

	/**
	 * Extension at channel_entries_tagdata hook
	 *
	 * Modify the row's tagata by replacing variables that generate paths with
	 * transcribe's uri path translator
	 */
	public function transcribe_channel_entries_tagdata( $tagdata, $row, $object )
	{
		//checking to see if Transcribe is enabled for this site
		$settings = $this->transcribe->_get_settings();
		if( empty($settings->enable_transcribe) ) return $tagdata;

		// replace variable tag: url_title_path
		$tagdata = preg_replace("/\{url_title_path=\'([^']*)'\}/", "{exp:transcribe:uri path='$1'}{url_title}", $tagdata);

		// replace variable tag: entry_id_path
		$tagdata = preg_replace("/\{entry_id_path=\'([^']*)'\}/", "{exp:transcribe:uri path='$1'}{entry_id}", $tagdata);

		return $tagdata;
	}

	/**
	 * Extension at channel_module_create_pagination hook
	 *
	 * Override the urls in $object to display the translated url for the
	 * current language
	 */
	public function transcribe_channel_module_create_pagination( $object )
	{
		// check the current version of EE... if it's 280 or greater we remove the hook for this function
		if(version_compare(APP_VER, '2.8.0', '>='))
		{
			// we're on 280 or greater.. need to remove this hook.
			ee()->db->delete('extensions ', array('method' => 'transcribe_channel_module_create_pagination'));
		}

		$disable_transcribe = ee()->TMPL->fetch_param('transcribe');
		if( !empty($disable_transcribe) AND $disable_transcribe == 'disable' ) return false;

		// checking to see if Transcribe is enabled for this site
		$settings = $this->transcribe->_get_settings();
		if( empty($settings->enable_transcribe) ) return FALSE;

		// If SELF does not exists in site_index config item, set
		// site_index to empty. If the language abbreviation is there it
		// will auto-append SELF in the pagination library.

		$object->EE->config->config['site_index'] = (strpos($object->EE->config->config['site_index'], SELF) === FALSE) ? '' : $object->EE->config->config['site_index'];

		// is this a search query?
		if( !empty(ee()->TMPL->module_data['Search']) )
		{
			$search_id = ee()->uri->query_string;

			$pattern = '/\/.+/';
			$replacement = '';
			$search_id = preg_replace($pattern, $replacement, $search_id);

			$search_result = ee()->db->get_where('search', array('search_id' => $search_id), 1)->row();

			if( !empty($search_result) )
			{
				// rewriting the search query here to only reflect entries from our current language.
				$search_query = unserialize(stripslashes($search_result->query));

				// check to see if the query has already been modified
				$already_modified = strstr($search_query, 'transcribe_entries_languages');

				if( $already_modified === FALSE )
				{
					// this means query is not modified and we can continue

					$search_parts = explode('WHERE', $search_query);

					$num_parts = count($search_parts);

					// this will only work when there is no subquery with a where clause... not an issue currently.
					if($num_parts == 2 && !empty($_SESSION['transcribe']['id']))
					{
						$transcribe_join = ' JOIN MDBMPREFIXtranscribe_entries_languages AS te ON t.entry_id = te.entry_id WHERE te.language_id = '.$_SESSION['transcribe']['id'];

						$new_search_query = $search_parts[0].$transcribe_join.' AND'.$search_parts[1];

						// get results to modify the number of pages for the initial results
						$sql = str_replace('MDBMPREFIX', 'exp_', $new_search_query);

						$query = ee()->db->query($sql);
						$object->total_rows = $query->num_rows();

						$new_search_query = addslashes(serialize($new_search_query));

						// query has now been rewritten, insert it into the db.
						ee()->db->where('search_id', $search_id);
						ee()->db->update('search', array('query'  => $new_search_query, 'total_results' => $object->total_rows));
						$_SESSION['search'] = TRUE;
					}

				}

				return false;
			}
		}

		// normal pagination
		$site_url = ee()->functions->fetch_site_index(0);
		$uri_string = $this->transcribe->_uri_reverse_lookup( str_replace($site_url, '', $object->basepath) );

		$object->basepath = $this->transcribe->reduce_slashes($site_url . '/' . $uri_string);

		return $object;
	}


	/**
	 * Extension at session_start hook
	 *
	 * Process the url to load the proper template group and template name
	 * depending on the current language or the language passed in the first
	 * segment.
	 */
	public function transcribe_session_start( $object )
	{
		if (REQ == 'CP') return FALSE;

		// were going to need to do a str_replace on the url string before we explode the segments.
		// might want to switch this so use segment_array
		$route = '';
		$segments = explode('/', ee()->uri->uri_string);

		// following code will check if this is being executed on an Action ID.. if so we'll go ahead and kill the processing.
		$act = ee()->input->get_post('ACT');

		if($act)
		{
			$action_id = ee()->db->get_where('actions', array( 'class'=>'Transcribe', 'method'=>'language_switcher' ), 1);
			$action_id = $action_id->row('action_id');

			// we need to check if this is a dev deamon form submission.
			if(is_numeric($act))
			{
				$submitted_action_id_data = ee()->db->get_where('actions', array('action_id' => $act));

				// do we have a result?
				if($submitted_action_id_data->num_rows() == 1)
				{
					$submitted_action_id_data = $submitted_action_id_data->row();

					// is it the forms class?... if so we don't want this to process... we need to have transcribe continue to run for the Forms module.
					if($submitted_action_id_data->class != 'Forms')
					{
						if($act != $action_id)
						{
							// before we return false we want to set the language.
							// if the first segment is an abbreviation for a language, set as active language
							$is_abbreviation = ee()->db->get_where('transcribe_languages', array('abbreviation' => $segments[0]));
							$is_abbreviation = $is_abbreviation->row();

							if( !empty($is_abbreviation->abbreviation) )
							{
								$current_language = $this->transcribe->_set_current_language($is_abbreviation->abbreviation);
							}
							else
							{
								$no_abbr_lang = ee()->config->item('transcribe_no_abbr');

								if( !empty($no_abbr_lang) )
								{
									if( is_array($no_abbr_lang) )
									{
										$no_abbr_lang = $no_abbr_lang[ee()->config->item('site_short_name')];
									}
										$current_language = $this->transcribe->_set_current_language($no_abbr_lang);
								}
								else
								{
									$current_language = $this->transcribe->_set_current_language();
								}
							}

							ee()->config->_global_vars['transcribe:language_abbreviation'] = $current_language['abbreviation'];
							ee()->config->_global_vars['transcribe:lang_id'] = $current_language['id'];
							ee()->config->_global_vars['transcribe:language_name'] = $current_language['name'];
							ee()->db->save_queries = TRUE;
							// end setting the language.

							return FALSE;
						}
					}
				}
			}
		}

		// tell EE to start tracking the queries
		ee('Database')->getLog()->saveQueries('y');

		// variable to detect if were changing languages
		$switching_lang = ee()->input->get_post('lang', TRUE);

		// start session if it hasn't been already
		if( session_id() == '' ) session_start();

		// if the first segment is an abbreviation for a language, set as active language
		$is_abbreviation = ee()->db->get_where('transcribe_languages', array('abbreviation' => $segments[0]));
		$is_abbreviation = $is_abbreviation->row();

		//set the browser segments to early parse order variables
		$browser_segments = array_map('strtolower', ee()->uri->segment_array());

		if(!empty($is_abbreviation->abbreviation))
			unset($browser_segments['1']);

		for( $i = 1; $i<=10; $i++ )
		{
			ee()->config->_global_vars['transcribe:segment_'.$i] = FALSE;
		}

		$i = 1;

		foreach($browser_segments as $brow_seg)
		{
			ee()->config->_global_vars['transcribe:segment_'.$i] = $brow_seg;
			$i++;
		}

		if(count($browser_segments) > 0)
		{
			ee()->config->_global_vars['transcribe:last_segment'] = $brow_seg;
		}

		//set the current lang details.
		if( !empty($is_abbreviation->abbreviation) )
				Cache::set(array('current_lang', $is_abbreviation->abbreviation), $is_abbreviation);


		// checking to see if Transcribe is enabled for this site
		// we might want to move this check to the top of the function eventually.
		$this->_get_transcribe_settings();
		if( empty($this->transcribe_settings->enable_transcribe) ) return FALSE;

		// set language module wide with _set_current_language call
		if( empty($switching_lang) )
		{
			if( !empty($is_abbreviation->abbreviation) )
			{
				$current_language = $this->transcribe->_set_current_language($is_abbreviation->abbreviation);
			}
			else
			{
				$no_abbr_lang = ee()->config->item('transcribe_no_abbr');

				if( !empty($no_abbr_lang) )
				{
					if( is_array($no_abbr_lang) )
					{
						$no_abbr_lang = $no_abbr_lang[ee()->config->item('site_short_name')];
					}
						$current_language = $this->transcribe->_set_current_language($no_abbr_lang);
				}
				else
				{
					$current_language = $this->transcribe->_set_current_language();
				}

			}
			ee()->config->_global_vars['transcribe:language_abbreviation'] = $current_language['abbreviation'];
			ee()->config->_global_vars['transcribe:lang_id'] = $current_language['id'];
			ee()->config->_global_vars['transcribe:language_name'] = $current_language['name'];
		}
		else
		{
			// set the language were switching to to be our current language
			$current_language = $this->transcribe->_set_current_language($switching_lang);
		}

		// do we have the pages module installed?
		/** check if it's a pages URL.. if so lets grab the associated one. */
		$pages_exists = ee()->db->get_where('modules', array('module_name' => 'Pages'));

		if($pages_exists->num_rows() == 1)
		{
			// this means we have the pages module installed.
			$site_pages = ee()->config->item('site_pages');

			if( !empty($this->transcribe_settings->force_prefix) )
			{
				if( $this->transcribe_settings->force_prefix == 1 || ($this->transcribe_settings->force_prefix == 2 && !empty($current_language['force_prefix'])) )
				{

					$site_pages[$this->site_id]['url'] = ee()->uri->config->config['site_url'].'/'.ee()->config->config['site_index'];

					$site_pages[$this->site_id]['url'] = $this->transcribe->reduce_slashes($site_pages[$this->site_id]['url']);
				}
			}

			// removing all pages items for other languages here.
			$site_pages_entry_ids = array_keys($site_pages[$this->site_id]['uris']);

			$site_pages_entry_languages = $this->transcribe->_get_language($site_pages_entry_ids);

			// are we switching languages
			if( empty($switching_lang) )
			{
				foreach($site_pages_entry_languages as $entry)
				{
					if( $entry['language_id']!= $current_language['id'] )
					{
						unset($site_pages[$this->site_id]['uris'][$entry['entry_id']]);
						unset($site_pages[$this->site_id]['templates'][$entry['entry_id']]);
						continue;
					}
				}

				// $site_pages = $this->_get_related_structure_url($site_pages);
				ee()->config->set_item('site_pages', $site_pages);
			}
		}
		/* End pages support here*/

		if( !empty($is_abbreviation) ) array_shift($segments);


		if(!empty($remove_trailing_slash->var_value) && $remove_trailing_slash->var_value == 'n')
			$needle = '/'.implode('/', $segments);
		else
			$needle = '/'.implode('/', $segments).'/';


		if( !empty($site_pages) AND in_array($needle, $site_pages[$this->site_id]['uris']) )
		{
			// removing the injected segment.
			ee()->uri->uri_string = (empty($segments) ? '' : '/' . implode('/', $segments));

			ee()->uri->segments = array();
			ee()->uri->rsegments = array();
			ee()->uri->_explode_segments();
			ee()->uri->_reindex_segments();

			return FALSE;
		}

		// check if a template exists for segment_1/segment_2
		if( !empty($segments) AND !empty($segments[1]) )
		{
			$route = $this->transcribe->_template_for_route( implode('/', array($segments[0], $segments[1])) );

			if( !empty($route) )
				$segments = array_slice($segments, 2);
		}

		// check if a template exists for segment_1
		if( empty($route) AND !empty($segments) )
		{
			$route = $this->transcribe->_template_for_route($segments[0]);

			if( !empty($route) )
				$segments = array_slice($segments, 1);
		}

		// now if the transcribe segments is turned on lets go ahead and do a check for that segment facade
		$transcribe_uri_enabled = ee()->config->item('transcribe_uri_facade');

		if( !empty($transcribe_uri_enabled) && !empty($segments) )
		{
			// facade is turned on lets translate the segments.
			foreach($segments as $key => $segment)
			{
				$new_uri = ee()->db->select('ct.url_title')
							->from('transcribe_uris as tu')
							->join('channel_titles as ct', 'ct.entry_id = tu.entry_id')
							->join('transcribe_entries_languages as el', 'el.entry_id = tu.entry_id')
							->where('tu.uri', $segment)
							->where('el.language_id', $current_language['id'])
							->get();

				if( $new_uri->num_rows() == 1 )
				{
					// we have a facade
					$last = end(array_keys($segments));
					$segments[$key] = $new_uri->row('url_title');
				}
			}
		}

		ee()->uri->uri_string = $route . (empty($segments) ? '' : '/' . implode('/', $segments));
		ee()->uri->segments = array();
		ee()->uri->rsegments = array();
		ee()->uri->_explode_segments();
		ee()->uri->_reindex_segments();
	}


	public function _get_add_in_langs()
	{
		$add_langs = FALSE;
		// if we have more then one langauge enabled... We'll grab it here.
		$add_langs = ee()->TMPL->fetch_param('transcribe_add_lang');

		if(!empty($add_langs))
		{
			// turn this into an array
			$add_langs = explode('|', $add_langs);
		}

		return $add_langs;
	}

	public function _get_all_languages($id = false)
	{
		$return = FALSE;
		// transcribe_add_lang
		$add_langs = ee()->TMPL->fetch_param('transcribe_add_lang');

		// turn langauges into an array of elements
		if(!empty($add_langs))
		{
			$all_languages = Cache::get(array('all_languages'));

			if(empty($all_languages))
			{
				// get all languages from the db.
				$all_languages = ee()->db->get('transcribe_languages');
				$all_languages = $all_languages->result();

				// put these in the format we need later
				foreach($all_languages as $lang_data)
				{
					$formatted_all_langs[$lang_data->abbreviation] = $lang_data;
				}

				$all_languages = $formatted_all_langs;
				Cache::set(array('all_languages'), $all_languages);
			}

			// do we need to reformat?
			if(!empty($id))
			{
				foreach($all_languages as $lang_data)
				{
					$formatted_all_langs[$lang_data->id] = $lang_data;
				}

				$all_languages = $formatted_all_langs;
			}

			$return = $all_languages;
		}

		return $return;
		// end more then one language enabled function
	}

	/**
	 * Removes URL parameters from the url before processing segments
	 */
	private function _remove_params()
	{
		$pattern = '/[\?|\&].*$/';
		preg_match($pattern, ee()->uri->uri_string, $matches);
		$this->url_params = (isset($matches[0])) ? $matches[0] : '';
		ee()->uri->uri_string = preg_replace($pattern, '', ee()->uri->uri_string);
	}

	/**
	 * Restores URL parameters to the url after processing segments
	 */
	private function _restore_params()
	{
		ee()->uri->uri_string .= $this->url_params;
	}

	public function update_extension( $current='' )
	{
		if( $current == '' OR $current == $this->version )
		{
			return FALSE;
		}

		if(!empty($hooks))
		{
			foreach ($hooks as $hook => $ext_method)
			{
				ee()->db->insert('extensions', array(
					'class'		=> __CLASS__,
					'method'	=> $ext_method,
					'hook'		=> $hook,
					'settings'	=> serialize($this->settings),
					'priority'	=> 9,
					'version'	=> $this->version,
					'enabled'	=> 'y'
				));
			}
		}

		if(!empty($remove_extension))
		{
			foreach( $remove_extension as $method)
			{
				ee()->db->where('method', $method);
				ee()->db->delete('extensions');
			}
		}

		ee()->db->where('class', __CLASS__);
		ee()->db->update('extensions', array('version' => $this->version));
	}

	public function disable_extension()
	{
		ee()->db->where('class', __CLASS__);
		ee()->db->delete('extensions');
	}

	//this funnction used to rewrite the comment and channel urls for use in the templates
	private function rewrite_channel_and_comment_urls($results, $language_id)
	{
		$force = $this->transcribe->_get_settings();

		$language = ee()->db->get_where('transcribe_languages', array('id' => $language_id));
		$language = $language->row_array();
		if( !empty($force->force_prefix))
		{
			if( $force->force_prefix == 1 || ($force->force_prefix == 2 && !empty($language['force_prefix'])) )
			{
				foreach( $results as $key => $row)
				{
					$site_url = ee()->config->item('site_url');

					// channel_url
					$results[$key]['channel_url'] = str_replace($site_url, $site_url.$language['abbreviation'].'/', $results[$key]['channel_url']);

					$results[$key]['channel_url'] = $this->transcribe->reduce_slashes($results[$key]['channel_url']);

					// comment_url
					$results[$key]['comment_url'] = str_replace($site_url, $site_url.$language['abbreviation'].'/', $results[$key]['comment_url']);

					$results[$key]['comment_url'] = $this->transcribe->reduce_slashes($results[$key]['channel_url']);

					// search_results_url
					if( !empty($results[$key]['search_results_url']) )
					{
						$results[$key]['search_results_url'] = str_replace($site_url, $site_url.$language['abbreviation'].'/', $results[$key]['search_results_url']);

						$results[$key]['search_results_url'] = $this->transcribe->reduce_slashes($results[$key]['search_results_url']);
					}
				}
			}
		}

		return $results;
	}

	// this function based on CI DB caching function... modified for use in Transcribe
	function write_cache($name, $object)
	{
		ee()->load->helper('file');
		if ( ! $this->check_cache_path())
		{
			return FALSE;
		}

		$dir_path = $cache_path = APPPATH.'cache/transcribe/';

		$filename = md5($name);

		if ( ! @is_dir($dir_path))
		{
			if ( ! @mkdir($dir_path, DIR_WRITE_MODE))
			{
				return FALSE;
			}

			@chmod($dir_path, DIR_WRITE_MODE);
		}

		if (write_file($dir_path.$filename, serialize($object)) === FALSE)
		{
			return FALSE;
		}

		@chmod($dir_path.$filename, FILE_WRITE_MODE);
		return TRUE;
	}

	// this function based on CI DB caching function... modified for Transcribe
	function check_cache_path()
	{
		$path = APPPATH.'cache/';

		// Add a trailing slash to the path if needed
		$path = preg_replace("/(.+?)\/*$/", "\\1/",  $path);

		if ( ! is_dir($path) OR ! is_really_writable($path))
		{
			// If the path is wrong we'll turn off caching
			return FALSE;
		}

		return TRUE;
	}

	// this function based on CI DB cache... modified for use with Transcribe

	function read_cache($name)
	{
		ee()->load->helper('file');

		if ( ! $this->check_cache_path())
		{
			return FALSE;
		}

		// $filepath = $this->db->cachedir.$segment_one.'+'.$segment_two.'/'.md5($name);
		$filepath = APPPATH.'cache/transcribe/'.md5($name);

		if (FALSE === ($cachedata = read_file($filepath)))
		{
			return FALSE;
		}

		return unserialize($cachedata);
	}

	private function _get_transcribe_settings()
	{
		$this->transcribe_settings = $this->transcribe->_get_settings();
	}



	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
	# Hooks for Adding "Filter by Language" in Content Edit Screen  #
	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #
	# 																#
	# Credit where credit is due:									#
	# The basis of this technique was published by Rob Sanchez	 	#
	# in March 2012 - https://github.com/rsanchez/filter_by_author  #
	# 																#
	# # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

	/**
	 * Checks for a language ID and retrieves a list of related entry_ids which are added as a where_in clause
	 *
	 * @author Bryan Nielsen
	 * @param array $filter_data the original filter data from the search query
	 * @return array    addtional wheres for the query
	 */
	public function transcribe_edit_entries_additional_where($filter_data)
	{
		$_hook_wheres = ee()->extensions->last_call;

		if (ee()->input->post('language_id'))
		{
			// Run query and get entry ids that are in requested language id
			// Then add where_in clause with those entry ids
			$query = ee()->db->select('entry_id')->get_where('transcribe_entries_languages', array('language_id' => ee()->input->post('language_id')));

			if($query->num_rows() > 0) {
				$entry_ids = array();
				foreach($query->result() as $row)
				{
					$entry_ids[] = $row->entry_id;
				}
				$_hook_wheres['entry_id'] = $entry_ids;
			}
		}

		return $_hook_wheres;
	}

	/**
	 * Adds the Filter by language dropdown to the edit entries screen via JS
	 *
	 * @author Bryan Nielsen
	 * @param array $menu the menu array
	 * @return array    the menu array
	 */
	public function transcribe_cp_menu_array($menu)
	{
		if (ee()->extensions->last_call !== FALSE)
		{
			$menu = ee()->extensions->last_call;
		}

		//confirm we're on the edit entries screen
		if (ee()->input->get('C') === 'content_edit' && ! ee()->input->get('M') && version_compare(APP_VER, '2.4.0', '>=') )
		{
			ee()->load->library('javascript');

			ee()->lang->loadfile('transcribe');
			$languages = array('' => lang('transcribe_select_language_default'));

			//get list of available languages
			$query = ee()->db->select('id, name')->get('transcribe_languages');

			foreach ($query->result() as $row)
			{
				$languages[$row->id] = $row->name;
			}

			//add the dropdown filter
			if(version_compare(APP_VER, '2.6.0', '>='))
				$json_data = json_encode(NBS.NBS.form_dropdown('language_id', $languages, NULL, 'id="language_id"'));
			else
				$json_data = ee()->javascript->generate_json(NBS.NBS.form_dropdown('language_id', $languages, NULL, 'id="language_id"'));

			ee()->javascript->output('
				$("form#filterform div.group").append('.$json_data.');
				$("#language_id").on("change", function() {
					$("#search_button").trigger("click");
				});
			');
		}

		return $menu;
	}
}

/* End of File: ext.transcribe.php */
