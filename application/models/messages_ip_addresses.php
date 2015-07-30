<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Message IP adresses. Enables redirection to IP address.
 * 
 * @package Model
 */
class Messages_ip_addresses_Model extends Model
{
	/**
	 * Gets all redirections of IP address
	 *
	 * @param integre $ip_address_id
	 * @return Mysql_Result
	 */
	public function get_redirections_of_ip_address($ip_address_id)
	{
		return $this->db->query("
				SELECT m.id, m.name, mip.ip_address_id, mip.message_id
				FROM messages m
				JOIN messages_ip_addresses mip ON mip.message_id = m.id
				WHERE mip.ip_address_id = ?
		", $ip_address_id);
	}
	
	/**
	 * Deletes redirection of ip address
	 * 
	 * @author Michal Kliment
	 * @param integer $message_id
	 * @param integer $ip_address_id
	 * @return integer
	 */
	public function delete_redirection_of_ip_address ($message_id, $ip_address_id)
	{
		return $this->db->delete('messages_ip_addresses', array
		(
				'message_id'	=> $message_id,
				'ip_address_id'	=> $ip_address_id
		))->count();
	}
	
	/**
	 * Deletes all redirections of ip address
	 * 
	 * @author Michal Kliment
	 * @param integer $ip_address_id
	 * @return integer 
	 */
	public function delete_all_redirections_of_ip_address ($ip_address_id)
	{
		return $this->db->delete('messages_ip_addresses', array
		(
				'ip_address_id'	=> $ip_address_id
		))->count();
	}
	
	/**
	 * Deletes all system redirections of ip address
	 * 
	 * @author Michal Kliment
	 * @param integer $ip_address_id
	 * @return integer 
	 */
	public function delete_all_system_redirections_of_ip_address ($ip_address_id)
	{
		return $this->db->query("
			DELETE mip FROM messages_ip_addresses mip, messages m
			WHERE mip.message_id = m.id AND mip.ip_address_id = ? AND m.type > 0
		", $ip_address_id);
	}
	
	/**
	 * Adds new redirection to ip address
	 * 
	 * @author Michal Kliment
	 * @param integer $message_id
	 * @param integer $ip_address_id
	 * @param string $comment
	 * @return integer 
	 */
	public function add_redirection_to_ip_address ($message_id, $ip_address_id, $comment)
	{
		return $this->db->insert('messages_ip_addresses', array
		(
				'message_id'	=> $message_id,
				'ip_address_id'	=> $ip_address_id,
				'user_id'		=> Session::instance()->get('user_id'),
				'comment'		=> $comment,
				'datetime'		=> date('Y-m-d H:i:s')
		))->count();
	}
	
}