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
 * DBase table
 *
 * @author Jan Dubina
 *
 */
class Dbase_Table {
	const ENC_UTF = 'UTF-8';
	const ENC_CP852 = 'CP852';

	protected $file;
	protected $filesize;
	protected $filename;
	protected $columns;
	protected $records;
	protected $num_rows;			//number of records
	protected $b_header;			//number of bytes in header
	protected $b_record;			//number of bytes in record
	protected $version;				//Dbase version

	protected $last_update;			//date of last update
	protected $mdx_flag;			//MDX file exists
	protected $lang_id;				//language driver id
	protected $num_col;				//number of columns
	
	function create_table($columns, $records) {
		$this->columns = array();
		if ($columns && is_array($columns) && 
				$records && is_array($records))
			foreach ($columns as $column) {
				if ($column && is_array($column) && count($column)>1)
					$this->columns[] = new Dbase_Column($column[0],
														$column[1],
														isset($column[2]) ? $column[2] : 0,
														isset($column[3]) ? $column[3] : 0
														);
				else
					throw new Exception('Wrong arguments.');
			}
		else
			throw new Exception('Wrong arguments.');

		//flag preceding every record
		$this->b_record = 1;
		foreach ($this->columns as $column)
			$this->b_record += $column->get_length();

		$this->file = fopen('php://temp', 'w');
		$this->records = $records;
		$this->version = 3;
		
		$this->num_rows = count($records);
		
		//33B dbf header length + field terminator, 32B field descriptor length
		$this->b_header = 33 + 32 * count($columns);	

		$this->create_header();
		$this->write_records();
		$contents = $this->get_file_contents();
		fclose($this->file);
		return $contents;
	}
	
	function read_table($filename) {
		$this->file = fopen($filename, 'r');
		if (!$this->file)
			throw new Exception('Cannot open file.');
		
		$this->filesize = filesize($filename);
		$this->filename = $filename;
		
		$this->read_header();
		$records = $this->read_records();
		
		fclose($this->file);
		
		return $records;
	}
	
	protected function read_header() {
		$this->version = $this->read_char();			//version
		$this->last_update = $this->read_date();		//date of last update
		$this->num_rows = $this->read_int();			//number of records
		$this->b_header = $this->read_short();			//number of bytes in header
		
		//filesize is smaller than header size
		if ($this->filesize < $this->b_header)
			throw new Exception('Wrong file format.');
		
		$this->b_record = $this->read_short();			//number of bytes in record
		$this->read_bytes(2);							//reserved
		
		//encryption flag + flag indicating incomplete transaction
		if ($this->read_char() != 0 || $this->read_char() != 0)
			throw new Exception('Wrong file format.');
		
		$this->read_bytes(12);							//reserved
		$this->mdx_flag = $this->read_bytes();			//mdx flag
		$this->lang_id = $this->read_bytes();			//language driver id
		$this->read_bytes(2);							//reserver
		
		$dbf_size = $this->b_header + $this->b_record * $this->num_rows + 1;
		
		//checks if filesize is correct
		if ($this->filesize != $dbf_size)
			throw new Exception('Wrong file format.');
		
		$this->num_col = ($this->b_header - 33) / 32;

		//reads field descriptor bytes
		$this->columns = array();
		for ($i = 0; $i < $this->num_col; $i++) {
			$name = $this->read_column_name();
			$this->read_bytes();
			$type = $this->read_bytes();
			$this->read_bytes(4);
			$length = $this->read_char();
			$precision = $this->read_char();
			$this->read_bytes(14);
			
			$column = new Dbase_Column($name, $type, $length, $precision);
			$this->columns[] = $column;
		}
		
		//field terminator 0Dh
		if ($this->read_char() != 13)
			throw new Exception('Wrong file format.');
	}

	protected function create_header() {
		$this->write_char($this->version);		//version
		$this->write_date(time());				//last update
		$this->write_int($this->num_rows);		//number of rows
		$this->write_short($this->b_header);	//number of bytes in header
		$this->write_short($this->b_record);	//number of bytes in record		
		$this->write_n_bytes(chr(0), 2);		//reserved
		$this->write_char(0);					//indication of incomplete trasaction
		$this->write_char(0);					//encryption flag;
		$this->write_n_bytes(chr(0), 12);		//reserved for multi-user processing	
		$this->write_char(0);					//no MDX file exists
		$this->write_char(0);					//language driver id
		$this->write_n_bytes(chr(0), 2);		//reserved

		$unique = array();
		//field description bytes
		foreach ($this->columns as $column) {
			if (!in_array($column->get_name(), $unique))
				$unique[] = $column->get_name();
			else
				throw new Exception('Column names must be unique.');
			
			$this->write_bytes($column->get_name_padded());		//zero filled column name
			$this->write_bytes($column->get_type());			//type
			$this->write_n_bytes(chr(0), 4);					//reserved
			$this->write_char($column->get_length());			//collumn length in binary
			$this->write_char($column->get_precision());		//decimal count in binary
			$this->write_n_bytes(chr(0), 2);					//reserved
			$this->write_char(0);								//work area ID
			$this->write_n_bytes(chr(0), 10);					//reserved
			$this->write_char(0);								//field not indexed
		}

		$this->write_char(13);									//field terminator 0Dh
	}
	
	protected function read_records() {
		$records = array();
		
		for ($i = 0; $i < $this->num_rows; $i++) {
			$record = array();
			
			//every record starts with space (20h)
			if ($this->read_bytes() != ' ')
				throw new Exception('Wrong file format.');
			
			foreach ($this->columns as $column) {
				$value = null;
				switch ($column->get_type()) {
					case Dbase_Column::DBFFIELD_TYPE_CHAR:
						$value = $this->read_string($column->get_length());
						break;
					case Dbase_Column::DBFFIELD_TYPE_DATE:
						$value = $this->read_date_long();
						break;
					case Dbase_Column::DBFFIELD_TYPE_LOGICAL:
						$bool = strtolower($this->read_bytes());
						
						if ($bool == 1 || $bool == 't' || $bool == 'y')
							$value = true;
						elseif ($bool == 0 || $bool == 'f' || $bool == 'n')
							$value = false;
						break;
					case Dbase_Column::DBFFIELD_TYPE_NUMERIC:
					case Dbase_Column::DBFFIELD_TYPE_FLOATING:
						$value = floatval($this->read_bytes($column->get_length()));
					default:
						break;
				}
				
				$record[$column->get_name()] = $value;
			}
			$records[] = $record;
		}
		
		//eof 1Ah
		if ($this->read_char() != 26)
			throw new Exception('Wrong file format.');
		
		return $records;
	}
	
	protected function write_records() {
		foreach ($this->records as $record) {
			//space 20h preceding record
			$this->write_char(32);
			foreach ($this->columns as $column) {
				if (isset($record[$column->get_name()]))
					switch ($column->get_type()) {
						case Dbase_Column::DBFFIELD_TYPE_CHAR:
							$this->write_string($record[$column->get_name()], 
												$column->get_length());
							break;
						case Dbase_Column::DBFFIELD_TYPE_DATE:
							$this->write_date_long($this->my_check_date($record[$column->get_name()]) ? 
																		$record[$column->get_name()] :
																		'0000-00-00');
							break;
						case Dbase_Column::DBFFIELD_TYPE_LOGICAL:
							$this->write_bytes($record[$column->get_name()] ? 'T' : 'F');
							break;
						case Dbase_Column::DBFFIELD_TYPE_NUMERIC:
						case Dbase_Column::DBFFIELD_TYPE_FLOATING:
							$this->write_number($record[$column->get_name()], 
												$column->get_length(), 
												$column->get_precision());
							break;
						default:
							break;
					}
				else
					$this->write_n_bytes (chr(32), $column->get_length ());
			}
		}
		//eof 1Ah
		$this->write_char(26);
	}
	
	protected function get_file_contents() {
		rewind($this->file);
		return stream_get_contents($this->file);
	}
	
	protected function my_check_date($date) { 
		@list($y,$m,$d)=explode("-",$date); 
		if (is_numeric($y) && is_numeric($m) && is_numeric($d)) 
		{ 
			return checkdate($m,$d,$y); 
		} 
		return false;
	} 
	
	protected function write_number($number, $length, $precision) {
		$n = str_pad(number_format($number, $precision, '.', ''), $length, ' ', STR_PAD_LEFT);
		return fwrite($this->file, $n);
	}
	
	protected function write_string($string, $length) {
		$s = str_pad(iconv(self::ENC_UTF, self::ENC_CP852, $string), $length, ' ');
		return fwrite($this->file, $s);
	}
	
	protected function write_bytes($b) {
		return fwrite($this->file, $b);
	}
	
	protected function write_n_bytes($b, $n = 1) {
		$r = 0;
		if ($n > 1) {
			for ($i = 0; $i < $n; $i++)
				$r += fwrite($this->file, $b);
			return $r;
		} else
			return false;
	}
	
	protected function write_date($tstamp) {
		$d = getdate($tstamp);
		return $this->write_char($d['year'] % 100) + 
				$this->write_char($d['mon']) + 
				$this->write_char($d['mday']);
	}
	
	protected function write_date_long($date) {
		$tstamp = strtotime($date);
		$date_arr = str_split(date('Ymd',$tstamp));

		$r = 0;
		foreach ($date_arr as $c)
			$r += $this->write_bytes($c);

		return $r;
	}
	
	protected function write_short($short) {
		$b = pack('S', $short);
		return $this->write_bytes($b);
	}
	
	protected function write_int($int) {
		$b = pack('I', $int);
		return $this->write_bytes($b);
	}
	
	protected function write_char($char) {
		$b = pack('C', $char);
		return $this->write_bytes($b);
	}
	
	protected function read_bytes($length = 1) {
		return fread($this->file, $length);
	}
	
	protected function read_char() {
		$char = unpack('C', $this->read_bytes(1));
		return $char[1];
	}

	protected function read_short() {
		$short = unpack('S', $this->read_bytes(2));
		return $short[1];
	}

	protected function read_int() {
		$int = unpack('I', $this->read_bytes(4));
		return $int[1];
	} 
	
	protected function read_string($length) {
		return iconv(Dbase_Table::ENC_CP852, Dbase_Table::ENC_UTF, rtrim($this->read_bytes($length), ' '));
	}
	
	protected function read_column_name() {
		return iconv(Dbase_Table::ENC_CP852, Dbase_Table::ENC_UTF, rtrim($this->read_bytes(Dbase_Column::DBFIELD_MAX_NAME_LENGTH), chr(0)));
	}

	protected function read_date() {
		$y = unpack('C',$this->read_bytes());
		$m = unpack('C',$this->read_bytes());
		$d = unpack('C',$this->read_bytes());
		return date('Y-m-d', mktime(0, 0, 0, $m[1], $d[1], 
									$y[1]>69 ? 1900+$y[1] : 2000+$y[1]));
	}
	
	protected function read_date_long() {
		$y = intval($this->read_bytes(4));
		$m = intval($this->read_bytes(2));
		$d = intval($this->read_bytes(2));
		
		$tstamp = mktime(0,0,0,$m,$d,$y);
		
		if ($tstamp == false)
			$tstamp = 0;
		
		return date('Y-m-d', $tstamp);
	}
}
?>
