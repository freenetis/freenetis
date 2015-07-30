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
class Parser_Pohoda_Invoice
{
	/**
	 * Lines
	 * @var array
	 */
	private $lines = array();

	/**
	 * XML string
	 * @var string
	 */
	private $xml_string = '';

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
	 * Try to open file and read it
	 *
	 * @param string $filename
	 * @return bool
	 */
    private function open($filename = '')
	{
		if ($filename == '' || !file_exists($filename))
		{
			return false;
		}
		
		$this->lines = file($filename);
		
		if (!$this->lines)
		{
			return false;
		}
		
		return true;
	}

	/**
	 * Get XML string
	 */
	private function get_xml_string()
	{
		$xml = false;
		
		foreach ($this->lines as $line)
		{
			if (trim($line) == '<XML>')
			{
				$xml = true;
			}
			
			if ($xml)
			{
				$this->xml_string .= $line;
			}
			
			if (trim($line) == '</XML>')
			{
				$xml = false;
			}
		}
		
		$this->xml_string = iconv('Windows-1250','UTF-8',$this->xml_string);
	}

	/**
	 * Load data from XML
	 *
	 * @return bool
	 */
    private function load_data_from_xml_string()
    {
        if ($this->data)
		{
			return true;
		}
		
        $this->data  = @simplexml_load_string($this->xml_string);
		
        if (!$this->data)
		{
			return false;
		}
		
        return true;
    }

    /**
     * Private method to setting up final values from basic data
     */
    private function set_values_from_data()
    {
        if (!$this->data)
		{
			die('Need data!');
		}
		
        $this->values = new StdClass();

		$member_model = new Member_Model();
		$company = $this->data->eform->invoice->supplier->company;
		$supplier = ORM::factory('member')->like('name', $company)->find();

		$documentTax = $this->data->eform->invoice->documentTax;
		
        $this->values->supplier_id	= $supplier->id;
        $this->values->invoice_nr	= (double) $documentTax['number'];
        $this->values->var_sym		= (double) $documentTax['symVar'];
        $this->values->con_sym		= (double) $documentTax['symConst'];
        $this->values->date_inv		= (string) $documentTax['date'];
        $this->values->date_due		= (string) $documentTax['dateDue'];
        $this->values->date_vat		= (string) $documentTax['dateTax'];
        $this->values->vat			= 0;
        $this->values->order_nr		= (int) $documentTax['numberOrder'];
        $this->values->currency		= '';

        $this->values->items = array();
        $i = 0;

        foreach ($this->data->eform->invoice->invoiceItem as $item)
        {
			$price_vat = $this->values->items[$i]->price + (double) $item['priceSumVAT'];
			
            $this->values->items[$i]->name = (string) $item;
            $this->values->items[$i]->code = (string) $item['code'];
            $this->values->items[$i]->quantity = (double) $item['quantity'];
            $this->values->items[$i]->author_fee = 0;
            $this->values->items[$i]->contractual_increase = 0;
            $this->values->items[$i]->service = !isset($item['refStockItem']);
            $this->values->items[$i]->price = (double) $item['priceSum'];
            $this->values->items[$i]->price_vat = $price_vat;
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
        if (strlen($string) != 10)
		{
			return NULL;
		}

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
        return (strtolower($value) == 'true') ? 1 : 0;
    }

    /**
     * Public method to parse XML file (uses private methods)
	 * 
     * @param $file file to parse
     */
    public function parse($file)
    {
		if (!$this->open($file))
		{
			die('Bad file');
		}

		$this->get_xml_string();

        $this->load_data_from_xml_string();

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
            