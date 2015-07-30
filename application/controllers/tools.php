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
 * Tools controller (SSH console, WHO IS info)
 *
 * @package Controller
 */
class Tools_Controller extends Controller
{
	/**
	 * Contruct of controller sets tabs names
	 */
	public function __construct()
	{
		parent::__construct();

		$this->sections = array
		(
			'ssh'					=> __('SSH'),
			'whois'					=> __('WHOIS')
		);
		
		// access control
		if (!Settings::get('networks_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Redirects to ssh
	 */
	public function index()
	{
		$this->ssh();
	}

	/**
	 * SSH tool
	 *
	 * @param string $ip
	 * @param integer $port 
	 */
	public function ssh($ip = NULL, $port = NULL)
	{
		if (!$this->acl_check_view('Tools_Controller', 'tools'))
			Controller::error(ACCESS);
		
		if (!isset($ip) || !valid::ip($ip))
			$ip = '';

		$view = new View('main');
		$view->title = __('Tools') . ' - ' . __('SSH');
		$view->content = new View('tools/index');
		$view->content->current = 'ssh';
		$view->content->headline = __('SSH');
		$view->content->content = new View('tools/ssh');
		$view->content->content->ip = $ip;
		$view->render(TRUE);
	}

	/**
	 * Who is tool
	 *
	 * @param string $hostname 
	 */
	public function whois($hostname = NULL)
	{
		if (!$this->acl_check_view('Tools_Controller', 'tools'))
			Controller::error(ACCESS);
		
		if (!$this->input->post('query') == NULL)
			$hostname = $this->input->post('query');

		$winfo = '';

		if (isset($hostname))
		{
			require_once(APPPATH . 'vendors/phpwhois/whois.main.php');
			include_once(APPPATH . 'vendors/phpwhois/whois.utils.php');

			$whois = new Whois();
			$query = $hostname;
			$result = $whois->Lookup($query);

			if (!empty($result['rawdata']))
			{
				$utils = new utils;
				$winfo = $utils->showHTML($result);
			}
			else
			{
				if (isset($whois->Query['errstr']))
					$winfo = implode($whois->Query['errstr'], "\n<br></br>");
				else
					$winfo = __('Unexpected error');
			}
		}
		
		$view = new View('main');
		$view->title = __('Tools') . ' - ' . __('WHOIS');
		$view->content = new View('tools/index');
		$view->content->current = 'whois';
		$view->content->headline = __('WHOIS');
		$view->content->content = new View('tools/whois');
		$view->content->content->hostname = $hostname;
		$view->content->content->winfo = $winfo;
		$view->render(TRUE);
	}

}
