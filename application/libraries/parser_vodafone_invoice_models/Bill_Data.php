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

require_once "Object.php";

/**
 * Informace o faktuře a seznam čísel, které jsou fakturované.
 * Seznam čísel je ukládán v poli jejichž index je fakturované číslo a hodnota
 * třída Services, která obsahuje fakturované položky.
 *
 * @author Ondřej Fibich
 *
 * @property DateTime $billing_period_from
 * @property DateTime $billing_period_to
 * @property DateTime $date_of_issuance
 * @property int $variable_symbol
 * @property int $specific_symbol
 * @property float $total_price
 * @property float $dph
 * @property int $dph_rate
 * @property-read array $bill_numbers
 */
class Bill_Data extends Object
{

    /**
     * Zůčtovací období od
     * @var DateTime
     */
    protected $billing_period_from;
    /**
     * Zůčtovací období do
     * @var DateTime
     */
    protected $billing_period_to;
    /**
     * Datum vystavení
     * @var DateTime
     */
    protected $date_of_issuance;
    /**
     * Variabilní symbol
     * @var int
     */
    protected $variable_symbol;
    /**
     * Specifický symbol
     * @var int
     */
    protected $specific_symbol;
    /**
     * Celková cena
     * @var float
     */
    protected $total_price;
    /**
     * Daň
     * @var float
     */
    protected $dph;
    /**
     * Sazba daně faktury
     * @var integer
     */
    protected $dph_rate;
    /**
     * Pole fakturovaných čísel, index pole je číslo, hodnota třída Services
     * @var array
     */
    protected $bill_numbers;

    /**
     * Vytvoří instanci a inicializuje objekty pro datumy
     */
    public function __construct()
    {
        $this->billing_period_from = new DateTime();
        $this->billing_period_to = new DateTime();
        $this->date_of_issuance = new DateTime();
        $this->bill_numbers = array();
    }

    /**
     * Přidá služby čísla
     * @param mixed $number
     * @param Services $services
     * @throws InvalidArgumentException Při neexistujcím čísle
     */
    public function add_bill_number($number, Services $services)
    {
        if (array_key_exists($number, $this->bill_numbers))
        {
            throw new InvalidArgumentException();
        }
        $this->bill_numbers[$number] = $services;
    }

    /**
     * Vrací služby čísla
     * @param mixed $number
     * @return Services
     * @throws InvalidArgumentException Při neexistujcím čísle
     */
    public function get_bill_number($number)
    {
        if (!array_key_exists($number, $this->bill_numbers))
        {
            throw new InvalidArgumentException();
        }
        return $this->bill_numbers[$number];
    }

    /**
     * Nastaví služby
     * @param string $number
     * @param Services $services
     * @throws InvalidArgumentException Při neexistujcím čísle
     */
    public function set_bill_number($number, Services $services)
    {
        if (!array_key_exists($number, $this->bill_numbers))
        {
            throw new InvalidArgumentException();
        }
        $this->bill_numbers[$number] = $services;
    }

    protected function set_bill_numbers($bill_numbers)
    {
        if (!is_array($bill_numbers))
        {
            throw new InvalidArgumentException();
        }
        $this->bill_numbers = $bill_numbers;
    }

    protected function set_billing_period_from(DateTime $billing_period_from)
    {
        $this->billing_period_from = $billing_period_from;
    }

    protected function set_billing_period_to(DateTime $billing_period_to)
    {
        $this->billing_period_to = $billing_period_to;
    }

    protected function set_date_of_issuance(DateTime $date_of_issuance)
    {
        $this->date_of_issuance = $date_of_issuance;
    }

    protected function set_variable_symbol($variable_symbol)
    {
        $this->variable_symbol = intval($variable_symbol);
    }

    protected function set_specific_symbol($specific_symbol)
    {
        $this->specific_symbol = intval($specific_symbol);
    }

    protected function set_total_price($total_price)
    {
        $this->total_price = floatval($total_price);
    }

    protected function set_dph($dph)
    {
        $this->dph = floatval($dph);
    }

    protected function set_rate($dph_rate)
    {
        $this->dph_rate = intval($dph_rate);
    }

}
