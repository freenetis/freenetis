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

require_once "Call_Service.php";
require_once "Sms_Service.php";
require_once "Pay_Service.php";
require_once "Internet_Service.php";
require_once "Fixed_Call_Service.php";
require_once "Vpn_Call_Service.php";

/**
 * Informace o fakturovaných položkách.
 * Obsahuje pole, ve kterých se nachází jednotlivé položky faktury.
 * Položky jsou zastoupeny, speciálními třídami.
 *
 * @author Ondřej Fibich
 *
 * @property string $number
 * @property-read array $calls
 * @property-read array $vpn_calls
 * @property-read array $fixed_calls
 * @property-read array $internet
 * @property-read array $smss
 * @property-read array $pays
 * @property-read array $roaming_smss
 */
class Services
{

    /**
     * Fakturované číslo
     * @var string
     */
    protected $number;
    /**
     * Hovory
     * @var array
     */
    protected $calls;
    /**
     * VPN hovory
     * @var array
     */
    protected $vpn_calls;
    /**
     * Hovory do pevné linky
     * @var array
     */
    protected $fixed_calls;
    /**
     * Internetové služby
     * @var array
     */
    protected $internet;
    /**
     * Textové zprávy
     * @var array
     */
    protected $smss;
    /**
     * Platby
     * @var array
     */
    protected $pays;
    /**
     * Roamingoé sms
     * @var array
     */
    protected $roaming_smss;

    /**
     * Konstruktor
     * @param string $number
     */
    function __construct($number)
    {
        $this->number = $number;
        $this->calls = array();
        $this->fixed_calls = array();
        $this->internet = array();
        $this->pays = array();
        $this->vpn_calls = array();
        $this->roaming_smss = array();
        $this->smss = array();
    }

    public function __get($name)
    {
        if (property_exists(get_class($this), $name))
        {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if ($name == "number")
        {
            $this->number = $value;
        }
        else
        {
            throw new InvalidArgumentException();
        }
    }

    public function add_call(Call_Service $call)
    {
        $this->calls[] = $call;
    }

    public function add_vpn_call(Vpn_Call_Service $vpn_call)
    {
        $this->vpn_calls[] = $vpn_call;
    }

    public function add_fixed_call(Call_Service $fixed_call)
    {
        $this->fixed_calls[] = $fixed_call;
    }

    public function add_sms(Sms_Service $sms)
    {
        $this->smss[] = $sms;
    }

    public function add_roaming_sms(RoamingSms_Service $rss)
    {
        $this->roaming_smss[] = $rss;
    }

    public function add_pay(Pay_Service $pay)
    {
        $this->pays[] = $pay;
    }

    public function add_internet(Internet_Service $internet)
    {
        $this->internet[] = $internet;
    }

}
