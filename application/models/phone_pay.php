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
 * Descriptions of Phone_pay_Model
 *
 * @author Ondřej Fibich
 * @package Model
 * 
 * @property integer $id
 * @property integer $phone_invoice_user_id
 * @property Phone_invoice_user_Model $phone_invoice_user
 * @property datetime $datetime
 * @property double $price
 * @property string $description
 * @property string $number
 * @property boolean $private
 */
class Phone_pay_Model extends ORM
{

    protected $belongs_to = array('phone_invoice_user');

	/**
	 * Gets pays from phone invoice user
	 *
	 * @param integer $phone_invoice_user_id
	 * @return Mysql_Result 
	 */
    public function get_pays_from($phone_invoice_user_id)
    {
        return $this->db->query("
				SELECT phone_invoice_users.user_id, phone_pays.id, phone_pays.price,
					phone_pays.private, phone_pays.datetime,
					phone_pays.number, phone_pays.description, phone_invoice_user_id
				FROM phone_pays
				LEFT JOIN phone_invoice_users ON phone_pays.phone_invoice_user_id = phone_invoice_users.id
				WHERE phone_pays.phone_invoice_user_id=?
		", array($phone_invoice_user_id));
    }

    /**
     * Set private flag
	 * 
     * @param integer $phone_invoice_user_id
     * @param array $private_ids  Key id, value const 1
     */
    public function set_pays_private($phone_invoice_user_id, $private_ids)
    {
		// reset all
		$this->db->query("
				UPDATE phone_pays
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
				UPDATE phone_pays
				SET private = '1'
				WHERE id IN(" . implode(',', $private_ids) . ")
			");
		}
    }

}
