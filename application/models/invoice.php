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
 * Invoice
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $supplier_id
 * @property Member_Model $supplier
 * @property double $invoice_nr
 * @property double $var_sym
 * @property double $con_sym
 * @property date $date_inv
 * @property date $date_due
 * @property date $date_vat
 * @property double $vat
 * @property double $order_nr
 * @property string $currency
 * @property ORM_Iterator $invoice_items
 */
class Invoice_Model extends ORM
{
    protected $belongs_to = array('supplier' => 'member');
	protected $has_many = array('invoice_items');

    /**
     * Returns ORM_Iterator of all invoices
	 * 
     * @author Michal Kliment
     * @param $limit_from
     * @param $limit_results
     * @param $order_by
     * @param $order_by_direction
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
}
