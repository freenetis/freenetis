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
 * DBase column
 *
 * @author Jan Dubina
 *
 */
class Dbase_Column {
	//DBase field types
	const DBFFIELD_TYPE_CHAR = 'C';
	const DBFFIELD_TYPE_NUMERIC = 'N';
	const DBFFIELD_TYPE_FLOATING = 'F';
	const DBFFIELD_TYPE_DATE = 'D';
	const DBFFIELD_TYPE_LOGICAL = 'L';
	
	//Maximum field name length
	const DBFIELD_MAX_NAME_LENGTH = 10;
	
	protected $name;
	protected $type;
	protected $length;
	protected $precision;
	
	protected $trans = array(
							'ä'=>'a',
							'Ä'=>'A',
							'á'=>'a',
							'Á'=>'A',
							'à'=>'a',
							'À'=>'A',
							'ã'=>'a',
							'Ã'=>'A',
							'â'=>'a',
							'Â'=>'A',
							'č'=>'c',
							'Č'=>'C',
							'ć'=>'c',
							'Ć'=>'C',
							'ď'=>'d',
							'Ď'=>'D',
							'ě'=>'e',
							'Ě'=>'E',
							'é'=>'e',
							'É'=>'E',
							'ë'=>'e',
							'Ë'=>'E',
							'è'=>'e',
							'È'=>'E',
							'ê'=>'e',
							'Ê'=>'E',
							'í'=>'i',
							'Í'=>'I',
							'ï'=>'i',
							'Ï'=>'I',
							'ì'=>'i',
							'Ì'=>'I',
							'î'=>'i',
							'Î'=>'I',
							'ľ'=>'l',
							'Ľ'=>'L',
							'ĺ'=>'l',
							'Ĺ'=>'L',
							'ń'=>'n',
							'Ń'=>'N',
							'ň'=>'n',
							'Ň'=>'N',
							'ñ'=>'n',
							'Ñ'=>'N',
							'ó'=>'o',
							'Ó'=>'O',
							'ö'=>'o',
							'Ö'=>'O',
							'ô'=>'o',
							'Ô'=>'O',
							'ò'=>'o',
							'Ò'=>'O',
							'õ'=>'o',
							'Õ'=>'O',
							'ő'=>'o',
							'Ő'=>'O',
							'ř'=>'r',
							'Ř'=>'R',
							'ŕ'=>'r',
							'Ŕ'=>'R',
							'š'=>'s',
							'Š'=>'S',
							'ś'=>'s',
							'Ś'=>'S',
							'ť'=>'t',
							'Ť'=>'T',
							'ú'=>'u',
							'Ú'=>'U',
							'ů'=>'u',
							'Ů'=>'U',
							'ü'=>'u',
							'Ü'=>'U',
							'ù'=>'u',
							'Ù'=>'U',
							'ũ'=>'u',
							'Ũ'=>'U',
							'û'=>'u',
							'Û'=>'U',
							'ý'=>'y',
							'Ý'=>'Y',
							'ž'=>'z',
							'Ž'=>'Z',
							'ź'=>'z',
							'Ź'=>'Z'
	);

	
	function __construct($name, $type, $length = 1, $precision = 0) {
		if ($this->check_name($name))
			$this->name = $name;
		else
			throw new Exception('Invalid column name.');
		
		$this->type = $type;
		$this->precision = $precision;
		$this->length = $length;
	}
	
	function get_length() {
		switch ($this->type) {
			//Boolean - length 1B
			case self::DBFFIELD_TYPE_LOGICAL:
				return 1;
			//Date - length 8B (8 digits, YYYYMMDD format)
			case self::DBFFIELD_TYPE_DATE:
				return 8;
			default:
				return $this->length;
		}
	}
	
	function get_name() {
		return $this->name;
	}
	
	function get_name_padded() {
		return str_pad(iconv(Dbase_Table::ENC_UTF, Dbase_Table::ENC_CP852, $this->name), 
									self::DBFIELD_MAX_NAME_LENGTH + 1, chr(0));
	}
	
	function get_type() {
		return $this->type;
	}
	
	function get_precision() {
		return $this->precision;
	}
	
	protected function check_name($name) {
		$name2 = iconv(Dbase_Table::ENC_UTF, Dbase_Table::ENC_CP852, $name);

		if (strlen($name2) > self::DBFIELD_MAX_NAME_LENGTH)
			return false;
		
		$name = strtr($name, $this->trans);

		return preg_match ("/^[a-zA-Z0-9_]+$/", $name);
	}
}
?>
