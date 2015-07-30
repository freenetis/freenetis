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
 * Abstrakní třída pro parsery telefonnich faktur.
 *
 * Cílem parseru je získat informace o faktuře, fakturovaných telefoních číslech
 * a o službách které byly číslům poskytnuty.
 *
 * @author Ondřej Fibich - ondrej.fibich(at)gmail(dot)com
 * @version 1.0
 */
abstract class Parser_Phone_Invoice
{

    /**
     * Parsovací funkce.
     *
     * Obsahuje vnitřní testování správnosti parsování a integrity dat ve 2 bodech:
     * - Testuje zda-li odpovídá počet fakturovaných a parsovaných čísel.
     * - Testuje zda-li odpovídají ceny položek služeb s celkovou cenou za danou službu
     *   daného čísla.
     *
     * @param string $text		         Text k parsování(vstup)
     * @param boolean $integrity_test_enabled
	 *								     Povolení testování integrity čísel
     *								     v podrobných výpisech
     * @return Bill_Data Data faktury
     * @throws Exception				 Při chybě při parsování
     * @throws InvalidArgumentException  Při prázdném vstupu
     */
    public static abstract function parse($text, $integrity_test_enabled = TRUE);

}
