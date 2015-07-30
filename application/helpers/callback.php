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
 * Helper for callback of Grid used at controllers.
 * 
 * @author Jiri Svitak
 */
class callback
{
	/************** Callbacks for global use **********************************/
	
	/**
	 * Callback function to print boolean as word (yes or no)
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function boolean($item, $name)
	{
		if ($item->$name)
		{
			echo __('Yes');
		}
		else
		{
			echo __('No');
		}
	}
	
	/**
	 * Callback for date
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function date($item, $name)
	{
		echo date('j.n. Y', strtotime($item->$name));
	}

	/**
	 * Callback function to show datetime in human format
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function datetime($item, $name)
	{
		echo date::pretty($item->$name).' '.date::pretty_time($item->$name);
	}
	
	/**
	 * Returns diff between field and current time
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name
	 * @param type $args 
	 */
	public static function datetime_diff($item, $name, $args = array())
	{
		// return in array ('y' => years, 'm' => months, etc.)
		$interval = date::interval(date('Y-m-d H:i:s'), $item->$name);
		
		// don't print days
		unset($interval['days']);
		
		$units = array
		(
				'y' => 'year',
				'm' => 'month',
				'd' => 'day',
				'h' => 'hour',
				'i' => 'minute',
				's' => 'second'
		);
		
		// short output?
		$short = (isset($args[0]) && $args[0] == 'short');
					
		$pieces = array();
		foreach ($interval as $unit => $val)
		{	
			// do not work with empty values (fixes #412)
			if (empty($val))
			{
				continue;
			}
			
			// make plural (if needed) and translate
			$unit = strtolower(__($units[$unit]. (($val > 1) ? 's' : '')));

			// create short output (if needed)
			if ($short)
				$unit = substr($unit,0,1);
			
			$pieces[] = $val.' '.$unit;
		}
		
		// return result
		echo implode(', ',$pieces);
	} 
	
	/**
	 * Callback for display GPS
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function gps_field($item, $name)
	{
	    echo gps::degrees_from_str($item->$name, true);
	}
	
	/**
	 * Callback for limited text, text over 50 character is stripped and added to title.
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function limited_text($item, $name)
	{
		$text = strip_tags($item->$name);
		
		if (mb_strlen($text) > 50)
		{
			$title = htmlspecialchars($text);
			$text = text::limit_chars(htmlspecialchars($text), 50);
			
			echo '<span class="help" title="' . $title . '">' . $text . '</span>';
		}
		else
		{
			echo $text;
		}
	}
	
	/**
	 * Callback for money, gets price from property with name given by args.
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function money($item, $name)
	{
		$price = number_format($item->$name, 2, ',', ' ') . ' ';
		$price = str_replace(' ', '&nbsp;', $price);
		
		// has currency?
		if (property_exists($item, 'currency'))
		{
			$price .= __($item->currency);
		}
		// default currency
		else
		{
			$price .= __(Settings::get('currency'));
		}
		
		// has transfer?
		if (property_exists($item, 'transfer_id') && $item->transfer_id)
		{
			echo html::anchor('transfers/show/' . $item->transfer_id, $price);
		}
		// just price
		else
		{
			echo $price;
		}
	}
	
	/**
	 * Prints value only if it is not empty, else prints dash (or given string)
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name
	 * @param type $args 
	 */
	public static function not_empty($item, $name, $args = array())
	{
		if ($item->$name === NULL)
		{
			echo (isset($args[0])) ? $args[0] : '-';
		}
		else
			echo $item->$name;
	}
	
	/**
	 * Callback for percents.
	 * 
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function percent($item, $name)
	{
		echo $item->$name.'%';
	}
	
	/**
	 * Round value by first argument. If first argument is lower than 0,
	 * value is rouded right from decimal point, left otherwise.
	 * 
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param name $name
	 * @param array $args 
	 */
	public static function round($item, $name, $args = array())
	{
		$precision = isset($args[0]) ? intval($args[0]) : 2; 
		echo round($item->$name, $precision);
	}
	
	/************** Callbacks for using in special ocations *******************/
	
	/**
	 * Callback function to print ACOs count with their preview of values
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function aco_count_field ($item, $name)
	{
		if ($item->aco_count)
			echo "<span class='help' title='$item->aco_value'>$item->aco_count</span>";
		else
			echo $item->aco_count;
	}
	
	public static function aco_value_field ($item, $name)
	{
		echo Aco_Model::get_action($item->value);
	}
	
	/**
	 * Callback to draw active/inactive image in grid field
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function active_field($item, $name)
	{
		$color = 'red';
		$state = 'Yes';
		
		if (!$item->$name)
		{
			$state = 'No';
			$color = 'green';
		}
		
		echo '<span style="color: '.$color.'">'.__(''.$state).'</span>';
	}

	/**
	 * Callback function to print type of members address points
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function address_point_member_field($item, $name)
	{
		switch ($item->type)
		{
			// it's domicile of member
			case 1:
				echo __('Domicile'). ' '
					. help::hint('address_point_member_domicile');
				break;
			// it's only connecting place of member, he has domicile on another address point
			case 2:
				echo __('Connecting place'). ' '
					. help::hint('address_point_member_connecting_place');
				break;
		}
	}

	/**
	 * Callback for amount field in transfers of account
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name 
	 */
	public static function amount_field($item, $name)
	{
		if ($item->amount > 0)
		{
			echo  '<span style="color:green">'
				. number_format((float)$item->amount, 2, ',', ' ')
				. '</span>';
		}
		else if ($item->amount < 0)
		{
			echo  '<span style="color:red">'
				. number_format((float)$item->amount, 2, ',', ' ')
				. '</span>';
		}
		else
		{
			echo $item->amount;
		}
	}
	
	/**
	 * Callback function to print ARO groups count with their preview of values
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function aro_groups_count_field ($item, $name)
	{
		if ($item->aro_groups_count)
			echo "<span class='help' title='$item->aro_groups_value'>$item->aro_groups_count</span>";
		else
			echo $item->aro_groups_count;
	}
	
	/**
	 * Callback function to print ARO groups count with their preview of values
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function axo_count_field ($item, $name)
	{
		if ($item->axo_count)
			echo "<span class='help' title='$item->axo_value'>$item->axo_count</span>";
		else
			echo $item->axo_count;
	}
	
	/**
	 * Callback for balance field in accounts.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name 
	 */
	public static function balance_field($item, $name)
	{
		$balance = $item->balance;
		
		if ($item->balance != 0)
		{
			$balance = number_format((float)$item->balance, 2, ',', ' ');
		}
		
		$color = '';
		
		if ($item->balance > 0)
		{
			$color = ' style="color:green"';
		}
		elseif ($item->balance < 0)
		{
			$color = ' style="color:red"';
		}

		echo "<span$color>$balance</span>";
		
		if (isset($item->a_comment) && $item->a_comment != '')
		{
			echo "<span class='help' title='".$item->a_comment."'>";
			echo "<img src='".url::base()."media/images/icons/comment.png'>";
			echo "</span>";
		}

		if (isset($item->member_id) && $item->member_id != 1)
		{
			echo ' '.html::anchor(
					($item->a_comments_thread_id) ? 
						'comments/add/'.$item->a_comments_thread_id :
						'comments_threads/add/account/'.$item->aid,
					html::image('media/images/icons/ico_add.gif'), array
					(
						'title' => __('Add comment to financial state of member')
					)
			);
		}
	}
	
	/**
	 * Callback function to print bitrate of link
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function bitrate_field ($item, $name, $args = array())
	{
		if($item->bitrate)
		{
			$byte = TRUE;

			if (isset($args[0]))
				$byte = $args[0];

			echo network::size($item->bitrate/1024, $byte);
		}
		else
			echo '-';
	}

	/**
	 * Callback for device field.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function device_field($item, $name)
	{
		if ($item->device_id)
		{
			echo html::anchor("devices/show/$item->device_id", $item->device_name);
		}
		else if ($item->device_name != '')
		{
			echo $item->device_name;
		}
		else if (!$item->ip_address_id)
		{
			echo '<span style="color: green">'.__('Free').'</span>';
		}
	}
	
	/**
	 * Callback function to print count of ports and grouped ports as title
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function devices_field ($item, $name)
	{
		if ($item->devices_count)
		{
			echo '<span class="help" title="'.$item->devices.'">'.$item->devices_count.'</span>';
		}
		else
		{
			echo $item->devices_count;
		}
	}

	/**
	 * Callback field for canceling redirection of
	 * one IP address on member's profile screen.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function cancel_redirection_of_member($item, $name)
	{
		if (empty($item->message_id))
		{
			echo '&nbsp;-&nbsp;';
		}
		else
		{
			echo __('Cancel').' ';
			
			echo html::anchor(
					"redirect/delete/$item->ip_address_id/$item->message_id/member",
					__('one IP address')
			);
			
			echo ' | ';
			
			echo html::anchor(
					"redirect/delete_from_member/$item->member_id/$item->message_id",
					__('all IP addresses of member')
			);
		}
	}
	
	/**
	 * Callback function to print network address in CIDR format
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function cidr_field ($item, $name)
	{
		$last_ip = long2ip(ip2long($item->network_address) + (~ip2long($item->netmask) & 0xffffffff));
		
		echo "<span class='help' title='$item->network_address - $last_ip'>$item->cidr_address</span>";
	}

	/**
	 * Callback to print comments count with comments as title
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function comments_field ($item, $name)
	{
		echo $item->comments_count;

		if ($item->comments_count)
		{
			echo "<span title='$item->comments' class='help'>";
			echo "<img src='".url::base()."media/images/icons/comment.png'>";
			echo "</span>";
		}
	}
	
	/**
	 * Callback function to print subject of comment
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function comment_to_field ($item, $name)
	{
		switch ($item->type)
		{
			case 'account':
				echo  __('Financial state of member') . ' ' . html::anchor(
							'members/show/'.$item->member_id, $item->member_name,
							array('title' => __('Show member'))
				);
				break;
			
			case 'job':
				echo html::anchor(
						'works/show/'.$item->work_id, __('Work'), 
						array('title' => __('Show work'))
				).' '.__('of user').' '.html::anchor(
						'users/show/'.$item->work_user_id,
						$item->work_user_name,
						array('title' => __('Show user'))
				);
				break;
		}
	}
	
	/**
	 * Callback function to print connected device
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function connected_to_device ($item, $name)
	{
		if ($item->connected_to_device_id)
		{
			if ($item->connected_to_devices_count == 1)
			{
				echo html::anchor(
						'devices/show/'.$item->connected_to_device_id,
						$item->connected_to_device_name
				);
			}
			else if (isset($item->link_id))
			{
				echo html::anchor(
						'links/show/'.$item->link_id,
						__('More devices'), array
						(
							'class' => 'more',
							'title' => $item->connected_to_devices
						)
				);
			}
			else
			{
				echo '<span class="more" title="'.$item->connected_to_devices.'">'.__('More devices').'</span>';
			}
		}
		else
		{		
			if (Iface_Model::type_has_link($item->type))
				echo '<span style="color: green">'.__('Not connected').'</span>';
			else
				echo '-';
		}
	}
	
	/**
	 * Callback function to print connected device to device
	 * 
	 * @author Ondřej Fibich
	 * @param type $item
	 * @param type $name 
	 */
	public static function device_connected_to_device ($item, $name)
	{
		$connected = ORM::factory('device')->get_all_connected_to_device($item->id);
		
		if (isset($connected->connected_to_device_id) &&
			$connected->connected_to_device_id)
		{
			if ($connected->connected_to_devices_count == 1)
			{
				echo html::anchor(
						'devices/show/'.$connected->connected_to_device_id,
						$connected->connected_to_device_name
				);
			}
			else
			{
				echo "<span class='more' title='".$connected->connected_to_devices."'>".__('More devices')."</span>";
			}
		}
		else
		{		
			echo "<span style='color: green'>".__('Not connected')."</span>";
		}
	}
	
	/**
	 * Callback function to print e-mail From address
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function email_from_field($item, $name)
	{
		if ($item->from_user_id)
		{
			echo html::anchor(
					'users/show/'.$item->from_user_id,
					$item->from_user_name,
					array('title' => $item->from)
			);
		}
		else
		{
			echo $item->from;
		}
	}
	
	/**
	 * Callback function to print e-mail To address
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function email_to_field ($item, $name)
	{
		if ($item->to_user_id)
		{
			echo html::anchor(
					'users/show/'.$item->to_user_id,
					$item->to_user_name,
					array('title' => $item->to)
			);
		}
		else
		{
			echo $item->to;
		}
	}
	
	/**
	 * Callback function to print e-mail subject
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function email_subject_field ($item, $name)
	{
		$body = preg_replace('<(br|BR)( ?\/)?>', '\n', $item->body);
		$body = strip_tags($body);
		echo "<span class='help' title='".$body."'>".$item->subject."</span>";
	}
	
	/**
	 * Callback function to print e-mail state
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function email_state_field ($item, $name)
	{
		switch ($item->state)
		{
			case Email_queue_Model::STATE_NEW:
				echo __('New');
				break;
			
			case Email_queue_Model::STATE_OK:
				echo "<span style='color: green'>".__('Sent')."</span>";
				break;
			
			case Email_queue_Model::STATE_FAIL:
				echo "<span style='color: red'>".__('Failed')."</span>";
				break;
		}
	}

	/**
	 * Callback to print enable/disable image
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param array $args
	 */
	public static function enabled_field($item, $name, $args = array())
	{
		// 1 => active, 0 => inactive
		switch ($item->$name)
		{
			case 1:
				$state = 'active';
				break;
			case 0:
				$state = 'inactive';
				break;
			default:
				$state = 'inactive';
				break;
		}

		if (isset($args[0]) && $args[0] != '')
		{
			echo html::anchor($args[0] . $item->id, html::image(array
			(
				'src'	=> 'media/images/states/' . $state . '.png',
				'title'	=> __('' . $state))
			));
		}
		else
		{
			echo html::image(array
			(
				'src'	=> 'media/images/states/' . $state . '.png',
				'title'	=> __('' . $state)
			));
		}
	}
	
	/**
	 * Callback function to print type of interface
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function iface_type_field ($item, $name)
	{
		switch ($item->type)
		{
			case Iface_Model::TYPE_WIRELESS:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/wireless.png',
					'title' => __('Wireless')
				));
				break;
			
			case Iface_Model::TYPE_ETHERNET:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/ethernet.png',
					'title' => __('Ethernet')
				));
				break;
			
			case Iface_Model::TYPE_PORT:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/port.png',
					'title' => __('Port')
				));
				break;
			
			case Iface_Model::TYPE_BRIDGE:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/bridge.png',
					'title' => __('Bridge')
				));
				break;
			
			case Iface_Model::TYPE_VLAN:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/vlan.png',
					'title' => __('Vlan')
				));
				break;
			
			case Iface_Model::TYPE_VIRTUAL_AP:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/vlan_ap.png',
					'title' => __('Virtual AP')
				));
				break;
			
			case Iface_Model::TYPE_INTERNAL:
				echo html::image(array
				(
					'src' => 'media/images/icons/ifaces/internal.png',
					'title' => __('Internal')
				));
				break;
		}
	}

	/**
	 * Callback field for ip address. Leaves blank ip if needed.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function ip_address_field($item, $name, $args = array())
	{
		$class = '';
		$title = '';
		
		$ip_address = (isset($args[0]) && $args[0]) ?
							$item->ip_address.'/'.$item->subnet_range :
							$item->ip_address;
		
		if (isset($item->ip_addresses))
		{
			$title = $item->ip_addresses;
			
			if (count(explode(',', $item->ip_addresses)) > 2)
			{
				$class = 'more';
			}
		}

		if ((!isset($args[1]) || $args[1]) && $item->ip_address_id)
		{
			if (url_lang::current(1) == 'devices')
			{
				echo html::anchor(
					"devices/show_ip_address/$item->ip_address_id",
					$ip_address, array('class' => $class, 'title' => $title)
				);
			}
			else
			{
				echo html::anchor(
					"ip_addresses/show/$item->ip_address_id",
					$ip_address,
					array('class' => $class, 'title' => $title)
				);
			}
		}
		else if ($item->ip_address != '')
		{
			if (!isset($args[2]) || $args[2])
				echo '<span style="color: green" class="'.$class.'" title="'.$title.'">'.$ip_address.'</span>';
			else
				echo $ip_address;
		}
		else
			echo '&nbsp';
	}
	
	/**
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function items_count_field ($item, $name)
	{
		echo '<span class="help" title="'.$item->items_count_title.'">'.$item->items_count.'</span>';
	}
	
	/**
	 * Callback print latency (from monitoring)
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function latency_field ($item, $name)
	{
		$title = __('Last').": $item->latency_current ".__('ms')." \n"
				.__('Minimum').": ".round($item->latency_min,2)." ".__('ms')."\n"
				.__('Average').": ".round($item->latency_avg,2)." ".__('ms')."\n"
				.__('Maximum').": ".round($item->latency_max,2)." ".__('ms')."\n";
		
		$text = $item->latency_avg ? round($item->latency_avg,2).' '.__('ms') : '-';
		
		echo "<span class='help' title='$title'>$text</span>";
	}
	
	/**
	 * Callback for action of log
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function log_action_field($item, $name)
	{
		switch ($item->action)
		{
			case Log_Model::ACTION_ADD:
				echo __('Added');
				break;
			case Log_Model::ACTION_DELETE:
				echo __('Deleted');
				break;
			case Log_Model::ACTION_UPDATE:
				echo __('Updated');
				break;
		}
	}

	/**
	 * Callback fields prints either translated system message or
	 * user message for redirection.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function message_field($item, $name)
	{
		// system messages
		if ($item->type > 0)
		{
			// system messages
			echo __($item->message);
		}
		else if ($item->type === NULL)
		{
			// null value means no message
			echo '<span style="color: #999">' . __('No redirection') . '</span>';
		}
		else
		{
			// user messages
			echo $item->message;
		}
	}

	/**
	 * Callback for message type, it distinguishes between system and user redirection messages.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function message_type_field($item, $name)
	{
		if ($item->type == 0)
		{
			echo __('User message');
		}
		else
		{
			echo __('System message');
		}
	}
	
	/**
	 * Callback to print host state (from monitoring)
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function monitor_state_field($item, $name)
	{
		switch ($item->$name)
		{
			case Monitor_host_Model::STATE_UP:
				$image = url::base().'media/images/states/active.png';
				$title = __('Online');
				break;
				
			case Monitor_host_Model::STATE_DOWN:
				$image = url::base().'media/images/states/inactive.png';
				$title = __('Offline');
				break;
				
			case Monitor_host_Model::STATE_UNKNOWN:
				$image = url::base().'media/images/states/inactive.png';
				$title = __('Offline');
				break;
		}
		
		echo html::image(array
		(
				'src' => $image,
				'title' => $title,
				'class' => 'monitor-state'
		));
	}
	
	/**
	 * Callback function to print name of month
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function month_field ($item, $name)
	{
		echo strftime('%B', mktime(0,0,0,$item->month));
	}
	
	/**
	 * Callback function to print form field for notification action
	 * @param type $item
	 * @param type $name
	 * @param type $input
	 * @param type $args 
	 */
	public static function notification_form_field ($item, $name, $input, $args = array())
	{
		$selected = Notifications_Controller::KEEP;
		switch ($args[0])
		{
			case Message_Model::DEBTOR_MESSAGE:
				
				if ($item->balance < Settings::get('debtor_boundary')
					&& !$item->whitelisted
					&& (!$item->interrupt || ($name == 'redirection' && $item->interrupt))
					&& ($item->type != Member_Model::TYPE_FORMER || ($name == 'redirection' && $item->type == Member_Model::TYPE_FORMER)))
				{
					$selected = Notifications_Controller::ACTIVATE;
				}
				
				break;
				
			case Message_Model::PAYMENT_NOTICE_MESSAGE:
				
				if ($item->balance >= Settings::get('debtor_boundary')
					&& $item->balance < Settings::get('payment_notice_boundary')
					&& (!$item->interrupt || ($name == 'redirection' && $item->interrupt))
					&& ($item->type != Member_Model::TYPE_FORMER || ($name == 'redirection' && $item->type == Member_Model::TYPE_FORMER))
					&& !$item->whitelisted)
				{
					$selected = Notifications_Controller::ACTIVATE;
				}
				
				break;
				
			case Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE:
				
				if ($item->interrupt)
					$selected = Notifications_Controller::ACTIVATE;
				
				break;
				
			case Message_Model::UNALLOWED_CONNECTING_PLACE_MESSAGE:
				
				if (!$item->allowed && (!$item->interrupt || ($name == 'redirection' && $item->interrupt)))
					$selected = Notifications_Controller::ACTIVATE;
				
				break;
				
			case Message_Model::USER_MESSAGE:
				
				if ((!$item->interrupt || ($name == 'redirection' && $item->interrupt)) && (!$item->whitelisted || ($item->whitelisted && $args[1]==1)))
					$selected = Notifications_Controller::ACTIVATE;
				
				break;
			
		}
		
		$input->selected($selected);
		echo $input->html();
	}

	/**
	 * Callback field for member name. Leaves blank name if needed.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name 
	 */
	public static function member_field($item, $name, $args = array())
	{
		if ($item->member_id)
		{
			echo html::anchor("members/show/$item->member_id", $item->member_name);
		}
		elseif (isset($args[0]))
		{
			echo $args[0];
		}
		else
		{
			echo '&nbsp';
		}
	}
	
	/**
	 * Callback function to print type of member
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function member_type_field ($item, $name)
	{
		echo Member_Model::get_type($item->$name);
	}
	
	/**
	 * Callback function to print avarage of traffic of members
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name
	 * @param type $args 
	 */
	public static function members_traffic_avg_field($item, $name, $args = array())
	{
		$val = $item->$name;
		
		if (isset($args[0]))
		{
			switch ($args[0])
			{
				case 'weekly':
					$val /= 7;
					break;
				
				case 'monthly':
					$val /= date::days_of_month($item->month);
					break;
				
				case 'yearly':
					$val /= 365;
					break;
			}
		}
		
		echo network::size($val);
	}

	/**
	 * Activates multiple redirections. All interrupted members, all debtors
	 * with credit below debtor boundary and all should-pay members with
	 * credit below payment notice boundary may be redirected.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function message_activate_field($item, $name)
	{
		$message = new Message_Model($item->id);
		
		if ($message->type == Message_Model::CONTACT_INFORMATION ||
			$message->type == Message_Model::CANCEL_MESSAGE ||
			$message->type == Message_Model::UNKNOWN_DEVICE_MESSAGE)
		{
			echo '&nbsp;';
		}
		else
		{
			echo html::anchor('messages/activate/'.$message->id, __('Activate'));
		}
	}

	/**
	 * Deactivates all activated redirections for given message.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function message_deactivate_field($item, $name)
	{
		$message = new Message_Model($item->id);
		
		if ($message->type == Message_Model::CONTACT_INFORMATION ||
			$message->type == Message_Model::CANCEL_MESSAGE ||
			$message->type == Message_Model::UNKNOWN_DEVICE_MESSAGE)
		{
			echo '&nbsp;';
		}
		else
		{
			echo html::anchor('messages/deactivate/'.$message->id, __('Deactivate'));
		}
	}

	/**
	 * Field for deleting messages. Only user messages are allowed to be deleted.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function message_delete_field($item, $name)
	{
		$message = new Message_Model($item->id);
		
		if ($message->type == Message_Model::USER_MESSAGE)
		{
			echo html::anchor('messages/delete/'.$message->id, __('Delete'));
		}
		else
		{
			echo '&nbsp';
		}
	}

	/**
	 * Callback function to print preview link of redirection message
	 * 
	 * @author Michal Kliment
	 * @param unknown_type $item
	 * @param unknown_type $name 
	 */
	public static function message_preview_field($item, $name)
	{
		$message = new Message_Model($item->id);
		
		if (empty($message->text))
		{
			echo '<span class="red">' . __('Empty') . '</span>';
		}
		else
		{
			echo html::anchor(
					url::base().'redirection/?id='.$message->id,
					__('Preview'), array('target' => '_blank')
			);
		}
	}
	
	/**
	 * Callback function to print self-cancel state of message
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function message_self_cancel_field ($item, $name)
	{
		switch ($item->self_cancel)
		{
			case Message_Model::SELF_CANCEL_DISABLED:
				echo __('No');
				break;
			
			case Message_Model::SELF_CANCEL_MEMBER:
				$title = __('Possibility of canceling redirection to all IP addresses of member');
				echo "<span class='help' title='".$title."'>".__('Yes')."</span>";
				break;
			
			case Message_Model::SELF_CANCEL_IP:
				$title = __('Possibility of canceling redirection to only current IP address');
				echo "<span class='help' title='".$title."'>".__('Yes')."</span>";
				break;
		}
	}

	/**
	 * Callback for object of log
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function object_log_field($item, $name)
	{
		echo html::anchor(
			'logs/show_object/'.$item->table_name.'/'.$item->object_id,
			$item->object_id
		);
	}
	
	/**
	 * Callback for order number field in grid
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param array $args
	 */
	public static function order_number_field($item, $name, $args = array())
	{
		static $order_number;

		// first time set default number
		if (!isset($order_number))
			$order_number = $args[0];

		// direction
		switch ($args[1])
		{
			// increases
			case 1:
				$order_number++;
				break;
			// decreases
			case -1:
				$order_number--;
				break;
			default:
				// do nothing
				break;
		}

		echo $order_number.'.';
	}

	/**
	 * Callback for locked private field in phone invoices
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function phone_invoice_private_field_locked($item, $name)
	{
		if ($item->private == 1)
		{
			echo '<span style="color: green">' . __('yes') . '</span>';
		}
		else
		{
			echo '<span style="color: red">' . __('no') . '</span>';
		}
	}
	
	/**
	 * Callback for showing state of invoice
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function phone_invoice_user_state($item, $name)
	{		
		if ($item->locked == 1)
		{
			$title = __('Locked by admin');
			$src = 'media/images/states/readonly_16x16.png';
		}
		else if ($item->filled == 1)
		{
			$title = __('Already filled in');
			$src = 'media/images/states/good_16x16.png';
		}
		else
		{
			$title = __('Not filled in');
			$src = 'media/images/states/publish_x.png';
		}
		
		echo Html::image(array
		(
			'src'	=> $src,
			'title'	=> $title
		));
	}
	
	/**
	 * Callback for showing state of invoice
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function phone_invoice_user_state2($item, $name)
	{		
		if ($item->filled == 1)
		{
			echo Html::image(array
			(
				'src'	=> 'media/images/states/good_16x16.png',
				'title'	=> __('Already filled in')
			));
		}
		else
		{
			echo Html::image(array
			(
				'src'	=> 'media/images/states/publish_x.png',
				'title'	=> __('Not filled in')
			));
		}
	}

	/**
	 * Callback for phone period field
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function phone_period_field($item, $name)
	{
		echo period::get_name($item->period);
	}

	/**
	 * Callback for phone field in phone invoices
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function phone_number_field($item, $name)
	{
		static $pucontact = null;

		if ($pucontact == null)
		{
			$pucontact = new Private_users_contact_Model();
		}
		
		if (!mb_eregi("^[0-9]+$", $item->number))
		{
			echo $item->number;
			return;
		}

		$pucontact_id = Private_phone_contacts_Controller::private_contacts_cache($item->user_id, $item->number);
		$user = new User_Model($item->user_id);

		if ($pucontact_id > 0)
		{
			$pucontact = $pucontact->find($pucontact_id);

			if ($user->id && $user->member_id == $_SESSION['member_id'])
			{
				echo '<span style="color: green;" title="' . $pucontact->contact->value . '">' .
				$pucontact->description . '</span> ';

				echo html::anchor(
					'private_phone_contacts/edit/' . $pucontact_id,
					html::image(array('src' => 'media/images/icons/gtk_edit.png')),
					array('rel' => 'dialog', 'class' => 'link_private_contact_edit',
					    'title' => __('Edit'))
				);

				echo ' ' . html::anchor(
					'private_phone_contacts/delete/' . $pucontact_id,
					html::image(array('src' => 'media/images/icons/delete.png')),
					array('rel' => 'dialog', 'class' => 'link_private_contact_delete',
					    'title' => __('Delete'))
				);
			}
			else
			{
				echo '<b style="color: #888;" title="' . $pucontact->description . '">' .
				$item->number . '</b>';

				if ($pucontact->user_id)
				{
					echo ' ' . html::anchor(
						'phone_invoices/show_history/' .
						$pucontact->user_id . '/' . $item->number . '/' . $item->phone_invoice_user_id,
						html::image(array('src' => 'media/images/icons/history.png')),
						array('rel' => 'dialog', 'class' => 'link_private_contact_delete',
						    'title' => __('History'))
					);
				}
			}
		}
		else
		{
			$uid = Phone_invoices_Controller::user_number_cache($item->number);
			if ($uid == 0)
			{ // number is not in database => display it and allow to add
				// b  title is jused for selection of number by javascript
				echo '<b title="' . $item->number . '">' . $item->number . '</b> ';

				if ($user->id && $user->member_id == $_SESSION['member_id'])
				{

					echo html::anchor(
						'private_phone_contacts/add/' . $item->user_id . '/' . $item->number,
						html::image(array('src' => 'media/images/icons/ico_add.gif')),
						array('rel' => 'dialog', 'class' => 'link_private_contact_add',
						    'title' => __('Add'))
					);
				}

				if ($item->user_id)
				{
					echo ' ' . html::anchor(
						'phone_invoices/show_history/' .
						$item->user_id . '/' . $item->number . '/' . $item->phone_invoice_user_id,
						html::image(array('src' => 'media/images/icons/history.png')),
						array('rel' => 'dialog', 'class' => 'link_private_contact_delete',
						    'title' => __('History'))
					);
				}
			}
			else
			{ // number bellow to user of free net is, display his name
				$user->find($uid);

				echo '<span style="color: green;">' . $user->name . ' ' . $user->surname . '</span>';
			}
		}
	}

	/**
	 * Callback for private checkbox field in phone invoices
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 * @param object $input  Checkbox
	 */
	public static function phone_private_checkbox($item, $name, $input)
	{
		echo $input->html();

		if ($item->private == 1)
		{
			$contact_id = Private_phone_contacts_Controller::private_contacts_cache(
					$item->user_id, $item->number
			);

			if ($contact_id > 0)
			{
				echo ' <span style="color: green">' .
					__('private contact') . '</span>';
			}
		}
	}
	
	/**
	 * Callback function to print mode of port
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function port_mode_field($item, $name)
	{
		echo Iface_Model::get_port_mode($item->port_mode);
	}
	
	/**
	 * Callback function to print count of ports and grouped ports as title
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function ports_field ($item, $name)
	{
		if ($item->ports_count)
		{
			echo '<span class="help" title="'.$item->ports.'">'.$item->ports_count.'</span>';
		}
		else
		{
			echo $item->ports_count;
		}
	}

	/**
	 * Callback field for redirection.
	 * 
	 * @param object $item
	 * @param string $name 
	 */
	public static function redirect_field($item, $name)
	{
		echo "<span class='help' title='$item->redirect_text'>$item->redirect</span>";
	}

	/**
	 * Callback field for user login.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function redirection_preview_field($item, $name)
	{
		echo html::anchor(
				url::base().'redirection/?ip_address='.$item->ip_address,
				__('Show')
		);
	}
	
	/**
	 * Callback function to print registration state
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function registration_field ($item, $name)
	{
		if ($item->registration == __('Yes'))
		{
			echo '<span style="color: green">'.__('Yes').'</span>';
		}
		else
		{
			echo '<span style="color: red">'.__('No').'</span>';
		}
	}
	
	/**
	 * Callback function to print name of segment's item
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function link_item_field ($item, $name)
	{
		echo html::anchor('ifaces/show/'.$item->id, $item->name);
	}

	/**
	 * Callback to print segment medium
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function link_medium_field ($item, $name)
	{
		echo Link_Model::get_medium_type($item->medium);
	}
	
	/**
	 * Callback function to print SMS receiver
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function sms_receiver_field ($item, $name)
	{
		if ($item->receiver_id)
		{
			echo html::anchor(
				url_lang::base().'users/show/'.$item->receiver_id,
				$item->receiver_name,
				array('title' => $item->receiver)
			);
		}
		else
			echo $item->receiver;
		
	}

	/**
	 * Callback function to print SMS sender
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function sms_sender_field ($item, $name)
	{
		if ($item->sender_id)
		{
			echo html::anchor(
				url_lang::base().'users/show/'.$item->sender_id,
				$item->sender_name,
				array('title' => $item->sender)
			);
		}
		else
			echo $item->sender;
	}

	/**
	 * Callback for traffic field
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function traffic_field($item, $name)
	{
		echo network::size($item->$name);
	}
	
	/**
	 * Callback function to download traffic
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function traffic_download_field($item, $name)
	{
		echo network::size($item->$name).' '.html::image(array('src' => url::base().'media/images/icons/download.png'));
	}
	
	/**
	 * Callback function to upload traffic
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function traffic_upload_field($item, $name)
	{
		echo html::image(array('src' => url::base().'media/images/icons/upload.png')).' '.network::size($item->$name);
	}
	
	/**
	 * Callback for transfer
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function transfer_link($item, $name)
	{
		if (isset($item->transfer_id, $item->amount) && $item->transfer_id)
		{
			echo html::anchor(
					'transfers/show/' . $item->transfer_id,
					number_format($item->amount, 2, ',', ' ') .
					' ' . __(Settings::get('currency'))
			);
		}
	}

	/**
	 * Callback to print value of used (e. g. used of ip addresses in subnet)
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function used_field ($item, $name)
	{
		$color = '';

		if ($item->used > 80)
			$color = 'red';

		if ($item->used < 20)
			$color = 'green';

		echo ($color != '') ?
				'<span style="color: '.$color.'">'.$item->used.' %</span>' :
				$item->used.' %';
	}

	/**
	 * Callback function to print traffic field in ulogd
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function ulogd_traffic_field($item, $name)
	{
		echo "<span";
		
		if (!$item->member_id && network::ip_address_in_ranges($item->ip_address))
			echo " class='red'";
		
		echo ">";
		self::traffic_field($item, $name);
		echo "</span>";
	}

	/**
	 * Callback function to print order number field in ulogd
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param array $args
	 */
	public static function ulogd_order_number_field($item, $name, $args = array())
	{
		echo "<span";
		
		if (!$item->member_id && network::ip_address_in_ranges($item->ip_address))
			echo " class='red'";
		
		echo ">";
		self::order_number_field($item, $name, $args);
		echo "</span>";
	}

	/**
	 * Callback function to print ip address field in ulogd
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function ulogd_ip_address_field($item, $name)
	{
		echo "<span";
		
		if (!$item->member_id && network::ip_address_in_ranges($item->ip_address))
			echo " class='red'";
		
		echo ">";
		echo $item->ip_address;
		echo "</span>";
	}

	/**
	 * Callback function to print member field in ulogd
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param array $args
	 */
	public static function ulogd_member_field($item, $name, $args = array())
	{
		echo "<span";
		
		if (!$item->member_id && network::ip_address_in_ranges($item->ip_address))
			echo " class='red'";
		
		echo ">";
		self::member_field($item, $name, $args);
		echo "</span>";
	}
	
	/**
	 * Callback field for user name of user given by id.
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function user_id_log_field($item, $name)
	{
		$user = new User_Model($item->user_id);

		if ($user->id)
		{
			echo html::anchor('users/show/'.$user->id, $user->get_full_name());
			echo ' ';
			echo html::anchor('logs/show_by_user/'.$user->id, html::image(array
			(
				'src' => 'media/images/icons/history.png',
				'title' => __('Show user actions')
			)));
		}
		else
		{
			echo $item->user_id;
		}
	}
	
	/**
	 * Callback for values of log
	 * 
	 * @author Ondřej Fibich
	 * @param object $item
	 * @param string $name
	 */
	public static function value_log_field($item, $name)
	{
		if (!empty($item->values))
		{
			$array = json_decode($item->values);

			foreach ($array as $key => $value)
			{
				echo htmlspecialchars($key) .' = '.
				     htmlspecialchars($value) . '<br />';
			}
		}
	}
	
	/**
	 * Callback function to print VLANS count with preview of names
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function vlans_field ($item, $name)
	{
		if ($item->vlans_count)
			echo '<span class="help" title="'.$item->vlans.'">'.$item->vlans_count.'</span>';
		else
			echo $item->vlans_count;
	}

	/**
	 * Callback to print caller
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param array $args
	 */
	public static function voip_caller($item, $name, $args = array())
	{
		$number = VoIP_calls_Controller::parse_number(
				substr($item->caller, 4, strlen($item->caller) - 4)
		);

		echo VoIP_calls_Controller::number($number, $args[0], $args[1]);
	}

	/**
	 * Callback to print called
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 * @param array $args
	 */
	public static function voip_callcon($item, $name, $args = array())
	{
		$number = VoIP_calls_Controller::parse_number(
				substr($item->callcon, 4, strlen($item->callcon) - 4), $item->area
		);

		echo VoIP_calls_Controller::number($number, $args[0], $args[1]);
	}

	/**
	 * Callback function to show vote as image
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function vote($item, $name)
	{
		switch ($item->vote)
		{
			case 1:
				$img = 'agree';
				break;
			case -1:
				$img = 'disagree';
				break;
			case 0:
				$img = 'abstain';
				break;
		}
		
		echo html::image(array
		(
			'src'	=> 'media/images/states/' . $img . '.png',
			'alt'	=> __($img),
			'title'	=> __($img),
			'id'	=> 'i' . $item->id
		));
	}
	
	/**
	 * Callback to print vote state with vote comments as title
	 *
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function vote_state_field ($item, $name)
	{
		if (property_exists($item, 'vote_comments'))
		{
			echo "<span title='$item->vote_comments' class='help'>";
		}
		
		echo '<span style="color: green">'.$item->agree_count.'</span>';
		echo '/';
		echo '<span style="color: red">'.$item->disagree_count.'</span>';
		echo '/';
		echo $item->abstain_count;
			
		if (property_exists($item, 'vote_comments'))
		{
			echo "</span>";
		}
	}
	
	/**
	 * Callback function to print week
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function week_field ($item, $name)
	{
		$start = date::start_of_week($item->week, $item->year);
		$end = date::end_of_week($item->week, $item->year);
		
		echo '<span title="'.$start. ' - ' . $end.'">'.$item->week.'.</span>';
	}
	
	/**
	 * Callback field for correct view of whitelisted option of ip address.
	 * 
	 * @author Jiri Svitak
	 * @param object $item
	 * @param string $name
	 */
	public static function whitelisted_field($item, $name)
	{
		switch ($item->whitelisted)
		{
			case Ip_address_Model::PERNAMENT_WHITELIST:
				echo __('Permanent whitelist');
			break;
			case Ip_address_Model::TEMPORARY_WHITELIST:
				echo __('Temporary whitelist');
			break;
			default:
				echo __('No whitelist');
		}
	}
	
	/**
	 * Callback function to print mode of wireless interface
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function wireless_mode ($item, $name)
	{
		echo Iface_Model::get_wireless_mode($item->$name);
	}
	
	/**
	 * Callback function to print frequence of wireless segment
	 * 
	 * @author Michal Kliment
	 * @param type $item
	 * @param type $name 
	 */
	public static function wireless_segment_frequence ($item, $name)
	{
		$class = $title = '';
		
		if ($item->channel != '')
		{
			$class = 'help';
			$title = __('Channel %s', $item->channel);
		}
		
		echo "<span class='$class' title='$title'>$item->frequence MHz</span>";
	}
	
	/**
	 * Callback function to print norm of wireless segment
	 * 
	 * @author Michal Kliment
	 * @param object $item
	 * @param string $name
	 */
	public static function wireless_link_norm ($item, $name)
	{
		echo Link_Model::get_wireless_norm($item->wireless_norm);
	}
	
	/**
	 * Callback for true false values.
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function work_approved($item, $name)
	{
		$bool = $item->$name;
		
		echo html::image(array
		(
			'src' => 'media/images/states/' . ($bool ? 'agree.png' : 'disagree.png')
		));
	}
	
	/**
	 * Callback for work report rating
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function work_report_rating($item, $name)
	{
		$rating = $item->$name;
		
		if (is_numeric($rating))
		{
			echo html::anchor(
					'transfers/show/'.$item->transfer_id,
					number_format($rating, 2, ',', ' ').' '.
					__(Settings::get('currency'))
			);
		}
		else
		{
			echo '<span class="red">' . $rating . '</span>';
		}
	}
	
	/**
	 * Callback for work report type
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function work_report_type($item, $name)
	{
		$type = $item->$name;
		
		if ($type && preg_match("/^[0-9]{4}-[0-9]{1,2}$/", $type))
		{
			$date = explode('-', $type);
			
			echo '<span title="' . __('Work report per month') . '" class="more">' . __('MWR', '', 3) . '</span> ';
			echo __('for', '', 1);
			echo ' <b>' . __(date::$months[intval($date[1])]) . ' ' . $date[0] . '</b>';
		}
		else
		{
			echo '<span title="' . __('Grouped works') . '" class="more">' . __('GW', '', 3) . '</span> ';
			echo __('since', '', 1) . ' ';
			echo date('j.n.Y', strtotime($item->date_from)) . ' ';
			echo __('until', '', 1) . ' ';
			echo date('j.n.Y', strtotime($item->date_to));
		}
	}
	
	/**
	 * Callback for work reports. Displayes votes of a voter on a report.
	 *
	 * @author Ondrej Fibich
	 * @param object $item
	 * @param string $name 
	 */
	public static function votes_of_voter($item, $name)
	{
		static $job_report_model = NULL;
		
		if (isset($item->user_id, $item->id))
		{
			if ($job_report_model == NULL)
			{
				$job_report_model = new Job_report_Model();
			}
			
			$votes = $job_report_model->get_votes_of_voter_on_work_report(
					$item->id, Session::instance()->get('user_id')
			);
			
			if ($votes->count())
			{	
				$count_votes = 0;
				$tvotes = array(-1 => 0, 0 => 0, 1 => 0);
				$icon = 'abstain';
				
				foreach ($votes as $vote)
				{
					if ($vote->id)
					{
						$count_votes++;
						$tvotes[$vote->vote]++;
					}
				}
				
				if ($count_votes > 0)
				{
					$icon = ($tvotes[1] == $votes->count()) ? 'agree' : 'disagree';
				}
			
				echo '<img alt="voted" src="' . url::base() . '/media/images/states/' . $icon . '.png" title="';
				
				if ($count_votes > 0)
				{
					echo __('You have voted in') . ' ' . $count_votes . ' ' . __('of') . ' ' . $votes->count() . "\n";
					echo __('Your votes') . ': ' . $tvotes[1] . '/' . $tvotes[-1] . '/' . $tvotes[0];
					echo ' (' . __('Agree') . '/' . __('Disagree') . '/' . __('Abstain') . ')';
				}
				
				echo '" />';
			}
		}
	}
}
