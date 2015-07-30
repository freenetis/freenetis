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
 * Invoice teplates for import
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property integer $supplier_id
 * @property Member_Model $supplier
 * @property string $invoice_nr
 * @property string $var_sym
 * @property string $con_sym
 * @property string $date_inv
 * @property string $date_due
 * @property string $date_vat
 * @property string $vat
 * @property string $order_nr
 * @property string $currency
 * @property string $org_id
 * @property string $charset
 * @property boolean $xml
 * @property string $begin_tag
 * @property string $end_tag
 */
class Invoice_template_Model extends ORM
{
    protected $belongs_to = array('supplier' => 'member');
	
    /**
     * Function to get all invoice templates
	 * 
     * @author Michal Kliment
     * @return Mysql_Result
     */
    public function get_all_invoice_templates()
    {
		return $this->db->query("
				SELECT it.id, IFNULL(t.translated_term, it.name) as name
				FROM invoice_templates it
				LEFT JOIN translations t ON it.name = t.original_term
		");
    }

}
