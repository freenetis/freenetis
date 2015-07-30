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
 * Výčet platebních období SMS a telefonátů
 *
 * @see Phone_invoices_Controller
 * @author Ondřej Fibich
 * @package Helper
 */
class period
{
    /**
     * Období špičky
     */
    const PEAK_HOURS = 1;

    /**
     * Období mimo špičku
     */
    const OUT_PEAK_HOURS = 2;

    /**
     * Období víkendu
     */
    const WEEKEND = 3;

    /**
     * Ve více obdobích
     */
    const MORE_PERIOD = 4;

    /**
     * Není v období
     */
    const NO_PERIOD = 5;

    /**
     * Pole zkratek období
     * @var array[string]
     */
    private static $short_cuts = array("Šp", "MŠp", "Vkn", "+=", "");
    /**
     * Pole celých jmen období
     * @var array[string]
     */
    private static $names = array("Peak", "Out of peak", "Weekend", "More period", "");

    /**
     * Kontroluje zda-li je perioda platná
     * @param int $period
     * @return boolean
     */
    public static function is_valid($period)
    {
        return ($period >= self::PEAK_HOURS && $period <= self::NO_PERIOD);
    }

    /**
     * Metoda pro výběr dat z polí dle periody
     * @param array[string] $source
     * @param integer $period
     * @return string
     * @throws InvalidArgumentException Při chybné periodě
     */
    protected static function _get($source, $period)
    {
        if (!self::is_valid($period))
        {
            throw new InvalidArgumentException();
        }
        return $source[$period - 1];
    }

    /**
     * Získá zkratku periody
     * @param integer $period  Konstanty PEAK_HOURS, OUT_PEAK_HOURS, WEEKEND
     * @return string  Zkratka
     * @throws InvalidArgumentException Při chybné periodě
     */
    public static function get_short_cut($period)
    {
        return self::_get(self::$short_cuts, $period);
    }

    /**
     * Získá celé jméno periody
     * @param integer $period  Konstanty PEAK_HOURS, OUT_PEAK_HOURS, WEEKEND
     * @return string  Jméno periody
     * @throws InvalidArgumentException Při chybné periodě
     */
    public static function get_name($period)
    {
        return url_lang::lang("texts." . self::_get(self::$names, $period));
    }

}
