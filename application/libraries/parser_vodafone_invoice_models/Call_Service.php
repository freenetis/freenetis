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

require_once "Service.php";

/**
 * Služba volání
 *
 * @author Ondřej Fibich
 *
 * @property DateTime $date_time
 * @property float $price
 * @property integer $period
 * @property string $number
 * @property string $length
 * @property string $description
 */
class Call_Service extends Service
{

    /**
     * Tarifové období
     * @see Period
     * @var integer
     */
    protected $period;
    /**
     * Volané číslo
     * @var string
     */
    protected $number;
    /**
     * Délka hovoru ve formátu hh:mm:ss
     * @var string
     */
    protected $length;
	/**
	 * Popis hovoru
	 * @param type $description
	 */
	protected $description;

    protected function set_period($period)
    {
        if (!period::is_valid($period))
        {
            throw new InvalidArgumentException(__('Wrong period'));
        }
        $this->period = $period;
    }

}
