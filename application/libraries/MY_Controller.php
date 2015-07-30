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
	const ICON_ERROR		= 1;
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
		'address_points/get_gps_by_street_street_number_town_country',
		'address_points/get_gps_by_address_from_google',
		'json/get_streets_by_town',
		'address_points/get_gps_by_address',
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
	public $user_has_phone_invoices = 0;
	/** @var boolean */
	public $user_has_voip = 0;
	/** @var string */
	public $ip_address_span = '';
	/** @var integer */
	public $unread_user_mails = 0;
	/** @var integer */
	public $count_of_registered_members = 0;
	/** @var integer */
	public $count_of_unvoted_works_of_voter = 0;
	/** @var integer */
	public $count_of_unvoted_works_reports_of_voter = 0;
	/** @var integer */
	public $count_unfilled_phone_invoices = 0;
	/** @var integer */
	public $devices_down_count = 0;
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
			// init sessions
			$this->session = Session::instance();

			// test if visitor is logged in, or he accesses public
			// controllers like registration, redirect, installation, etc.
			if (!in_array(url_lang::current(), self::$login_not_required) &&
				strpos(url_lang::current(), 'web_interface') === false &&
				url_lang::current(true) != 'web_interface' &&
				!$this->session->get('user_id', 0))
			{
				// Not logged in - redirect to login page
				$this->session->set_flash('err_message', __('Must be logged in'));
				
				// Do not logout after login
				if (url_lang::current() != 'login/logout' &&
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
			
			// init settings
			$this->settings = new Settings();

			// if true, freenetis will run in popup mode (without header and menu)
			$this->popup = (isset($_GET['popup']) && $_GET['popup']) ? 1 : 0;

			// if true, freenetis will run in text mod for dialog
			$this->dialog = (isset($_GET['dialog']) && $_GET['dialog']) ? 1 : 0;
			
			// if true, method redirect will not redirect
			$this->noredirect = ($this->input->get('noredirect') || $this->input->post('noredirect'));

			// config file doesn't exist, we must create it
			if (!file_exists('config.php') || !file_exists('.htaccess'))
			{
				// protection before loop
				if (url_lang::current(1) == 'setup_config')
					return;
				
				if (!file_exists('.htaccess'))
				{
					Settings::set('index_page', 1);
				}
				
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
			
			// load these variables only for logged user
			if ($this->session->get('user_id', 0))
			{
				// for preprocessing some variable
				try
				{
					$this->preprocessor();
				}
				catch(Exception $e)
				{
				}
			}

			// Singleton instance
			self::$instance = $this;
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
		switch ($message_type)
		{
			case ACCESS:
				$message = url_lang::lang('states.Access denied');
				break;
			case EMAIL:
				$message = url_lang::lang('states.Failed to send e-mail') . 
					'<br />' . url_lang::lang('states.Please check settings.');
				break;
			case DATABASE:
				$message = url_lang::lang('states.Failed to connect to database') .
					'<br />' . url_lang::lang('states.Please check settings.');
				break;
			case DATABASE_DOWNGRATE:
				$message = url_lang::lang('states.Failed to update database') .
					'<br />' . url_lang::lang('states.Downgrade not allowed.');
				break;
			case DATABASE_OLD_MECHANISM:
				$message = url_lang::lang('states.Failed to update database') .
					'<br /><br /><span style="font-size:12px">' .
					url_lang::lang('help.old_upgrade_system') . '</span>';
				break;
			case RECORD:
				$message = url_lang::lang('states.This record does not exist');
				break;
			case PAGE:
				$message = url_lang::lang('states.Page not found');
				break;
			case WRITABLE:
				$message = url_lang::lang('states.Directory or file is not writable.');
				break;
			case READONLY:
				$message = url_lang::lang('states.Item is read only.');
				break;
			default:
				$message = url_lang::lang('states.Unknown error message');
				break;
		}
		
		self::showbox($message, self::ICON_ERROR, $content);
	}

	/**
	 * Function shows warning of given message number.
	 * 
	 * @param integer $message_type
	 * @param string $content
	 */
	public static function warning($message_type, $content = NULL)
	{
		switch ($message_type)
		{
			case PARAMETER:
				$message = url_lang::lang('states.Parameter required');
				break;
			default:
				$message = url_lang::lang('states.Unknown warning message');
				break;
		}
		
		self::showbox($message, self::ICON_WARNING, $content);
	}

	/**
	 * Function renders error and warning messages.
	 * 
	 * @param string $message
	 * @param integer $type
	 * @param string $content
	 */
	private static function showbox($message, $type, $content = NULL)
	{
		$view = new View('main');
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
	 * Fuction checks access rights
	 * Return true if currently logged user (stored in $_SESSION['user_id'])
	 * may view own $axo_value object in $axo_section
	 * (and in variable $member_id is his own id of member) or if currently logged user
	 * may confirm all $axo_value object in $axo_section else return false
	 *
	 * @param $axo_section			Group of objects to confirm
	 * @param $axo_value			Object to confirm
	 * @param $member_id			Optional variable, id of other member 
	 *								who is being showed by logged member
	 * @param boolean $force_own	Force to use own rules for not logged user
	 *								Used at: Phone_invoices_Controller#user_field()
	 * @return boolean				Returns true if member has enough access rights
	 */
	public function acl_check_confirm(
			$axo_section, $axo_value, $member_id = NULL, $force_own = FALSE)
	{
		return $this->acl_check(
				$axo_section, $axo_value, 'confirm', $member_id, $force_own
		);
	}

	/**
	 * Function to preprocessing of some useful variables
	 * 
	 * @author Michal Kliment
	 */
	private function preprocessor()
	{
		// helper class
		$member = new Member_Model();
		
		// store user ID from session
		$this->user_id = $this->session->get('user_id');
		
		// store member ID from session
		$this->member_id = $this->session->get('member_id');
		
		// boolean variable if user has any phone invoices (for menu rendering)
		$phone_invoice_user = new Phone_invoice_user_Model();
		
		$this->user_has_phone_invoices = (
				$this->member_id != 1 &&
				$phone_invoice_user->has_phone_invoices($this->user_id)
		);
		
		// count of unfilled phone invoices
		if ($this->user_has_phone_invoices)
		{
			$this->count_unfilled_phone_invoices = $phone_invoice_user
					->count_unfilled_phone_invoices($this->user_id);
		}

		// boolean variable if user has active voip number (for menu rendering)
		$this->user_has_voip = (bool) ORM::factory('voip_sip')
				->has_voip_sips($this->user_id);
		
		// count of unread mail messages of user
		$this->unread_user_mails = ORM::factory('mail_message')
				->count_all_unread_inbox_messages_by_user_id($this->user_id);

		// count registered members if enabled
		if (Settings::get('self_registration'))
		{
			$this->count_of_registered_members = $member->count_of_registered_members();
		}
		
		// gets account id of memeber
		if ($this->acl_check_view('Accounts_Controller', 'transfers', $this->member_id) &&
			$this->member_id != 1)
		{
			$this->member_account_id = $member->get_first_member_account_id($this->member_id);
		}
		
		// gets counts of unvoted user's works and work reports
		if ($this->acl_check_view('Users_Controller', 'work'))
		{
			$this->count_of_unvoted_works_of_voter = ORM::factory('job')
					->get_count_of_unvoted_works_of_voter($this->user_id);
			
			$this->count_of_unvoted_works_reports_of_voter = ORM::factory('job_report')
					->get_count_of_unvoted_work_reports_of_voter($this->user_id);
		}
		
		// ip address span
		$this->ip_address_span = server::remote_addr();
		
		// DZOLO (2011-09-05)
		// This function is wery slow, when internet connection is off.
		if (($ptr_record = dns::get_ptr_record($this->ip_address_span)) != '')
		{
			$this->ip_address_span .= ' <i>(' . $ptr_record . ')</i>';
		}
		
		// monitoring - devices down
		$this->devices_down_count = ORM::factory('monitor_host')->count_off_down_devices();

		// allowed subnets are enabled
		if (Settings::get('allowed_subnets_enabled') && $this->member_id &&
			$this->acl_check_edit(
					'Devices_Controller', 'allowed_subnet', $this->member_id
			))
		{
			// toggle button between allowed subnets
			$asm = new Allowed_subnet_Model();
			
			$as = $asm->get_allowed_subnet_by_member_and_ip_address(
					$this->member_id, server::remote_addr()
			);
			
			// it's possible to change allowed allowed subnets
			if ($as && $as->id &&
				$asm->count_all_disabled_allowed_subnets_by_member($this->member_id))
			{
				$uri = 'allowed_subnets/change/' .$as->id;
				
				if ($as->enabled)
				{
					$this->ip_address_span .= ' ' . html::anchor($uri, html::image(array
					(
						'src'	=> 'media/images/states/active.png',
						'title'	=> 'Disable this subnet'
					))) . ' ' . help::hint('allowed_subnets_enabled');
				}
				else
				{
					$this->ip_address_span .= ' ' . html::anchor($uri, html::image(array
					(
						'src'	=> 'media/images/states/inactive.png',
						'title'	=> 'Enable this subnet'
					))) . ' ' . help::hint('allowed_subnets_disabled');
				}
			}
		}

	}
	
	/**
	 * Return URL for controller and method
	 * 
	 * @author Michal Kliment
	 * @param type $method
	 * @param type $controller
	 * @return type 
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
	 * @return type 
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
			$url = trim($uri, '/');
		
		if ($id)
			$url .= $glue.$id;

		if ($this->noredirect)
		{
			die(json_encode(array
			(
				'url' => $url,
				'id' => $id
			)));
		}
		else
			url::redirect($url);
	}

}
