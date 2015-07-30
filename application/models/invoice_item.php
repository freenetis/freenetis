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
 * Invoice items
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $invoice_id
 * @property string $name
 * @property string $code
 * @property double $quantity
 * @property double $author_fee
 * @property double $contractual_increase
 * @property boolean $service
 * @property double $price
 * @property double $price_vat
 */
class Invoice_item_Model extends ORM
{
    protected $belongs_to = array('invoice');

    /**
     * Returns ORM_Iterator of all invoices
	 * 
     * @author Michal Kliment
     * @param integer $limit_from
     * @param integer $limit_results
     * @param string $order_by
     * @param string $order_by_direction
     * @return Mysql_Result
     */
	public function get_all_invoices(
			$limit_from = 0, $limit_results = 50,
			$order_by = 'id', $order_by_direction = 'ASC')
    {
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// query
        return $this->db->query("
				SELECT i.id, m.name as supplier, invoice_nr, var_sym, con_sym,
					date_inv, date_due, date_vat, vat, order_nr, currency
				FROM invoices i
				LEFT JOIN members m ON i.supplier_id = m.id
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
		");
    }
	
	/**
	 * Gets items of invoice
	 *
	 * @param integer $invoice_id
	 * @return Mysql_Result
	 */
	public function get_items_of_invoice($invoice_id)
	{
		return $this->where('invoice_id', $invoice_id)->find_all();
	}
	
}
