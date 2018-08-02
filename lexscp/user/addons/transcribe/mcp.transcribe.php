<?php if( ! defined('BASEPATH')) exit('No direct script access allowed');

class Transcribe_mcp {

	private $EE;
	private $base_url;
	private $data = array();

	public function __construct()
	{
		$this->data['base_url'] = ee('CP/URL', 'addons/settings/transcribe/');
	}

	/**
	 * Generates sidbar nav
	 *
	 * @method generateSidebar
	 * @param  string $active Section that is active
	 * @param  int $active_language_id Language id that is currently active
	 * @return sid bar navigation
	 */
	private function generateSidebar($active = NULL, $active_language_id = NULL)
	{
		$sidebar = ee('CP/Sidebar')->make();

		$sidebar->addHeader(lang('transcribe_nav_home'))
			->withUrl(ee('CP/URL', 'addons/settings/transcribe/index'));

		// add languages to the nav here.
		if($active == 'languages')
		{
			$sidebar->addHeader(lang('transcribe_nav_languages'))
			->withUrl(ee('CP/URL', 'addons/settings/transcribe/languages'))
			->isActive()
			->withButton(lang('new'),  ee('CP/URL', 'addons/settings/transcribe/create'));
		}
		else
		{
			$sidebar->addHeader(lang('transcribe_nav_languages'))
			->withUrl(ee('CP/URL', 'addons/settings/transcribe/languages'))
			->withButton(lang('new'),  ee('CP/URL', 'addons/settings/transcribe/create'));
		}


		// add the tempaltes nav item
		$sidebar->addHeader(lang('transcribe_nav_templates'))
			->withUrl(ee('CP/URL', 'addons/settings/transcribe/templates'));

		// are we currently looking at the tempaltes?
		if($active == 'tempaltes')
		{
			// $item = $board_list->addItem($board->board_label, ee('CP/URL')->make($this->base . 'index/' . $board->board_id))
			// 		->withEditUrl(ee('CP/URL')->make($this->base . 'edit/board/' . $board->board_id))
			// 		->withRemoveConfirmation(lang('forum_board') . ': <b>' . $board->board_label . '</b>')
			// 		->identifiedBy($board->board_id);

		}

		// settings nav item
		$sidebar->addHeader(lang('transcribe_nav_settings'))
			->withUrl(ee('CP/URL', 'addons/settings/transcribe/settings'));
	}

	/**
	 * Main Variable screen of the module
	 *
	 * @method index
	 * @return view to manage variables in Transcribe
	 */
	public function index($selected_language_id = NULL)
	{
		// load libraries
		ee()->load->library('table');

		if( ee()->input->post('translation') )
		{
			$language_id = ee()->input->post('language');
			$site_id = ee()->input->post('site');
			$postdata = ee()->input->post('translation');

			foreach( $postdata as $key => $data )
			{
				// if variable name is blank, skip it
				if( empty($data['variable']['name']) AND empty($data['variable']['select']) ) continue;

				// update variable
				if( empty($data['variable']['id']) )
				{
					if( empty($data['variable']['select']) )
					{
						// variable not exists
						ee()->db->insert('transcribe_variables', array('name' => $data['variable']['name']));
						$variable_id = ee()->db->insert_id();
					}
					else
					{
						// selected variable through select box
						$variable_id = $data['variable']['select'];
					}
				}
				else
				{
					ee()->db->where('id', $data['variable']['id']);
					ee()->db->update('transcribe_variables', array('name' => $data['variable']['name']));
				}

				// update translation
				if( empty($data['translation']['id']) )
				{
					// translation not exists
					if( empty($variable_id) && !empty($data['variable']['id']) )
						$variable_id = $data['variable']['id'];

					ee()->db->insert('transcribe_translations', array(
						'content' => $data['translation']['content'],
						'variable_id' => $variable_id
					));
					$translation_id = ee()->db->insert_id();
				}
				else
				{
					ee()->db->where('id', $data['translation']['id']);
					ee()->db->update('transcribe_translations', array('content' => $data['translation']['content']));
				}

				// if we have created a new variable, create the variable -> translation link with the language
				if( !empty($variable_id) AND !empty($translation_id) )
				{
					ee()->db->insert('transcribe_variables_languages', array(
						'variable_id' => $variable_id,
						'translation_id' => $translation_id,
						'language_id' => $language_id,
						'site_id' => $site_id,
					));
				}
			}

			// set the alert and redirect
			$this->alert('success', 'transcribe_variables_save_success');
			ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/index/'.$language_id));
		}

		// get lanugages
		$languages = ee()->db->get('transcribe_languages');
		$languages = $languages->result();

		if( empty($languages) ) ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/create'));

		$languages_dropdown = array(ee()->lang->line('transcribe_select_language_default'));
		foreach( $languages as $language )
		{
			$languages_dropdown[$language->id] = $language->name;
		}
		$this->data['languages_dropdown'] = $languages_dropdown;

		// get sites
		$sites = ee()->db->get('sites');
		$sites = $sites->result();

		$sites_dropdown = array(ee()->lang->line('transcribe_select_site_default'));
		foreach( $sites as $site )
		{
			$sites_dropdown[$site->site_id] = $site->site_label;
		}
		$this->data['sites_dropdown'] = $sites_dropdown;

		// show first translation by default
		if(empty($selected_language_id))
			$selected_language_id = ee()->input->get('language');

		$selected_language_id = empty($selected_language_id) ? ee()->input->get_post('language') : $selected_language_id;
		$selected_language_id = empty($selected_language_id) ? $languages[0]->id : $selected_language_id;
		$this->data['selected_language_id'] = $selected_language_id;

		// limit to current site by default
		$selected_site_id = ee()->input->get('site');
		$selected_site_id = empty($selected_site_id) ? ee()->input->get_post('site') : $selected_site_id;
		$selected_site_id = empty($selected_site_id) ? ee()->config->config['site_id'] : $selected_site_id;
		$this->data['selected_site_id'] = $selected_site_id;

		// get total number of variables for this language to use in pagination
		ee()->db->select('transcribe_variables.id AS variable_id');
		ee()->db->join('transcribe_variables', 'transcribe_variables.id = transcribe_variables_languages.variable_id');
		ee()->db->join('transcribe_translations', 'transcribe_translations.id = transcribe_variables_languages.translation_id');
		ee()->db->where('transcribe_variables_languages.language_id', $selected_language_id);
		ee()->db->where('transcribe_variables_languages.site_id', $selected_site_id);

		// ee()->load->library('pagination');
		$config = array();
		$config['base_url'] = $this->base_url.AMP.'method=index';

		if( ee()->input->get_post('language') )
			$config['base_url'] .= AMP.'language='.ee()->input->get_post("language");

		if( ee()->input->get_post('site') )
			$config['base_url'] .= AMP.'site='.ee()->input->get_post("site");



		$base_url = ee('CP/URL', 'addons/settings/transcribe/index/'.$selected_language_id);
		$config['total_rows'] = ee()->db->get('transcribe_variables_languages')->num_rows();
		$config['per_page'] = 50;
		$current_page = ee()->input->get('page', 1);



		if(empty($current_page))
			$current_page = 1;

		// var_dump($current_page);

		$offset = ($current_page-1) * $config['per_page'];
		$this->data['pagination'] = ee('CP/Pagination', $config['total_rows'] )
				->perPage($config['per_page'])
				->currentPage($current_page)
				->render($base_url);

		// get language variables and translations
		ee()->db->select('transcribe_variables.id AS variable_id, transcribe_variables.name');
		ee()->db->select('transcribe_translations.id AS translation_id, transcribe_translations.content');
		ee()->db->join('transcribe_variables', 'transcribe_variables.id = transcribe_variables_languages.variable_id');
		ee()->db->join('transcribe_translations', 'transcribe_translations.id = transcribe_variables_languages.translation_id');
		ee()->db->where('transcribe_variables_languages.language_id', $selected_language_id);
		ee()->db->where('transcribe_variables_languages.site_id', $selected_site_id);
		ee()->db->order_by('transcribe_variables.name');

		$selected_language_variables = ee()->db->get('transcribe_variables_languages', $config['per_page'], $offset);
		$selected_language_variables = $selected_language_variables->result();

		$this->data['selected_language_variables'] = $selected_language_variables;

		// get unused variables for dropdown
		ee()->db->select('transcribe_variables.*');
		ee()->db->join('transcribe_variables_languages', 'transcribe_variables_languages.variable_id = transcribe_variables.id AND '.ee()->db->dbprefix('transcribe_variables_languages').'.language_id = '.$selected_language_id.' AND '.ee()->db->dbprefix('transcribe_variables_languages').'.site_id = '.$selected_site_id, 'LEFT');
		ee()->db->where('transcribe_variables_languages.id IS NULL');
		ee()->db->group_by('transcribe_variables.id');
		ee()->db->order_by('transcribe_variables.name');
		$unused_variables = ee()->db->get('transcribe_variables');

		$unused_variables = $unused_variables->result();

		$unused_variables_dropdown = array('Textfield:');
		foreach( $unused_variables as $variable )
		{
			$unused_variables_dropdown[$variable->id] = $variable->name;
		}
		$this->data['unused_variables_dropdown'] = $unused_variables_dropdown;

		ee()->cp->add_js_script(array('ui' => array('core', 'position', 'widget')));

		$this->generateSidebar('variables');
		$this->addModal('log-replace');

		return ee()->load->view('index', $this->data, TRUE);
	}


	public function deletevar($var_id)
	{
		var_dump($var_id);
		exit;
	}

	/**
	 * Function to display the temaplte translation screen
	 *
	 * @method templates
	 * @param  int $lang_id Language id to display
	 * @return View to enter translations
	 */
	public function templates($lang_id = false)
	{
		// load libraries
		ee()->load->library('table');

		// get lanugages
		$languages = ee()->db->get('transcribe_languages');
		$languages = $languages->result();

		if( empty($languages) ) ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/create'));

		$languages_dropdown = array(ee()->lang->line('transcribe_select_language_default'));
		foreach( $languages as $language )
		{
			$languages_dropdown[$language->id] = $language->name;
		}
		$this->data['languages_dropdown'] = $languages_dropdown;

		// get sites
		$sites = ee()->db->get('sites');
		$sites = $sites->result();

		$sites_dropdown = array(ee()->lang->line('transcribe_select_site_default'));
		foreach( $sites as $site )
		{
			$sites_dropdown[$site->site_id] = $site->site_label;
		}
		$this->data['sites_dropdown'] = $sites_dropdown;

		// show first translation by default

		$selected_language_id = $lang_id;
		$selected_language_id = empty($selected_language_id) ? ee()->input->post('language') : $selected_language_id;
		$selected_language_id = empty($selected_language_id) ? $languages[0]->id : $selected_language_id;
		$this->data['selected_language_id'] = $selected_language_id;

		// limit to current site by default
		$selected_site_id = ee()->input->get('site');
		$selected_site_id = empty($selected_site_id) ? ee()->input->post('site') : $selected_site_id;
		$selected_site_id = empty($selected_site_id) ? ee()->config->config['site_id'] : $selected_site_id;
		$this->data['selected_site_id'] = $selected_site_id;

		// get template groups
		ee()->db->select('template_groups.group_id, template_groups.group_name, transcribe_template_groups_languages.content, transcribe_template_groups_languages.language_id');
		ee()->db->where('template_groups.site_id', $selected_site_id);
		ee()->db->join('transcribe_template_groups_languages', 'transcribe_template_groups_languages.template_group_id = template_groups.group_id AND '.ee()->db->dbprefix('transcribe_template_groups_languages').'.language_id = '.$selected_language_id, 'LEFT');
		$template_groups = ee()->db->get('template_groups');
		$template_groups = $template_groups->result();
		$this->data['template_groups'] = $template_groups;

		// get template groups
		ee()->db->select('templates.template_id, templates.group_id, templates.template_name, transcribe_templates_languages.content, transcribe_templates_languages.language_id');
		ee()->db->where('templates.site_id', $selected_site_id);
		ee()->db->join('transcribe_templates_languages', 'transcribe_templates_languages.template_id = templates.template_id AND '.ee()->db->dbprefix('transcribe_templates_languages').'.language_id = '.$selected_language_id, 'LEFT');
		$templates = ee()->db->get('templates');
		$templates = $templates->result();
		$this->data['templates'] = $templates;

		$this->generateSidebar('templates');

		return ee()->load->view('templates', $this->data, TRUE);
	}

	/**
	 * Process the temaplte translation for post
	 *
	 * @method postTemplates
	 * @return redirect for success of fail
	 */
	public function postTemplates()
	{
		if( ee()->input->post('templates') )
		{
			$language_id = ee()->input->post('language');
			$site_id = ee()->input->post('site');
			$postdata = ee()->input->post('templates');

			// update group translation
			ee()->db->where(array(
				'language_id' => $language_id,
				'site_id' => $site_id,
			));
			ee()->db->delete('transcribe_template_groups_languages');

			$groups_batch = array();
			foreach( $postdata['groups'] as $group ) {
				if( !empty($group['content']) )
				{
					$groups_batch[] = array(
						'content' => trim($group['content']),
						'template_group_id' => $group['group_id'],
						'language_id' => $language_id,
						'site_id' => $site_id,
					);
				}
			}
			if( !empty($groups_batch) )
				ee()->db->insert_batch('transcribe_template_groups_languages', $groups_batch);

			// update templates translation
			ee()->db->where(array(
				'language_id' => $language_id,
				'site_id' => $site_id,
			));
			ee()->db->delete('transcribe_templates_languages');

			$templates_batch = array();
			foreach( $postdata['templates'] as $template ) {
				if( !empty($template['content']) )
				{
					$templates_batch[] = array(
						'content' => trim($template['content']),
						'template_id' => $template['template_id'],
						'language_id' => $language_id,
						'site_id' => $site_id,
					);
				}
			}
			if( !empty($templates_batch) )
				ee()->db->insert_batch('transcribe_templates_languages', $templates_batch);

			// set success and redirect
			$this->alert('success', 'transcribe_templates_save_success');
			ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/templates/'.$language_id));
		}
	}

	/**
	 * Displays the settings for each language
	 *
	 * @method languages
	 * @return view of langauges settings form
	 */
	public function languages()
	{

		// get lanugages
		$languages = ee()->db->get('transcribe_languages');
		$languages = $languages->result();

		// if we dont' have a language redirect here
		if( empty($languages) ) ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/create'));

		//build out the view
		$vars['sections'] = $this->getLanguagesForm($languages);
		// $vars['errors'] = $result;

		$vars += array(
			'base_url' => ee('CP/URL', 'addons/settings/transcribe/postLanguages'),
			'cp_page_title' => lang('transcribe_nav_languages'),
			'save_btn_text' => 'btn_save_settings',

			'save_btn_text_working' => 'btn_saving'
			);

		// generate navigation
		$this->generateSidebar('settings');

		return array(
			'heading' => $vars['cp_page_title'],
			'breadcrumb' => array(
				ee('CP/URL', 'addons/settings/transcribe/')->compile() => lang('transcribe_module_name')
				),
			'body' => ee('View')->make('transcribe:languages')->render($vars)
		);
	}

	/**
	 * Saves langauge settigns on post
	 *
	 * @method postLanguages
	 * @return redirect back to languages page with flash data
	 */
	public function postLanguages()
	{

		// do we have a form submission?
		if( ee()->input->post('language') )
		{

			$languages = ee()->input->post('language');

			foreach( $languages as $key => $language )
			{
				// we need to convert this back from a y or n to a 1 or 0
				if( $language['enabled'] == 'y')
				$language['enabled'] = 1;
			else
				$language['enabled'] = 0;

			if( $language['force_prefix'] == 'y')
				$language['force_prefix'] = 1;
			else
				$language['force_prefix'] = 0;


				ee()->db->where('id', $key);
				ee()->db->update('transcribe_languages', array(
					'name' => $language['name'],
					'abbreviation' => $language['abbreviation'],
					'force_prefix' => $language['force_prefix'],
					'enabled' => $language['enabled']
				));
			}

			// set success message and redirect
			$this->alert('success', 'transcribe_languages_save_success');
			ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/languages'));
		}

		// set the success message and redirect
		$this->alert('issue', 'transcribe_languages_save_fail');
		ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/languages'));
	}

	/**
	 * Creates a new languages
	 *
	 * @method create
	 * @return view to create a new language
	 */
	public function create()
	{
		// get the form fields to populate this view
		$vars['sections'] = $this->getLanguagesNewForm();

		$vars += array(
			'base_url' => ee('CP/URL', 'addons/settings/transcribe/postCreate'),
			'cp_page_title' => lang('transcribe_nav_languages'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving'
			);

		// generate navigation
		$this->generateSidebar('settings');

		return array(
			'heading' => $vars['cp_page_title'],
			'breadcrumb' => array(
				ee('CP/URL', 'addons/settings/transcribe/')->compile() => lang('transcribe_module_name')
				),
			'body' => ee('View')->make('transcribe:languages')->render($vars)
		);

	}

	/**
	 * Creats a language on post
	 *
	 * @method postCreate
	 * @return redirects back to create on error or langauges page on success
	 */
	public function postCreate()
	{
		// load libraries
		ee()->load->library('form_validation');

		// setup validation
		ee()->form_validation->set_rules('language', 'language', 'required');
		ee()->form_validation->set_rules('abbreviation', 'abbreviation', 'required');

		if( ee()->form_validation->run() )
		{
			// get langauge count
			$language_count = ee()->db->count_all_results('transcribe_languages');

			$language_name = ee()->input->post('language');
			$abbreviation = ee()->input->post('abbreviation');
			$assign_entries = ee()->input->post('assign_entries');

			ee()->db->insert('transcribe_languages', array('name' => $language_name, 'abbreviation' => $abbreviation));
			$language_id = ee()->db->insert_id();

			if( $assign_entries == 'y' )
			{
				ee()->db->select('entry_id');
				$entries = ee()->db->get_where('channel_titles', array('site_id' => ee()->config->config['site_id']));
				$entries = $entries->result();

				foreach( $entries as $entry )
				{
					ee()->db->insert('transcribe_entries_languages', array(
						'entry_id' => $entry->entry_id,
						'language_id' => $language_id,
						'relationship_id' => uniqid(),
					));
				}
			}

			$this->alert('success', 'transcribe_language_create_success');

			if( empty($language_count) )
				ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/settings'));
			else
				ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/languages'));
		}
		else
		{
			$this->alert('issue', validation_errors());
			ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/create'));
		}
	}

	/**
	 * Displays settings view
	 *
	 * @method settings
	 * @return view of settings form
	 */
	public function settings()
	{
		// get available languages
		$languages = ee()->db->get('transcribe_languages');
		$languages = $languages->result();

		// if we don't have any langauges lets redirect to create one
		if( empty($languages) ) ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/create'));

		$languages_dropdown = array('' => ee()->lang->line('transcribe_select_none'));

		// put languages in an array
		foreach( $languages as $language )
			$languages_dropdown[$language->id] = $language->name;


		$table = ee('CP/Table', array('autosort' => false, 'sortable' => false));

		// set the table columns for the main page
		$table->setColumns(
					array(
						lang('transcribe_col_label_site'),
						lang('transcribe_col_label_default') => array('encode' => FALSE ),
						lang('transcribe_col_use_url_prefix') => array('encode' => FALSE ),
						lang('transcribe_col_enable_for_site') => array('encode' => FALSE )
					));

		$table->setData($this->getSettingsColData($languages_dropdown));

		$vars['base_url'] = ee('CP/URL', 'addons/settings/transcribe');
		$vars['table'] = $table->viewData($vars['base_url']);

		// generate nav
		$this->generateSidebar();
		return array(
			'heading' => lang('transcribe_module_name'),
			'body' => ee('View')->make('transcribe:settings')->render($vars)
		);
	}

	/**
	 * Processes and saves posted settings
	 *
	 * @method postSettings
	 * @return redirect back to settings page with alert
	 */
	public function postSettings()
	{
		// do we have a form submission?
		if( ee()->input->post('default') && ee()->input->post('force_prefix') && ee()->input->post('enable_transcribe') )
		{
			$settings = ee()->input->post('default');
			$force_prefix = ee()->input->post('force_prefix', TRUE);
			$enable_transcribe = ee()->input->post('enable_transcribe', TRUE);

			foreach( $settings as $site_id => $language_id )
			{
				// does setting exist?
				$exists = ee()->db->get_where('transcribe_settings', array('site_id' => $site_id), 1);
				$exists = $exists->row();

				if( empty($exists) )
				{
					ee()->db->insert('transcribe_settings', array(
						'site_id' => $site_id,
						'language_id' => $language_id,
						'force_prefix'=> $force_prefix[$site_id],
						'enable_transcribe'=> $enable_transcribe[$site_id],
					));
				}
				else
				{
					ee()->db->where('id', $exists->id);
					ee()->db->update('transcribe_settings', array(
						'site_id' => $site_id,
						'language_id' => $language_id,
						'force_prefix'=> $force_prefix[$site_id],
						'enable_transcribe'=> $enable_transcribe[$site_id],
					));
				}
			}

			$this->alert('success', 'transcribe_settings_save_success');
			ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/settings'));
		}
	}

	/**
	 * Displays delete var confirmation
	 *
	 * @method delete_var_confirm
	 * @return view of delete variable ocnfirmation
	 */
	public function delete_var_confirm($variable_id, $translation_id)
	{
		if( !empty($translation_id) && $variable_id )
		{
			ee()->db->select('transcribe_variables.id AS variable_id, transcribe_variables.name');
			ee()->db->select('transcribe_translations.id AS translation_id, transcribe_translations.content');
			ee()->db->join('transcribe_variables', 'transcribe_variables.id = transcribe_variables_languages.variable_id');
			ee()->db->join('transcribe_translations', 'transcribe_translations.id = transcribe_variables_languages.translation_id');
			ee()->db->where('transcribe_translations.id', $translation_id);
			$this->data['var']  = ee()->db->get('transcribe_variables_languages')->row();
			//create deket kubj
			$delete_link = ee('CP/URL', 'addons/settings/transcribe/delete_var/' . $variable_id.'/'.$translation_id);
			$this->data['delete_link'] = '<a style="color:#ffffff; text-decoration: none;" href="'.$delete_link.'">'.lang('delete').'</a>';

			$content =  ee()->load->view('modal_delete', $this->data, TRUE);

			$this->sendModalContent($content);
		}
	}

	/**
	 * Deletes a variable in Transcribe
	 *
	 * @method delete_var
	 * @return redirects after deleting a variable
	 */
	public function delete_var($variable_id, $translation_id)
	{
		// $translation_id = (int)ee()->input->get('ID');


		// // get the variable id
		// $variable_id = ee()->db->select('variable_id')
		// 							->where('id', $translation_id)
		// 							->get('transcribe_translations')->row();

		// delete db rows for the translation of this var
		ee()->db->delete('transcribe_variables_languages ', array('translation_id' => $translation_id));
		ee()->db->delete('transcribe_translations ', array('id' => $translation_id));

		// are there other variables with this id?
		$other_variables = ee()->db->select('id')
										->where('variable_id', $variable_id)->get('transcribe_translations');

		// if not lets go ahead and delete the variable
		if($other_variables->num_rows() == 0)
			ee()->db->delete('transcribe_variables ', array('id' => $variable_id));

		// set the alert
		$this->alert('success', 'transcribe_var_deleted');
		ee()->functions->redirect(ee('CP/URL', 'addons/settings/transcribe/'));
	}

	/**
	 * Used to get the table cols for the settings page
	 *
	 * @method getSettingsColData
	 * @param  array $languages_dropdown of languages for use in a dropdown
	 * @return array of table col data for the population of a table
	 */
	private function getSettingsColData($languages_dropdown)
	{
		// get Settings
		$site_settings = ee()->db->get('transcribe_settings');
		$site_settings = $site_settings->result();

		$site_defaults = array();

		// put the site details in an array/
		foreach( $site_settings as $setting )
		{
			$site_defaults[$setting->site_id]['lang'] = $setting->language_id;
			$site_defaults[$setting->site_id]['force_prefix'] = $setting->force_prefix;
			$site_defaults[$setting->site_id]['enable_transcribe'] = $setting->enable_transcribe;
		}

		// set the force prefix options
		$force_prefix_options = array('0' => ee()->lang->line('transcribe_select_no'), '1' => ee()->lang->line('transcribe_select_yes'), '2' => ee()->lang->line('transcribe_per_language'));

		// set enable transcribe options
		$enable_transcribe_options = array('0' => ee()->lang->line('transcribe_select_no'), '1' => ee()->lang->line('transcribe_select_yes'));

		// get the sites... we're going to display the settings one per line
		$sites = ee()->db->get('sites');
		$sites = $sites->result();

		// set defaults
		$x = 0;
		$col_data = array();

		// loop over the sites... we're going to populate our col_data here
		foreach($sites as $site)
		{
			$col_data[$x][] = $site->site_label;

			// this needs to be hte HTML dropdown here.. with the selected one
			$col_data[$x][] = form_dropdown('default[' . $site->site_id . ']', $languages_dropdown, (empty($site_defaults[$site->site_id]['lang']) ? NULL : $site_defaults[$site->site_id]['lang']));

			// thsi needs to be html dropdown with the correct one selected
			$col_data[$x][] = form_dropdown('force_prefix[' . $site->site_id . ']', $force_prefix_options, (empty($site_defaults[$site->site_id]['force_prefix']) ? NULL : $site_defaults[$site->site_id]['force_prefix']));

			// this needs to be the HTML drop down with the correct one seleted.
			$col_data[$x][] = form_dropdown('enable_transcribe[' . $site->site_id . ']', $enable_transcribe_options, (empty($site_defaults[$site->site_id]['enable_transcribe']) ? NULL : $site_defaults[$site->site_id]['enable_transcribe']));

			$x++;
		}

		return $col_data;
	}

	/**
	 * builds out the array for the langauge settings
	 *
	 * @method getLanguagesForm
	 * @param  array $languages array of objects.. one for each language
	 * @return array to build out settings view in CP
	 */
	public function getLanguagesForm($languages)
	{
		$first = true;
		// loop over each langauge and build the form elemets out for it.
		foreach( $languages as $language )
		{

			// first element can't have a name or it displays ugly double bar at the top.
			if($first)
				$name = 0;
			else
				$name = $language->name;

			// set the values here for the form input
			if( $language->enabled == 1)
				$language->enabled = 'y';
			else
				$language->enabled = 'n';

			if( $language->force_prefix == 1)
				$language->force_prefix = 'y';
			else
				$language->force_prefix = 'n';

			// set for fields here
			$form[$name][0]['title'] = lang('transcribe_label_language');
			$form[$name][0]['desc'] = '';
			$form[$name][0]['fields'] = array(
				'language['.$language->id.'][name]' => array('type' => 'text',
										'value' => $language->name));

			$form[$name][1]['title'] = lang('transcribe_label_abbreveation');
			$form[$name][1]['desc'] = '';
			$form[$name][1]['fields'] = array(
				'language['.$language->id.'][abbreviation]' => array('type' => 'text',
										'value' => $language->abbreviation));

			$form[$name][2]['title'] = lang('transcribe_col_use_url_prefix');
			$form[$name][2]['desc'] = '';
			$form[$name][2]['fields'] = array(
				'language['.$language->id.'][force_prefix]' => array('type' => 'yes_no',
									'value' => $language->force_prefix));

			$form[$name][3]['title'] = lang('transcribe_language_enabled');
			$form[$name][3]['desc'] = '';
			$form[$name][3]['fields'] = array(
				'language['.$language->id.'][enabled]' => array('type' => 'yes_no',
									'value' => $language->enabled));

			$first = false;
		}

		return $form;
	}

	/**
	 * Returns the array for the form creation of a new language
	 *
	 * @method getLanguagesNewForm
	 * @return array to build form needed
	 */
	public function getLanguagesNewForm()
	{
		// set for fields here
		$form[0][0]['title'] = lang('transcribe_label_language');
		$form[0][0]['desc'] = '';
		$form[0][0]['fields'] = array(
			'language' => array('type' => 'text'));

		$form[0][1]['title'] = lang('transcribe_label_abbreveation');
		$form[0][1]['desc'] = '';
		$form[0][1]['fields'] = array(
			'abbreviation' => array('type' => 'text'));

		$languages = ee()->db->get('transcribe_languages');
		$languages = $languages->result();

		// if we don't have lanaguages let people assign all of the entires to this language.
		if(empty($languages))
		{
			$form[0][2]['title'] = lang('transcribe_label_assign_entries');
			$form[0][2]['desc'] = '';
			$form[0][2]['fields'] = array(
					'assign_entries' => array('type' => 'yes_no'));
			}


		return $form;
	}


	///////////////////////
	// Helper functions  //
	///////////////////////

	/**
	 * Sets the page alert
	 *
	 * @method alert
	 * @param  string $type Success or Issue alert?
	 * @param  string $title lang file string to display as the title
	 * @param  string $body String of body contents.
	 * @return cp alert
	 */
	public function alert($type, $title, $body = false)
	{
		// check the type and set the message box
		if($type == 'success')
		{
			// does it have body text?
			if($body)
			{
				ee('CP/Alert')->makeBanner('box')
					->asSuccess()
					->withTitle(lang($title))
					->addToBody(lang($body))
					->defer();
			}
			else
			{
				// no body text
				ee('CP/Alert')->makeBanner('box')
					->asSuccess()
					->withTitle(lang($title))
					->defer();
			}

		}
		else
		{
			// does it have body text?
			if($body)
			{
				ee('CP/Alert')->makeBanner('box')
						->asIssue()
						->withTitle(lang($title))
						->addToBody(lang($body))
						->defer();
			}
			else
			{
				// no body text
				ee('CP/Alert')->makeBanner('box')
						->asIssue()
						->withTitle(lang($title))
						->defer();
			}
		}
	}

	/**
	 * Adds a modal for the view... with out content in it
	 *
	 * @method addModal
	 * @param  strin $name Name we want the modal to have
	 */
	public function addModal($name)
	{
		// if we had a name passed in of "replace-log" we would need a link like the following to trigger the modal.
		// please note.. the way EEHarbor loads the modal, it requires us to have a href defined to the function
		// we want to hit.
		// <a href="'.ee('CP/URL', 'addons/settings/safeharbor_lite/log').'/'.$backup->id.'" class="m-link replace-log" rel="modal-log-replace" title="remove">Log</a>';

		// Add modal for log details
		// ee('CP/Modal')->addModal(
		//     'replace-details',
		//     ee('View')->make('ee:_shared/modal')->render(array(
		//         'name'     => 'modal-replace-details',
		//         'contents' => ''
		//     ))
		// );

		// EEHarbor leaves all contents blank so we can load it form aJax
		$modal_vars = array('name' => 'modal-'.$name, 'contents' => '');
		$modal = ee('View')->make('ee:_shared/modal')->render($modal_vars);
		ee('CP/Modal')->addModal('modal-'.$name, $modal);
	}


	/**
	 * Sends data to the modal request and dies so we don't get the whole CP in our modal
	 *
	 * @method sendModalContent
	 * @param  strig $content any content we want... txt or HTML etc
	 * @return echo's content and exites
	 */
	public function sendModalContent($content)
	{
		if( ob_get_length() > 0 ) ob_end_clean();
		echo $content;
		exit;
	}

}

/* End of File: mcp.transcribe.php */
