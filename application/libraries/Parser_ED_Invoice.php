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
 * This is one from parsers of some kinds of invoice.
 * Parser loads data from XML file and store it in object.
 * 
 * @author Michal Kliment
 * @version 1.0
 */
class Parser_ED_Invoice  {

    /**
     * Basic data obtained from XML file
     * @var SimpleXML object
     */
    private $data = NULL;

    /**
     * Object of final data
     * @var StdClass object
     */
    private $values = NULL;

    /**
     * Private method to set up id of supplier
     * @param $id  if of member to set up as supplier
     */
    public function set_supplier_id ($id = NULL)
    {
        $this->supplier_id = $id;
    }

    /**
     * Private method to open XML file and load data in SImpleXML object
	 * 
     * @param $file file to parse
     * @return true if data was successfully loaded, false if not
     */
    private function load_data_from_xml_file($file)
    {
        if ($this->data) return true;
        $this->data  = @simplexml_load_file($file);
        if (!$this->data) return false;
        return true;
    }

    /**
     * Private method to setting up final values from basic data
     */
    private function set_values_from_data()
	{
		if (!$this->data)
			die('Need data!');
		
		$this->values = new StdClass();
		$this->values->supplier_id = $this->supplier_id;
		$this->values->invoice_nr = (double) $this->data->INVOICE->INV_ID;
		$this->values->var_sym = (double) $this->data->INVOICE->VAR_SYM;
		$this->values->con_sym = (double) $this->data->INVOICE->CON_SYM;
		$this->values->date_inv = $this->get_datetime_from_string((string) $this->data->INVOICE->DATE_INV);
		$this->values->date_due = $this->get_datetime_from_string((string) $this->data->INVOICE->DATE_DUE);
		$this->values->date_vat = $this->get_datetime_from_string((string) $this->data->INVOICE->DATE_VAT);
		$this->values->vat = $this->bool($this->data->INVOICE->VAT);
		$this->values->order_nr = (int) $this->data->INVOICE->ORD_ID;
		$this->values->currency = (string) $this->data->INVOICE->CUR_ID;

		$this->values->items = array();
		$i = 0;

		foreach ($this->data->INVOICE->ITEMS->ITEM as $item)
		{
			$this->values->items[$i]->name = (string) $item->PRO_NAME;
			$this->values->items[$i]->code = (string) $item->PRO_CODE;
			$this->values->items[$i]->quantity = (double) strtr($item->QTY, ',', '.');
			$this->values->items[$i]->author_fee = (double) strtr($item->AO, ',', '.');
			$this->values->items[$i]->contractual_increase = (double) strtr($item->SNC, ',', '.');
			$this->values->items[$i]->service = $this->bool($item->IS_TEXT);
			$this->values->items[$i]->price = (double) strtr($item->PRICE, ',', '.');
			$this->values->items[$i]->price_vat = (double) strtr($item->PRICE_VAT, ',', '.');
			$i++;
		}
	}

    /**
     * Private method to convert string to datetime
	 * 
     * @param $string string to convert
     * @return converted datetime
     */
    private function get_datetime_from_string($string = NULL)
    {
        if (strlen($string)!=10)  return NULL;

       $day = substr($string, 0, 2);
       $month = substr($string, 3, 2);
       $year = substr($string, 6, 4);
       return $year.'-'.$month.'-'.$day;
    }

    /**
     * Private method to convert string to bool
	 * 
     * @param $value string, probably 'true' or 'false'
     * @return return boolean true or false (without quotes)
     */
    private function bool($value = 'false')
    {
        return (strtolower($value)=='true') ? 1 : 0;
    }

    /**
     * Public method to parse XML file (uses private methods)
	 * 
     * @param $file file to parse
     */
    public function parse($file)
    {
        if (!$this->load_data_from_xml_file($file)) die('Spatny format souboru');
			$this->set_values_from_data();
    }

    /**
     * Public method to get final values
	 * 
     * @return StdClass object final values
     */
    public function get_values()
    {
        return $this->values;
    }

}
            