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
 * Předek všech služeb operátora
 *
 * @author Ondřej Fibich
 *
 * @property DateTime $date_time
 * @property float $price
 */
class Service extends Object
{

    /**
     * Začátek služby
     * @var DateTime
     */
    protected $date_time;
    /**
     * Cena služby bez DPH
     * @var float
     */
    protected $price;

    /**
     * Konstruktor
     */
    function __construct()
    {
        $this->date_time = new DateTime();
    }

    protected function set_date_time(DateTime $date_time)
    {
        $this->date_time = $date_time;
    }

    protected function set_price($price)
    {
        $this->price = floatval($price);
    }

}
