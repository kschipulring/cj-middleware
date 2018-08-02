<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');


require_once PATH_THIRD . 'transcribe/libraries/Cache.php';

use Transcribe\Cache;

class Transcribe {

	private $EE;
	private $site_id;

	public $routes = array();
	public $return_data = NULL;

	public function __construct()
	{
		$this->EE =& get_instance();
		$this->site_id = $this->EE->config->config['site_id'];
	}

	/**
	 * {exp:transcribe:translate} tag pair
	 *
	 * Parameters:
	 *	id - the variable id to use
	 *	name - the variable name to use
	 *
	 * Replaces content between the tags with the translated content variable of
	 * the current langauge with the id or name specified.
	 */
	public function translate()
	{
		// call the replace function
		return $this->replace();
	}

	/**
	 * {exp:transcribe:replace} single tag
	 *
	 * Parameters:
	 *	id - the variable id to use
	 *	name - the variable name to use
	 *
	 * Outputs the translated content variable of the current language with the
	 * id or name specified.
	 */
	public function replace()
	{
		if( empty($_SESSION['transcribe']['id']) )
		{
			$lang_id = $this->_set_current_language();
			$lang_id = $lang_id['id'];
		}
		else
		{
			$lang_id = $_SESSION['transcribe']['id'];
		}
		// determine variable through id or name
		$variable_id = $this->EE->TMPL->fetch_param('id');
		$variable_name = strtolower($this->EE->TMPL->fetch_param('name'));

		// the following params will be used to pull a variable from a specific site
		$site_name = $this->EE->TMPL->fetch_param('site');
		// language to be passed in as a language abbreviation
		$var_lang =  $this->EE->TMPL->fetch_param('lang');

		//get the site id fom the name
		if( !empty($site_name) )
		{
			$site_id = $this->EE->db->select('site_id')->get_where('sites', array('site_name' => $site_name), 1)->row();

			if( !empty($site_id->site_id) )
				$this->site_id = $site_id->site_id;
		}

		if( !empty($var_lang) )
		{
			$var_lang_id = $this->EE->db->select('id')->get_where('transcribe_languages', array('abbreviation'=> $var_lang), 1)->row();

			if( !empty($var_lang_id->id) )
				$lang_id = $var_lang_id->id;
		}

		// check to see if we have the variables cached already
		$all_vars_for_lang = Cache::get(array('variables',$this->site_id, $lang_id));

		if( empty($all_vars_for_lang) )
		{
			// not cached, grab all vars for the language being queried
			$this->EE->db->select('transcribe_variables.name, transcribe_translations.content, transcribe_variables.id');
			$this->EE->db->join('transcribe_variables', 'transcribe_variables.id = transcribe_variables_languages.variable_id');
			$this->EE->db->join('transcribe_translations', 'transcribe_translations.id = transcribe_variables_languages.translation_id');
			$this->EE->db->where('transcribe_variables_languages.language_id', $lang_id);
			$this->EE->db->where('transcribe_variables_languages.site_id', $this->site_id);
			$variables = $this->EE->db->get('transcribe_variables_languages');
			$variables = $variables->result_array();

			// var name is key since this is generally how people use variables
			foreach($variables as $var)
				$all_vars_for_lang[strtolower($var['name'])] = $var;

			Cache::set(array('variables', $this->site_id, $lang_id), $all_vars_for_lang);
		}

		// were going to set the contet of the tag pair as the variable_name if the var_id and var_name are empty
		if( empty($variable_id) && empty($variable_name))
			$variable_name = $this->EE->TMPL->tagdata;

		if( !empty($variable_id) )
		{
			foreach( $all_vars_for_lang  as $var_name => $var )
				if( $var['id'] == $variable_id )
					$variable = $var['content'];
		}
		elseif( !empty($variable_name) )
		{
			if( !empty($all_vars_for_lang[$variable_name]['content']) )
				$variable = $all_vars_for_lang[$variable_name]['content'];
		}

		if( !empty($variable) )
		{
			$this->return_data = empty($variable) ? $this->EE->TMPL->tagdata : $variable;
		}
		else
		{
			$this->return_data = empty($this->EE->TMPL->tagdata) ? 'Transcribe: Translation not found.' : $this->EE->TMPL->tagdata;
		}

		return $this->return_data;
	}

	/**
	 * {exp:transcribe:uri} single tag
	 *
	 * Parameters:
	 *  id - id of the language to use
	 *  name - name of the language to use
	 *
	 *  Sets the language to be used from the template. This can be used to
	 *  override the default behaviour of Transcribe which is to figure out
	 *  what language to load automatically.
	 */
	public function language()
	{
		$old_language = $_SESSION['transcribe'];

		// determine language through id or name
		$language_id = $this->EE->TMPL->fetch_param('id');
		$language_name = $this->EE->TMPL->fetch_param('name');

		if( !empty($language_id) )
			$this->EE->db->or_where('id', $language_id);

		if( !empty($language_name) )
			$this->EE->db->or_where('name', $language_name);

		$language = $this->EE->db->get('transcribe_languages', 1);
		$language = $language->row();

		if( !empty($language) )
		{
			$this->_set_current_language($language->abbreviation);

			if(!empty($old_language['abbreviation']) AND ($old_language['abbreviation'] != $language->abbreviation))
			{
				$segments = explode('/', $this->EE->session->tracker[0]);

				$template_segments = array_slice($segments, 0, 2);
				$extra_segments = array_slice($segments, 2);

				// blow out the cached index for fetch_site_index before we call the function.
				// this allows us to pull back site index with the correct language abbr applied when needed.
				unset($this->EE->functions->cached_index);

				$new_url  = $this->EE->functions->fetch_site_index(TRUE);
				$new_url .= $this->_uri_reverse_lookup( implode('/', $template_segments) );
				$new_url .= '/' . implode('/', $extra_segments);

				$new_url = $this->reduce_slashes($new_url);

				// send back to last page
				$this->EE->functions->redirect($new_url);
			}
		}
	}

	/**
	 * {exp:transcribe:language_abbreviation} single tag
	 *
	 * Parameters:
	 *	nonet
	 *
	 * Outputs the current languages abbreviation
	 */
	public function language_abbreviation()
	{
		if(!empty($_SESSION['transcribe']['abbreviation']))
			return $_SESSION['transcribe']['abbreviation'];
	}

	/**
	 * {exp:transcribe:uri} single tag
	 *
	 * Parameters:
	 *	path - the path to output
	 *
	 * Outputs the path specified in it's translated state for the current language.
	 * Used as a replacement to {path=""}
	 */
	public function uri($uri_lang = '', $path = '')
	{

		$site_name = $this->EE->TMPL->fetch_param('site');

		if(empty($uri_lang))
		{
			// language to be passed in as a language abbreviation
			$uri_lang =  $this->EE->TMPL->fetch_param('lang');
		}

		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		$trim_slashes = $this->EE->TMPL->fetch_param('trim_slashes');


		// grab the path if it wasn't passed in or if we don't have an entry id
		if( empty($entry_id))
		{
			if(empty($path))
				$path = $this->EE->TMPL->fetch_param('path');
		}
		else
		{
			$path = FALSE;
		}

		$include_site_url = strtolower($this->EE->TMPL->fetch_param('site_url'));

		if($include_site_url == "no")
		{
			// remove last trailing slash
			// Couldn't replicate this bug.. But user experienced it
			$last_char = substr($path, -1);

			if($last_char == '/')
				rtrim($path, "/");
		}

		//get the site id fom the name
		if( !empty($site_name) )
		{
			$site_data= $this->EE->db->get_where('sites', array('site_name' => $site_name), 1)->row();

			if( !empty($site_data->site_id) )
				$this->site_id = $site_data->site_id;
		}

		// get the language id from the abbreviation or entry id
		if( !empty($uri_lang) || !empty($entry_id) )
		{
			if(empty($uri_lang_data) && !empty($entry_id))
			{
				// get the language of this entry id
				$lang = $this->_get_language($entry_id);

				// Grab the lang data
				$uri_lang_data = $this->EE->db->get_where('transcribe_languages', array('id'=> $lang[0]['language_id']), 1)->row();
			}

			if(empty($uri_lang_data))
			{
				$uri_lang_data = $this->EE->db->get_where('transcribe_languages', array('abbreviation'=> $uri_lang), 1)->row();
			}


			if( !empty($uri_lang_data->id) )
			{
				$lang_id = $uri_lang_data->id;
				$_SESSION['transcribe']['uri_override'] = $lang_id;
			}
		}

		$language = $this->_set_current_language();

		// remove the base URL for langauge URI lookup
		// first we'll go ahead and remove it with the language specific segment
		$count = null;
		$site_index = $this->EE->functions->fetch_site_index(TRUE);
		if( '/' !== $site_index )
			$path = str_replace($site_index, '', $path, $count);

		// did our first str_replace hit it?
		if( empty($count) )
		{
			// first str_reaplce didn't hit it.. get less specific
			$index = $this->EE->config->item('site_url').$this->EE->config->item('index_page');
			if( '/' !== $index )
				$path = str_replace($index, '', $path, $count);

			if( empty($count) )
			{
				// get less specific still
				$site_url = $this->EE->config->item('site_url');

				if ( '/' !== $site_url )
					$path = str_replace($site_url, '', $path, $count);
			}
		}

		$path = $this->_uri_reverse_lookup($path, 1);

		// pull vars for categories
		$category_id = $this->EE->TMPL->fetch_param('category_id');
		$category_prefix = $this->EE->TMPL->fetch_param('category_prefix');
		$category_url_indicator = $this->EE->TMPL->fetch_param('category_url_indicator');
		$category_url_title = $this->EE->TMPL->fetch_param('category_url_title');

		if(empty($category_prefix))
		{
			$category_prefix = 'C';
		}

		if( $category_url_indicator == 'yes' )
		{
			// pull the category url indicator here
			$this->EE->db->select('site_channel_preferences');
			$data = $this->EE->db->get('sites');
			$data = $data->row();
			$data = unserialize(base64_decode($data->site_channel_preferences));

			// need to cache this so we don't have a query each time.
			$category_reserved_word = $data['reserved_category_word'];
		}

		// get site settings
		$settings = $this->_get_settings();
		$this->EE->config->set_item('site_index', $this->_set_base($this->EE->config->config['site_index']));

		// build category URL's
		if(!empty($category_reserved_word))
		{
			$path .= '/'.$category_reserved_word;
		}
		if(!empty($category_url_title))
		{
			$path .= '/'.$category_url_title;
		}
		if( !empty($category_id) && !empty($category_prefix) )
		{
			$path .= '/'.$category_prefix.$category_id;
		}
		if( empty($site_name) )
			$site_index = $this->EE->functions->fetch_site_index(1);
		else
		{
			$site_system_preferences = unserialize(base64_decode($site_data->site_system_preferences));
			$site_index = $site_system_preferences['site_url'];
		}

		// run check to see if langauge prefix is currently present... if so remove it and add the uri_lang passed in
		if( !empty($uri_lang) && $uri_lang_data->force_prefix == 1 )
		{
			$site_index = str_replace($_SESSION['transcribe']['abbreviation'].'/', '', $site_index);
			$site_index = $site_index .= $uri_lang.'/';
		}
		elseif( !empty($uri_lang) && $uri_lang_data->force_prefix == 0 )
		{
			$site_index = $this->_remove_abbreviation($site_index).'/';

		}

		// were generating the link based on the passed in entry_id
		if(!empty($entry_id))
		{
			// remove the current langauge abbreviation
			$site_index = $this->_remove_abbreviation($site_index).'/';
			// get the language of this entry id
			$lang = $this->_get_language($entry_id);

			// Grab the lang data
			$uri_lang_data = $this->EE->db->get_where('transcribe_languages', array('id'=> $lang[0]['language_id']), 1)->row();

			// if the abbriviation is needed add it here.
			if( !empty($uri_lang_data->id) && $uri_lang_data->force_prefix == 1)
			{
				$site_index .= $uri_lang_data->abbreviation;
			}
		}

		if($include_site_url == 'no')
			if( !empty($uri_lang) )
				$site_index = $uri_lang.'/';
			else
				$site_index = '';

		$url = $site_index . $path . '/';

		$url = $this->reduce_slashes($url);

		// if trim_slashes
		if(!empty($trim_slashes))
		{
			$url = ltrim($url, '/');
			$url = rtrim($url, '/');
		}

		return $url;
	}

	/**
	 * {exp:transcribe:language_links} tag pair
	 *
	 * Retrieves an array of availabled languages for the current site. Can be
	 * used in the template to create a language switcher.
	 */
	public function language_links()
	{
		$variables = array();

		// get languages that contain translations (variables or entries)

		// updated query to be faster... submitted by William Isted
		// faster switcher code line below removed due to issues found in tests
		$this->EE->db->select('DISTINCT(tl.`id`) as id, name, abbreviation, force_prefix, enabled');
		// $this->EE->db->select('tl.*');
		$this->EE->db->from('transcribe_languages AS tl');
		$this->EE->db->join('transcribe_variables_languages AS tvl', 'tvl.language_id = tl.id AND tvl.site_id = '.$this->site_id, 'LEFT');
		$this->EE->db->join('transcribe_entries_languages AS tel', 'tel.language_id = tl.id', 'LEFT');
		$this->EE->db->join('channel_titles AS ct', 'ct.entry_id = tel.entry_id AND ct.site_id = '.$this->site_id, 'LEFT');
		$this->EE->db->or_where('tvl.site_id IS NOT NULL');
		$this->EE->db->or_where('ct.site_id IS NOT NULL');
		// group_by command added back in was removed for faster language switcher.... removed again
		// $this->EE->db->group_by('tl.id');
		$languages = $this->EE->db->get();
		$languages = $languages->result_array();
		// end updated query

		// the following allows you to make links for only languages that have relationships to the current entry.

		if(empty($languages)) return FALSE;


		// grab all related entries
		$entry_id = (int)$this->EE->TMPL->fetch_param('entry_id');
		$has_entry = $this->EE->TMPL->fetch_param('has_entry');
		$show_all = $this->EE->TMPL->fetch_param('show_all');

		// following line left in on purpose
//		if( !empty($entry_id) && !empty($has_entry) && (is_numeric($entry_id) && $entry_id>=1 && $entry_id==round($entry_id)))
		if( !empty($entry_id) && !empty($has_entry) && is_int($entry_id) )
		{
			// grab the related entries
			$entries = $this->get_related_entries($entry_id);

			if($entries)
			{
				// remove languages that don't have a translation
				foreach($languages as $key => $language)
				{
					$pres = 0;

					foreach($entries as $entry)
					{
						// language is present... this means we get to keep it!
						if($entry['language_id'] == $language['id'])
						{
							$languages[$key]['entry_id'] = $entry['entry_id'];
							$pres = 1;
						}
					}

					// if we don't want to display the no_lang_abbr tag in the results we drop the current empty language
					// we added this as an additonal param so we could avoide changing
					if($pres == 0)
					{
						$no_related[] = $languages[$key];
						unset($languages[$key]);
					}
				}
			}
		}

		// need to make sue languages isn't empty again after we removed the ones that don't have entries.
		if(empty($languages)) return FALSE;

		// get action url
		$trigger_url = $this->action_url();

		// get currently selected language
		$current_language = $this->_set_current_language();

		foreach( $languages as $language )
		{
			// check if language is enabled... if not we'll hide it for non super admins
			if($this->EE->session->userdata('group_id') != 1 && $language['enabled'] == 0 && empty($show_all))
			{
				continue;
			}

			if(empty($language['entry_id']))
			{
				// if it's not set... set it here
				$language['entry_id'] = FALSE;
			}

			// populate template data
			$variables['languages'][] = array(
				'id' => $language['id'],
				'rel:entry_id' => $language['entry_id'],
				'name' => $language['name'],
				'enabled' => $language['enabled'],
				'abbreviation' => $language['abbreviation'],
				'link' => $trigger_url.AMP.'lang='.$language['abbreviation'],
				'current' => ($current_language['id'] == $language['id']) ? TRUE : FALSE,
			);
		}

		// loop over the languages that don't have a related language.
		if(!empty($no_related))
		{
			foreach($no_related as $no_related_entry_lang)
			{
				//the no_en (no_language abbreviation variable) is in place to run logic on
				$variables['no_'.$no_related_entry_lang['abbreviation']] = TRUE;

				//create an en_data tag (abbreviation_data) tag for each unrelated language entry
				$variables[$no_related_entry_lang['abbreviation'].'_data'][] = $no_related_entry_lang;
			}
		}

		// parse the output
		return $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, array($variables));
	}

	/**
	 * Accepts the action of switching the current language from the switcher
	 * and redirects to the properly translated url of the current page (where
	 * the langauge was switched).
	 */
	public function language_switcher()
	{
		$this->EE->functions->cached_index = NULL;
		$_SESSION['transcribe'] = $this->language = NULL;

		// set new language
		$language = $this->EE->input->get('lang');
		$language = empty($language) ? $this->EE->input->post('lang') : $language;

		// lets make sure the language submitted is one of the ones in our system.
		$all_langs = $this->EE->db->select('id, abbreviation')->get('transcribe_languages')->result_array();

		if(!empty($all_langs))
		{
			$real_lang = FALSE;

			foreach($all_langs as $lang)
			{
				// is this a real language?
				if($lang['abbreviation'] == $language)
				{
					$real_lang = TRUE;
					break;
				}
			}
		}

		// if we don't have a real lanugage return user to last page
		if($real_lang === FALSE)
		{
			$this->EE->functions->redirect(array_pop($this->EE->session->tracker));
		}

		$this->_set_current_language($language);
		$tracker_routes = array();

		if( !empty($_SESSION['transcribe_no_match']) )
		{
			foreach($this->EE->session->tracker as $raw_route)
				$tracker_routes[] = trim($raw_route, '/');

			foreach($_SESSION['transcribe_no_match'] as $key => $no_match_route)
				$_SESSION['transcribe_no_match'][$key] =  trim($no_match_route, '/');

			$diff = array_diff($tracker_routes, $_SESSION['transcribe_no_match'] );

			if( !empty($diff) )
			{
				$diff = array_values($diff);
				$uri_string = $diff[0];
			}
		}

		if( !empty($_SESSION['transcribe_no_match_segment']) )
		{
			//transcribe_no_match_segment
			if( empty($diff) )
			{
				foreach($this->EE->session->tracker as $raw_route)
				{
					$raw_route = '/'.ltrim($raw_route, '/');
					$tracker_routes[] = rtrim($raw_route, '/').'/';
				}
			}
			else
			{
				unset($tracker_routes);
				$tracker_routes = array();

				foreach($diff as $raw_route)
				{
					$raw_route = '/'.ltrim($raw_route, '/');
					$tracker_routes[] = rtrim($raw_route, '/').'/';
				}
			}

			foreach($_SESSION['transcribe_no_match_segment'] as $no_match_segment)
			{
				$no_match_segment = '/'.ltrim($no_match_segment, '/');
				$no_match_segment = rtrim($no_match_segment, '/').'/';

				$matches = $this->array_contains($tracker_routes, $no_match_segment);
				$tracker_routes = array_diff($tracker_routes, $matches);

				if( empty($tracker_routes) )
					$tracker_routes[0] = '/';

			}

			//we now have a clean tracker routes array.
			if( !empty($tracker_routes) )
			{
				$tracker_routes = array_values($tracker_routes);
				$uri_string = $tracker_routes[0];
			}
		}

		if( empty($uri_string) )
		{
			$uri_string = isset($this->EE->session->tracker[0]) ? $this->EE->session->tracker[0] : (isset($_SESSION['HTTP_REFERER']) ? $_SESSION['HTTP_REFERER'] : 'index');
		}

		if( $uri_string == 'index' || $uri_string == '/index/')
			$uri_string = '/';


		$segments = explode('/', $uri_string);

		$is_abbreviation = $this->EE->db->get_where('transcribe_languages', array('abbreviation' => $segments[0]))->row();

		if( !empty($is_abbreviation) )
				array_shift($segments);

		// Eventually move the below logic over to the uri_reverse_lookup funciton (to support cats as well etc)

		//remove empty segments & reindex array
		$segments = array_values(array_filter($segments));


// check if a template exists for segment_1/segment_2/segment_3/segment_4/segment_5
		if( !empty($segments) AND !empty($segments[1]) AND !empty($segments[2]) AND !empty($segments[3]) AND !empty($segments[4]))
		{
			$route = $this->_uri_reverse_lookup( implode('/', array($segments[0], $segments[1], $segments[2], $segments[3], $segments[4])), 1);

			if( !empty($route) )
				$segments = array_slice($segments, 5);
		}

		// check if a template exists for segment_1/segment_2/segment_3/segment_4
		if( !empty($segments) AND !empty($segments[2]) AND !empty($segments[3]))
		{
			$route = $this->_uri_reverse_lookup( implode('/', array($segments[0], $segments[1], $segments[2], $segments[3])), 1);

			if( !empty($route) )
				$segments = array_slice($segments, 4);
		}


		// check if a template exists for segment_1/segment_2/segment_3
		if( !empty($segments) AND !empty($segments[1]) AND !empty($segments[2]))
		{
			$route = $this->_uri_reverse_lookup( implode('/', array($segments[0], $segments[1], $segments[2])), 1);

			if( !empty($route) )
				$segments = array_slice($segments, 3);
		}

		// check if a template exists for segment_1/segment_2
		if( !empty($segments) AND !empty($segments[1]) )
		{
			$route = $this->_uri_reverse_lookup( implode('/', array($segments[0], $segments[1])), 1);

			if( !empty($route) )
				$segments = array_slice($segments, 2);
		}

		// check if a template exists for segment_1
		if( empty($route) AND !empty($segments) )
		{
			$route = $this->_uri_reverse_lookup($segments[0], 1);

			if( !empty($route) )
				$segments = array_slice($segments, 1);
		}

		// check if the remaining segments are an entry, category or search.
		// we might need to remove the category check here

		if( !empty($route) AND !empty($segments) )
		{
			$has_entry = FALSE;
			$is_cat = FALSE;

			// check structure segements here
			foreach($segments as $segment)
			{
				// check if segment is an entry
				$this->EE->db->from('channel_titles as ct');
				$this->EE->db->join('transcribe_entries_languages as tel', 'tel.entry_id = ct.entry_id');
				$this->EE->db->where(array('url_title' => $segment, 'tel.language_id' => $this->language['id']));
				$is_entry = $this->EE->db->get()->row();
				if( ! empty($is_entry) ) $has_entry = TRUE;

				// check if segment is a search id
				if( !empty($this->EE->TMPL->module_data['Search']) )
					$is_search = $this->EE->db->get_where('search', array('search_id' => $segment), 1)->row();

				if( ! empty($is_search) ) $has_entry = TRUE;

				// check if segment is a category ID or Category url
				// $prefix_removed is the segment with the category prefix removed
				$prefix_removed = str_replace('C', '', $segment);
				if( !empty($prefix_removed) )
				{
					if(is_numeric($prefix_removed))
					{
						// this means we have a category id
						$is_cat_id = $this->EE->db->get_where('categories', array('cat_id' => $prefix_removed))->row();
						if( !empty($is_cat_id) ) $is_cat = TRUE;
					}
					else
					{
						//checking to see if it's a category URL segment
						$is_cat_url = $this->EE->db->get_where('categories', array('cat_url_title' => $segment))->row();
						if( !empty($is_cat_url) ) $is_cat = TRUE;
					}
				}
			}

			// send to site index
			if( !$has_entry AND !$is_cat )
				$this->EE->functions->redirect($this->EE->functions->fetch_site_index(TRUE));
		}

		if(empty($route))
			$route = '';

		$new_url = $this->reduce_slashes($this->EE->functions->fetch_site_index(TRUE) . '/' . $route . '/' . implode('/', $segments));

		$transcribe_trim_trailing_slash = $this->EE->config->item('transcribe_trim_trailing_slash');

		if($transcribe_trim_trailing_slash)
		{
			$new_url = rtrim($new_url, '/');
		}

		$new_url = '/';
		// send back to last page
		$this->EE->functions->redirect($new_url);
	}

	/*
	 * This function is used to generate the entry ids for the current language in a channel
	 * with the channel name passed in from the teamplate
	 * orignally built for use with the next_prev tags
	 */
	public function entry_ids()
	{
		$return_data = FALSE;

		$language_id = $this->_set_current_language();
		$language_id = $language_id['id'];

		$channel_name = $this->EE->TMPL->fetch_param('channel');

		$channel_id = $this->EE->db->select('channel_id')
									->from('channels')
									->where(array('channel_name'=> $channel_name, 'site_id' => $this->site_id))->get()->row('channel_id');

		$entry_ids = $this->EE->db->select('ct.entry_id')
									->from('channel_titles as ct')
									->join('transcribe_entries_languages as tel', 'tel.entry_id = ct.entry_id')
									->where(array('ct.channel_id'=> $channel_id, 'tel.language_id' => $language_id, 'ct.site_id' => $this->site_id))->get();
		if($entry_ids->num_rows() > 0)
			foreach($entry_ids->result() as $row)
				$return_data[] = $row->entry_id;

		if(!empty($return_data))
		{
			$return_data = implode('|', $return_data);
		}

		return $return_data;
	}


	/**
	 * This function sets routes that can't be switched between languages
	 */
	public function no_match()
	{
		$route = $this->EE->TMPL->fetch_param('url');
		$segment = $this->EE->TMPL->fetch_param('segment');
		$key = "";

		if( !empty($route))
		{
			if( !empty($_SESSION['transcribe_no_match']) )
				$key = array_search($route, $_SESSION['transcribe_no_match']);

			if(!is_int($key))
				$_SESSION['transcribe_no_match'][] = $route;
		}
		if( !empty($segment) )
		{
			if( !empty($_SESSION['transcribe_no_match_segment']) )
				$key = array_search($segment, $_SESSION['transcribe_no_match_segment']);

			if(!is_int($key))
				$_SESSION['transcribe_no_match_segment'][] = $segment;
		}
	}


	public function entry_group_data()
	{

		// $this->EE->db->get_where('transcribe_entries_languages', array('entry_id' => $entry_id))->result();
		$entry_id = $this->EE->TMPL->fetch_param('entry_id');
		$variables = array();

		if(!empty($entry_id))
		{
			$this->EE->db->select('tel.entry_id, el.language_id as language_id' );
			$this->EE->db->from('transcribe_entries_languages AS tel');
			$this->EE->db->join('transcribe_entries_languages AS el', 'el.relationship_id = tel.relationship_id', 'INNER');
			$this->EE->db->where('el.entry_id', $entry_id);
			$result = $this->EE->db->get();

			if($result->num_rows() >= 1)
			{
				foreach($result->result_array() as $row)
				{
					$variables[] = array(
						'set_entry_id' => $row['entry_id'],
						'group_entry_id' => $row['entry_id'],
						'language_id' => $row['language_id']);
				}
			}
		}

		return $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $variables);
	}



	/**
	 * Retrieves the action url for this module
	 */
	public function action_url()
	{
		$trigger_url = $this->EE->functions->fetch_site_index(0);

		$action_id = $this->EE->db->get_where('actions', array( 'class'=>'Transcribe', 'method'=>'language_switcher' ), 1);
		$action_id = $action_id->row('action_id');

		// remove any current abbreviations
		$trigger_url = $this->_remove_abbreviation($trigger_url);

		if( strpos($trigger_url, SELF) === FALSE )
			$trigger_url .= '/' . SELF;

		$trigger_url .= QUERY_MARKER . 'ACT=' . $action_id;

		$url = $this->reduce_slashes($trigger_url);

		return $url;
	}

	/**
	 * Load the default template routes and the translated versions for the
	 * current language.
	 */
	private function _load_template_routes()
	{
		if( empty($this->template_routes) )
		{
			$this->template_routes = array();

			$this->EE->db->select('tl.id as language_id, tl.name as language');
			$this->EE->db->select('tg.group_name as default_group, t.template_name as default_template');
			$this->EE->db->select('ttgl.content as transcribe_group, ttl.content as transcribe_template');
			$this->EE->db->from('template_groups tg');
			$this->EE->db->join('templates t', 'tg.group_id = t.group_id');
			$this->EE->db->join('transcribe_template_groups_languages ttgl', 'ttgl.template_group_id = tg.group_id');
			$this->EE->db->join('transcribe_templates_languages ttl', 'ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id');
			$this->EE->db->join('transcribe_languages tl', 'tl.id = ttgl.language_id');
			$this->EE->db->where('tg.site_id', $this->site_id);
			$this->EE->db->order_by('tg.group_name, t.template_name');
			$routes_result = $this->EE->db->get();
			$routes_result = $routes_result->result();

			foreach( $routes_result as $route )
			{
				$this->template_routes[$route->default_group.'/'.$route->default_template][$route->language_id] = $route->transcribe_group.'/'.$route->transcribe_template;
			}
		}

		return $this->template_routes;
	}

	/**
	 * set the language for this request
	 *
	 * Sets the language for the current request. If an abbreviation is not
	 * passed, we check the session for the language and finally we find the
	 * default language for the site if the previous options have been
	 * exhausted.
	 *
	 * $param string $abbreviation (optional) abbreviation of language to set
	 * @return array $language
	 */
	public function _set_current_language( $abbreviation='' )
	{
		// start session if it hasn't been already
		if( session_id() == '' ) session_start();
		// if cookie exists, restore into session
		$cookie = $this->_get_cookie();
		if( !empty($cookie) AND empty($_SESSION['transcribe']) ) $_SESSION['transcribe'] = $cookie;

		// get settings
		$settings = $this->_get_settings();

		if( empty($this->language) OR ($abbreviation != $this->language['abbreviation']) )
		{
			$language = NULL;

			if( !empty($abbreviation) )
			{
				$language = Cache::get(array('current_lang', $abbreviation));
				$this->language = is_object($language) ? get_object_vars($language) : array();
				if(empty($language))
				{
					$language = $this->EE->db->get_where('transcribe_languages', array('abbreviation' => $abbreviation));
					$language = $language->row();
					$this->language = is_object($language) ? get_object_vars($language) : array();
					Cache::set(array('current_lang', $abbreviation), $language);
				}
			}

			if( empty($language) AND !empty($_SESSION['transcribe']) )
			{
				$this->language = $language = $_SESSION['transcribe'];

				$language_check = Cache::get(array('current_lang', $_SESSION['transcribe']['abbreviation']));

				if(empty($language_check))
					$language_check = $this->EE->db->get_where('transcribe_languages', array('abbreviation' => $_SESSION['transcribe']['abbreviation']), 1)->row();

				if( empty($language_check) )
					unset($this->language, $language, $_SESSION['transcribe']);

			}

			// this means it's the first time someone has been to the site.
			if( empty($language) AND empty($_SESSION['transcribe']) )
			{
				// lets check the browser language and redirect the user base on a match if we have one... if not we'll send them to the default.
				if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
				{
					$lang_code = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
					$language = $this->EE->db->get_where('transcribe_languages', array('abbreviation' => $lang_code));

					// if($language->num_rows() == 1)
					// {
					// 	$action_url = $this->action_url();
					// 	$action_url .=AMP.'lang='.$language->row('abbreviation');

					// 	$this->EE->functions->redirect($action_url);
					// 	exit;
					// }

				}
				// grab the default and roll
				$language_id = empty($settings->language_id) ? 1 : $settings->language_id;
				$language = $this->EE->db->get_where('transcribe_languages', array('id' => $language_id));
				$language = $language->row_array();
				$this->language = $language;
			}

			if( !empty($settings->force_prefix) )
			{
				if( $settings->force_prefix == 1 || ($settings->force_prefix == 2 && !empty($this->language['force_prefix'])) )
				{
					// remove SELF constant from site_index (if found). re-add it later if it exists.
					$index_has_self = FALSE;
					if( strpos($this->EE->config->config['site_index'], SELF) !== FALSE )
					{
						$index_has_self = TRUE;
						$this->EE->config->set_item('site_index', str_replace(SELF, '', $this->EE->config->config['site_index']));
					}

					// remove any current abbreviations
					$this->EE->config->config['site_index'] = $this->_remove_abbreviation($this->EE->config->config['site_index']);

					$site_index = ($index_has_self ? SELF . '/' : '') . $this->language['abbreviation'] . '/';

					$this->EE->config->set_item('site_index', $this->reduce_slashes($site_index));
				}
				else
				{
					$this->EE->config->config['site_index'] = $this->_remove_abbreviation($this->EE->config->config['site_index']);
				}
			}
			else
			{
				$this->EE->config->set_item('site_index', $this->_remove_abbreviation($this->EE->config->config['site_index']));
				// removing preceeding slash... this will be on the site_url
				$this->EE->config->set_item('site_index', ltrim($this->EE->config->config['site_index'], '/' ));
			}
		}

		if( !empty($this->language['id']) && !empty($cookie['id']) && $cookie['id'] != $this->language['id'])
			$this->_save_cookie($this->language);

		return $_SESSION['transcribe'] = $this->language;
	}

	/**
	 * Removes a potential language abbreviation from a string.
	 *
	 * Retrieve a list of all possible abbreviations from the database and
	 * remove the first occurence in the string provided. Return the altered
	 * string
	 *
	 * $param string $string string to remove abbreviation
	 * @return string $string modified string
	 */
	public function _remove_abbreviation( $string )
	{

		// make sure string has a slash on either side of it.
		$string = '/'.$string.'/';
		$string = str_replace('/http', 'http', $string);
		$string = $this->reduce_slashes($string);

		$abbreviations_result = Cache::get(array('abbreviations'));

		if(empty($abbreviations_result))
		{
			$abbreviations_result = $this->EE->db->select('abbreviation')->get('transcribe_languages')->result();
			Cache::set(array('abbreviations'), $abbreviations_result);
		}

		$abbreviations = array();
		foreach($abbreviations_result as $row)
			$abbreviations[] = $row->abbreviation;

		$pattern = '/(\/(?:' . implode('|', $abbreviations) . ')\/)/';
		return preg_replace($pattern, '', $string, 1);
	}

	/**
	 * retrieves the route/relationship
	 *
	 * Retrieves the template_group/template_name routes and their relationship
	 * (the actual template they point to) from the database.
	 *
	 * 1. Get transcribes routes
	 * 2. Get transcribes routes for the default template group
	 * 3. Get ExpressionEngine's normal routes
	 * 4. Get ExpressionEngine's routes for the default template group.
	 *
	 * @return array $routes
	 */
	public function _get_routes()
	{
		if( empty($this->routes) )
		{
			$sql = array();

			// Transcribe Routes
			$sql[] = "SELECT";
			$sql[] = "CONCAT_WS('/', ttgl.content, ttl.content) as route,";
			$sql[] = "CONCAT_WS('/', tg.group_name, t.template_name) as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "WHERE tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;

			$sql[] = "UNION ALL";

			// ExpressionEngine Normal Routes
			$sql[] = "SELECT";
			$sql[] = "CONCAT_WS('/', tg.group_name, t.template_name) as route,";
			$sql[] = "CONCAT_WS('/', tg.group_name, t.template_name) as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "WHERE tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;

			$sql[] = "UNION ALL";

			// ExpressionEngine Default Template Group Routes
			$sql[] = "SELECT";
			$sql[] = "t.template_name as route,";
			$sql[] = "t.template_name as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "WHERE tg.is_site_default = 'y'";
			$sql[] = "AND tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;

			$sql[] = "UNION ALL";

			// Transcribe Default Template Routes
			$sql[] = "SELECT";
			$sql[] = "ttl.content as route,";
			$sql[] = "t.template_name as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "WHERE tg.is_site_default = 'y'";
			$sql[] = "AND tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;

			$sql[] = "UNION ALL";

			$sql[] = "SELECT";
			$sql[] = "ttgl.content as route,";
			$sql[] = "tg.group_name as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "WHERE  t.template_name = 'index'";
			$sql[] = "AND tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id . "";

			$sql[] = "UNION ALL";

			$sql[] = "SELECT";
			$sql[] = "tg.group_name as route,";
			$sql[] = "tg.group_name as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "WHERE t.template_name = 'index'";
			$sql[] = "AND tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id . "";

			$routes = $this->EE->db->query(implode(' ', $sql));
			$this->routes = $routes->result();

			$routes->free_result();
		}

		return $this->routes;
	}

	/**
	 * URI Reverse Lookup to point urls to the properly translated url for the
	 * current langauge. Basically, take every combination of
	 * template_group/template_name from every langauge and figure out where it
	 * should be pointing based on the current langauge.
	 */
	public function _uri_reverse_lookup( $uri_string, $return = 0 )
	{
		$lang_id = $_SESSION['transcribe']['id'];

		if( !empty($_SESSION['transcribe']['uri_override']) )
		{
			$lang_id = $_SESSION['transcribe']['uri_override'];
		}

		$language_abbr = $this->EE->input->get('lang');
		$language_abbr = empty($language_abbr) ? $this->EE->input->post('lang', TRUE) : $language_abbr;

		if(empty($language_abbr) && $return == 1)
		{
			$language_abbr = $this->language_abbreviation();
		}

		/** check if it's a pages URL.. if so lets grab the associated one. */
		$pages_exists = ee()->db->get_where('modules', array('module_name' => 'Pages'));

		// if the Pages module is installed.
		if($pages_exists->num_rows() == 1)
		{
			// if this isn't the current langauge lets get the full pages array.
			if($lang_id != $_SESSION['transcribe']['id'])
			{
				// get site pages from the DB for this site
				$site_pages = ee()->db->select('site_pages')->get_where('sites', array('site_id' => $this->site_id));

				// do we have a result?
				if($site_pages->num_rows() > 0)
				{
					$site_pages = $site_pages->row();

					$current_pages = unserialize(base64_decode($site_pages->site_pages));
				}
			}
			else
			{
				// its in the same language lets get the current pages array.
				$current_pages = $this->EE->config->item('site_pages');
			}

			// check if this URL is a pages URL
			// do we have pages data for this site id?
			if(!empty($current_pages[$this->site_id]['uris']))
			{
				// we do have pages data for this site id
				// lets see if the current URL is in the pages array
				$uri_string_for_pages =  '/' . ltrim($uri_string, '/');
				$entry_id = array_search($uri_string_for_pages, $current_pages[$this->site_id]['uris']);

				if(!empty($entry_id))
				{
					// lets get the related entries to this one.
					$entries = $this->get_related_entries($entry_id, $lang_id);

					// do we related entries?
					if(!empty($entries))
					{
						// we do have related entries
						foreach($entries as $related_entry)
						{
							// do we have a pages page for this related entry?
							if(array_key_exists($related_entry['entry_id'], $current_pages[$this->site_id]['uris']))
							{
								// this means we have a pages item thats related to the current page.. lets use this url.
								$route = $current_pages[$this->site_id]['uris'][$related_entry['entry_id']];

								// trim the slashes
								$route  = ltrim($route, '/');
								$route  = rtrim($route, '/');

								// return this new fangled pages route.
								return $route;
							}
						}
					}
				}
			}
		}
		/** end check if it's a pages URL check. */

		$route_lookup = Cache::get(array('lookup','route', $lang_id));

		// is this caching enabled?
		$file_cache_route_lookup = $this->EE->config->item('transcribe_file_cache_route_lookup');

		// if it's empty we're going to go ahead and check the file level cache.
		if( empty($route_lookup) && !empty($file_cache_route_lookup) )
		{
			if(!empty($file_cache_route_lookup))
			{
				// get cache
				$route_lookup_from_file = $this->read_cache('route_lookup'.$language_id);

				// if we have a cache check to make sure it's valid
				if(!empty($route_lookup_from_file))
				{
					$age = time() - $route_lookup_from_file['stored_time'];
					// is it younger then 15 min?
					if($age < 900)
					{
						$route_lookup = $route_lookup_from_file['route_lookup'];
					}
				}
			}
		}
		if( empty($route_lookup) )
		{
			$sql = array();

			// Routes for template_group/template_name
			$sql[] = "SELECT";
			$sql[] = "CONCAT_WS('/', ttgl.content, ttl.content) as route,";
			$sql[] = "CONCAT_WS('/', ttgl2.content, ttl2.content) as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl2 ON ttgl2.template_group_id = tg.group_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl2 ON ttl2.template_id = t.template_id AND ttl2.language_id = ttgl2.language_id";
			$sql[] = "WHERE tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;
			$sql[] = "AND ttgl2.language_id = " . $lang_id;

			$sql[] = "UNION ALL";

			// Routes for template_group
			$sql[] = "SELECT";
			$sql[] = "ttgl.content as route,";
			$sql[] = "ttgl2.content as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl2 ON ttgl2.template_group_id = tg.group_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl2 ON ttl2.template_id = t.template_id AND ttl2.language_id = ttgl2.language_id";
			$sql[] = "WHERE tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;
			$sql[] = "AND t.template_name = 'index'";
			$sql[] = "AND ttgl2.language_id = " . $lang_id;
			$sql[] = "GROUP BY ttgl.content";

			$sql[] = "UNION ALL";

			// Routes for template_group (default)
			$sql[] = "SELECT";
			$sql[] = "ttgl.content as route,";
			$sql[] = "ttgl2.content as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl2 ON ttgl2.template_group_id = tg.group_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl2 ON ttl2.template_id = t.template_id AND ttl2.language_id = ttgl2.language_id";
			$sql[] = "WHERE tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;
			$sql[] = "AND tg.is_site_default = 'y'";
			$sql[] = "AND t.template_name = 'index'";
			$sql[] = "AND ttgl2.language_id = " . $lang_id;
			$sql[] = "GROUP BY ttgl.content";

			$sql[] = "UNION ALL";

			// Routes for template_name (default)
			$sql[] = "SELECT";
			$sql[] = "ttl.content as route,";
			$sql[] = "ttl2.content as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('template_groups') . " as tg";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('templates') . " as t ON t.group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl ON ttgl.template_group_id = tg.group_id";
			$sql[] = "JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl ON ttl.template_id = t.template_id AND ttl.language_id = ttgl.language_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_template_groups_languages') . " as ttgl2 ON ttgl2.template_group_id = tg.group_id";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_templates_languages') . " as ttl2 ON ttl2.template_id = t.template_id AND ttl2.language_id = ttgl2.language_id";
			$sql[] = "WHERE tg.site_id = " . $this->site_id . " AND t.site_id = " . $this->site_id;
			$sql[] = "AND tg.is_site_default = 'y'";
			$sql[] = "AND ttgl2.language_id = " . $lang_id;

			$route_lookup = $this->EE->db->query(implode(' ', $sql))->result();
			Cache::set(array('lookup','route', $lang_id), $route_lookup);

			if(!empty($file_cache_route_lookup))
			{
				// calulating this can be really taxing to User experience... so let's cache it if caching it is enabled in the config.
				$store['route_lookup'] = $route_lookup;
				$store['stored_time'] = time();

				$this->write_cache('route_lookup'.$language_id, $store);
			}

		}

		$route = array();
		$uri_string = trim($uri_string, '/');
		$segments = explode('/', $uri_string);

		// if the last page was 'index' remove it from segments so the homepage
		// doesn't have it upon switching languages
		if( count($segments) == 1 AND $segments[0] == 'index' )
			unset($segments[0]);

		// check route for segment_1/segment_2
		if( !empty($segments) AND !empty($segments[1]) )
		{
			foreach( $route_lookup as $row )
			{
				if( trim($row->route) == implode('/', array($segments[0], $segments[1])) )
				{
					$route = explode('/', trim($row->relationship));
					$segments[0] = $route[0];
					$segments[1] = $route[1];
					break;
				}
			}
		}

		// check route for segment_1
		if( empty($route) AND !empty($segments) )
		{
			foreach( $route_lookup as $row )
			{
				if( trim($row->route) == $segments[0] )
				{
					$segments[0] = trim($row->relationship);
					break;
				}
			}
		}

		// check route for all other segments
		if( ! Cache::get(array('lookup','entry', $lang_id)) )
		{
			$sql = array();

			$sql[] = "SELECT";
			$sql[] = "cr.url_title as route, tel2.entry_id, ";
			$sql[] = "cr2.url_title as relationship";
			$sql[] = "FROM " . $this->EE->db->dbprefix('transcribe_entries_languages') . " as tel";
			$sql[] = "INNER JOIN " . $this->EE->db->dbprefix('transcribe_entries_languages') . " as tel2 ON tel2.relationship_id = tel.relationship_id";
			$sql[] = "LEFT JOIN " . $this->EE->db->dbprefix('channel_titles') . " as cr ON cr.entry_id = tel.entry_id";
			$sql[] = "LEFT JOIN " . $this->EE->db->dbprefix('channel_titles') . " as cr2 ON cr2.entry_id = tel2.entry_id";
			$sql[] = "WHERE tel2.language_id = " . $lang_id;
			$sql[] = "AND cr.site_id = " . $this->site_id;

			$entry_lookup = $this->EE->db->query(implode(' ', $sql))->result();

			Cache::set(array('lookup','entry', $lang_id), $entry_lookup);
		}
		else
		{
			$entry_lookup = Cache::get(array('lookup','entry', $lang_id));
		}

		foreach( $segments as $key => $segment )
		{
			foreach( $entry_lookup as $row )
			{
				if( trim($row->route) == $segment )
				{
					$segments[$key] = $row->relationship;
					$segment_entry_id[$key] = $row->entry_id;
				}
			}
		}

		// now if the transcribe segments is turned on lets go ahead and do a check for that segment facade
		$transcribe_uri_enabled = $this->EE->config->item('transcribe_uri_facade');

		if( !empty($transcribe_uri_enabled) && !empty($segments) && !empty($segment_entry_id))
		{
			// facade is turned on lets translate the last segment.
			// $last_segment = end($segments);

			// loop thorough all segments to look for ones we have facades for
			foreach($segment_entry_id as $segment_key => $entry_id)
			{
				$new_uri = $this->EE->db->select('uri')
							->where('entry_id', $entry_id)
							->get('transcribe_uris');

				if( $new_uri->num_rows() == 1 )
				{
					// we have a facade
					// $last = end(array_keys($segments));
					$segments[$segment_key] = $new_uri->row('uri');
				}
			}

		}

		if( !empty($_SESSION['transcribe']['uri_override']) )
		{
			unset($_SESSION['transcribe']['uri_override']);
		}

		return implode('/', $segments);
	}

	/**
	 * Get releasted entries returns the related entries to a given entry id
	 *
	 * @method get_related_entries
	 * @param  int $entry_id entry_id in ExpressionEngine that you want results returned for
	 * @return False on NO results or array of related entries
	 */
	private function get_related_entries($entry_id, $language_id = false)
	{
		$return = false;

		// grab the relationship
		$relationship =$this->EE->db
			->select('relationship_id')
			->from('transcribe_entries_languages')
			->where("entry_id", $entry_id)
			->get();

		if($relationship->num_rows() > 0)
		{
			$relationship = $relationship->row('relationship_id');

			// what langauges have a translation?
			$this->EE->db->select('language_id, entry_id')
				->from('transcribe_entries_languages')
				->where('relationship_id ', $relationship);

			if(!empty($language_id))
				$this->EE->db->where('language_id', $language_id);

			$entries = $this->EE->db->get();

			if($entries->num_rows > 0)
				$return = $entries->result_array();
		}

		return $return;
	}

	/**
	 * Return the peoper relationship for the route provided.
	 */
	public function _template_for_route( $uri_string )
	{
		$this->_get_routes();

		foreach( $this->routes as $route )
		{
			if( $route->route == $uri_string )
			{
				return $route->relationship;
			}
		}

		return FALSE;
	}

	// function to grab the language id's for a given list of entry_ids
	public function _get_language($entry_ids)
	{
		$languages = array();

		if(!empty($entry_ids))
		{
			$this->EE->db->where_in('entry_id', $entry_ids);
			$languages = $this->EE->db->get('transcribe_entries_languages');
			$languages = $languages->result_array();
		}

		return $languages;
	}


	public function _save_cookie( $data )
	{
		$expires = 60*60*24*7; // 7 days
		$data = in_array(gettype($data), array('array','object')) ? serialize($data) : $data;

		//check to see if the cookie concent module is installed
		if(version_compare(APP_VER, '2.5.0', '>='))
		{
			if($this->EE->input->cookie('cookies_allowed') == 'y')
			{
				$this->EE->functions->set_cookie('transcribe', $data, $expires);
			}
		}
		else
		{
			$this->EE->functions->set_cookie('transcribe', $data, $expires);

		}


	}

	public function _get_cookie()
	{
		$cookie = $this->EE->input->cookie('transcribe');
		return @unserialize($cookie) ? unserialize($cookie) : $cookie;
	}

	public function _get_settings()
	{
		$settings = Cache::get(array('transcribe_settings'));

		if(empty($settings))
		{
			$settings = $this->EE->db->get_where('transcribe_settings', array('site_id' => $this->site_id), 1)->row();
			Cache::set(array('transcribe_settings'), $settings);
		}

		return $settings;
	}

	/*
	 * This function will go ahead and set the base site url based on if we have a language segment being injected or not.  It will go ahead and return it
	 */
	public function _set_base($basepath)
	{
		$language_abbreviation = $this->language_abbreviation();

		$language = Cache::get(array('current_lang', $language_abbreviation));

		if(empty($language))
		{
			$language = $this->EE->db->get_where('transcribe_languages', array('abbreviation ' => $language_abbreviation));
			$language = $language->row();

			Cache::set(array('current_lang', $language->abbreviation), $language);
		}

		$settings = $this->_get_settings();
		if( !empty($settings->force_prefix) )
		{
			if( $settings->force_prefix == 1 || ($settings->force_prefix == 2 && !empty($language->force_prefix)) )
			{
				// remove SELF constant from site_index (if found). re-add it later if it exists.
				$index_has_self = FALSE;
				if( strpos($basepath, SELF) !== FALSE )
				{
					$index_has_self = TRUE;
					// we might want to change the $this->EE->config->config['site_index'] to be $basepath
					$basepath = str_replace(SELF, '', $this->EE->config->config['site_index']);
				}

				// remove any current abbreviations
				$basepath = $this->_remove_abbreviation($basepath);

				$site_index = ($index_has_self ? SELF . '/' : '') . $language_abbreviation . '/';

				$basepath = $this->reduce_slashes($site_index);
			}
		}
		return $basepath;
	}

	function array_contains($input, $search_value, $strict = false)
	{

		$tmpkeys = array();
		$input = array_flip($input);
		$keys = array_keys($input);

		foreach ($keys as $k)
		{
			if ($strict && strpos($k, $search_value) !== FALSE)
				$tmpkeys[] = $k;
			elseif (!$strict && stripos($k, $search_value) !== FALSE)
				$tmpkeys[] = $k;
		}

		return $tmpkeys;
	}

	/**
	 * function reduce_slashes supports old and new ee versions
	 */
	public function reduce_slashes($string)
	{

		if(version_compare(APP_VER, '2.6.0', '<'))
		{
			$new_string = $this->EE->functions->remove_double_slashes($string);
		}
		else
		{
			$this->EE->load->helper('string');
			$new_string = reduce_double_slashes($string);

		}

		return $new_string;
	}

	// The following functions are from the ext file... We need to make these a helper before this is released @TODO
	// this function based on CI DB caching function... modified for use in Transcribe
	public function write_cache($name, $object)
	{
		$this->EE->load->helper('file');
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
	public function check_cache_path()
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

	public function read_cache($name)
	{
		$this->EE->load->helper('file');

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
}


/* End of File: mod.transcribe.php */
