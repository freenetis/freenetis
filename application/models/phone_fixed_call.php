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
 * Descriptions of Phone_fixed_call_Model
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
 * @property string $destiny
 * @property integer $period
 * @property boolean $private
 */
class Phone_fixed_call_Model extends ORM
{

    protected $belongs_to = array('phone_invoice_user');

    /**
     * Get fixed calls between user and number
	 * 
     * @param integer $user_id
     * @param string $number
     * @return Mysql_Result
     */
    public function get_history($user_id, $number)
    {
		return $this->db->query("
				SELECT * FROM phone_fixed_calls
				WHERE phone_invoice_user_id IN (
					SELECT id FROM phone_invoice_users WHERE user_id = ?
				) AND number LIKE ?
				ORDER BY datetime DESC
		", array($user_id, $number));
    }

    /**
     * Get private property by history of private services
	 * 
     * @param integer $user_id
     * @param integer $phone_invoice_user_id
     * @return Mysql_Result
     */
    public function get_private_property_by_history($user_id, $phone_invoice_user_id)
    {
		return $this->db->query("
				SELECT id, (
					SELECT IF(SUM(private) >= COUNT(private)/2, '1', '0') as private
					FROM phone_fixed_calls
					WHERE phone_invoice_user_id IN (
						SELECT id FROM phone_invoice_users
						WHERE user_id = ? AND id < ?
					) AND number LIKE p.number
				) as private
				FROM phone_fixed_calls p
				WHERE phone_invoice_user_id = ?
		", array($user_id, $phone_invoice_user_id, $phone_invoice_user_id));
    }

    /**
     * @param integer $phone_invoice_user_id
     * @return Mysql_Result
     */
    public function get_fixed_calls_from($phone_invoice_user_id)
    {
        return $this->db->query("
				SELECT phone_invoice_users.user_id, phone_fixed_calls.id, phone_fixed_calls.price,
					phone_fixed_calls.private, phone_fixed_calls.datetime,
					phone_fixed_calls.length, phone_fixed_calls.number, phone_fixed_calls.period,
					phone_fixed_calls.destiny, phone_invoice_user_id
				FROM phone_fixed_calls
				LEFT JOIN phone_invoice_users ON phone_fixed_calls.phone_invoice_user_id = phone_invoice_users.id
				WHERE phone_fixed_calls.phone_invoice_user_id=?
		", array($phone_invoice_user_id));
    }

    /**
     * Set private flag
	 * 
     * @param integer $phone_invoice_user_id
     * @param array $private_ids  Key id, value const 1
     */
    public function set_fixed_calls_private($phone_invoice_user_id, $private_ids)
	{
		// reset all
		$this->db->query(
				"UPDATE phone_fixed_calls
		 SET private = '0'
		 WHERE phone_invoice_user_id=?", array($phone_invoice_user_id)
		);
		// set private
		if (is_array($private_ids) && count($private_ids))
		{
			$private_ids = array_keys($private_ids);
			// protection from SQL injection
			$private_ids = array_map('intval', $private_ids);

			$this->db->query("
					UPDATE phone_fixed_calls
					SET private = 1
					WHERE ID IN(" . implode(',', $private_ids) . ")
			");
		}
	}

}
