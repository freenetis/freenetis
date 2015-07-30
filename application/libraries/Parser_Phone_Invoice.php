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
 *			David Raška - jeffraska(at)gmail(dot)com
 * @version 1.0
 */
abstract class Parser_Phone_Invoice
{
	/**
	 * Dir with classes
	 */
	const DIR = 'phone_invoice_parsers';
	
	/** Parser require file upload */
	const TYPE_UPLOAD = 1;
	/** Parser require data in textarea */
	const TYPE_TEXTAREA = 2;
	
	/**
	 * Array of availables drivers for factory method.
	 * Keys:
	 * 
	 *	id					Parser ID
	 *  name				Name
	 *  class				Class name in phone invoice parsers folder
	 *
	 * @var array
	 */
	private static $PARSERS = array
	(
		'vodafone_onenet_xml' => array
		(
			'id'		=> 'vodafone_onenet_xml',
			'name'		=> 'Vodafone OneNet XML',
			'class'		=> 'Vodafone_Onenet_Xml',
			'input'		=> self::TYPE_UPLOAD
		),
		'vodafone_onenet_xls_csv_ge_8_2012' => array
		(
			'id'		=> 'vodafone_onenet_xls_csv_ge_8_2012',
			'name'		=> 'Vodafone OneNet XLS > CSV, >= 8.2012',
			'class'		=> 'Vodafone_Onenet_Csv_Ge_8_2012',
			'input'		=> self::TYPE_UPLOAD,
			'files'		=> 3
		),
		'vodafone_onenet_xls_csv_le_7_2012' => array
		(
			'id'		=> 'vodafone_onenet_xls_csv_le_7_2012',
			'name'		=> 'Vodafone OneNet XLS > CSV, <= 7.2012',
			'class'		=> 'Vodafone_Onenet_Csv_Le_7_2012',
			'input'		=> self::TYPE_UPLOAD,
			'files'		=> 2
		),
		'vodafone_ge_9_2011' => array
		(
			'id'		=> 'vodafone_ge_9_2011',
			'name'		=> 'Vodafone, >= 09.2011',
			'class'		=> 'Parser_Phone_Invoice_Vodafone2',
			'input'		=> self::TYPE_TEXTAREA
		),
		'vodafone_le_8_2011' => array
		(
			'id'		=> 'vodafone_le_8_2011',
			'name'		=> 'Vodafone, <= 08.2011',
			'class'		=> 'Parser_Phone_Invoice_Vodafone',
			'input'		=> self::TYPE_TEXTAREA
		),
	);

	/**
	 * Factory for parsers
	 *
	 * @param mixed $parser			String index of parser or integer ID of driver
	 * @return Parser_Phone_Invoice	Parser instance or NULL
	 *									if driver name or ID is incorect.
	 */
	public static function factory($parser = NULL)
	{
		if ($parser)
		{
			$selected_parser = self::_get_parser_index($parser);
			
			if ($selected_parser)
			{
				$parser = self::$PARSERS[$selected_parser];
				$class_name = $parser['class'];
				$class_path = dirname(__FILE__) . '/' . self::DIR
						. '/' . $class_name . '.php';

				require_once $class_path;
				return new $class_name;
			}
		}
			
		return NULL;
	}
	
	/**
	 * Gets index of driver
	 *
	 * @param mixed $parser	String index of driver or integer ID of driver.
	 * @return mixed		String key on success FALSE on error.
	 */
	private static function _get_parser_index($parser)
	{
		if (array_key_exists($parser, self::$PARSERS))
		{
			return $parser;
		}
		else
		{
			foreach (self::$PARSERS as $key => $available_parser)
			{
				if ($available_parser['id'] == $parser)
				{
					return $key;
				}
			}
		}
		
		return FALSE;
	}
	
	/**
	 * Gets parser input types
	 * 
	 * @return type
	 */
	public static function get_parser_input_types()
	{
		$options = array();
		
		foreach (self::$PARSERS as $d)
		{
			$options[$d['id']] = $d['input'];
		}
		
		return $options;
	}
	
	/**
	 * Gets parser upload files num
	 * 
	 * @return array
	 */
	public static function get_parser_upload_files()
	{
		$files = array();
		
		foreach (self::$PARSERS as $d)
		{
			if (isset($d['files']))
			{
				$f = $d['files'];
			}
			else if ($d['input'] == self::TYPE_UPLOAD)
			{
				$f = 1;
			}
			else
			{
				$f = 0;
			}
			
			$files[$d['id']] = $f;
		}
		
		return $files;
	}
	
	/**
	 * Gets parsers array for dropdown
	 * 
	 * @return array
	 */
	public static function get_parsers_for_dropdown()
	{
		$options = array();
		
		foreach (self::$PARSERS as $d)
		{
			$options[$d['id']] = __($d['name']);
		}
		
		return $options;
	}
		
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
