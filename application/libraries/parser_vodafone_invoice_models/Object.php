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
 * Třída Object
 * Předek všech datových modelů.
 * Přetěžuje get a set metody.
 *
 * @author Ondřej Fibich
 */
class Object
{
    /**
     * Vrací hodnotu vlastnosti třídy.
     * Vyhledá předdefinovanou get metodu get_<vlastnost> a zavolá ji,
     * pokud ji nenalezne vrátí vlastnost bez kontroly její existence.
     * @param string $name Jméno vlastnosti
     * @return mixed       Hodnota vlastnosti
     */
    public function __get($name)
    {
        if (method_exists($this, ($method = "get_" . $name)))
        {
            return $this->$method();
        }
        else
        {
            return $this->$name;
        }
    }

    /**
     * Nastavuje hodnotu vlastnosti třídy.
     * Vyhledá předdefinovanou get metodu set_<vlastnost> a zavolá ji,
     * pokud ji nenalezne nastaví vlastnost bez kontroly její existence.
     * @param string $name Jméno vlastnosti
     * @param mixed $value Nová hodnota vlastnosti
     */
    public function __set($name, $value)
    {
        if (method_exists($this, ($method = "set_" . $name)))
        {
            $this->$method($value);
        }
        else
        {
            $this->$name = $value;
        }
    }

    /**
     * To string pomocí var_dump
     * @return string
     */
    public function __toString()
    {
        ob_start();
        var_dump(get_object_vars($this));
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }

}
