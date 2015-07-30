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
 * Controller used for communication between Freenetis and other remote devices,
 * for example network gateways, routers, access points etc.
 * 
 * @author Jiri Svitak
 * @package Controller
 */
class Web_interface_Controller extends Controller
{

	const MEMBERS_QOS_PARENT			=	1;
	const MEMBERS_QOS_ORDINARY			=	2;
	const MEMBERS_QOS_ACTIVE			=	3;
	const MEMBERS_QOS_HIGH_PRIORITY		=	4;
	const MEMBERS_QOS_MEMBERS			=	5;
	
	const MEMBERS_QOS_PRIORITY_NORMAL	=	1;
	const MEMBERS_QOS_PRIORITY_HIGH		=	0;
	
	const MEMBERS_QOS_PROTOCOL_ALL		=	'all';
	
	/**
	 * Index prints about message
	 */
	public function index()
	{
		echo __('Interface for communication over http protocol with remote devices');
	}

	/**
	 * Prints to output all subnets which have enabled redirection.
	 * Any IP address belonging to these subnet ranges can be redirected.
	 * 
	 * @author Jiri Svitak
	 * @return unknown_type
	 */
	public function redirected_ranges($paginator = FALSE, $page = NULL)
	{
		$ranges = ORM::factory('subnet')->get_redirected_ranges();
		
		$items = array();
		foreach ($ranges as $range)
			$items[] = $range->subnet_range;
		
		// stupid mikrotik cannot handle file >= 4096kB
		if ($paginator)
		{
			echo $this->paginator(4096, $items, $page);
		}
		else
		{
			echo implode("\n", $items)."\n";
		}
		
		// set state of module (last activation time)
		Settings::set('redirection_state', date('Y-m-d H:i:s'));
	}

	/**
	 * Prints all allowed IP addresses.
	 * These are registered IP addresses, which have no redirection set.
	 * Unknown IP addresses (not present in system) and
	 * IP addresses with a redirection set are not exported.
	 * 
	 * @author Jiri Svitak
	 * @return unknown_type
	 */
	public function allowed_ip_addresses($paginator = FALSE, $page = NULL)
	{
		$ip_adresses = ORM::factory('ip_address')->get_allowed_ip_addresses();
		
		$items = array();
		foreach ($ip_adresses as $ip_adress)
			$items[] = $ip_adress->ip_address;
		
		// stupid mikrotik cannot handle file >= 4096kB
		if ($paginator)
		{
			echo $this->paginator(4096, $items, $page);
		}
		else
		{
			echo implode("\n", $items)."\n";
		}
	}
	
	/**
	 * Prints all unallowed ip addresses, otherwise same as previous method
	 * 
	 * @author Michal Kliments
	 * @param type $paginator
	 * @param type $page 
	 */
	public function unallowed_ip_addresses($paginator = FALSE, $page = NULL)
	{
		$ip_adresses = ORM::factory('ip_address')->get_unallowed_ip_addresses();
		
		$items = array();
		foreach ($ip_adresses as $ip_adress)
			$items[] = $ip_adress->ip_address;
		
		// stupid mikrotik cannot handle file >= 4096kB
		if ($paginator)
		{
			echo $this->paginator(4096, $items, $page);
		}
		else
		{
			echo implode("\n", $items)."\n";
		}
		
		// set state of module (last activation time)
		Settings::set('redirection_state', date('Y-m-d H:i:s'));
	}

	/**
	 * @author Michal Kliment
	 */
    public function self_cancelable_ip_addresses($paginator = FALSE, $page = NULL)
    {
        $ip_adresses = ORM::factory('ip_address')->get_ip_addresses_with_self_cancel();

		$items = array();
		foreach ($ip_adresses as $ip_adress)
			$items[] = $ip_adress->ip_address;
		
		// stupid mikrotik cannot handle file >= 4096kB
		if ($paginator)
		{
			echo $this->paginator(4096, $items, $page);
		}
		else
		{
			echo implode("\n", $items)."\n";
		}
		
		// set state of module (last activation time)
		Settings::set('redirection_state', date('Y-m-d H:i:s'));
    }
	
	/**
	 * Prints all local subnets
	 * 
	 * @author Michal Kliment
	 * @param type $paginator
	 * @param type $page 
	 */
	public function local_subnets($paginator = FALSE, $page = NULL)
	{
		$local_subnets = ORM::factory('local_subnet')->get_all_local_subnets();
		
		$items = array();
		foreach ($local_subnets as $local_subnet)
			$items[] = $local_subnet->address;
		
		// stupid mikrotik cannot handle file >= 4096kB
		if ($paginator)
		{
			echo $this->paginator(4096, $items, $page);
		}
		else
		{
			echo implode("\n", $items)."\n";
		}
	}

	/**
	 * Function is used for VoIP callback.
	 * @param integer $user
	 * @param string $pass
	 * @param integer $number
	 */
	public function callback($user = null, $pass = null, $number = null)
	{

		if ($user == null || $pass == null || $number == null)
		{
			echo 'No input data';
			exit;
		}

		if (!valid::digit($user) || !valid::digit($number))
		{
			echo 'Not valid input data';
			exit;
		}

		$voip = ORM::factory('voip_sip')->get_voip_sip_by_name($user);

		if (count($voip) == 0 || $voip->current()->secret != $pass)
		{
			echo 'Bad user or password';
			exit;
		}
		else
		{
			$this->settings = new Settings();

			$asm = new AsteriskManager();

			$ahostname = $this->settings->get('voip_asterisk_hostname');
			$auser = $this->settings->get('voip_asterisk_user');
			$apass = $this->settings->get('voip_asterisk_pass');

			if ($ahostname == null ||
				$ahostname == '' ||
				$auser == null ||
				$auser == '' ||
				$apass == null ||
				$apass == '')
			{
				echo 'Error. Check freenetis settings.';
				exit;
			}

			if ($asm->connect($ahostname, $auser, $apass))
			{
				//http://www.voip-info.org/wiki/index.php?page=Asterisk+Manager+API+Action+Originate
				$call = $asm->Originate(
						'SIP/' . $user, $number, 'internal', 1,
						$application = NULL, $data = NULL,
						30000, $user, $variable = NULL,
						$account = NULL, 'Async',
						$actionid = NULL
				);
				$asm->disconnect();
				echo 'Success';
			}
			else
			{
				echo 'Error. Could not connect to server.';
			}
		}
	}

	/**
	 * Generates authorized keys to device
	 *
	 * @author Michal Kliment
	 * @param integer $device_id
	 * @return mixed
	 */
	public function authorized_keys($device_id = NULL)
	{
		// device_id is set
		if ($device_id && is_numeric($device_id))
		{
			// finds all keys by device
			$keys = ORM::factory('users_key')->get_keys_by_device($device_id);
		}
		// prints all keys
		else
		{
			// finds all keys
			$keys = ORM::factory('users_key')->orderby('user_id')->find_all();
		}

		echo "#\n";
		echo "# Generated by FreenetIS at " . date("Y-m-d H:i:s") . "\n";
		echo "#\n";

		foreach ($keys as $key)
		{
			echo "$key->key\n";
		}
	}

	/**
	 * Prints all user's keys
	 *
	 * @author Michal Kliment
	 * @param integer $user_id
	 * @return mixed
	 */
	public function key($user_id = NULL)
	{
		// bad parameter
		if (!$user_id || !is_numeric($user_id))
			return;

		// finds all keys by user
		$keys = ORM::factory('users_key')->where('user_id', $user_id)->find_all();

		foreach ($keys as $key)
		{
			echo "$key->key\n";
		}
	}
	
	/**
	 * Prints members QoS's data in columns
	 * 
	 * @author Michal Kliment
	 * @return type 
	 */
	public function members_qos_ceil_rate()
	{
		// qos is not enabled, we ends
		if (!Settings::get('qos_enabled'))
			return;
	
		// finds total qos speed
		$total = network::speed_size(Settings::get('qos_total_speed'), "M");
		
		$members_upload_rate = $total['upload'];
		$members_download_rate = $total['download'];
		
		// ulogd is enabled
		if (Settings::get('ulogd_enabled'))
		{
			// finds qos speed for active members
			$active = network::speed_size(Settings::get('qos_active_speed'), "M");
		
			$members_upload_rate -= $active['upload'];
			$members_download_rate -= $active['download'];
		}
		
		$i = self::MEMBERS_QOS_MEMBERS;
		$total_upload_rate = 0;
		$total_download_rate = 0;
		
		$data = array();
		$members_data = array();
		
		// finds all members with guaranteed speed
		$members = ORM::factory('member')->get_members_qos_ceil_rate();
		
		foreach ($members as $member)
		{
			$qos_ceil = network::speed_size($member->qos_ceil,"M");
			$qos_rate = network::speed_size($member->qos_rate,"M");
			
			$total_upload_rate += $qos_rate['upload'];
			$total_download_rate += $qos_rate['download'];
			
			$members_data[$i] = array
			(
				"id"			=> $i,
				"upload_ceil"	=> num::decimal_point($qos_ceil['upload'])."M",
				"download_ceil"	=> num::decimal_point($qos_ceil['download'])."M",
				"upload_rate"	=> num::decimal_point($qos_rate['upload'])."M",
				"download_rate"	=> num::decimal_point($qos_rate['download'])."M",
				"parent"		=> self::MEMBERS_QOS_PARENT,
				"ipset"			=> "member_".($i-self::MEMBERS_QOS_MEMBERS+1),
				"priority"		=> self::MEMBERS_QOS_PRIORITY_NORMAL,
				"protocol"		=> self::MEMBERS_QOS_PROTOCOL_ALL
			);
			
			$i++;
		}
		
		// deducts qos rate of member with guaranteed speed from total qos rate
		if ($members_upload_rate > $total_upload_rate
			&& $members_download_rate > $total_download_rate)
		{
			$members_upload_rate -= $total_upload_rate;
			$members_download_rate -= $total_download_rate;
		}
		
		// adds line for parent (total qos rate)
		$data[self::MEMBERS_QOS_PARENT] = array
		(
			"id"			=> self::MEMBERS_QOS_PARENT,
			"upload_ceil"	=> $total['upload']."M",
			"download_ceil"	=> $total['download']."M",
			"upload_rate"	=> $total['upload']."M",
			"download_rate"	=> $total['download']."M",
			"priority"		=> self::MEMBERS_QOS_PRIORITY_NORMAL,
			"protocol"		=> self::MEMBERS_QOS_PROTOCOL_ALL
		);
		
		// adds line for ordinary members
		$data[self::MEMBERS_QOS_ORDINARY] = array
		(
			"id"			=> self::MEMBERS_QOS_ORDINARY,
			"upload_ceil"	=> "0M",
			"download_ceil"	=> "0M",
			"upload_rate"	=> num::decimal_point($members_upload_rate)."M",
			"download_rate"	=> num::decimal_point($members_download_rate)."M",
			"parent"		=> self::MEMBERS_QOS_PARENT,
			"priority"		=> self::MEMBERS_QOS_PRIORITY_NORMAL,
			"protocol"		=> self::MEMBERS_QOS_PROTOCOL_ALL
		);
		
		// ulogd is enabled
		if (Settings::get('ulogd_enabled'))
		{
			// adds line for active members
			$data[self::MEMBERS_QOS_ACTIVE] = array
			(
				"id"			=> self::MEMBERS_QOS_ACTIVE,
				"upload_ceil"	=> "0M",
				"download_ceil"	=> "0M",
				"upload_rate"	=> $active['upload']."M",
				"download_rate"	=> $active['download']."M",
				"parent"		=> self::MEMBERS_QOS_PARENT,
				"ipset"			=> "active",
				"priority"		=> self::MEMBERS_QOS_PRIORITY_NORMAL,
				"protocol"		=> self::MEMBERS_QOS_PROTOCOL_ALL
			);
		}
		
		$data[self::MEMBERS_QOS_HIGH_PRIORITY] = array
		(
			"id"			=> self::MEMBERS_QOS_HIGH_PRIORITY,
			"upload_ceil"	=> "0M",
			"download_ceil"	=> "0M",
			"upload_rate"	=> "10M",
			"download_rate"	=> "10M",
			"parent"		=> self::MEMBERS_QOS_PARENT,
			"priority"		=> self::MEMBERS_QOS_PRIORITY_HIGH,
			"protocol"		=> self::MEMBERS_QOS_PROTOCOL_ALL,
			"ipset"			=> "priority"
		);
		
		// adds lines with qos rates of member with guaranteed speed
		$data = arr::merge($data, $members_data);
		
		// definition of columns
		$columns = array
		(
			"id",
			"upload_ceil",
			"download_ceil",
			"upload_rate",
			"download_rate",
			"priority",
			"protocol",
			"parent",
			"ipset"
		);
		
		echo text::print_in_columns($data, $columns);
		
		// set state of module (last activation time)
		Settings::set('qos_state', date('Y-m-d H:i:s'));
	}
	
	/**
	 * Prints ip addresses of members with guaranteed speed
	 * 
	 * @author Michal Kliment
	 * @return type 
	 */
	public function ip_addresses_qos_ceil_rate()
	{
		// qos is not enabled, we ends
		if (!Settings::get('qos_enabled'))
			return;
		
		$data = array();
		
		if (Settings::get('ulogd_enabled'))
		{
			$ips = ORM::factory('member')->get_active_traffic_members_ip_addresses(date('Y-m-d'));

			foreach ($ips as $ip)
			{
				$data[] = array
				(
					"id"			=> self::MEMBERS_QOS_ACTIVE,
					"ip_address"	=> $ip->ip_address
				);
			}
		}
		
		foreach (explode(",", Settings::get('qos_high_priority_ip_addresses'))
			as $qos_high_priority_ip_address)
		{
			$data[] = array
			(
				"id"			=> self::MEMBERS_QOS_HIGH_PRIORITY,
				"ip_address"	=> $qos_high_priority_ip_address
			);
		}
		
		$ip_addresses = ORM::factory('ip_address')->get_ip_addresses_qos_ceil_rate();
		
		$i = self::MEMBERS_QOS_MEMBERS-1;
		$member_id = 0;
		
		foreach ($ip_addresses as $ip_address)
		{
			if ($ip_address->member_id != $member_id)
			{
				$i++;
				$member_id = $ip_address->member_id;
			}
			
			$data[] = array
			(
				"id"			=> $i,
				"ip_address"	=> $ip_address->ip_address
			);
		}
		
		// definition of columns
		$columns = array ("id", "ip_address");
		
		echo text::print_in_columns($data, $columns);
		
		// set state of module (last activation time)
		Settings::set('qos_state', date('Y-m-d H:i:s'));
	}
	
	/**
	 * Prints all IP addresses of hosts to monitored 
	 * 
	 * @author Michal Kliment
	 * @param type $priority 
	 */
	public function monitoring_hosts($priority = NULL)
	{
		if (!Settings::get('monitoring_enabled') ||
			(server::remote_addr() != Settings::get('monitoring_server_ip_address')))
		{
			@header('HTTP/1.0 403 Forbidden');
			die();
		}
		
		$monitor_host_model = new Monitor_host_Model();
		
		$hosts = $monitor_host_model->get_all_monitored_hosts($priority);
		
		foreach ($hosts as $host)
		{
			echo $host->ip_address."\n";
		}
		
		// set state of module (last activation time)
		Settings::set('monitoring_state', date('Y-m-d H:i:s'));
	}
	
	/**
	 * Sets states of monitored hosts
	 * 
	 * @author Michal Kliment
	 * @return type 
	 */
	public function monitoring_states()
	{	
		if (!Settings::get('monitoring_enabled') ||
			(server::remote_addr() != Settings::get('monitoring_server_ip_address')))
		{
			@header('HTTP/1.0 403 Forbidden');
			die();
		}
		
		$ips	= $this->input->post('ip');
		$states = $this->input->post('state');
		$lats	= $this->input->post('lat');
		
		$monitor_host_model = new Monitor_host_Model();
		
		if (is_array($ips) && is_array($states) && is_array($lats))
		{
			foreach ($ips as $i => $ip)
			{			
				if (isset($states[$i]) && isset($lats[$i]))
				$monitor_host_model->update_host($ip, Monitor_host_Model::get_state($states[$i]), $lats[$i]);
			}

			// set state of module (last activation time)
			Settings::set('monitoring_state', date('Y-m-d H:i:s'));
		}
	}
	
	/**
	 * It creates pagination of results, it is used for buffering result
	 * for smaller parts, e.g. Mikrotik cannot handle file >= 4096 kB
	 * 
	 * @author Michal Kliment
	 * @param type $delimeter_size
	 * @param type $items
	 * @param type $page
	 * @return string 
	 */
	private function paginator($delimeter_size, $items, $page = NULL)
	{
		$data = array();
		
		$current_page = 1;
		
		// data for first page
		$data[$current_page] = '';
		
		foreach ($items as $item)
		{	
			// overflow data of current page
			if (strlen($data[$current_page]) + strlen($item) +1 >= $delimeter_size)
			{
				// we want current page => return it
				if ($page && $page == $current_page)
				{
					return $data[$current_page];
				}
				
				// otherwise create new page
				$current_page++;

				$data[$current_page] = '';
			}

			// store data to current page
			$data[$current_page] .= "$item\n";
				
		}
		
		// we want last page => return it
		if ($page)
		{
			return $data[$current_page];
		}
		
		// otherwise print number of total pages
		return count($data);
	}

}
