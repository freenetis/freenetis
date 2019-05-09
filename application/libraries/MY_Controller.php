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

// numbers of errors
define('ACCESS', 1);
define('EMAIL', 2);
define('DATABASE', 3);
define('DATABASE_DOWNGRATE', 9);
define('DATABASE_OLD_MECHANISM', 10);
define('DATABASE_UPGRADE_NOT_ENABLED', 11);
define('RECORD', 4);
define('PAGE', 5);
define('WRITABLE', 7);
define('READONLY', 8);

// numbers of warnings, their identifier numbers have to differ from error messages
// for example when programmer misleads error and warning
define('PARAMETER', 1001);

/**
 * Main controller creates menu, handles changes in svn repository (database upgrade), ...
 *
 * BE CAREFUL HERE, CATCH EVERY EXCEPTION, OTHERWISE FREENETIS
 * WITH JUST SMALL ERROR BECOMES COMPLETELY UNUSABLE
 */
class Controller extends Controller_Core
{
	/** @var integer */
	const ICON_ERROR	= 1;
	/** @var integer */
	const ICON_GOOD		= 2;
	/** @var integer */
	const ICON_HELP		= 3;
	/** @var integer */
	const ICON_INFO		= 4;
	/** @var integer */
	const ICON_WARNING	= 5;

	/**
	 * Controller singleton
	 * 
	 * @var Controller
	 */
	private static $instance;
	
	/**
	 * Paths for which login is not required
	 *
	 * @var array
	 */
	private static $login_not_required = array
	(
		/* login, scheduler, instalation */
		'login',
		'forgotten_password',
		'scheduler/run',
		'installation',
		'setup_config',
		'setup_config/htaccess',
		'setup_config/setup',
		'redirect/logo',
		/* registration */
		'registration',
		'registration/complete',
		'js/registration',
		'json/get_streets_by_town',
		'json/get_address',
		'address_points/get_gps_by_address',
		'address_points/get_gps_by_address_string',
	);
	
	/** @var unknown_type */
	public $arr;
	/** @var Setting_Model Settings */
	public $settings = NULL;
	/** @var integer */
	public $popup = 0;
	/** @var integer */
	public $dialog = 0;
	/** @var boolean */
	public $noredirect = FALSE;
	/** @var boolean */
	public $user_has_voip = 0;
	/** @var string */
	public $ip_address_span = '';
	/** @var integer */
	public $unread_user_mails = 0;
	/** @var integer */
	public $count_of_unclosed_logged_errors = 0;
	/** @var integer */
	public $devices_down_count = 0;
	/** @var Database_Result */
	public $user_favourites_pages = NULL;
	/** @var boolean $axo_doc_access Enable access to AXO doc */
	public $axo_doc_access = FALSE;
	/** @var integer $member_id		ID of logged member */
	protected $member_id;
	/** @var integer $user_id		ID of logged user */
	protected $user_id;
	/** @var integer $account_id	ID of logged member account */
	protected $member_account_id = 1;
	/** @var Session */
	protected $session;
	/** @var $groups_aro_map Groups_aro_map_Model */
	private $groups_aro_map;
	
	/**
	 * Contruct of controller, creates singleton or return it
	 */
	public function __construct()
	{
		parent::__construct();
		
		// This part only needs to be run once
		if (self::$instance === NULL)
		{
			// init settings
			$this->settings = new Settings();

			$not_setup = !file_exists('config.php') || !file_exists('.htaccess');

			// change setting for non-setup in order to prevent database init
			// in Settings
			if ($not_setup)
			{
				Settings::set_offline_mode(TRUE);

				$this->settings->set('index_page', !file_exists('.htaccess'));
				// Choose all automatically for setup (see url helper)
				$this->settings->set('domain', '');
				$this->settings->set('suffix', '');
				$this->settings->set('protocol', '');
			}

			// init sessions
			$this->session = Session::instance();
			
			// store user ID from session
			$this->user_id = $this->session->get('user_id', 0);
		
			// store member ID from session
			$this->member_id = $this->session->get('member_id', 0);

			// test if visitor is logged in, or he accesses public
			// controllers like registration, redirect, installation, etc.
			if (!in_array(url_lang::current(), self::$login_not_required) &&
				url_lang::current(1) != 'web_interface' &&
				url_lang::current(2) != 'devices/export' &&
				!$this->user_id)
			{
				// Not logged in - redirect to login page
				$this->session->set_flash('err_message', __('Must be logged in'));
				
				// Do not logout after login
				if (url_lang::current(1) != 'login' &&
					url_lang::current(1) != 'js')
				{
					$this->session->set('referer', url_lang::current());
				}
				else
				{
					$this->session->set('referer', '');
				}
				
				// Redirect to login
				url::redirect('login');
				
				// Die
				die();
			}

			// if true, freenetis will run in popup mode (without header and menu)
			$this->popup = (isset($_GET['popup']) && $_GET['popup']) ? 1 : 0;

			// if true, freenetis will run in text mod for dialog
			$this->dialog = (isset($_GET['dialog']) && $_GET['dialog']) ? 1 : 0;
			
			// if true, method redirect will not redirect
			$this->noredirect = ($this->input->get('noredirect') || $this->input->post('noredirect'));

			// config file doesn't exist, we must create it
			if ($not_setup)
			{
				// protection before loop
				if (url_lang::current(1) == 'setup_config')
					return;
				
				url::redirect('setup_config');
			}

			// protection before loop
			if (url_lang::current(1) == 'installation')
			{
				return;
			}

			// test database connection
			if (!db::test())
			{
				Controller::error(DATABASE);
			}
			
			// db schema version is null => we must run install
			if (!Version::get_db_version())
			{
				url::redirect('installation');
			}
			// db schema is not up to date => we must run upgrade
			else if (!Version::is_db_up_to_date())
			{
				// change database encoding if incorect
				try
				{
					$db = Database::instance();
					
					/**
					 * @todo in the future the collate should be used according  
					 *		 to language system settings
					 */
					if ($db->get_variable_value('character_set_database') != 'utf8' || 
						$db->get_variable_value('collation_database') != 'utf8_czech_ci')
					{
						$db->alter_db_character_set(
							Config::get('db_name'), 'utf8', 'utf8_czech_ci'
						);
					}
				}
				catch (Exception $e)
				{
					Log::add_exception($e);
					$m = __('Cannot set database character set to UTF8');
					self::showbox($m, self::ICON_ERROR);
				}
				
				// try to open mutex file
				if (($f = @fopen(server::base_dir().'/upload/mutex', 'w')) === FALSE)
				{
					// directory is not writeable
					self::error(WRITABLE, server::base_dir().'/upload/');
				}
				
				// acquire an exclusive access to file
				// wait while database is being updated
				if (flock($f, LOCK_EX))
				{
					// first access - update db
					// other access - skip
					if (!Version::is_db_up_to_date())
					{
						try
						{
							Version::make_db_up_to_date();
						}
						catch (Not_Enabled_Upgrade_Exception $neu)
						{
							self::error(DATABASE_UPGRADE_NOT_ENABLED, $neu->getMessage());
						}
						catch (Old_Mechanism_Exception $ome)
						{
							self::error(DATABASE_OLD_MECHANISM);
						}
						catch (Database_Downgrate_Exception $dde)
						{
							self::error(DATABASE_DOWNGRATE);
						}
						catch (Exception $e)
						{
							throw new Exception(
									__('Database upgrade failed') . ': ' .
									$e->getMessage(), 0, $e
							);
						}
					}
					
					// unlock mutex file
					flock($f, LOCK_UN);
				}
				
				// close mutex file
				fclose($f);
			}
			
			$this->preprocessor_if_enabled();

			// Singleton instance
			self::$instance = $this;
		}
		else // copy resources from singleton in order to be capable to initiate another controller
		{
			$this->settings = self::$instance->settings;
			$this->session = self::$instance->session;
			$this->user_id = self::$instance->user_id;
			$this->member_id = self::$instance->member_id;
			$this->popup = self::$instance->popup;
			$this->dialog = self::$instance->dialog;
			$this->noredirect = self::$instance->noredirect;

			$this->preprocessor_if_enabled();
		}
	}
	
	/**
	 * Singleton instance of Controller.
	 * 
	 * @author Michal Kliment
	 * @return Controller object
	 */
	public static function & instance()
	{
		// Create the instance if it does not exist
		empty(self::$instance) and new Controller;

		return self::$instance;
	}

	/**
	 * Function shows error of given message number.
	 * 
	 * @param integer $message_type
	 * @param string $content
	 */
	public static function error($message_type, $content = NULL)
	{
		$response_code = NULL;
		$fatal = FALSE;
		
		switch ($message_type)
		{
			case ACCESS:
				$message = url_lang::lang('states.Access denied');
				$response_code = 403; // Forbidden
				break;
			case EMAIL:
				$message = url_lang::lang('states.Failed to send e-mail') .
					'<br />' . url_lang::lang('states.Please check settings.');
				$response_code = 500; // Internal server error
				break;
			case DATABASE:
				$message = url_lang::lang('states.Failed to connect to database') .
					'<br />' . url_lang::lang('states.Please check settings.');
				$response_code = 500; // Internal server error
				$fatal = TRUE;
				break;
			case DATABASE_DOWNGRATE:
				$message = url_lang::lang('states.Failed to update database') .
					'<br />' . url_lang::lang('states.Downgrade not allowed.');
				$response_code = 500; // Internal server error
				$fatal = TRUE;
				break;
			case DATABASE_OLD_MECHANISM:
				$message = url_lang::lang('states.Failed to update database') .
					'<br /><br /><span style="font-size:12px">' .
					url_lang::lang('help.old_upgrade_system') . '</span>';
				$response_code = 500; // Internal server error
				$fatal = TRUE;
				break;
			case DATABASE_UPGRADE_NOT_ENABLED:
				$message = url_lang::lang('states.Failed to update database');
				$response_code = 500; // Internal server error
				$fatal = TRUE;
				break;
			case RECORD:
				$message = url_lang::lang('states.This record does not exist');
				$response_code = 404; // Not found
				break;
			case PAGE:
				$message = url_lang::lang('states.Page not found');
				$response_code = 404; // Not found
				break;
			case WRITABLE:
				$message = url_lang::lang('states.Directory or file is not writable.');
				$response_code = 500; // Internal server error
				break;
			case READONLY:
				$message = url_lang::lang('states.Item is read only.');
				$response_code = 403; // Internal server error
				break;
			default:
				$message = url_lang::lang('states.Unknown error message');
				$response_code = 500; // Internal server error
				break;
		}
		
		self::showbox($message, self::ICON_ERROR, $content, $response_code, $fatal);
	}

	/**
	 * Function shows warning of given message number.
	 * 
	 * @param integer $message_type
	 * @param string $content
	 */
	public static function warning($message_type, $content = NULL)
	{
		$response_code = NULL;
		
		switch ($message_type)
		{
			case PARAMETER:
				$message = url_lang::lang('states.Parameter required');
				$response_code = 404; // Internal server error
				break;
			default:
				$message = url_lang::lang('states.Unknown warning message');
				$response_code = 500; // Internal server error
				break;
		}
		
		self::showbox($message, self::ICON_WARNING, $content, $response_code);
	}

	/**
	 * Function renders error and warning messages.
	 * 
	 * @param string $message Message to display
	 * @param integer $type Type of message (error, info, warning, etc.)
	 * @param string $content Some message that describe message in more detail way
	 * @param string $http_response_code Send some response code (xxx)
	 * @param boolean $fatal_error Is this error a fatal error?
	 */
	private static function showbox($message, $type, $content = NULL, 
		$http_response_code = NULL, $fatal_error = FALSE)
	{
		if ($fatal_error)
		{
			$view = new View('main_statesbox');
		}
		else
		{
			$view = new View('main');
		}
		
		$view->content = new View('statesbox');

		$src = NULL;
		
		switch ($type)
		{
			case self::ICON_ERROR:
				$view->title = __('Error');
				$src = 'media/images/states/error.png';
				break;
			case self::ICON_GOOD:
				$view->title = __('Good');
				$src = 'media/images/states/good.png';
				break;
			case self::ICON_HELP:
				$view->title = __('Help');
				$src = 'media/images/states/help.png';
				break;
			case self::ICON_INFO:
				$view->title = __('Info');
				$src = 'media/images/states/info.png';
				break;
			case self::ICON_WARNING:
				$view->title = __('Warning');
				$src = 'media/images/states/warning.png';
				break;
		}
		
		if ($http_response_code)
		{
			header("HTTP/1.1 $http_response_code");
		}
		
		$view->content->icon = html::image(array
		(
			'src'		=> $src,
			'width'		=> '100',
			'height'	=> '100',
			'alt'		=> 'Image',
			'class'		=> 'noborder'
		));
		
		$view->content->message = $message;
		
		if (isset($content))
		{
			$view->content->content = $content;
		}
		
		$view->loading_hide = TRUE;
		$view->render(TRUE);
		
		// must be die() - else it will be render twice !
		die();
	}
	
	/**
	 * Checks user's access to system
	 * 
	 * @author Ondřej Fibich
	 *
	 * @param type $axo_section_value	AXO section value - Controller name
	 * @param type $axo_value			AXO value - part of Controller
	 * @param type $aco_type			ACO type of action (view, new, edit, delete, confirm)
	 * @param integer $member_id		Member to check access
	 * @param boolean $force_own		Force to use own rules for not logged user
	 *									Used at: Phone_invoices_Controller#user_field()
	 * @return bool
	 */
	private function acl_check(
			$axo_section, $axo_value, $aco_type, $member_id = NULL,
			$force_own = FALSE)
	{
		// groups aro map loaded?
		if (empty($this->groups_aro_map))
		{
			$this->groups_aro_map = new Groups_aro_map_Model();
		}
		
		// check own?
		if (($member_id == $_SESSION['member_id']) || $force_own)
		{
			// check own access
			if ($this->groups_aro_map->has_access(
					$_SESSION['user_id'], $aco_type . '_own',
					$axo_section, $axo_value
				))
			{
				// access valid
				return true;
			}
		}
		
		// check all
		return $this->groups_aro_map->has_access(
				$_SESSION['user_id'], $aco_type . '_all',
				$axo_section, $axo_value
		);
	}
	
	/**
	 * Checks if user is in ARO group
	 *
	 * @author Ondřej Fibich
	 * @param integer $group_id		ARO group ID
	 * @param integer $aro_id		User ID
	 * @return boolean				true if exists false otherwise
	 */
	public function is_user_in_group($aro_group_id, $aro_id)
	{
		return $this->groups_aro_map->groups_aro_map_exists($aro_group_id, $aro_id);
	}

	/**
	 * Fuction checks access rights
	 * Return true if currently logged user (stored in $_SESSION['user_id'])
	 * may view own $axo_value object in $axo_section
	 * (and in variable $member_id is his own id of member) or if currently logged user
	 * may view all $axo_value object in $axo_section else return false
	 * 
	 * @param $axo_section			Group of objects to view
	 * @param $axo_value			Object to view
	 * @param $member_id			Optional variable, id of other member
	 *								who is being showed by logged member 
	 * @param boolean $force_own	Force to use own rules for not logged user
	 *								Used at: Phone_invoices_Controller#user_field()
	 * @return boolean				returns true if member has enough access rights
	 */
	public function acl_check_view(
			$axo_section, $axo_value, $member_id = NULL, $force_own = FALSE)
	{
		return $this->acl_check(
				$axo_section, $axo_value, 'view', $member_id, $force_own
		);
	}

	/**
	 * Fuction checks access rights
	 * Return true if currently logged user (stored in $_SESSION['user_id'])
	 * may view own $axo_value object in $axo_section
	 * (and in variable $member_id is his own id of member) or if currently logged user
	 * may edit all $axo_value object in $axo_section else return false
	 *
	 * @param $axo_section			Group of objects to edit
	 * @param $axo_value			Object to edit
	 * @param $member_id			Optional variable, id of other member
	 *								who is being showed by logged member
	 * @param boolean $force_own	Force to use own rules for not logged user
	 *								Used at: Phone_invoices_Controller#user_field()
	 * @return boolean				Returns true if member has enough access rights
	 */
	public function acl_check_edit(
			$axo_section, $axo_value, $member_id = NULL, $force_own = FALSE)
	{
		return $this->acl_check(
				$axo_section, $axo_value, 'edit', $member_id, $force_own
		);
	}

	/**
	 * Fuction checks access rights
	 * Return true if currently logged user (stored in $_SESSION['user_id'])
	 * may view own $axo_value object in $axo_section
	 * (and in variable $member_id is his own id of member) or if currently logged user
	 * may add all $axo_value object in $axo_section else return false
	 *
	 * @param $axo_section			Group of objects to edit
	 * @param $axo_value			Object to add
	 * @param $member_id			Optional variable, id of other member
	 *								who is being showed by logged member
	 * @param boolean $force_own	Force to use own rules for not logged user
	 *								Used at: Phone_invoices_Controller#user_field()
	 * @return boolean				Returns true if member has enough access rights
	 */
	public function acl_check_new(
			$axo_section, $axo_value, $member_id = NULL, $force_own = FALSE)
	{
		return $this->acl_check(
				$axo_section, $axo_value, 'new', $member_id, $force_own
		);
	}

	/**
	 * Fuction checks access rights
	 * Return true if currently logged user (stored in $_SESSION['user_id'])
	 * may view own $axo_value object in $axo_section
	 * (and in variable $member_id is his own id of member) or if currently logged user
	 * may delete all $axo_value object in $axo_section else return false
	 *
	 * @param $axo_section			Group of objects to edit
	 * @param $axo_value			Object to delete
	 * @param $member_id			Optional variable, id of other member
	 *								who is being showed by logged member
	 * @param boolean $force_own	Force to use own rules for not logged user
	 *								Used at: Phone_invoices_Controller#user_field()
	 * @return boolean				Returns true if member has enough access rights
	 */
	public function acl_check_delete(
			$axo_section, $axo_value, $member_id = NULL, $force_own = FALSE)
	{
		return $this->acl_check(
				$axo_section, $axo_value, 'delete', $member_id, $force_own
		);
	}

	/**
	 * This methods defines whether the preprocessor of MY_Controller is loaded
	 * or not. By default preprocessor is loaded, for changing of this state 
	 * this method should be overriden in child class. (#328)
	 * 
	 * @author Ondřej Fibich
	 * @return boolean Is preprocessor loaded?
	 */
	protected function is_preprocesor_enabled()
	{
		return TRUE;
	}

	/**
	 * Loads variables only if preprocessor is enabled and user is logged.
	 */
	private function preprocessor_if_enabled()
	{
		if ($this->is_preprocesor_enabled() && $this->user_id)
		{
			// for preprocessing some variable
			try
			{
				$this->preprocessor();
			}
			catch(Exception $e)
			{
				Log::add_exception($e);
			}
		}
	}

	/**
	 * Function to preprocessing of some useful variables
	 * 
	 * @author Michal Kliment
	 */
	private function preprocessor()
	{
		// helper class
		$pm = new Preprocessor_Model();
		$ra_ip = server::remote_addr();
	
		// boolean variable if user has active voip number (for menu rendering)
		$this->user_has_voip = (bool) $pm->has_voip_sips($this->user_id);

		// count of unread mail messages of user
		$this->unread_user_mails = $pm->count_all_unread_inbox_messages_by_user_id($this->user_id);

		// is this page favourite
		$this->is_favourite = $pm->is_users_favourite($this->user_id, url_lang::current());

		// gets account id of memeber (do not use Member_Model::ASSOCIATION here!)
		if ($this->acl_check_view('Accounts_Controller', 'transfers', $this->member_id) &&
			$this->member_id != 1)
		{
			$this->member_account_id = $pm->get_first_member_account_id($this->member_id);
		}

		// ip address span
		$this->ip_address_span = $ra_ip;
		
		// DZOLO (2011-09-05)
		// This function is wery slow, when internet connection is off.
		/*if (($ptr_record = dns::get_ptr_record($this->ip_address_span)) != '')
		{
			$this->ip_address_span .= ' <i>(' . $ptr_record . ')</i>';
		}*/

		// allowed subnets are enabled
		if (Settings::get('allowed_subnets_enabled') && $this->member_id &&
			$this->acl_check_edit(
					'Allowed_subnets_Controller', 'allowed_subnet', $this->member_id
			))
		{
			// toggle button between allowed subnets
			$as = $pm->get_allowed_subnet_by_member_and_ip_address(
					$this->member_id, server::remote_addr()
			);
			
			// it's possible to change allowed allowed subnets
			if ($as && $as->id &&
				$pm->count_all_disabled_allowed_subnets_by_member($this->member_id))
			{
				$uri = 'allowed_subnets/change/' .$as->id;
				
				if ($as->enabled)
				{
					$this->ip_address_span .= ' ' . html::anchor($uri, html::image(array
					(
						'src'	=> 'media/images/states/active.png',
						'title'	=> __('Disable this subnet')
					)) . ' ' . __('Disable this subnet')) . ' ' . help::hint('allowed_subnets_enabled');
				}
				else
				{
					$this->ip_address_span .= ' ' . html::anchor($uri, html::image(array
					(
						'src'	=> 'media/images/states/inactive.png',
						'title'	=> __('Enable this subnet')
					)) . ' ' . __('Enable this subnet')) . ' ' . help::hint('allowed_subnets_disabled');
				}
			}
		}

		// connection request staff (#143)
		if (Settings::get('connection_request_enable') &&
			url_lang::current(2) != 'connection_requests/add' &&
			!$this->noredirect) // not show in dialogs
		{
			$sid = $pm->get_subnet_for_connection_request($ra_ip);
			// can user make connection request for $ra_ip?
			if ($sid)
			{ // so we will inform him!
				$url = '/connection_requests/add/' . $sid . '/' . $ra_ip;
				$link = html::anchor($url, __('register this connection'));
				$m = url_lang::lang('help.connection_request_user_pre_info', $link);
				status::minfo($m, FALSE);
			}
		}

		// log queue (#462)
		if ($this->acl_check_view('Log_queues_Controller', 'log_queue'))
		{
			$this->count_of_unclosed_logged_errors = 
					$pm->count_of_unclosed_logs();
			// inform box
			if ($this->count_of_unclosed_logged_errors &&
				url_lang::current(1) != 'log_queues' &&
				!$this->noredirect)
			{
				$link = html::anchor('/log_queues/show_all', __('check it'));
				status::mwarning(url_lang::lang('help.log_queues_info', $link), FALSE);
			}
		}

		// menu favourites
		$this->user_favourites_pages = $pm->get_users_favourites($this->user_id);
		
		// access to AXO doc
		$this->axo_doc_access = (
			$this->acl_check_new('Acl_Controller', 'acl') ||
			$this->acl_check_edit('Acl_Controller', 'acl')
		);
	}
	
	/**
	 * Build menu
	 * 
	 * @author Michal Kliment
	 * @return Menu_builder
	 * @throws Exception
	 */
	public function build_menu()
	{
		$menu = new Menu_builder();
		
		$pm = new Preprocessor_Model();
		
		/***********************    FAVOURITES     ***********************/
		
		if (!empty($this->user_favourites_pages) &&
			$this->user_favourites_pages->count())
		{
			$menu->addGroup('favourites', __('Favourites'));
			
			foreach ($this->user_favourites_pages as $fav)
			{
				$default = array();
				
				if ($fav->default_page)
				{
					$default = array
					(
						'default' => TRUE
					);
				}
				
				$menu->addItem($fav->page, $fav->title, 'favourites', $default);
			}
			
			unset($this->user_favourites_pages);
		}
		
		/***********************    MY PROFILE     ***********************/
		
		$menu->addGroup('account', __('My profile'));
		
		// my profile
		if ($this->session->get('user_type') == User_Model::MAIN_USER &&
		    $this->acl_check_view('Members_Controller', 'members', $this->member_id))
		{
			$menu->addItem(
				'members/show/'.$this->member_id,
				__('My profile'), 'account');
		}
		elseif ($this->acl_check_view('Users_Controller', 'users', $this->member_id))
		{
			$menu->addItem(
				'users/show/'.$this->user_id,
				__('My profile'), 'account');
		}
		
		// my transfers
		if (Settings::get('finance_enabled') && $this->member_account_id &&
		    ($this->acl_check_view('Accounts_Controller', 'transfers', $this->member_id) ||
		    $this->acl_check_view('Members_Controller', 'currentcredit')))
		{
			$menu->addItem(
				'transfers/show_by_account/'.$this->member_account_id,
				__('My transfers'), 'account');
		}
		
		// my devices
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Devices_Controller', 'devices',$this->member_id))
		{
			$menu->addItem(
				'devices/show_by_user/'.$this->user_id,
				__('My devices'), 'account');
		}
	
		// my connection requests
		if ($this->member_id != Member_Model::ASSOCIATION &&
		    $this->acl_check_view('Connection_Requests_Controller', 'request', $this->member_id) &&
		    Settings::get('connection_request_enable'))
		{
			$menu->addItem(
				'connection_requests/show_by_member/'.$this->member_id,
				__('My connection requests'), 'account');
		}
		
		// my works
		if (Settings::get('works_enabled') &&
		    $this->member_id != Member_Model::ASSOCIATION &&
		    $this->acl_check_view('Works_Controller', 'work', $this->member_id))
		{
			$menu->addItem(
				'works/show_by_user/'.$this->user_id,
				__('My works'), 'account');
		}
		
		// my work reports
		if (Settings::get('works_enabled') &&
		    $this->member_id != Member_Model::ASSOCIATION &&
			$this->acl_check_view('Work_reports_Controller', 'work_report', $this->member_id))
		{
			
			$menu->addItem('work_reports/show_by_user/'.$this->user_id,
				__('My work reports'), 'account');
		}
		
		// my requests
		if (Settings::get('approval_enabled') &&
			$this->member_id != Member_Model::ASSOCIATION &&
			$this->acl_check_view('Requests_Controller', 'request', $this->member_id))
		{
			
			$menu->addItem('requests/show_by_user/'.$this->user_id,
				__('My requests'), 'account');
		}
		
		// my phone invoices
		if (Settings::get('phone_invoices_enabled') &&
			$this->member_id != 1 &&
			$pm->has_phone_invoices($this->user_id))
		{
			$menu->addItem(
				'phone_invoices/show_by_user/'.$this->user_id,
				__('My phone invoices'), 'account',	array
				(
					'count' => $pm->count_unfilled_phone_invoices($this->user_id)
				));
		}
		
		// my VoIP calls
		if (Settings::get('voip_enabled') && $this->user_has_voip)
		{
			$menu->addItem(
				'voip_calls/show_by_user/'.$this->user_id, 
				__('My VoIP calls'), 'account');
		}
		
		//  my mail
		$menu->addItem('mail/inbox', __('My mail'), 'account', array
		(
			'count' => $this->unread_user_mails
		));
		
		/***********************     USERS     *************************/
		
		$menu->addGroup('users', __('Users'));
		
		// list of members
		if ($this->acl_check_view('Members_Controller', 'members'))
		{
			$menu->addItem(
				'members/show_all', __('Members'), 'users');
		}

		// list of former members to delete
		if ($this->acl_check_delete('Members_Controller', 'members'))
		{
			$Xyears = intval(Settings::get('member_former_limit_years'));
			$date_Xyears_before = date('Y-m-d', strtotime('-' . $Xyears . ' years'));
			// TODO: add support for building link in Filter_form library
			$menu->addItem(
				'members/show_all?on[0]=1&types[0]=type&opers[0]=3&values[0][]=15'
				. '&tables[type]=m&tables[leaving_date]=m&on[1]=1'
				. '&types[1]=leaving_date&opers[1]=8&values[1][0]=' . $date_Xyears_before
				. '&types[2]=type&opers[2]=3&values[2][0]=1',
				__('Former members (%d years)', array($Xyears)), 'users', array
				(
					'count' => $pm->count_of_former_members_to_delete($Xyears),
					'color' => 'red'
				));
		}
		
		/**
		 * @todo Add own AXO
		 */
		
		// list of registered applicants
		if (Settings::get('self_registration') &&
		    $this->acl_check_view('Members_Controller', 'members'))
		{
			$menu->addItem(
				'members/applicants', __('Registered applicants'),
				'users', array
				(
					'count' => $pm->count_of_registered_members()
				));
		}
		
		// list of membership interrupts
		if (Settings::get('membership_interrupt_enabled') &&
			$this->acl_check_view('Members_Controller', 'membership_interrupts'))
		{
			$menu->addItem(
				'membership_interrupts/show_all',
				__('Membership interrupts'),
				'users');
		}
		
		// list of membership interrupts
		if ($this->acl_check_view('Membership_transfers_Controller', 'membership_transfer'))
		{
			$menu->addItem(
				'membership_transfers/show_all',
				__('Membership transfers'),
				'users');
		}
		
		// list of users
		if ($this->acl_check_view('Users_Controller', 'users'))
		{
			$menu->addItem(
				'users/show_all', __('Users'),
				'users');
		}
		
		/**************************     FINANCES      *****************/
		
		$menu->addGroup('transfer', __('Finances'));
		
		// list of unidentified transfers
		if (Settings::get('finance_enabled') &&
			$this->acl_check_view('Accounts_Controller', 'unidentified_transfers'))
		{
			$menu->addItem(
				'bank_transfers/unidentified_transfers/',
				__('Unidentified transfers'), 'transfer',
				array
				(
				    'count' => $pm->scount_unidentified_transfers()
				));
		}
		
		// list of bank accounts
		if (Settings::get('finance_enabled') &&
			$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
		{
			$menu->addItem(
				'bank_accounts/show_all', __('Bank accounts'),
				'transfer');
		}
		
		// list of accounts
		if (Settings::get('finance_enabled') &&
			$this->acl_check_view('Accounts_Controller', 'accounts'))
		{
			$menu->addItem(
				'accounts/show_all', __('Double-entry accounts'),
				'transfer');
		}
		
		/**
		 * @todo Add own AXO
		 */
		
		// list of transfers
		if (Settings::get('finance_enabled') &&
			$this->acl_check_view('Accounts_Controller', 'transfers'))
		{
			$menu->addItem(
				'transfers/show_all', __('Day book'),'transfer');
		}
		
		/**
		 * @todo Add own AXO
		 */
		if (Settings::get('finance_enabled') &&
			$this->acl_check_view('Accounts_Controller', 'invoices'))
		{
			$menu->addItem(
				'invoices/show_all', __('Invoices'), 'transfer');
		}
		
		/**********************     APPROVAL     ***********************/
		
		$menu->addGroup('approval', __('Approval'));
		
		// list of works
		if (Settings::get('works_enabled') &&
		    $this->acl_check_view('Works_Controller', 'work'))
		{
			$menu->addItem(
				'works/show_all', __('Works'), 'approval',
				array
				(
					'count' => $pm->get_count_of_unvoted_works_of_voter($this->user_id)
				));
		}
		
		// list of work reports
		if (Settings::get('works_enabled') &&
		    $this->acl_check_view('Work_reports_Controller', 'work_report'))
		{
			$menu->addItem(
				'work_reports/show_all', __('Work reports'),
				'approval', array
				(
					'count' => $pm->get_count_of_unvoted_work_reports_of_voter($this->user_id)
				));
		}
		
		// list of requests
		if (Settings::get('approval_enabled') &&
			$this->acl_check_view('Requests_Controller', 'request'))
		{
			$menu->addItem(
				'requests/show_all', __('Requests'),
				'approval', array
				(
					'count' => $pm->get_count_of_unvoted_requests_of_voter($this->user_id)
				));
		}
		
		/***********************      NETWORKS       ********************/
		
		$menu->addGroup('networks', __('Networks'));
		
		// list of connection requests
		if (Settings::get('connection_request_enable') &&
		    $this->acl_check_view('Connection_Requests_Controller', 'request'))
		{
			$menu->addItem(
				'connection_requests/show_all',
				__('Connection requests'), 'networks',
				array
				(
					'count' => $pm->count_undecided_requests()
				));
		}
		
		// list of devices
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Devices_Controller', 'devices'))
		{
			$menu->addItem(
				'devices/show_all', __('Devices'),
				'networks');
		}
		
		// list of ifaces
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Ifaces_Controller', 'iface'))
		{
			$menu->addItem(
				'ifaces/show_all', __('Interfaces'),
				'networks');
		}
		
		// list of IP addresses
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Ip_addresses_Controller', 'ip_address'))
		{
			$menu->addItem(
				'ip_addresses/show_all', __('IP addresses'),
				'networks');
		}
		
		// list of subnets
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Subnets_Controller', 'subnet'))
		{
			$menu->addItem(
				'subnets/show_all', __('Subnets'), 'networks');
		}
		
		// list of links
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Links_Controller', 'link'))
		{
			$menu->addItem(
				'links/show_all', __('Links'), 'networks');
		}
		
		// list of VLANs
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Vlans_Controller', 'vlan'))
		{
			$menu->addItem(
				'vlans/show_all', __('Vlans'), 'networks');
		}
		
		// list of VoIP numbers
		if (Settings::get('voip_enabled') &&
		    $this->acl_check_view('VoIP_Controller', 'voip'))
		{
			$menu->addItem(
				'voip/show_all', __('VoIP'), 'networks');
		}
		
		// list of clouds
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Clouds_Controller', 'clouds'))
		{
			$menu->addItem(
				'clouds/show_all', __('Clouds'), 'networks');
		}
		
		// tools
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Tools_Controller', 'tools'))
		{
			$menu->addItem(
				'tools/ssh', __('Tools'), 'networks');
		}
		
		// traffic
		if (Settings::get('ulogd_enabled') && (
		    $this->acl_check_view('Ulogd_Controller', 'total') ||
			$this->acl_check_view('Ulogd_Controller', 'ip_address') ||
			$this->acl_check_view('Ulogd_Controller', 'member')))
		{
			$menu->addItem(
				'traffic', __('Traffic'), 'networks');
		}
		
		// monitoring
		if (Settings::get('monitoring_enabled') &&
		    $this->acl_check_view('Monitoring_Controller', 'monitoring'))
		{
			$menu->addItem(
				'monitoring/show_all', __('Monitoring'),
				'networks', array
				(
					'count' => $pm->count_off_down_devices(),
					'color' => 'red'
				));
		}
		
		// list of DHCP servers
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Devices_Controller', 'devices'))
		{
			$menu->addItem(
				'devices/show_all_dhcp_servers', __('DHCP servers'),
				'networks', array
				(
					'count' => $pm->count_inactive_dhcp_servers(),
					'color' => 'red'
				));
		}
		
		/*****************     NOTIFICATIONS      *********************/
		
		if (module::e('notification'))
		{
			$menu->addGroup('redirection', __('Notifications'));

			// list of messages
			if ($this->acl_check_view('Messages_Controller', 'message'))
			{
				$menu->addItem(
					'messages/show_all', __('Messages'),
					'redirection');
			}

			// list of whitelists
			if ($this->acl_check_view('Members_whitelists_Controller', 'whitelist'))
			{
				$menu->addItem(
					'members_whitelists/show_all', __('Whitelist'),
					'redirection');
			}

			// list of activated redirections
			if (Settings::get('redirection_enabled') &&
				$this->acl_check_view('Redirect_Controller', 'redirect'))
			{
				$menu->addItem(
					'redirect/show_all', __('Activated redirections'),
					'redirection');
			}
		}
		
		/****************       ADMINISTRATION       *******************/
		
		$menu->addGroup('administration', __('Administration'));
		
		// list of errors and logs
		if ($this->acl_check_view('Log_queues_Controller', 'log_queue'))
		{
			$menu->addItem(
				'log_queues/show_all', __('Errors and logs'),
				'administration',
				array
				(
					'count' => $this->count_of_unclosed_logged_errors,
					'color' => 'red'
				));
		}
		
		// settings
		if ($this->acl_check_view('Settings_Controller', 'info') ||
			$this->acl_check_edit('Settings_Controller', 'system_settings') ||
			$this->acl_check_edit('Settings_Controller', 'users_settings') ||
			$this->acl_check_edit('Settings_Controller', 'finance_settings') ||
			$this->acl_check_edit('Settings_Controller', 'approval_settings') ||
			$this->acl_check_edit('Settings_Controller', 'networks_settings') ||
			$this->acl_check_edit('Settings_Controller', 'email_settings') ||
			$this->acl_check_edit('Settings_Controller', 'sms_settings') ||
			$this->acl_check_edit('Settings_Controller', 'voip_settings') ||
			$this->acl_check_edit('Settings_Controller', 'notification_settings') ||
			$this->acl_check_edit('Settings_Controller', 'qos_settings') ||
			$this->acl_check_edit('Settings_Controller', 'monitoring_settings') ||
			$this->acl_check_edit('Settings_Controller', 'qos_settings') ||
			$this->acl_check_edit('Settings_Controller', 'vtiger_settings') ||
			$this->acl_check_edit('Settings_Controller', 'logging_settings'))
		{
			$menu->addItem(
				'settings/', __('Settings'),
				'administration');
		}
		
		// list of address points
		if ($this->acl_check_view('Address_points_Controller', 'address_point'))
		{
			$menu->addItem(
				'address_points/show_all', __('Address points'),
				'administration');
		}
		
		// list of SMS messages
		if (Settings::get('sms_enabled') &&
			$this->acl_check_view('Sms_Controller', 'sms'))
		{
			$menu->addItem(
				'sms/show_all', __('SMS messages'),
				'administration');
		}
		
		// list of e-mails
		if (Settings::get('email_enabled') &&
			$this->acl_check_view('Email_queues_Controller', 'email_queue'))
		{
			$menu->addItem(
				'email_queues/show_all_unsent', __('E-mails'),
				'administration');
		}
		
		// list of device_templates
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Device_templates_Controller', 'device_template'))
		{
			$menu->addItem(
				'device_templates/show_all', __('Device templates'),
				'administration');
		}
		
		// list of device_templates
		if (Settings::get('networks_enabled') &&
			$this->acl_check_view('Device_active_links_Controller', 'active_links'))
		{
			$menu->addItem(
					'device_active_links/show_all', __('Device active links'),
					'administration');
		}
		
		// list of approval templates
		if (Settings::get('approval_enabled') &&
			$this->acl_check_view('approval', 'templates'))
		{
			$menu->addItem(
				'approval_templates/show_all', __('Approval templates'),
				'administration');
		}
		
		// access rights
		if ($this->acl_check_view('Acl_Controller', 'acl'))
		{
			$menu->addItem(
				'acl/show_all', __('Access rights'),
				'administration');
		}
		else if ($this->acl_check_view('Aro_groups_Controller', 'aro_group'))
		{
			$menu->addItem(
				'aro_groups/show_all', __('Access control groups of users'),
				'administration');
		}
		
		// list of fees
		if (Settings::get('finance_enabled') &&
			$this->acl_check_view('Fees_Controller', 'fees'))
		{
			$menu->addItem(
				'fees/show_all', __('Fees'),
				'administration');
		}
		
		// list of login logs
		if ($this->acl_check_view('Login_logs_Controller', 'logs'))
		{
			$menu->addItem(
				'login_logs/show_all', __('Login logs'),
				'administration');
		}
		
		// list of action logs
		if (Settings::get('action_logs_active') &&
		    $this->acl_check_view('Logs_Controller', 'logs'))
		{
			$menu->addItem(
				'logs/show_all', __('Action logs'),
				'administration');
		}
		
		// list of stats
		if ($this->acl_check_view('Stats_Controller', 'members_fees') ||
			$this->acl_check_view('Stats_Controller', 'incoming_member_payment') ||
			$this->acl_check_view('Stats_Controller', 'members_growth') || 
			$this->acl_check_view('Stats_Controller', 'members_increase_decrease'))
		{
			$menu->addItem(
				'stats', __('Stats'),
				'administration');
		}
		
		// list of translations
		if ($this->acl_check_view('Translations_Controller', 'translation'))
		{
			$menu->addItem(
				'translations/show_all', __('Translations'),
				'administration');
		}
		
		// list of enumerations
		if ($this->acl_check_view('Enum_types_Controller', 'enum_types'))
		{
			$menu->addItem(
				'enum_types/show_all', __('Enumerations'),
				'administration');
		}
		
		// list of phone invoices
		if (Settings::get('phone_invoices_enabled') &&
			$this->acl_check_view('Phone_invoices_Controller', 'invoices'))
		{
			$menu->addItem(
				'phone_invoices/show_all', __('Phone invoices'),
				'administration');
		}
		
		// list of speed classes
		if ($this->acl_check_view('Speed_classes_Controller', 'speed_classes'))
		{
			$menu->addItem(
				'speed_classes/show_all', __('Speed classes'),
				'administration');
		}
		
		// list of phone operators
		if (Settings::get('sms_enabled') &&
			$this->acl_check_view('Phone_operators_Controller', 'phone_operators'))
		{
			$menu->addItem(
				'phone_operators/show_all', __('Phone operators'),
				'administration');
		}
		
		// list of filter queries
		if ($this->acl_check_view('Filter_queries_Controller', 'filter_queries'))
		{
			$menu->addItem(
				'filter_queries/show_all', __('Filter queries'),
				'administration');
		}
		
		return $menu;
	}
	
	/**
	 * Return URL for controller and method
	 * 
	 * @author Michal Kliment
	 * @param string $method
	 * @param string $controller
	 * @return string
	 */
	public function url()
	{
		$args = func_get_args();
		$additional = '';
		
		switch (func_num_args())
		{
			case 0:
				// method is not set, use current
				$debug = debug_backtrace();
				$method = $debug[1]['function'];
				
				// controller is not set use current
				$controller = str_replace('_controller', '', strtolower(get_class($this)));
				break;
			case 1:	
				$method = $args[0];
				
				// controller is not set use current
				$controller = str_replace('_controller', '', strtolower(get_class($this)));
				break;
			
			default:
				$controller = array_shift($args);
				$method = array_shift($args);
				$additional = implode('/', $args);
				if (!empty($additional))
					$additional = '/'.$additional;
				break;
		}
		
		return url_lang::base(). $controller.'/'.$method.$additional;
	}
	
	/**
	 * Redirects to uri according to attribute noredirect
	 * 
	 * @author Michal Kliment
	 * @param string $uri
	 * @param integer $id [optional]
	 * @param string $glue [optional]
	 */
	public function redirect($uri = NULL, $id = NULL, $glue = '/')
	{	
		if (($pos = strpos($uri, url::base())) === FALSE)
		{
			$url = call_user_func_array(
				array($this, 'url'),
				explode('/', trim($uri, '/'))
			);
		}
		else
		{
			$url = trim($uri, '/');
		}
		
		if ($id)
		{
			$url .= $glue.$id;
		}

		if ($this->noredirect)
		{
			die(json_encode(array
			(
				'url' => $url,
				'id' => $id
			)));
		}
		else
		{
			url::redirect($url);
		}
	}

}
