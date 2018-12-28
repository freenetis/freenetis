<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is released under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Controller performs action for change preferences and settings of system.
 * 
 * @package Controller
 */
class Settings_Controller extends Controller
{

	private $links;
	
	/**
	 * Definitions of modules and their dependencies
	 * 
	 * @author Michal Kliment
	 * @var array 
	 */
	public static $modules = array
	(
		'allowed_subnets'		=> array
		(
			'name'			=> 'allowed_subnets_enabled',
			'dependencies'	=> array('notification', 'redirection')
		),
		'approval'				=> array
		(
			'name'			=> 'approval_enabled',
			'dependencies'	=> array()
		),
		'connection_request'	=> array
		(
			'name'			=> 'connection_request_enable',
			'dependencies'	=> array('networks'),
		),
		'email'					=> array
		(
			'name'			=> 'email_enabled',
			'dependencies'	=> array()
		),
		'finance'				=> array
		(
			'name'			=> 'finance_enabled',
			'dependencies'	=> array()
		),
		'forgotten_password'	=> array
		(
			'name'			=> 'forgotten_password',
			'dependencies'	=> array('email')
		),
		'membership_interrupt'	=> array
		(
			'name'			=> 'membership_interrupt_enabled',
			'dependencies'	=> array()
		),
		'monitoring'			=> array
		(
			'name'			=> 'monitoring_enabled',
			'dependencies'	=> array('networks', 'email', 'notification')
		),
		'networks'				=> array
		(
			'name'			=> 'networks_enabled',
			'dependencies'	=> array()
		),
		'notification'			=> array
		(
			'name'			=> 'notification_enabled',
			'dependencies'	=> array()
		),
		'phone_invoices'		=> array
		(
			'name'			=> 'users_enabled',
			'dependencies'	=> array()
		),
		'qos'					=> array
		(
			'name'			=> 'qos_enabled',
			'dependencies'	=> array('networks')
		),
		'redirection'			=> array
		(
			'name'			=> 'redirection_enabled',
			'dependencies'	=> array('networks', 'notification')
		),
		'self_registration'		=> array
		(
			'name'			=> 'self_registration',
			'dependencies'	=> array()
		),
		'sms'					=> array
		(
			'name'			=> 'sms_enabled',
			'dependencies'	=> array()
		),
		'snmp'					=> array
		(
			'name'			=> 'snmp_enabled',
			'dependencies'	=> array('networks')
		),
		'cgi'					=> array
		(
			'name'			=> 'cgi_enabled',
			'dependencies'	=> array('networks')
		),
		'ulogd'					=> array
		(
			'name'			=> 'ulogd_enabled',
			'dependencies'	=> array('networks')
		),
		'users'					=> array
		(
			'name'			=> 'users_enabled',
			'dependencies'	=> array()
		),
		'voip'					=> array
		(
			'name'			=> 'voip_enabled',
			'dependencies'	=> array()
		),
		'vtiger'			=> array
		(
			'name'			=> 'vtiger_integration',
			'dependencies'	=> array()
		),
		'works'					=> array
		(
			'name'			=> 'works_enabled',
			'dependencies'	=> array('approval')
		),
	);
	
	/**
	 * Test whether module is enabled
	 * 
	 * @author Michal Kliment
	 * @param string $module
	 * @return boolean
	 * @throws Exception
	 */
	public static function isModuleEnabled($module)
	{
		if (isset(self::$modules[$module]['name']))
		{
			return Settings::get(self::$modules[$module]['name']);
		}
		else
		{
			throw new Exception('Unknown module: '.$module);
		}
	}
	
	/**
	 * Disable given module
	 * 
	 * @author Michal Kliment
	 * @param type $module
	 * @return type
	 * @throws Exception
	 */
	private static function disableModule($module)
	{
		if (isset(self::$modules[$module]['name']))
		{
			return Settings::set(self::$modules[$module]['name'], 0);
		}
		else
		{
			throw new Exception('Unknown module: '.$module);
		}
	}

	/**
	 * Contruct of controller sets tabs names
	 */
	public function __construct()
	{
		parent::__construct();

		$this->sections = array();

		if ($this->acl_check_view('Settings_Controller', 'info'))
			$this->sections['info']         = __('Info');
		
		if ($this->acl_check_edit('Settings_Controller', 'system_settings'))
			$this->sections['system'] 		= __('System');

		if ($this->acl_check_edit('Settings_Controller', 'users_settings'))
			$this->sections['users'] 		= __('Users');

		// are finance enabled
		if (self::isModuleEnabled('finance') &&
			$this->acl_check_edit('Settings_Controller', 'finance_settings'))
			$this->sections['finance'] 		= __('Finance');

		// is approval enabled
		if (self::isModuleEnabled('approval') &&
			$this->acl_check_edit('Settings_Controller', 'approval_settings'))
			$this->sections['approval'] 	= __('Approval');

		// are networks enabled
		if (self::isModuleEnabled('networks') &&
			$this->acl_check_edit('Settings_Controller', 'networks_settings'))
			$this->sections['networks']		= __('Networks');

		if (self::isModuleEnabled('email') &&
			$this->acl_check_edit('Settings_Controller', 'email_settings'))
			$this->sections['email'] 		= __('Email');

		// are SMS enabled
		if (self::isModuleEnabled('sms') &&
			$this->acl_check_edit('Settings_Controller', 'sms_settings'))
			$this->sections['sms'] 			= __('SMS');

		// is voip enabled
		if (self::isModuleEnabled('voip') &&
			$this->acl_check_edit('Settings_Controller', 'voip_settings'))
			$this->sections['voip'] 		= __('VoIP');

		// is notification enabled
		if (self::isModuleEnabled('notification') &&
			$this->acl_check_edit('Settings_Controller', 'notification_settings'))
			$this->sections['notifications'] = __('Notifications');

		// is QoS enabled
		if (self::isModuleEnabled('qos') &&
			$this->acl_check_edit('Settings_Controller', 'qos_settings'))
			$this->sections['qos'] 			= __('QoS');

		// is monitoring enabled
		if (self::isModuleEnabled('monitoring') &&
			$this->acl_check_edit('Settings_Controller', 'monitoring_settings'))
			$this->sections['monitoring'] 	= __('Monitoring');

		// is vtiger integration enabled
		if (self::isModuleEnabled('vtiger') &&
			$this->acl_check_edit('Settings_Controller', 'vtiger_settings'))
			$this->sections['vtiger']		= __('Vtiger');

		if ($this->acl_check_edit('Settings_Controller', 'logging_settings'))
			$this->sections['logging'] 		= __('Logging');
		
		if (count($this->sections) <= 1)
			$this->sections = NULL;
	}

	/**
	 * Redirects to info
	 */
	public function index()
	{
		if ($this->acl_check_view('Settings_Controller', 'info'))
			$this->info();
		else if ($this->acl_check_edit('Settings_Controller', 'system_settings'))
			$this->system();
		else if ($this->acl_check_edit('Settings_Controller', 'users_settings'))
			$this->users();
		else if ($this->acl_check_edit('Settings_Controller', 'finance_settings'))
			$this->finance();
		else if ($this->acl_check_edit('Settings_Controller', 'approval_settings'))
			$this->approval();
		else if ($this->acl_check_edit('Settings_Controller', 'networks_settings'))
			$this->networks();
		else if ($this->acl_check_edit('Settings_Controller', 'email_settings'))
			$this->email();
		else if ($this->acl_check_edit('Settings_Controller', 'sms_settings'))
			$this->sms();
		else if ($this->acl_check_edit('Settings_Controller', 'voip_settings'))
			$this->voip();
		else if ($this->acl_check_edit('Settings_Controller', 'notification_settings'))
			$this->notifications();
		else if ($this->acl_check_edit('Settings_Controller', 'qos_settings'))
			$this->qos();
		else if ($this->acl_check_edit('Settings_Controller', 'monitoring_settings'))
			$this->monitoring();
		else if ($this->acl_check_edit('Settings_Controller', 'vtiger_settings'))
			$this->vtiger();
		else if ($this->acl_check_edit('Settings_Controller', 'logging_settings'))
			$this->logging();
		else
			Controller::error(ACCESS);
	}
	
	/**
	 * info() displays various system information
	 * 
	 * @author Tomas Dulik
	 */
	public function info()
	{
		if (!$this->acl_check_view('Settings_Controller', 'info'))
			Controller::error(ACCESS);

		$data = array();
		
		if (defined('FREENETIS_VERSION'))
		{
			$data['FreenetIS ' . __('version')] = FREENETIS_VERSION;
		}
		
		$data[__('DB schema revision')] = Settings::get('db_schema_version');
		$data[__('CRON state')] = module::get_state('cron');
		
		// redirection is enabled
		if (Settings::get('redirection_enabled'))
			$data[__('Redirection state')] = module::get_state('redirection');
		
		if (Settings::get('qos_enabled'))
			$data[__('QoS state')] = module::get_state('qos');
		
		if (Settings::get('monitoring_enabled'))
			$data[__('Monitoring state')] = module::get_state('monitoring');
		
		if (Settings::get('logging_enabled'))
			$data[__('Logging state')] = module::get_state('logging');
		
		if (Settings::get('allowed_subnets_enabled'))
			$data[__('Allowed subnet state')] = module::get_state('allowed_subnets_update');
		
		ob_start();
		
		$html = '';
		
		if (function_exists('phpinfo'))
		{
			phpinfo();
			$html = ob_get_contents();
			ob_end_clean();
			// Delete styles from output (credits: Moodle.org)
			$html = preg_replace('#(\n?<style[^>]*?>.*?</style[^>]*?>)|(\n?<style[^>]*?/>)#is', '', $html);
			$html = preg_replace('#(\n?<head[^>]*?>.*?</head[^>]*?>)|(\n?<head[^>]*?/>)#is', '', $html);
			// Delete DOCTYPE from output
			$html = preg_replace('/<!DOCTYPE html PUBLIC.*?>/is', '', $html);
			// Delete body and html tags
			$html = preg_replace('/<html.*?>.*?<body.*?>/is', '', $html);
			$html = preg_replace('/<\/body><\/html>/is', '', $html);
            if (PHP_VERSION_ID >= 50600) {
                $html = preg_replace('/<table>/is', '<table class="extended" width="720" style="table-layout:fixed; overflow: hidden">', $html);
            } else {
                $html = preg_replace('/table border="0"/is', 'table class="extended"', $html);
                $html = preg_replace('/width="600"/is', 'width="720" style="table-layout:fixed; overflow: hidden"', $html);
            }
		}
		
		$table = new View('table_2_columns');
		$table->table_data = $data;

		$view = new View('main');
		$view->title = __('System') . ' - ' . __('Info');
		$view->content = new View('settings/main');
		$view->content->current = 'info';
		$view->content->headline = __('Info');
		$view->content->content = $table . $html;
		$view->render(TRUE);
	}

	/**
	 * Form to set up system variables
	 * 
	 * @author Michal Kliment
	 */
	public function system()
	{
		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'system_settings'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge('settings/system');
		
		$this->form->set_attr('id', 'settings-system-form');

		$this->form->group('System variables');

		// page title
		$this->form->input('title')
				->label('Page title')
				->rules('length[3,40]|required')
				->value(Settings::get('title'));

		$countries = ORM::factory('country')->select_list('id', 'country_name');

		$this->form->dropdown('default_country')
				->label('Country')
				->rules('required')
				->options($countries)
				->selected(Settings::get('default_country'))
				->style('width:200px');
		
		$this->form->radio('grid_hide_on_first_load')
				->label('Hide grid on its first load')
				->options(arr::bool())
				->default(Settings::get('grid_hide_on_first_load'))
				->help('grid_hide_on_first_load');
		
		$form_modules = array();
                
		$this->form->group(__('Modules').' '.help::hint('modules'));

		// self-registration
		$form_modules['self_registration'] = $this->form->radio('self_registration')
				->label('Self-registration')
				->options(arr::bool())
				->default(Settings::get('self_registration'));
		
		// connection requests
		$form_modules['connection_request'] = $this->form->radio('connection_request_enable')
				->label('Connection requests')
				->options(arr::bool())
				->default(Settings::get('connection_request_enable'));

		// forgotten password
		$form_modules['forgotten_password'] = $this->form->radio('forgotten_password')
				->label('Forgotten password')
				->options(arr::bool())
				->default(Settings::get('forgotten_password'));
		
		// membership interrupts		
		$form_modules['membership_interrupt'] = $this->form->radio('membership_interrupt_enabled')
				->label('Membership interrupt')
				->options(arr::bool())
				->default(Settings::get('membership_interrupt_enabled'));
		
		// finance
		$form_modules['finance'] = $this->form->radio('finance_enabled')
				->label('Finance')
				->options(arr::bool())
				->default(Settings::get('finance_enabled'));
		
		// approval
		$form_modules['approval'] = $this->form->radio('approval_enabled')
				->label('Approval')
				->options(arr::bool())
				->default(Settings::get('approval_enabled'));
		
		// Works
		$form_modules['works'] = $this->form->radio('works_enabled')
				->label('Works')
				->options(arr::bool())
				->default(Settings::get('works_enabled'));
		
		// Phone invoice
		$form_modules['phone_invoices'] = $this->form->radio('phone_invoices_enabled')
				->label('Phone invoices')
				->options(arr::bool())
				->default(Settings::get('phone_invoices_enabled'));
		
		// e-mail
		$form_modules['email'] = $this->form->radio('email_enabled')
				->label('E-mail')
				->options(arr::bool())
				->default(Settings::get('email_enabled'));
		
		// SMS
		$form_modules['sms'] = $this->form->radio('sms_enabled')
				->label('SMS')
				->options(arr::bool())
				->default(Settings::get('sms_enabled'));
		
		// VoIP
		$form_modules['voip'] = $this->form->radio('voip_enabled')
				->label('VoIP')
				->options(arr::bool())
				->default(Settings::get('voip_enabled'));
		
		// Network
		$form_modules['networks'] = $this->form->radio('networks_enabled')
				->label('Networks')
				->options(arr::bool())
				->default(Settings::get('networks_enabled'));
		
		// SNMP
		$form_modules['snmp'] = $this->form->radio('snmp_enabled')
				->label('SNMP')
				->options(arr::bool())
				->default(Settings::get('snmp_enabled'));
		
		// CGI
		$form_modules['cgi'] = $this->form->radio('cgi_enabled')
				->label('CGI scripts')
				->options(arr::bool())
				->default(Settings::get('cgi_enabled'));
		
		// QoS
		$form_modules['qos'] = $this->form->radio('qos_enabled')
				->label('QoS')
				->options(arr::bool())
				->default(Settings::get('qos_enabled'));
		
		// Monitoring
		$form_modules['monitoring'] = $this->form->radio('monitoring_enabled')
				->label('Monitoring')
				->options(arr::bool())
				->default(Settings::get('monitoring_enabled'));
		
		// Redirection
		$form_modules['redirection'] = $this->form->radio('redirection_enabled')
				->label('Redirection')
				->options(arr::bool())
				->default(Settings::get('redirection_enabled'));
		
		// Notification
		$form_modules['notification'] = $this->form->radio('notification_enabled')
				->label('Notifications')
				->options(arr::bool())
				->default(Settings::get('notification_enabled'));

		// vtiger CRM integration
		$form_modules['vtiger'] = $this->form->radio('vtiger_integration')
				->label('Vtiger integration')
				->options(arr::bool())
				->default(Settings::get('vtiger_integration'));
		
		// add info about modules dependencies
		foreach ($form_modules as $module => $item)
		{
			// module have at least one dependency
			if (count(self::$modules[$module]['dependencies']))
			{
				$form_modules[$module]->additional_info(
					__('Require module') . ': <b>' . 
					implode(', ', array_map(
						'__', self::$modules[$module]['dependencies'])
					) . '</b>'
				);
			}
		}

		$this->form->group('Module settings');

		$timeout = Settings::get('module_status_timeout');
		
		$this->form->input('module_status_timeout')
				->rules('required|valid_numeric')
				->class('increase_decrease_buttons')
				->value($timeout)
				->style('width:30px')
				->help('Time threshold in minutes, before module is shown as inactive');
		
		$this->form->group(__('URL settings') . ' ' . help::hint('url_settings'));

		$this->form->dropdown('protocol')
				->rules('required|length[3,100]')
				->options(array
				(
					'http' => 'http',
					'https' => 'https'
				))->selected(url::protocol());
		
		$this->form->input('domain')
				->rules('required|length[3,100]')
				->callback(array($this, 'valid_domain'))
				->value(url::domain());
		
		$this->form->input('suffix')
				->rules('required')
				->callback(array($this, 'valid_suffix'))
				->value(url::suffix());
		
		$this->form->group(__('Address points'));
		
		$this->form->input('address_point_url')
				->label('Address point URL')
				->help('address_point_url')
				->value(Settings::get('address_point_url'));
		
		$selected_countries = ORM::factory('country')->select('id')->where('enabled', 1)->find_all()->as_array();
		
		$this->form->dropdown('enabled_countries[]')
					->label('Enabled countries')
					->options($countries)
					->selected($selected_countries)
					->multiple('multiple')
					->size(10);

		// load .htaccess sample file
		if (($htaccessFile = @file('.htaccess-sample')) != FALSE)
		{
			foreach ($htaccessFile as $line_num => $line)
			{
				// find line with RewriteBase
				if (preg_match("/^RewriteBase (.+)/", $line, $matches))
				{
					// and set there our suffix (subdirectory)
					$htaccessFile[$line_num] = preg_replace(
							"/^(RewriteBase )(.+)/",
							'${1}' . url::suffix(), $line
					);
				}
			}

			// write textarea with content of .htaccess sample file
			if (!is_writable('.') && !file_exists('.htaccess'))
			{
				$textarea = '';
				
				foreach ($htaccessFile as $line)
				{
					$textarea .= htmlentities($line);
				}
				
				$help = __('It\'s not possible to write your htacess file for clean URLS.');
				$help .= ' ' . __('You must create it manually and paste the following text into it.');

				$this->form->textarea('htaccess')
						->label('Content of file htaccess')
						->value($textarea)
						->help($help);
			}
		}
		
		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			$issaved = TRUE;
			$message = '';
			
			$issaved = $issaved && ORM::factory('country')->enable_countries($form_data['enabled_countries']);
			
			unset($form_data['enabled_countries']);
			
			// write suffix to .htaccess
			if (!file_exists('.htaccess'))
			{
				$htaccess = '.htaccess-sample';
			}
			else
			{
				$htaccess = '.htaccess';
			}
			
			if (is_writable('.') || (file_exists('.htaccess') && is_writable('.htaccess')))
			{
				// load .htaccess file
				$htaccessFile = @file($htaccess);

				if ($htaccessFile)
				{
					foreach ($htaccessFile as $line_num => $line)
					{
						// find line with RewriteBase
						if (preg_match("/^RewriteBase (.+)/", $line))
						{
							// and set there our suffix (subdirectory)
							$htaccessFile[$line_num] = preg_replace(
									"/^(RewriteBase )(.+)/", 
									'${1}/'.trim($form_data['suffix'], " /").'/', $line
							);
						}
					}
					
					$handle = @fopen('.htaccess', 'w');

					if ($handle)
					{
						foreach($htaccessFile as $line)
						{
							@fwrite($handle, $line);
						}

						@fclose($handle);
					}
					else
					{
						$issaved = FALSE;
						$message = __('Cannot open %s for writing', '.htaccess');
					
						// if not saved to htaccess do not save to database
						unset($form_data['suffix']);
					}
				}
				else
				{
					$issaved = FALSE;
					$message = __('Failed to read from %s', '.htaccess');
					
					// if not saved to htaccess do not save to database
					unset($form_data['suffix']);
				}
			}
			else
			{
				$issaved = FALSE;
				$message = __('File %s does not exists and cannot be created', '.htaccess');

				// if not saved to htaccess do not save to database
				unset($form_data['suffix']);
			}
			
			foreach ($form_data as $name => $value)
			{
				if ($name == 'module_status_timeout')
				{
					$value = max($value, 1);
				}
				else if ($name == 'security_password_length')
				{
					$value = min(50, max(1, $value)); // <1..50>
				}

				$issaved = $issaved && Settings::set($name, $value);
			}
			
			foreach (self::$modules as $module => $module_info)
			{
				foreach ($module_info['dependencies'] as $dependency)
				{
					if (self::isModuleEnabled($module) && !self::isModuleEnabled($dependency))
					{
						status::error(__('Cannot enable module %s, enabled module %s is required.', array($module, $dependency)));
						self::disableModule($module);
						break;
					}
				}
			}
			
			if ($issaved)
			// if all action were succesfull
			{
				status::success(
						__('System variables have been successfully updated.').
						'<br />' . $message, FALSE
				);
			}
			else
			// if not
			{
				status::error(
						__('System variables havent been updated.').
						'<br />' . $message, NULL, FALSE
				);
			}

			url::redirect('settings/system');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('System');
		$view->content = new View('settings/main');
		$view->content->current = 'system';
		$view->content->content = $this->form->html();
		$view->content->headline = __('System');
		
		$view->render(TRUE);
	}
	
	/**
	 * Settings of users variables
	 * 
	 * @author Michal Kliment
	 */
	public function users()
	{
		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'users_settings'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge();
		
		$this->form->group('Members');		

		$this->form->checkbox('former_member_auto_device_remove')
				->label('Enable automatical deletion of devices of former members')
				->checked(Settings::get('former_member_auto_device_remove'));
        
        $this->form->checkbox('user_phone_duplicities_enabled')
                ->label('Enable multiple users to have assigned same phone contact')
				->checked(Settings::get('user_phone_duplicities_enabled'));

        $this->form->checkbox('user_email_duplicities_enabled')
                ->label('Enable multiple users to have assigned same e-mail contact')
				->checked(Settings::get('user_email_duplicities_enabled'));

        $this->form->checkbox('users_birthday_empty_enabled')
                ->label('Users birthday can be empty')
				->checked(Settings::get('users_birthday_empty_enabled'));
		
		$this->form->group('Security');
		
		$this->form->input('security_password_length')
				->label('Minimal password length')
				->rules('required|valid_numeric')
				->class('increase_decrease_buttons')
				->style('width:30px')
				->value(Settings::get('security_password_length'));
		
		$pass_levels = array
		(
			1 => __('very weak'),
			2 => __('weak'),
			3 => __('good'),
			4 => __('strong'),
		);
		
		$this->form->dropdown('security_password_level')
				->options($pass_levels)
				->label('Minimal password level')
				->rules('required')
				->selected(Settings::get('security_password_level'));
		
		
		if (Settings::get('membership_interrupt_enabled'))
		{
			$this->form->group('Membership interrupt');

			$this->form->input('membership_interrupt_minimum')
					->label('Minimum membership interrupt period (months)')
					->rules('valid_numeric')
					->value(Settings::get('membership_interrupt_minimum'));

			$this->form->input('membership_interrupt_maximum')
					->label('Maximum membership interrupt period (months)')
					->rules('valid_numeric')
					->value(Settings::get('membership_interrupt_maximum'));
		}
		
		if (Settings::get('self_registration'))
		{
			$this->form->group('Applicant for membership');
			
			$this->form->checkbox('self_registration_enable_approval_without_registration')
					->label('Enable approval of membership without submited registration')
					->checked(Settings::get('self_registration_enable_approval_without_registration'));
			
			if (Settings::get('finance_enabled'))
			{
				$this->form->checkbox('self_registration_enable_additional_payment')
						->label('Enable additional member fee during the approval of membership')
						->checked(Settings::get('self_registration_enable_additional_payment'));
			}
		}
		
		$this->form->group('Export of registration');
		
		if (Settings::get('finance_enabled'))
		{
			$bank_account = new Bank_account_Model();
			$concat = "CONCAT(account_nr, '/', bank_nr, IF(name IS NULL, '', CONCAT(' - ', name)))";
		
			$this->form->dropdown('export_header_bank_account')
					->label('Bank account')
					->options($bank_account->where('member_id', Member_Model::ASSOCIATION)->select_list('id', $concat))
					->selected(Settings::get('export_header_bank_account'))
					->rules('required');
		}
		
		// directory is writable
		if (is_writable('upload'))
		{
			$additional_info = '';
			$logo = Settings::get('registration_logo');
			
			if (file_exists($logo))
			{
				$additional_info = '<img src="'. url_lang::base().'export/logo" style="margin-left:10%;height:50px;vertical-align:middle"/>';
			}
			
			$this->form->upload('registration_logo')
					->label('Logo')
					->rules('allow[jpg]')
					->new_name('registration_logo.jpg')
					->help(help::hint('registration_logo'))
					->additional_info($additional_info);
		}

		$this->form->html_textarea('registration_info')
				->label('Info')
				->rows(5)
				->cols(100)
				->value(Settings::get('registration_info'));
		
		$this->form->html_textarea('registration_license')
				->label('License')
				->rows(5)
				->cols(100)
				->value(Settings::get('registration_license'));

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);

			$issaved = true;

			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			// if all action were succesfull
			{
				status::success('System variables have been successfully updated.');
			}
			else
			// if not
			{
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('settings/users');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('Users');
		$view->content = new View('settings/main');
		$view->content->current = 'users';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Users');
		
		// directory is not writable
		if (!is_writable('upload'))
		{
			$view->content->warning = __(
					'Directory "upload" is not writable, change access ' .
					'rights to be able upload your own logo.'
			);
		}
		
		$view->render(TRUE);
	}
	
	/**
	 * Settings of finance variables
	 * 
	 * @author Ondrej Fibich
	 */
	public function finance()
	{
		// access control
		if (!module::e('finance') ||
			!$this->acl_check_edit('Settings_Controller', 'finance_settings'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge();
		
		$this->form->group('Finance settings');
		
		$this->form->input('currency')
				->rules('length[3,40]|required')
				->value(Settings::get('currency'));
		
		$this->form->group('Automatic actions');
		
		$deduct_day = Settings::get('deduct_day');
		
		$this->form->checkbox('deduct_fees_automatically_enabled')
				->value('1')
				->checked(Settings::get('deduct_fees_automatically_enabled'))
				->label(__('Deduct fees automatically') . ' ' . 
						help::hint('deduct_fees_automatically_enabled', $deduct_day));
		
		$this->form->group('Variable symbol settings');
		
		$this->form->dropdown('variable_key_generator_id')
				->options(array(NULL => '') + Variable_Key_Generator::get_drivers_for_dropdown())
				->label('Algorithm for generation of variable symbols')
				->selected(Variable_Key_Generator::get_active_driver())
				->style('width:200px');

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);

			$issaved = true;

			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			// if all action were succesfull
			{
				status::success('System variables have been successfully updated.');
			}
			else
			// if not
			{
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('settings/finance');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('Finance');
		$view->content = new View('settings/main');
		$view->content->current = 'finance';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Finance');
		
		$view->render(TRUE);
	}
	
	/**
	 * Settings for QoS 
	 */
	public function qos()
	{
		// access control
		if (!module::e('qos') ||
			!$this->acl_check_edit('Settings_Controller', 'qos_settings'))
			Controller::error(ACCESS);
		
		// creating of new forge
		$this->form = new Forge('settings/qos');
		
		$this->form->group('Variables for QoS');

		$this->form->input('qos_total_speed')
				->rules('valid_speed_size')
				->label(__('Total speed') . ': '.help::hint('qos_total_speed'))
				->value(Settings::get('qos_total_speed'));
		
		if (Settings::get('ulogd_enabled'))
		{
			$this->form->input('qos_active_speed')
				->label(__('Speed for active members') . ': '.help::hint('qos_active_speed'))
				->rules('valid_speed_size')
				->value(Settings::get('qos_active_speed'));
		}
		
		$this->form->input('qos_high_priority_speed')
				->rules('valid_speed_size')
				->label(__('Speed for high priority IP addresses') . ': '.help::hint('qos_high_priority_speed'))
				->value(Settings::get('qos_high_priority_speed'));
		
		$this->form->textarea('qos_high_priority_ip_addresses')
				->rules('valid_ip_address')
				->label(__('High priority IP addresses'). ': '.help::hint('qos_high_priority_ip_addresses'))
				->value(str_replace(",","\n",Settings::get('qos_high_priority_ip_addresses')));

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			// transform lines to one line
			$form_data["qos_high_priority_ip_addresses"] = implode(
				",",
				array_map("long2ip",
					arr::sort(
						array_map("ip2long",
							array_unique(
								explode(
									",",
									str_replace(
										"\n",
										",",
										$form_data["qos_high_priority_ip_addresses"]
									)
								)
							)
						)
					)
				)
			);

			$issaved = true;
			
			if (isset($form_data['qos_enabled']) &&
				$form_data['qos_enabled'] == 1)
			{
				$qos_enabled = '1';
			}
			else
			{
				$qos_enabled = '0';
			}
			
			$issaved = $issaved && Settings::set('qos_enabled', $qos_enabled);

			foreach ($form_data as $name => $value)
			{
				if ($name == 'qos_enabled')
					continue;
				
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			// if all action were succesfull
			{
				status::success('QoS variables have been successfully updated.');
			}
			else
			// if not
			{
				status::error('QoS variables havent been successfully updated.');
			}

			url::redirect('settings/qos');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('QoS');
		$view->content = new View('settings/main');
		$view->content->current = 'qos';
		$view->content->content = $this->form->html();
		$view->content->headline = __('QoS');
		$view->content->description = module::get_state('qos', TRUE);
		
		$view->render(TRUE);
	}

	/**
	 * Settings of email variables
	 * 
	 * @author Michal Kliment
	 */
	public function email()
	{
		// access control
		if (!module::e('email') ||
			!$this->acl_check_edit('Settings_Controller', 'email_settings'))
		{
			Controller::error(ACCESS);
		}

		// creating of new forge
		$this->form = new Forge('settings/email');

		$this->form->group('E-mail settings');

		$this->form->input('email_default_email')
				->label('Default e-mail')
				->rules('length[3,100]|valid_email')
				->value(Settings::get('email_default_email'));
		
		$this->form->group('E-mail variables');

		$this->form->dropdown('email_driver')
				->label('Driver')
				->options(array
				(
					'native'	=> __('Native'),
					'smtp'		=> __('SMTP'),
					'sendmail'	=> __('Sendmail')
				))->selected(Settings::get('email_driver'));

		$this->form->input('email_hostname')
				->label('Hostname')
				->value(Settings::get('email_hostname'))
				->help(__('For SMTP settings only.'));

		$this->form->input('email_port')
				->label('Port')
				->rules('valid_numeric')
				->value(Settings::get('email_port'))
				->help(__('For SMTP settings only.'));

		$this->form->dropdown('email_encryption')
				->label('Connection encryption')
				->options(array
				(
					'none'	=> __('none'),
					'tsl'	=> __('TSL'),
					'ssl'	=> __('SSL')
				))->selected(Settings::get('email_encryption'))
				->help(__('For SMTP settings only.'));

		$this->form->input('email_username')
				->label('User name')
				->value(Settings::get('email_username'))
				->help(__('For SMTP settings only.'));

		$this->form->input('email_password')
				->label('Password')
				->value(Settings::get('email_password'))
				->help(__('For SMTP settings only.'));

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);

			$issaved = true;

			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			// if all action were succesfull
			{
				status::success('E-mail variables have been successfully updated.');
			}
			else
			// if not
			{
				status::error('E-mail variables havent been successfully updated.');
			}

			url::redirect('settings/email');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('E-mail');
		$view->content = new View('settings/main');
		$view->content->current = 'email';
		$view->content->content = $this->form->html();
		$view->content->headline = __('E-mail');
		
		if (!empty($message))
			$view->content->message = $message;
		
		$view->render(TRUE);
	}

	/**
	 * Approval settings
	 */
	public function approval()
	{
		// access control
		if (!module::e('approval') ||
			!$this->acl_check_edit('Settings_Controller', 'approval_settings'))
			Controller::error(ACCESS);

		$approval_templates = ORM::factory('approval_template')->select_list('id', 'name');
		
		$arr_approval_templates = array
		(
			NULL => '----- ' . __('Select approval template') . ' -----'
		) + $approval_templates;

		// creating of new forge
		$this->form = new Forge('settings/approval');
		
		if (Settings::get('works_enabled'))
		{
		    $this->form->group('Work');
		    
		    $this->form->dropdown('default_work_approval_template')
				    ->label('Default approval template')
				    ->options($arr_approval_templates)
				    ->selected(Settings::get('default_work_approval_template'))
				    ->rules('required');

		    $this->form->group('Work report');

		    $this->form->dropdown('default_work_report_approval_template')
				    ->label('Default approval template')
				    ->options($arr_approval_templates)
				    ->selected(Settings::get('default_work_report_approval_template'))
				    ->rules('required');
		}

		$this->form->group('Request');

		$this->form->dropdown('default_request_approval_template')
				->label('Default approval template for "proposals to association"')
				->options($arr_approval_templates)
				->selected(Settings::get('default_request_approval_template'))
				->rules('required');

		$this->form->dropdown('default_request_support_approval_template')
				->label('Default approval template for "support requests"')
				->options($arr_approval_templates)
				->selected(Settings::get('default_request_support_approval_template'))
				->rules('required');


		$this->form->submit('Save');

		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);
			$issaved = true;
			
			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			{
				status::success('System variables have been successfully updated.');
			}
			else
			{
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('settings/approval');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('Approval');
		$view->content = new View('settings/main');
		$view->content->current = 'approval';
		$view->content->headline = __('Approval');
		$view->content->link_back = $this->links;
		$view->content->content = $this->form->html();
		$view->render(TRUE);
	}
	
	/**
	 * Networks settings
	 */
	public function networks()
	{
		// access control
		if (!module::e('networks') ||
			!$this->acl_check_edit('Settings_Controller', 'networks_settings'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge();
		
		$this->form->group('Network settings');

		$this->form->textarea('address_ranges')
				->help('address_ranges')
				->rules('valid_address_ranges')
				->value(str_replace(",","\n", Settings::get('address_ranges')))
				->class('autosize');

		$this->form->textarea('dns_servers')
				->help('dns_servers')
				->rules('valid_ip_address')
				->value(str_replace(",","\n", Settings::get('dns_servers')))
				->class('autosize');

		$this->form->input('dhcp_server_reload_timeout')
				->label('DHCP server maximal timeout')
				->rules('required|valid_numeric')
				->class('increase_decrease_buttons')
				->value(Settings::get('dhcp_server_reload_timeout'))
				->style('width:50px')
				->help('dhcp_server_reload_timeout');
		
		if (Settings::get('connection_request_enable'))
		{
			$this->form->group('Connection requests');
			
			// enum types for device
			$enum_type_model = new Enum_type_Model();
			$types = $enum_type_model->get_values(Enum_type_Model::DEVICE_TYPE_ID);
			
			$allowed_types = explode(':', Settings::get('connection_request_device_types'));
			$default_types = $types;
			
			// throw away unallowed types		
			if (Settings::get('connection_request_device_types'))
			{
				foreach ($default_types as $key => $val)
				{
					if (array_search($key, $allowed_types) === FALSE)
					{
						unset($default_types[$key]);
					}
				}
			}
			
			$default_types[NULL] = '--- ' . __('Select type') . ' ---';
			asort($default_types);
			
			$this->form->dropdown('connection_request_device_default_type')
					->label('Default device type')
					->options($default_types)
					->selected(Settings::get('connection_request_device_default_type'))
					->style('width:200px');
			
			$this->form->dropdown('connection_request_device_types[]')
					->label('Allowed device types')
					->options($types)
					->selected($allowed_types)
					->multiple('multiple')
					->size(10);
			
			$this->form->html_textarea('connection_request_info')
					->label('Add form information')
					->help(help::hint('connection_request_info_form'))
					->rows(5)
					->cols(100)
					->value(Settings::get('connection_request_info'));
		}
		
		$this->form->group('Other settings');
		
		$this->form->checkbox('device_add_auto_link_enabled')
				->label('Enable automatic loading of "connected to" field during adding of device')
				->checked(Settings::get('device_add_auto_link_enabled'));
		
		// CGI scripts
		if (module::e('cgi'))
		{
			$this->form->group('CGI scripts');
			
			$this->form->input('cgi_arp_url')
				->label('URL for ARP table')
				->value(Settings::get('cgi_arp_url'))
				->style('width: 400px');
		}

		$this->form->submit('Save');

		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);
			$issaved = true;
			
			foreach ($form_data as $name => $value)
			{
				if ($name == 'address_ranges')
				{
					$value = str_replace("\n", ",", $value);
				}
				else if ($name == 'connection_request_device_types')
				{
					$value = empty($value) ? '' : implode(':', $value);
				}
				else if ($name == 'dns_servers')
				{
					$old_value = Settings::get('dns_servers');
					// expire DHCP? (#472)
					if (trim($value) != trim($old_value))
					{
						ORM::factory('subnet')->set_expired_all_subnets();
					}
				}
				
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			{
				status::success('System variables have been successfully updated.');
			}
			else
			{
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('settings/networks');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('Networks');
		$view->content = new View('settings/main');
		$view->content->current = 'networks';
		$view->content->headline = __('Networks');
		$view->content->link_back = $this->links;
		$view->content->content = $this->form->html();
		$view->render(TRUE);
	}

	/**
	 * VOIP settings
	 */
	public function voip()
	{
		// access control
		if (!module::e('voip') ||
			!$this->acl_check_edit(get_class($this), 'voip_settings'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge('settings/voip');

		$this->form->group('VoIP settings');

		// page title
		$this->form->input('voip_number_interval')
				->label('Number interval')
				->rules('length[19,19]|required')
				->value(addslashes(Settings::get('voip_number_interval')))
				->callback(array($this, 'valid_voip_number_interval'));
		
		$this->form->input('voip_number_exclude')
				->label('Exclude numbers')
				->rules('length[9,100]')
				->value(addslashes(Settings::get('voip_number_exclude')))
				->callback(array($this, 'valid_voip_number_exclude'));
		
		$this->form->input('voip_sip_server')
				->label('SIP server')
				->rules('length[1,30]|required')
				->value(addslashes(Settings::get('voip_sip_server')));

		$this->form->group('Asterisk manager settings');
		
		$this->form->input('voip_asterisk_hostname')
				->label('Hostname')
				->rules('length[1,50]')
				->value(Settings::get('voip_asterisk_hostname'));
		
		$this->form->input('voip_asterisk_user')
				->label('User')
				->rules('length[1,50]')
				->value(Settings::get('voip_asterisk_user'));
		
		$this->form->input('voip_asterisk_pass')
				->label('Password')
				->rules('length[1,50]')
				->value(Settings::get('voip_asterisk_pass'));


		$this->form->group('Billing settings');
		
		$this->form->dropdown('voip_billing_driver')
				->label('Driver')
				->rules('required')
				->options(array
				(
					Billing::INACTIVE		=> __('Inactive'),
					Billing::NFX_LBILLING	=> 'lBilling - NFX'
				))->selected(Settings::get('voip_billing_driver'));
		
		$this->form->input('voip_billing_partner')
				->label('Partner')
				->rules('length[1,50]')
				->value(Settings::get('voip_billing_partner'));
		
		$this->form->input('voip_billing_password')
				->label('Password')
				->rules('length[1,50]')
				->value(Settings::get('voip_billing_password'));
		

		$this->form->group('Actual price of calls');
		
		$this->form->input('voip_tariff_fixed')
				->label('Fixed line number')
				->rules('valid_numeric')
				->value(Settings::get('voip_tariff_fixed'));
		
		$this->form->input('voip_tariff_cellphone')
				->label('Cellphone number')
				->rules('valid_numeric')
				->value(Settings::get('voip_tariff_cellphone'));
		
		$this->form->input('voip_tariff_voip')
				->label('VoIP number')
				->rules('valid_numeric')
				->value(Settings::get('voip_tariff_voip'));

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);
			$issaved = true;
			$driver = true;
			
			// enable VoIP driver
			if ($form_data['voip_billing_driver'] != Billing::INACTIVE)
			{
				$voip_sip = new Voip_sip_Model();
				
				// Check pre requirements of VoIP  
				if (!$voip_sip->check_pre_requirements())
				{
					// Make pre requirements
					try
					{
						// creates helper functions
						Voip_sip_Model::create_functions();
						// create views
						Voip_sip_Model::create_views();
					}
					catch (Exception $e)
					{
						// unset driver
						Settings::set('voip_billing_driver', Billing::INACTIVE);
						// don't change in next foreach
						unset($form_data['voip_billing_driver']);
						// error flad
						$driver = false;
					}
				}
			}

			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if (!$driver)
			{ // driver error
				status::error(__(
						'Cannot enable VoIP driver, allow `%s` rights for MySQL user',
						array('CREATE ROUTINE')
				) . '.',NULL,  FALSE);
			}
			else if ($issaved)
			{
				status::success('System variables have been successfully updated.');
			}
			else
			{
				status::success('System variables havent been successfully updated.');
			}

			url::redirect('settings/voip');
		}

		// drover informations
		$ai = null;

		if (Billing::instance()->has_driver())
		{
			if (Billing::instance()->test_conn())
			{
				$ai = __('Testing driver');
				$ai .= ': lBilling - NFX......<span style="color:green">OK</span>';
			}
			else
			{
				$ai = __('Testing driver');
				$ai .= ': lBilling - NFX......<span style="color:red">';
				$ai .= __('Failed') . '</span>';
			}
		}

		// create view for this template
		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('VoIP');
		$view->content = new View('settings/main');
		$view->content->current = 'voip';
		$view->content->content = $this->form->html();
		$view->content->headline = __('VoIP');
		$view->content->additional_info = $ai;
		$view->render(TRUE);
	}

	/**
	 * SMS settings
	 */
	public function sms()
	{
		// access control
		if (!module::e('sms') || 
			!$this->acl_check_edit(get_class($this), 'sms_settings'))
		{
			Controller::error(ACCESS);
		}
		
		// enabled SMS?
		if (!Sms::enabled())
		{
			$view = new View('main');
			$view->title = __('Settings') . ' - ' . __('SMS');
			$view->content = new View('settings/main');
			$view->content->current = 'sms';
			$view->content->content = new View('sms/not_enabled');
			$view->content->headline = __('Error - SMS not enabled');
			$view->render(TRUE);
			exit;
		}

		$drivers = Sms::get_active_drivers();

		// creating of new forge
		$this->form = new Forge('settings/sms');

		$this->form->group('SMS settings');

		// page title
		$this->form->input('sms_sender_number')
				->label('Number of the sender')
				->rules('length[12,12]|required|valid_phone')
				->value(Settings::get('sms_sender_number'));
		
		$this->form->dropdown('sms_driver')
				->label('Default driver')
				->options($drivers)
				->selected(Settings::get('sms_driver'));

		/* Forms for all drivers */
		
		$aditional_info = '';
		$drivers = Sms::get_drivers();
		
		foreach ($drivers as $driver)
		{
			$key = $driver['id'];
		
			/* Build form */
			
			$this->form->group(Sms::get_driver_name($key, TRUE));

			$this->form->dropdown('sms_driver_state' . $key)
					->label('Driver state')
					->options(array
					(
						Sms::DRIVER_INACTIVE	=> __('Inactive'),
						Sms::DRIVER_ACTIVE		=> __('Active')
					))->selected(Settings::get('sms_driver_state' . $key));
		
			// hostname not defined by config driver array?
			if (!array_key_exists('hostname', $driver))
			{
				$this->form->input('sms_hostname' . $key)
						->label('Hostname')
						->rules('length[1,50]')
						->value(Settings::get('sms_hostname' . $key));
			}

			$this->form->input('sms_user' . $key)
					->label('User')
					->rules('length[1,50]')
					->value(Settings::get('sms_user' . $key));

			$this->form->input('sms_password' . $key)
					->label('Password')
					->rules('length[1,50]')
					->value(Settings::get('sms_password' . $key));

			// test mode on?
			if ($driver['test_mode_enabled'])
			{
				$this->form->dropdown('sms_test_mode' . $key)
						->label(__('Test mode') . ':')
						->help(__('SMS will not be send if test mode is enabled, ' .
								  'driver will only try to send them'))
						->options(arr::rbool())
						->selected(Settings::get('sms_test_mode' . $key));
			}
			
			/* Testing of gates */
			
			if (Settings::get('sms_driver_state' . $key) == Sms::DRIVER_ACTIVE)
			{
				$aditional_info .= __('Testing driver') . ' : '
								. Sms::get_driver_name($key)
								. '......<span style="color:';
				
				// sms
				$sms = Sms::factory($key);

				// loaded?
				if (!$sms)
				{
					$aditional_info .= 'red">' . __('Load failed') . '</span><br />';
					continue;
				}

				// sets vars to class
				$sms->set_hostname(Settings::get('sms_hostname' . $key));
				$sms->set_user(Settings::get('sms_user' . $key));
				$sms->set_password(Settings::get('sms_password' . $key));
				$sms->set_test(Settings::get('sms_test_mode' . $key) == 1);

				if ($sms->test_conn())
				{
					$aditional_info .= 'green">OK</span><br />';
				}
				else
				{
					$aditional_info .= 'red">' . __('Failed') . '</span><br />';
				}
			}
		}

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);
			$issaved = true;

			foreach ($form_data as $name => $value)
			{
				$value = empty($value) ? '' : $value;
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			{
				status::success('System variables have been successfully updated.');
			}
			else
			{
				status::error('System variables havent been successfully updated.');
			}
			
			url::redirect('settings/sms');
		}
		
		// description
		$description = html::anchor('phone_operators', __(
				'Approved phone operators and prefixes'
		)) . ' ' . help::hint('sms_enabled');

		// create view for this template
		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('SMS');
		$view->content = new View('settings/main');
		$view->content->current = 'sms';
		$view->content->content = $this->form->html();
		$view->content->headline = __('SMS');
		$view->content->description = $description;
		$view->content->link_back = $this->links;
		$view->content->additional_info = $aditional_info;
		$view->render(TRUE);
	}

	/**
	 * Shows notification settings and enables their editing.
	 * 
	 * @author Jiri Svitak 
	 */
	public function notifications()
	{
		// access control
		if (!self::isModuleEnabled('notification') ||
			!$this->acl_check_edit('Settings_Controller', 'notification_settings'))
			Controller::error(ACCESS);
		
		// creating of new forge
		$this->form = new Forge('settings/notifications');
		
		$this->form->group('General settings');
		
		$this->form->input('payment_notice_boundary')
				->label(__('Payment notice boundary')." (".
						Settings::get('currency')."):&nbsp;".
						help::hint('payment_notice_boundary'))
				->rules('valid_numeric')
				->value(Settings::get('payment_notice_boundary'));
		
		$this->form->input('debtor_boundary')
				->label(__('Debtor boundary')." (".
						Settings::get('currency')."):&nbsp;".
						help::hint('debtor_boundary'))
				->rules('valid_numeric')
				->value(Settings::get('debtor_boundary'));

		$this->form->input('big_debtor_boundary')
				->label(__('Big debtor boundary')." (".
						Settings::get('currency')."):&nbsp;".
						help::hint('big_debtor_boundary'))
				->rules('valid_numeric')
				->value(Settings::get('big_debtor_boundary'));
		
		$this->form->input('initial_immunity')
				->label(__('Initial immunity').': '.
					help::hint('initial_immunity'))
				->rules('required|valid_numeric')
				->value(Settings::get('initial_immunity'));
		
		$this->form->input('initial_debtor_immunity')
				->label(__('Initial debtor immunity').': '.
					help::hint('initial_debtor_immunity'))
				->rules('required|valid_numeric')
				->value(Settings::get('initial_debtor_immunity'));
		
		if (Settings::get('self_registration'))
		{
			$this->form->input('applicant_connection_test_duration')
					->label('Test connection duration')
					->help(help::hint('applicant_connection_test_duration'))
					->rules('valid_numeric')
					->value(Settings::get('applicant_connection_test_duration'));
		}
		
		// redirection is enabled
		if (Settings::get('redirection_enabled'))
		{
			$this->form->group('Redirection');

			$this->form->input('gateway')
					->label(__('Gateway IP address').":")
					->value(Settings::get('gateway'));

			$this->form->input('redirection_port_self_cancel')
					->label(__('Port for self-canceling').": ".help::hint('redirection_port_self_cancel'))
					->rules('valid_numeric')
					->value(Settings::get('redirection_port_self_cancel'));

			// directory is writable
			if (is_writable('upload'))
			{
				$additional_info = '';
				$logo = Settings::get('redirection_logo_url');

				if (file_exists($logo))
				{
					$additional_info = '<img src="'. url_lang::base().'redirect/logo" style="margin-left:10%;height:49px;vertical-align:middle"/>';
				}

				$this->form->upload('redirection_logo_url')
						->label('Redirection logo URL')
						->rules('allow[jpg,png]')
						->new_name('redirection_logo.jpg')
						->help(help::hint('redirection_logo'))
						->additional_info($additional_info);
			}

			$this->form->input('self_cancel_text')
					->label(__('Text for self cancel anchor').":&nbsp;".
							help::hint('self_cancel_text'))
					->value(Settings::get('self_cancel_text'));
		
			// allowed subnets
			$this->form->group(__('Allowed subnets') . ' ' . help::hint('allowed_subnets'));
			
			$this->form->checkbox('allowed_subnets_enabled')
				->value('1')
				->checked(Settings::get('allowed_subnets_enabled'))
				->label('Enable allowed subnets');
			
			if (Settings::get('allowed_subnets_enabled'))
			{
				$this->form->input('allowed_subnets_default_count')
						->label('Default allowed subnets count')
						->help(help::hint('allowed_subnets_default_count'))
						->rules('required_with_zero|valid_digit')
						->value(Settings::get('allowed_subnets_default_count'));

				$this->form->input('allowed_subnets_update_interval')
						->label('Interval of update')
						->help(help::hint('allowed_subnets_update_interval'))
						->rules('required|valid_numeric')
						->value(Settings::get('allowed_subnets_update_interval'));
			}
		}
		
		$this->form->group('E-mail');
		
		$this->form->input('email_subject_prefix')
				->label(__('E-mail subject prefix').':')
				->value(Settings::get('email_subject_prefix'));

		$this->form->checkbox('notification_email_message_name_in_subject')
				->label(__('Append notification message name to e-mail message subject'))
				->checked(Settings::get('notification_email_message_name_in_subject'));
		
		$this->form->submit('submit')->value(__('Save'));
		
		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);
			$issaved = true;
			
			foreach ($form_data as $name => $value)
			{
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			{	// if all action were succesfull
				status::success('Notification settings have been successfully updated.');
			}
			else
			{	// if not
				status::error('Notification settings have not been updated.');
			}

			url::redirect('settings/notifications');
		}
		
		// states of modules
		$states = array();
		
		if (Settings::get('redirection_enabled'))
			$states[] = module::get_state('redirection', TRUE);
		
		if (Settings::get('allowed_subnets_enabled'))
			$states[] = module::get_state('allowed_subnets_update', TRUE);
		
		// create view for this template
		$view = new View('main');
		$view->title = __('System') . ' - ' . __('Notification settings');
		$view->content = new View('settings/main');
		$view->content->current = 'notifications';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Notification settings');
		$view->content->description = implode('<br />', $states);
		
		$view->render(TRUE);		
	}
	
	/**
	 * Settings for logging
	 *
	 * @author Michal Kliment
	 */
	public function logging()
	{
		// access control
		if (!$this->acl_check_edit(get_class($this), 'logging_settings'))
			Controller::error(ACCESS);

		$user_model = new User_Model();

		// creating of new forge
		$this->form = new Forge('settings/logging');
		
		$this->form->group('Centralized logging');
		
		$this->form->group('ulogd ' . help::hint('ulogd'))
				->message();
		
		$this->form->checkbox('ulogd_enabled')
				->value('1')
				->label('Enable ulogd');
		
		if (version_compare($user_model->get_mysql_version(), '5.1.0', '<'))
		{
			$this->form->ulogd_enabled->label(
					__('Enable ulogd') . ' - <span class="error">' .
					__('require MySQL %s and higher', array('5.1')) . '</span>')
					->disabled('disabled');
		}
		else
		{
			$this->form->input('ulogd_update_interval')
					->label(__('Interval of update') . ': ' .
							help::hint('ulogd_update_interval'))
					->rules('required|valid_numeric')
					->value(Settings::get('ulogd_update_interval'));

			$this->form->group('')
					->label(__('Active members') . ' ' .
							help::hint('ulogd_active'));

			$this->form->input('ulogd_active_count')
					->label(__('Base') . ': ' .
							help::hint('ulogd_active_count'))
					->rules('required|valid_ulogd_active_count')
					->value(Settings::get('ulogd_active_count'));

			$this->form->dropdown('ulogd_active_type')
					->label(__('Type of traffic') . ': ' .
							help::hint('ulogd_active_type'))
					->rules('required')
					->options(array
					(
						0			=> '----- ' . __('Select type') . ' -----',
						'upload'	=> __('upload'),
						'download'	=> __('download'),
						'total'		=> __('both') . ' (' . __('upload') . ' + ' . __('download') . ')'
					))->selected(Settings::get('ulogd_active_type'))
					->style('width:200px');

			$this->form->input('ulogd_active_min')
					->label(__('Minimum of traffic') . ': ' .
							help::hint('ulogd_active_min'))
					->rules('valid_byte_size')
					->value(Settings::get('ulogd_active_min'));
		}
		
		$this->form->group(__('Syslog NG MySQL API').' '.help::hint('syslog_ng_mysql_api'));
		
		$this->form->checkbox('syslog_ng_mysql_api_enabled')
				->value('1')
				->label('Enable');
		
		if (Settings::get('syslog_ng_mysql_api_enabled') == 1)
			$this->form->syslog_ng_mysql_api_enabled->checked('checked');
		
		$this->form->input('syslog_ng_mysql_api_url')
				->label(__('API URL').': '.help::hint('syslog_ng_mysql_api_url'))
				->value(Settings::get('syslog_ng_mysql_api_url'))
				->style('width: 500px;');
		
		$this->form->group(__('Action logs') . ' ' . help::hint('action_logs_active'));
		
		$this->form->checkbox('action_logs_active')
				->value('1')
				->label('Enable action logs');
		
		$this->form->submit('Save');
		
		if (version_compare($user_model->get_mysql_version(), '5.1.0', '<'))
		{
			$this->form->action_logs_active->label(
					__('Enable action logs') . ' - <span class="error">' .
					__('require MySQL %s and higher', array('5.1')) . '</span>'
			);
			$this->form->action_logs_active->disabled('disabled');
		}
		else if (Settings::get('action_logs_active') == 1)
		{
			$this->form->action_logs_active->checked('checked');
		}

		$ulog2_ct_model = new Ulog2_ct_Model();
		
		if (!$ulog2_ct_model->check_pre_requirements() &&
			!$user_model->check_permission('CREATE ROUTINE'))
		{
			$this->form->ulogd_enabled->label(
					__('Enable ulogd') . ' - <span class="error">' .
					__('it requires already created functions for '.
							'ulogd or MySQL permission CREATE ROUTINE for create them'
					) . '</span>');
			
			$this->form->ulogd_enabled->disabled('disabled');
		}
		else if (Settings::get('ulogd_enabled') == 1)
		{
			$this->form->ulogd_enabled->checked('checked');
		}

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array(FALSE);

			// action logs value
			if (isset($form_data['action_logs_active']) &&
				$form_data['action_logs_active'] == 1)
			{
				$action_logs_active = '1';
			}
			else
			{
				$action_logs_active = '0';
			}

			// action logs value
			if (Settings::get('networks_enabled') && isset($form_data['ulogd_enabled']) &&
				$form_data['ulogd_enabled'] == 1)
			{
				$ulogd_enabled = '1';
			}
			else
			{
				$ulogd_enabled = '0';
			}
			
			// syslog-ng mysql
			if (Settings::get('networks_enabled') && isset($form_data['syslog_ng_mysql_api_enabled']) &&
				$form_data['syslog_ng_mysql_api_enabled'] == 1)
			{
				$syslog_ng_mysql_api_enabled = '1';
			}
			else
			{
				$syslog_ng_mysql_api_enabled = '0';
			}

			// set logs checkbox value to db
			Settings::set('action_logs_active', $action_logs_active);
			
			Settings::set('syslog_ng_mysql_api_enabled', $syslog_ng_mysql_api_enabled);

			$issaved = true;
			
			// try to create log table if not exists
			if ($action_logs_active == '1')
			{
				try
				{
					// create table
					Log_Model::create_table();
					// create partition fo today (fixes #363)
					try
					{
						ORM::factory('log')->add_partition();
					}
					catch (Exception $ignore)
					{ // ignore errors (partition already exists)
					}
				}
				catch (Exception $e)
				{
					$issaved = false;
					Settings::set('action_logs_active', 0);
					status::error('Cannot enable logs, error: ' . $e->getMessage());
				}
			}

			// try to create ulogd function if not exists
			if ($ulogd_enabled == '1' && $user_model->check_permission('CREATE ROUTINE'))
			{
				try
				{
					Members_traffic_Model::create_tables(TRUE);
					Ulog2_ct_Model::create_functions();
					Settings::set('ulogd_enabled', 1);
				}
				catch (Exception $e)
				{
					$issaved = false;
					Settings::set('ulogd_enabled', 0);
					Ulog2_ct_Model::destroy_functions();
					status::error('Cannot enable ulogd, error: ' . $e->getMessage());
				}
			}
			else
			{
				Ulog2_ct_Model::destroy_functions();
				Settings::set('ulogd_enabled', 0);
			}
			
			foreach ($form_data as $name => $value)
			{
				if ($name == 'action_logs_active' OR
					$name == 'ulogd_enabled' OR
					$name == 'syslog_ng_mysql_api_enabled')
				{
					continue;
				}
				
				$issaved = $issaved && Settings::set($name, $value);
			}
			
			if ($issaved)
			{
				status::success('System variables have been successfully updated.');
			}
			else
			{
				status::error('System variables havent been successfully updated.');
			}
			
			url::redirect(url::base().url::current());
		}
		// create view for this template
		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('logging');
		$view->content = new View('settings/main');
		$view->content->current = 'logging';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Logging');
		$view->content->description = module::get_state('logging', TRUE);
		$view->render(TRUE);
	}
	
	/**
	 * Settings for monitoring
	 * 
	 * @author Michal Kliment 
	 */
	public function monitoring()
	{
		// access control
		if (!module::e('monitoring') ||
			!$this->acl_check_edit('Settings_Controller', 'monitoring_settings'))
			Controller::error(ACCESS);
		
		// creating of new forge
		$this->form = new Forge();

		$this->form->group('Variables for monitoring');
		
		$this->form->input('monitoring_server_ip_address')
				->rules('valid_ip_address')
				->value(Settings::get('monitoring_server_ip_address'))
				->label('IP address of monitoring server')
				->help('monitoring_server_ip_address');
		
		$this->form->group('Notifications');
		
		$this->form->input('monitoring_email_to')
				->rules('valid_email')
				->value(Settings::get('monitoring_email_to'))
				->label('Send to e-mail address')
				->help('monitoring_email_to');
		
		$this->form->input('monitoring_notification_interval')
				->label('Interval of loop')
				->value(Settings::get('monitoring_notification_interval'))
				->help('monitoring_notification_interval')
				->class('number increase_decrease_buttons');
		
		$this->form->input('monitoring_notification_down_host_interval')
				->label('Maximum period of notification from host failure')
				->value(Settings::get('monitoring_notification_down_host_interval'))
				->help('monitoring_notification_down_host_interval')
				->class('number increase_decrease_buttons');
		
		$this->form->input('monitoring_notification_up_host_interval')
				->label('Maximum period of notification from host functionality again')
				->value(Settings::get('monitoring_notification_up_host_interval'))
				->help('monitoring_notification_up_host_interval')
				->class('number increase_decrease_buttons');

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			$issaved = TRUE;
			
			foreach ($form_data as $key => $val)
				$issaved = $issaved && Settings::set($key, $val);
			
			if ($issaved)
				status::success('System variables have been successfully updated.');
			else
				status::error('System variables havent been successfully updated.');
			
			$this->redirect($this->url());
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('Monitoring');
		$view->content = new View('settings/main');
		$view->content->current = 'monitoring';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Monitoring');
		$view->content->description = module::get_state('monitoring', TRUE);		
		$view->render(TRUE);
	}

	/**
	 * Form to set up vtiger variables
	 * 
	 * @author Jan Dubina
	 */
	public function vtiger()
	{
		// access control
		if (!module::e('vtiger') ||
			!$this->acl_check_edit('Settings_Controller', 'vtiger_settings'))
			Controller::error(ACCESS);

		$values_member = json_decode(Settings::get('vtiger_member_fields'), true);
		$values_user = json_decode(Settings::get('vtiger_user_fields'), true);
		
		// creating of new forge
		$this->form = new Forge();
		
		$this->form->group('Vtiger integration');

		$this->form->input('vtiger_domain')
				->label('Domain')
				->rules('valid_url|required')
				->value(Settings::get('vtiger_domain'));

		$this->form->input('vtiger_username')
				->label('Username')
				->rules('required')
				->value(Settings::get('vtiger_username'));

		$this->form->input('vtiger_user_access_key')
				->label('User access key')
				->rules('required')
				->value(Settings::get('vtiger_user_access_key'));
		
		$this->form->group(__('Vtiger field names').' - '.__('Accounts'));
		
		$this->form->input('member_id')
				->label('FreenetIS ID')
				->rules('required')
				->value($values_member['id']);
		
		$this->form->input('member_name')
				->rules('required')
				->value($values_member['name']);
		
		$this->form->input('member_acc_type')
				->label('Account type')
				->rules('required')
				->value($values_member['acc_type']);
		
		$this->form->input('member_employees')
				->label('Employees')
				->rules('required')
				->value($values_member['employees']);
		
		$this->form->input('member_type')
				->label('Type')
				->rules('required')
				->value($values_member['type']);
		
		$this->form->input('member_entrance_date')
				->label('Entrance date')
				->rules('required')
				->value($values_member['entrance_date']);
		
		$this->form->input('member_var_sym')
				->label('Variable symbol')
				->rules('required')
				->value($values_member['var_sym']);
		
		$this->form->input('member_organization_identifier')
				->label('Organization identifier')
				->rules('required')
				->value($values_member['organization_identifier']);
		
		$this->form->input('member_do_not_send_emails')
				->label('Do not send emails')
				->rules('required')
				->value($values_member['do_not_send_emails']);
		
		$this->form->input('member_notify_owner')
				->label('notify owner')
				->rules('required')
				->value($values_member['notify_owner']);
		
		$this->form->input('member_phone1')
				->label('Phone')
				->rules('required')
				->value($values_member['phone1']);
		
		$this->form->input('member_phone2')
				->label(__('Phone').' 2')
				->rules('required')
				->value($values_member['phone2']);
		
		$this->form->input('member_phone3')
				->label(__('Phone').' 3')
				->rules('required')
				->value($values_member['phone3']);
		
		$this->form->input('member_email1')
				->label('Email')
				->rules('required')
				->value($values_member['email1']);
		
		$this->form->input('member_email2')
				->label(__('Email').' 2')
				->rules('required')
				->value($values_member['email2']);
		
		$this->form->input('member_email3')
				->label(__('Email').' 3')
				->rules('required')
				->value($values_member['email3']);
		
		$this->form->input('member_street')
				->label('Street')
				->rules('required')
				->value($values_member['street']);
		
		$this->form->input('member_town')
				->label('Town')
				->rules('required')
				->value($values_member['town']);
		
		$this->form->input('member_country')
				->label('Country')
				->rules('required')
				->value($values_member['country']);
		
		$this->form->input('member_zip_code')
				->label('Zip code')
				->rules('required')
				->value($values_member['zip_code']);
		
		$this->form->input('member_comment')
				->label('Comment')
				->rules('required')
				->value($values_member['comment']);
		
		$this->form->group(__('Vtiger field names').' - '.__('Contacts'));
		
		$this->form->input('user_id')
				->label('FreenetIS ID')
				->rules('required')
				->value($values_user['id']);
		
		$this->form->input('user_name')
				->label('Name')
				->rules('required')
				->value($values_user['name']);
		
		$this->form->input('user_middle_name')
				->label('Middle name')
				->rules('required')
				->value($values_user['middle_name']);
		
		$this->form->input('user_surname')
				->label('surname')
				->rules('required')
				->value($values_user['surname']);
		
		$this->form->input('user_pre_title')
				->label('Pre title')
				->rules('required')
				->value($values_user['pre_title']);
		
		$this->form->input('user_post_title')
				->label('Post title')
				->rules('required')
				->value($values_user['post_title']);
		
		$this->form->input('user_member_id')
				->label('Member ID')
				->rules('required')
				->value($values_user['member_id']);
		
		$this->form->input('user_birthday')
				->label('Birthday')
				->rules('required')
				->value($values_user['birthday']);
		
		$this->form->input('user_do_not_call')
				->label('Do not call')
				->rules('required')
				->value($values_user['do_not_call']);
		
		$this->form->input('user_do_not_send_emails')
				->label('Do not send emails')
				->rules('required')
				->value($values_user['do_not_send_emails']);
		
		$this->form->input('user_notify_owner')
				->label('Notify owner')
				->rules('required')
				->value($values_user['notify_owner']);
		
		$this->form->input('user_phone1')
				->label('Phone')
				->rules('required')
				->value($values_user['phone1']);
		
		$this->form->input('user_phone2')
				->label(__('Phone').' 2')
				->rules('required')
				->value($values_user['phone2']);
		
		$this->form->input('user_phone3')
				->label(__('Phone').' 3')
				->rules('required')
				->value($values_user['phone3']);
		
		$this->form->input('user_email1')
				->label('Email')
				->rules('required')
				->value($values_user['email1']);
		
		$this->form->input('user_email2')
				->label(__('Email').' 2')
				->rules('required')
				->value($values_user['email2']);
		
		$this->form->input('user_email3')
				->label(__('Email').' 3')
				->rules('required')
				->value($values_user['email3']);
		
		$this->form->input('user_street')
				->label('Street')
				->rules('required')
				->value($values_user['street']);
		
		$this->form->input('user_town')
				->label('Town')
				->rules('required')
				->value($values_user['town']);
		
		$this->form->input('user_country')
				->label('Country')
				->rules('required')
				->value($values_user['country']);
		
		$this->form->input('user_zip_code')
				->label('Zip code')
				->rules('required')
				->value($values_user['zip_code']);
		
		$this->form->input('user_comment')
				->label('Comment')
				->rules('required')
				->value($values_user['comment']);
		
		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			$values_member['id'] = $form_data['member_id'];
			$values_member['name'] = $form_data['member_name'];
			$values_member['acc_type'] = $form_data['member_acc_type'];
			$values_member['employees'] = $form_data['member_employees'];
			$values_member['type'] = $form_data['member_type'];
			$values_member['entrance_date'] = $form_data['member_entrance_date'];
			$values_member['var_sym'] = $form_data['member_var_sym'];
			$values_member['organization_identifier'] = $form_data['member_organization_identifier'];
			$values_member['do_not_send_emails'] = $form_data['member_do_not_send_emails'];
			$values_member['notify_owner'] = $form_data['member_notify_owner'];
			$values_member['phone1'] = $form_data['member_phone1'];
			$values_member['phone2'] = $form_data['member_phone2'];
			$values_member['phone3'] = $form_data['member_phone3'];
			$values_member['email1'] = $form_data['member_email1'];
			$values_member['email2'] = $form_data['member_email2'];
			$values_member['email3'] = $form_data['member_email3'];
			$values_member['street'] = $form_data['member_street'];
			$values_member['town'] = $form_data['member_town'];
			$values_member['country'] = $form_data['member_country'];
			$values_member['zip_code'] = $form_data['member_zip_code'];
			$values_member['comment'] = $form_data['member_comment'];
			
			$values_user['id'] = $form_data['user_id'];
			$values_user['name'] = $form_data['user_name'];
			$values_user['middle_name'] = $form_data['user_middle_name'];
			$values_user['surname'] = $form_data['user_surname'];
			$values_user['pre_title'] = $form_data['user_pre_title'];
			$values_user['post_title'] = $form_data['user_post_title'];
			$values_user['member_id'] = $form_data['user_member_id'];
			$values_user['birthday'] = $form_data['user_birthday'];
			$values_user['do_not_call'] = $form_data['user_do_not_call'];
			$values_user['do_not_send_emails'] = $form_data['user_do_not_send_emails'];
			$values_user['notify_owner'] = $form_data['user_notify_owner'];
			$values_user['phone1'] = $form_data['user_phone1'];
			$values_user['phone2'] = $form_data['user_phone2'];
			$values_user['phone3'] = $form_data['user_phone3'];
			$values_user['email1'] = $form_data['user_email1'];
			$values_user['email2'] = $form_data['user_email2'];
			$values_user['email3'] = $form_data['user_email3'];
			$values_user['street'] = $form_data['user_street'];
			$values_user['town'] = $form_data['user_town'];
			$values_user['country'] = $form_data['user_country'];
			$values_user['zip_code'] = $form_data['user_zip_code'];
			$values_user['comment'] = $form_data['user_comment'];
			
			$issaved = true;
			
			$issaved = $issaved && Settings::set('vtiger_domain', $form_data['vtiger_domain']);
			$issaved = $issaved && Settings::set('vtiger_username', $form_data['vtiger_username']);
			$issaved = $issaved && Settings::set('vtiger_user_access_key', $form_data['vtiger_user_access_key']);
			$issaved = $issaved && Settings::set('vtiger_member_fields', json_encode($values_member));
			$issaved = $issaved && Settings::set('vtiger_user_fields', json_encode($values_user));
			
			if ($issaved)
			// if all action were succesfull
			{
				status::success('System variables have been successfully updated.');
			}
			else
			// if not
			{
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('settings/vtiger');
		}

		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('Vtiger integration');
		$view->content = new View('settings/main');
		$view->content->current = 'vtiger';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Vtiger integration');
		
		$view->render(TRUE);
	}

	// start of validation function

	/**
	 * Check if intervals of VoIP numbers are valid
	 * 
	 * @author Michal Kliment
	 * @param object $input input to validation
	 */
	public function valid_voip_number_interval($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		// parse interval to array of VoIP numbers
		$numbers = explode('-', $input->value);
		// check if count of numbers is smaller or longer than 2 
		// (123456789, 987654321; NOT 123456,654321,987654)
		if (count($numbers) != 2)
		{
			$input->add_error('required', __('Bad VoIP number interval format'));
		}
		else
		{
			// check if first and second number are 9 characters long (123456789, NOT 1234567890)
			if (strlen($numbers[0]) != 9 || strlen($numbers[1]) != 9)
			{
				$input->add_error('required', __(
						'VoIP number must be 9 characters long'
				));
			}
			// check if first and second number are numbers :-) (123456789, NOT abcdefghi)
			if (!is_numeric($numbers[0]) || !is_numeric($numbers[1]))
			{
				$input->add_error('required', __('VoIP number must be a number'));
			}
			// check if first number are not larger than second number
			if ($numbers[0] > $numbers[1])
			{
				$input->add_error('required', __(
						'First number mustn\'t be larger then second number'
				));
			}
		}
	}

	/**
	 * Validator
	 *
	 * @param object $input 
	 */
	public function valid_voip_number_exclude($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

        $value = $input->value;

        // do not check empty
        if (empty($value)) {
            return;
        }
		
		// parse interval to array of VoIP numbers
		$numbers = explode(';', $value);
		// check if count of numbers is smaller or longer than 2 
		// (123456789, 987654321; NOT 123456,654321,987654)

		foreach ($numbers as $number)
		{
			// check if first and second number are 9 characters long (123456789, NOT 1234567890)
			if (strlen($number) != 9)
			{
				$input->add_error('required', __('VoIP number must be 9 characters long'));
				break;
			}
			// check if first and second number are numbers :-) (123456789, NOT abcdefghi)
			if (!is_numeric($number))
			{
				$input->add_error('required', __('VoIP number must be a number'));
				break;
			}
		}
	}// end of validatation function
	
	/**
	 * Validates suffix
	 * 
	 * @param object $input
	 */
	public function valid_suffix($input = null)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if (!preg_match ("/^\/([A-Za-z0-9\-_]+\/)*$/", $input->value))
		{
			$input->add_error('required', __("Suffix has to start with slash character, has to end with slash character and contains only a-z, 0-9, - and /"));
		}
	}
	
	/**
	 * Validates domain
	 * 
	 * @param object $input
	 */
	public function valid_domain($input = null)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		if (!preg_match ("/^[A-Za-z0-9\-_\.]+$/", $input->value))
		{
			$input->add_error('required', __("Domain has to contain only a-z, 0-9, -, / and dot characters"));
		}
	}
}
