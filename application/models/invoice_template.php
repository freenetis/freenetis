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
 * @property string $invoices
 * @property string $sup_company
 * @property string $sup_name
 * @property string $sup_street
 * @property string $sup_street_number
 * @property string $sup_town
 * @property string $sup_zip_code
 * @property string $sup_country
 * @property string $sup_organization_identifier
 * @property string $sup_phone_number
 * @property string $sup_email
 * @property string $cus_company
 * @property string $cus_name
 * @property string $cus_street
 * @property string $cus_street_number
 * @property string $cus_town
 * @property string $cus_zip_code
 * @property string $cus_country
 * @property string $cus_organization_identifier
 * @property string $cus_phone_number
 * @property string $cus_email
 * @property string $org_id
 * @property string $invoice_nr
 * @property string $invoice_type
 * @property string $invoice_type_issued
 * @property string $account_nr
 * @property string $var_sym
 * @property string $con_sym
 * @property string $date_inv
 * @property string $date_due
 * @property string $date_vat
 * @property string $vat
 * @property string $order_nr
 * @property string $price
 * @property string $price_vat
 * @property string $currency
 * @property string $note
 * @property string $items
 * @property string $item_name
 * @property string $item_code
 * @property string $item_quantity
 * @property string $item_price
 * @property string $item_vat
 * @property string $charset
 * @property string $namespace
 * @property string $vat_variables
 * @property integer $type
 * @property string $begin_tag
 * @property string $end_tag
 */
class Invoice_template_Model extends ORM
{	
	const TYPE_EFORM = 0;
	const TYPE_XML = 1;
	const TYPE_ISDOC = 2;
	const TYPE_DBASE = 3;
	const TYPE_ED_INV = 4;
	
	public static $fields = array(
						'type' => 'TYP',
						'form' =>'FORMA',
						'invoice_nr' => 'CISLO',
						'var_sym' => 'VARSYM',
						'date_inv' => 'DATUM',
						'date_due' => 'DATSPLAT',
						'date_vat' => 'DATZDPLN',
						'order_nr' => 'CISLOOBJ',
						'price_none' => 'KC0',
						'price_low' => 'KC1',
						'price_low_vat' => 'KCDPH1',
						'price_high' => 'KC2',
						'price_high_vat' => 'KCDPH2',
						'price_sum' => 'KCCELKEM',
						'price_liq' => 'KCLIKV',
						'rounding_amount' => 'KCZAOKR',
						'name' => 'JMENO',
						'company' => 'FIRMA',
						'street' => 'ULICE',
						'zip_code' => 'PSC',
						'town' => 'OBEC',
						'organization_identifier' => 'ICO',
						'email' => 'EMAIL',
						'phone' => 'TEL',
						'account_nr' => 'UCET',
						'bank_code' => 'KODBANKY',
						'con_sym' => 'KONSTSYM',
						'currency' => 'CIZI_MENA',
						'note' => 'STEXT'
	);
	
    protected $belongs_to = array('member' => 'member');
	
    /**
     * Function to get all invoice templates
	 * 
     * @author Michal Kliment
     * @return Mysql_Result
     */
    public function get_all_invoice_templates()
    {
		return $this->db->query("
				SELECT it.id, IFNULL(t.translated_term, it.name) as name,
					it.type AS type
				FROM invoice_templates it
				LEFT JOIN translations t ON it.name = t.original_term
		");
    }

}
