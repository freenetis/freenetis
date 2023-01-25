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
 * Description of Variable_Symbol_Model
 *
 * @author David Raška
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $account_id
 * @property string	 $variable_symbol
 */
class Variable_Symbol_Model extends ORM
{
	protected $belongs_to = array('account');
	
	/**
	 * Returns account variable symbols
	 * 
	 * @param int $account_id
	 * @return Mysql_Result
	 */	
	public function find_account_variable_symbols($account_id)
	{
		return $this->db->query("
				SELECT vs.id,variable_symbol
				FROM variable_symbols vs
				LEFT JOIN accounts a ON a.id = vs.account_id
				WHERE account_id = ?
		", $account_id);
	}
	
	/**
	 * Returns account id of given variable_symbol
	 * 
	 * @param int $variable_symbol
	 * @return Mysql_Result
	 */
	public function get_account_id($variable_symbol)
	{
		$query = $this->db->query("
				SELECT account_id
				FROM variable_symbols
				WHERE variable_symbol = ?
			", $variable_symbol);
		
		if ($query->count() > 0)
		{
			$query = $query->current()->account_id;
		}
		
		return $query;
	}
	
	/**
	 * Returns member id of given variable_symbol
	 * 
	 * @param int $variable_symbol
	 * @return null|int
	 */
	public function get_member_id($variable_symbol)
	{
		$query = $this->db->query("
				SELECT a.member_id
				FROM variable_symbols vs
				JOIN accounts a ON a.id = vs.account_id
				WHERE vs.variable_symbol = ?
			", $variable_symbol);
		
		if ($query->count() > 0)
		{
			return $query->current()->member_id;
		}
		
		return NULL;
	}
	
	/**
	 * Returns variable symbol id of given variable_symbol
	 * 
	 * @param int $variable_symbol
	 * @return Mysql_Result
	 */
	public function get_variable_symbol_id($variable_symbol)
	{
		$query = $this->db->query("
				SELECT *
				FROM variable_symbols
				WHERE variable_symbol = ?
			", $variable_symbol);
		
		if ($query->count() > 0)
		{
			return $query->current();
		}
		
		return NULL;
	}
	
	/**
	 * Checks if variable symbol is used
	 * 
	 * @param int $vs_id
	 * @return Mysql_Result
	 */
	public function variable_symbol_used($id)
	{
		$query = $this->db->query("
				SELECT bt.id
				FROM variable_symbols vs
				LEFT JOIN bank_transfers bt ON vs.variable_symbol = bt.variable_symbol
				WHERE vs.id = ?
			",$id);
		
		if ($query->count() > 0)
		{
			return $query = $query->current()->id;
		}
		
		return NULL;
	}
	
	/**Get variable symbol from member id
	*/
	public function get_variable_symbol_id_member($member_id)
	{
		$query = $this->db->query("
				SELECT v.*
				FROM variable_symbols v
				JOIN accounts a ON v.account_id = a.id
				WHERE a.member_id = ?
			",$member_id);
		if ($query->count() > 0)
		{
			return $query = $query->current()->id;
		}
		
		return NULL;

	}
	
	/** remove variable symbol from id
	*/
	public function delete_variable_symbol($id)
	{
		$this->db->query("
				DELETE FROM variable_symbols WHERE id = ?
				",$id);
	}
}
