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
 * Descriptions of Phone_vpn_call_Model
 *
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $phone_invoice_user_id
 * @property Phone_invoice_user_Model $phone_invoice_user
 * @property datetime $datetime
 * @property double $price
 * @property string $length
 * @property string $number
 * @property string $group
 * @property integer $period
 * @property boolean $private
 */
class Phone_vpn_call_Model extends ORM
{

    protected $belongs_to = array('phone_invoice_user');

	/**
	 * Gets VPN calls from phone invoice user
	 *
	 * @param integer $phone_invoice_user_id
	 * @return Mysql_Result
	 */
    public function get_vpn_calls_from($phone_invoice_user_id)
    {
        return $this->db->query("
				SELECT phone_invoice_users.user_id, phone_vpn_calls.id, phone_vpn_calls.price,
					phone_vpn_calls.private, phone_vpn_calls.datetime,
					phone_vpn_calls.length, phone_vpn_calls.number, phone_vpn_calls.period,
					phone_vpn_calls.group, phone_invoice_user_id
				FROM phone_vpn_calls
				LEFT JOIN phone_invoice_users ON phone_vpn_calls.phone_invoice_user_id = phone_invoice_users.id
				WHERE phone_vpn_calls.phone_invoice_user_id=?
		", array($phone_invoice_user_id));
    }

    /**
     * Set private flag
	 * 
     * @param integer $phone_invoice_user_id
     * @param array $private_ids  Key id, value const 1
     */
    public function set_vpn_calls_private($phone_invoice_user_id, $private_ids)
    {
		// reset all
		$this->db->query("
				UPDATE phone_vpn_calls
				SET private = '0'
				WHERE phone_invoice_user_id=?
		", array($phone_invoice_user_id));
		// set private
		if (is_array($private_ids) && count($private_ids))
		{
			$this->db->query("
					UPDATE phone_vpn_calls
					SET private = 1
					WHERE ID IN(" . implode(',', array_keys($private_ids)) . ")
			");
		}
    }

}
