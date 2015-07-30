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

/**
 * Služba volání do pevné sítě
 *
 * @author Ondřej Fibich
 *
 * @property DateTime $date_time
 * @property float $price
 * @property integer $period
 * @property string $number
 * @property integer $length
 * @property string $destiny
 */
class Fixed_Call_Service extends Call_Service {
    
    /**
     * Cílová oblast
     * @var string
     */
    protected $destiny;

}
