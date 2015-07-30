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
 * Descriptions of Phone_invoice_Model
 *
 * @author Ondřej Fibich
 * @package Model
 *
 * @property integer $id
 * @property Date $billing_period_from
 * @property Date $billing_period_to
 * @property string $specific_symbol
 * @property Date $date_of_issuance
 * @property string $variable_symbol
 * @property string $specific_symbol
 * @property double $total_price
 * @property double $tax
 * @property double $tax_rate
 * @property boolean $locked
 * @property ORM_Iterator $phone_invoice_users
 */
class Phone_invoice_Model extends ORM
{

    protected $has_many = array('phone_invoice_users');

	/**
	 * Test if users has payd for invoice
	 *
	 * @return bool
	 */
	public function is_payed()
	{
		static $cache = array();
		
		if (!$this->id)
		{
			return FALSE;
		}
		
		if (!isset($cache[$this->id]))
		{
			$cache[$this->id] = $this->db->query("
				SELECT COUNT(*) AS count
				FROM phone_invoice_users
				WHERE phone_invoice_id = ? AND transfer_id IS NOT NULL
			", $this->id)->current()->count > 0;
		}
		
		return $cache[$this->id];
	}
	
    /**
     * Test if invoice is not in database already
	 * 
     * @return bool
     */
    public function is_unique()
	{
		return ($this->db->query("
			SELECT COUNT(*) AS count FROM `phone_invoices` 
			WHERE billing_period_from = ? 
				AND billing_period_to = ? 
				AND specific_symbol = ? 
				AND variable_symbol = ? 
				AND date_of_issuance = ?;
		", array(
			$this->billing_period_from,
			$this->billing_period_to,
			$this->specific_symbol,
			$this->variable_symbol,
			$this->date_of_issuance
		))->current()->count <= 0);
	}

    /**
     * Return al phone invoicces
	 * 
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
     * @return Mysql_Result
     */
    public function get_all_phone_invoices(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'billing_period_from',
			$order_by_direction = 'desc')
    {
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
		return $this->db->query("
				SELECT id, date_of_issuance, billing_period_from,
					billing_period_to, variable_symbol, specific_symbol,
					(total_price+tax) AS price, locked
				FROM phone_invoices
				ORDER BY ".$this->db->escape_column($order_by)." $order_by_direction
				LIMIT ".intval($limit_from).", ".intval($limit_results)."
		");
    }

}
