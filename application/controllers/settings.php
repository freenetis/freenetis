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
	 * Contruct of controller sets tabs names
	 */
	public function __construct()
	{
		parent::__construct();

		$this->sections = array
		(
			'info'					=> __('Info'),
			'system'				=> __('System'),
			'approval'				=> __('Approval'),
			'email'					=> __('Email'),
			'sms'					=> __('SMS'),
			'voip'					=> __('VoIP'),
			'notifications'			=> __('Notifications'),
			'qos'					=> __('QoS'),
			'monitoring'			=> __('Monitoring'),
			'logging'				=> __('Logging'),
			'registration_export'	=> __('Export of registration')
		);
	}

	/**
	 * Redirects to info
	 */
	public function index()
	{
		$this->info();
	}
	
	/**
	 * info() displays various system information
	 * 
	 * @author Tomas Dulik
	 */
	public function info()
	{
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		$data = array();
		
		if (defined('FREENETIS_VERSION'))
		{
			$data['FreenetIS ' . __('version')] = FREENETIS_VERSION;
		}
		
		$data[__('DB schema revision')] = Settings::get('db_schema_version');
		$data[__('CRON state')] = module_state::get_state('cron');
		$data[__('Redirection state')] = module_state::get_state('redirection');
		$data[__('QoS state')] = module_state::get_state('qos');
		$data[__('Monitoring state')] = module_state::get_state('monitoring');
		$data[__('Logging state')] = module_state::get_state('logging');
		
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
			$html = preg_replace('/table border="0"/is', 'table class="extended"', $html);
			$html = preg_replace('/width="600"/is', 'width="720" style="table-layout:fixed; overflow: hidden"', $html);
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
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge('settings/system');

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

		// currency
		$this->form->input('currency')
				->rules('length[3,40]|required')
				->value(Settings::get('currency'));

		// self-registration
		$this->form->radio('self_registration')
				->label('Self-registration')
				->options(arr::bool())
				->default(Settings::get('self_registration'));

		// forgotten password
		$this->form->radio('forgotten_password')
				->label('Forgotten password')
				->options(arr::bool())
				->default(Settings::get('forgotten_password'));

		$this->form->group('E-mail settings');

		$this->form->input('email_default_email')
				->label('Default e-mail')
				->rules('length[3,100]|valid_email')
				->value(Settings::get('email_default_email'));

		$this->form->group(__('URL settings') . ' ' . help::hint('url_settings'));

		$this->form->dropdown('protocol')
				->rules('length[3,100]')
				->options(array
				(
					'http' => 'http',
					'https' => 'https'
				))->selected(url::protocol());
		
		$this->form->input('domain')
				->rules('required|length[3,100]')
				->value(url::domain());
		
		$this->form->input('suffix')
				->rules('required|valid_suffix')
				->value(url::suffix());

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

		$this->form->group('Network settings');

		$this->form->textarea('address_ranges')
				->help('address_ranges')
				->rules('valid_address_ranges')
				->value(str_replace(",","\n", Settings::get('address_ranges')))
				->class('autosize');

		$this->form->group('Module settings');

		$timeout = Settings::get('module_status_timeout');
		
		$this->form->input('module_status_timeout')
				->rules('required|valid_numeric')
				->class('increase_decrease_buttons')
				->value($timeout)
				->help('Time threshold in minutes, before module is shown as inactive');

		
		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			$issaved = true;
			$message = '';

			foreach ($form_data as $name => $value)
			{
				if ($name == 'module_status_timeout')
				{
					$value = max($value, 1);
				}
				else if ($name == 'address_ranges')
				{
					$value = str_replace("\n", ",", $value);
				}

				$issaved = $issaved && Settings::set($name, $value);
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
						__('System variables havent been successfully updated.').
						'<br />' . $message, FALSE
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
	 * Settings for QoS 
	 */
	public function qos()
	{
		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// creating of new forge
		$this->form = new Forge('settings/qos');
		
		$this->form->group('Variables for QoS');
		
		$this->form->checkbox('qos_enabled')
				->value('1')
				->label('Enable QoS');
		
		if (Settings::get('qos_enabled') == 1)
			$this->form->qos_enabled->checked('checked');

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
		$view->content->description = module_state::get_state('qos', TRUE);
		
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
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge('settings/email');

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
				->help('For SMTP settings only.');

		$this->form->input('email_port')
				->label('Port')
				->rules('valid_numeric')
				->value(Settings::get('email_port'))
				->help('For SMTP settings only.');

		$this->form->input('email_username')
				->label('User name')
				->value(Settings::get('email_username'))
				->help('For SMTP settings only.');

		$this->form->input('email_password')
				->label('Password')
				->value(Settings::get('email_password'))
				->help('For SMTP settings only.');

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
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		$approval_templates = ORM::factory('approval_template')->select_list('id', 'name');
		
		$arr_approval_templates = array
		(
			NULL => '----- ' . __('Select approval template') . ' -----'
		) + $approval_templates;

		// creating of new forge
		$this->form = new Forge('settings/approval');

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

		$this->form->group('Request');

		$this->form->dropdown('default_request_approval_template')
				->label('Default approval template')
				->options($arr_approval_templates)
				->selected(Settings::get('default_request_approval_template'))
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
	 * VOIP settings
	 */
	public function voip()
	{
		// access control
		if (!$this->acl_check_edit(get_class($this), 'system'))
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
						$voip_sip->create_functions();
						// create views
						$voip_sip->create_views();
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
				) . '.', FALSE);
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
		if (!$this->acl_check_edit(get_class($this), 'system'))
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
			
			$this->form->group(Sms::get_driver_name($key));

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
		if (!$this->acl_check_edit('Messages_Controller', 'message'))
			Controller::error(ACCESS);
		
		// creating of new forge
		$this->form = new Forge('settings/notifications');
		
		$this->form->group('General settings');
		
		$this->form->input('payment_notice_boundary')
				->label(__('Payment notice boundary')." (".
						Settings::get('currency')."):&nbsp;".
						help::hint('payment_notice_boundary'))
				->value(Settings::get('payment_notice_boundary'));
		
		$this->form->input('debtor_boundary')
				->label(__('Debtor boundary')." (".
						Settings::get('currency')."):&nbsp;".
						help::hint('debtor_boundary'))
				->value(Settings::get('debtor_boundary'));
		
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
		
		$this->form->group('E-mail');
		
		$this->form->input('email_subject_prefix')
				->label(__('E-mail subject prefix').':')
				->value(Settings::get('email_subject_prefix'));
		
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
		 
		// create view for this template
		$view = new View('main');
		$view->title = __('System') . ' - ' . __('Notification settings');
		$view->content = new View('settings/main');
		$view->content->current = 'notifications';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Notification settings');
		$view->content->description = module_state::get_state('redirection', TRUE);
		
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
		if (!$this->acl_check_edit(get_class($this), 'system'))
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
			if (isset($form_data['ulogd_enabled']) &&
				$form_data['ulogd_enabled'] == 1)
			{
				$ulogd_enabled = '1';
			}
			else
			{
				$ulogd_enabled = '0';
			}
			
			// syslog-ng mysql
			if (isset($form_data['syslog_ng_mysql_api_enabled']) &&
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
		}
		// create view for this template
		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('logging');
		$view->content = new View('settings/main');
		$view->content->current = 'logging';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Logging');
		$view->content->description = module_state::get_state('logging', TRUE);
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
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);
		
		// creating of new forge
		$this->form = new Forge();

		$this->form->group('Variables for monitoring');
		
		$this->form->checkbox('monitoring_enabled')
				->value('1')
				->label('Enable monitoring');
		
		if (Settings::get('monitoring_enabled') == 1)
			$this->form->monitoring_enabled->checked('checked');
		
		$this->form->input('monitoring_server_ip_address')
				->rules('valid_ip_address')
				->value(Settings::get('monitoring_server_ip_address'))
				->label('IP address of monitoring server')
				->help('monitoring_server_ip_address');
		
		$this->form->input('monitoring_email_to')
				->rules('valid_email')
				->value(Settings::get('monitoring_email_to'))
				->label('Send to e-mail address')
				->help('monitoring_email_to');

		$this->form->submit('Save');

		// form validate
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			
			$issaved = TRUE;
			
			if (isset($form_data['monitoring_enabled']))
				$monitoring_enabled = arr::remove('monitoring_enabled', $form_data);
			else
				$monitoring_enabled = 0;

			$issaved = $issaved && Settings::set('monitoring_enabled', $monitoring_enabled);
			
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
		$view->content->description = module_state::get_state('monitoring', TRUE);		
		$view->render(TRUE);
	}

	/**
	 * Export of registration
	 */
	public function registration_export()
	{
		// access control
		if (!$this->acl_check_edit(get_class($this), 'system'))
			Controller::error(ACCESS);

		// creating of new forge
		$this->form = new Forge('settings/registration_export');
		
		$this->form->group('Export of registration');

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
			{
				status::success('System variables have been successfully updated.');
			}
			else
			{
				status::error('System variables havent been successfully updated.');
			}

			url::redirect('settings/registration_export');
		}
		
		// create view for this template
		$view = new View('main');
		$view->title = __('Settings') . ' - ' . __('export of registration');
		$view->content = new View('settings/main');
		$view->content->current = 'registration_export';
		$view->content->content = $this->form->html();
		$view->content->headline = __('Export of registration');

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
		
		// parse interval to array of VoIP numbers
		$numbers = explode(';', $input->value);
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
	
}
