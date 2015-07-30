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
 * Platební služba
 *
 * @author Ondřej Fibich
 *
 * @property DateTime $date_time
 * @property float $price
 * @property string $number
 * @property string $description
 */
class Pay_Service extends Service
{

    /**
     * Volané číslo
     * @var string
     */
    protected $number;
    /**
     * Popis platby
     * @var string
     */
    protected $description;

}
