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
 * Bank accounts
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property integer $member_id
 * @property Member_Model $member
 * @property integer $account_nr
 * @property integer $bank_nr
 * @property string $IBAN
 * @property string $SWIFT
 * @property ORM_Iterator $accounts
 */
class Bank_account_Model extends ORM
{
	protected $belongs_to = array('member');
	protected $has_and_belongs_to_many = array('accounts');

	/**
	 * Contruct of app, shutdown action logs by default
	 * @param type $id 
	 */
	public function __construct($id = NULL)
	{
		parent::__construct($id);

		// disable action log
		$this->set_logger(FALSE);
	}

	/**
	 * @author Tomas Dulik
	 * @param string $name - name of the bank account
	 * @param integer $account_nr - bank account number
	 * @param integer $bank_nr - bank number 
	 * @param integer $member_id - id of the owner
	 * @return Bank_account_Model new object containing the new record model
	 */
	public static function create($name, $account_nr, $bank_nr, $member_id)
	{
		$member_id = intval($member_id);
		
		$bank_acc = new Bank_account_model();
		$bank_acc->member_id = ($member_id) ? $member_id : NULL;
		$bank_acc->name = $name;
		$bank_acc->account_nr = $account_nr;
		$bank_acc->bank_nr = $bank_acc;
		$bank_acc->save();		
		return $bank_acc;
	}
	
	/**
	 * It gets all bank accounts of association.
	 * 
	 * @author Jiri Svitak
	 * @return Mysql_Result
	 */
	public function get_assoc_bank_accounts()
	{	
		return $this->db->query("
				SELECT ba.id, ba.name AS baname, m.name AS mname,
					CONCAT(ba.account_nr, '/', ba.bank_nr) AS account_number
				FROM bank_accounts ba
				LEFT JOIN members m ON m.id = ba.member_id
				WHERE ba.member_id = 1
		");
	}

	/**
	 * It gets all bank accounts except bank accounts of association.
	 * @return Mysql_Result
	 */
	public function get_bank_accounts(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'id', $order_by_direction = 'asc',
			$filter_values = array())
	{
		$where = '';
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// filter
		if (is_array($filter_values))
		{
			foreach($filter_values as $key => $value)
			{
				if($key != 'submit')
				{
					if ($key == 'name')
					{
						$key = 'ba.name';
					}
					$where .= ' AND '.$this->db->escape_column($key).' LIKE '
							. $this->db->escape("%$value%")
							. ' COLLATE utf8_general_ci';
				}
			}
		}
		// query
		return $this->db->query("
				SELECT ba.id, ba.name AS baname, ba.account_nr, ba.bank_nr,
					m.name AS member_name, ba.member_id
				FROM bank_accounts ba
				LEFT JOIN members m ON m.id = ba.member_id
				WHERE ba.member_id <> 1 $where
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");	
	}
	
	/**
	 * It counts bank accounts except bank accounts of association.
	 * @return integer
	 */
	public function count_bank_accounts($filter_values = array())
	{
		$where = '';
		// filter
		if (is_array($filter_values))
		{
			foreach($filter_values as $key => $value)
			{
				if($key != 'submit')
				{
					if ($key == 'name')
						$key = 'ba.name';
					$where .= ' AND '.$this->db->escape_column($key)
							. ' LIKE ' . $this->db->escape("%$value%")
							. ' COLLATE utf8_general_ci';
				}
			}
		}
		// query
		return $this->db->query("
				SELECT COUNT(*) AS total
				FROM bank_accounts ba
				WHERE ba.member_id <> 1 $where"
		)->current()->total;	
	}	
	
	/**
	 * Function gets bank accounts except bank account with origin_id.
	 * @param $origin_id
	 * @return Mysql_Result
	 */
	public function get_destination_bank_accounts($origin_id)
	{
		return $this->db->query("
				SELECT *
				FROM bank_accounts
				WHERE id <> ?	
		", array($origin_id));
	}
	
	/**
	 * @author Tomas Dulik
	 * @param $attribute_id - a value from accounts.account_attribute_id
	 * @return object containing first related account from the account table having the $type 
	 */
	public function get_related_account_by_attribute_id($attribute_id)
	{
		if (!$this->id)
		{
			return FALSE;
		}
		
		$result = $this->db->query("
				SELECT accounts.* FROM accounts
				JOIN accounts_bank_accounts AS pivot 
				ON accounts.id=pivot.account_id AND pivot.bank_account_id=?
				AND accounts.account_attribute_id=?
		", array($this->id, $attribute_id));
		
		return ($result && $result->count()) ? $result->current() : FALSE;
	}
	
}
