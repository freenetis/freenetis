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
 * Parser_Html_Table is ABSTRACT class containing methods useful
 * for parsing HTML tables in generic HTML files.
 *
 * Motivation: we want to parse HTML tables to get interesting data from various web sites.
 * The HTML code of the tables often does not conforms to XML/XHTML rules.
 * It often does not conform even HTML4, e.g. - the table row is not closed by </tr>,
 * table cell is not closed by </td> etc.
 * Therefore, XML parsers can't be used for this.
 * The Tidy extension is not available on all hostings.
 * If you think about parsing a non-XHTML non-HTML4.0 table, look at this class.
 * The methods have been optimized to give maximum possible performance
 * and memory efficiency.
 * For an example how to use this class, see the Parser_Ebanka class
 * 
 * @author Tomas <Dulik at unart dot cz>
 * @version 1.0
 */
abstract class Parser_Html_Table
{
	const TIMEOUT = 3;

	/**
	 * File descriptor
	 *
	 * @var resource
	 */
	protected $file;
	
	/**
	 * Charset
	 *
	 * @var string
	 */
	protected $charset;
	
	/**
	 * Buffer
	 *
	 * @var string
	 */
	protected $buffer;
	
	/**
	 * The position of the last End Of Line in the buffer
	 * 
	 * @var integer 
	 */
	protected $eoln_pos; 
	
	/**
	 * Var for transfering matched items from preg_match
	 *
	 * @var array
	 */
	protected $matches;
	
	/**
	 * Table end indicator
	 *
	 * @var bool
	 */
	protected $table_end = false;

	/**
	 * Opens URL
	 *
	 * @param string $url 
	 */
	public function open($url)
	{
		if ($url != "")
		{
			$old = ini_set('default_socket_timeout', self::TIMEOUT);
			
			if (($this->file = fopen($url, "rb")) === false)
				die("Can not open file! Check if $url exists!");
			
			ini_set('default_socket_timeout', $old);
			stream_set_timeout($this->file, self::TIMEOUT);
			//stream_set_blocking($this->file, 0);
		}
	}

	/**
	 * get_line appends **AT LEAST** one line from the $file into the $buffer.
	 *
	 *
	 * @return boolean
	 * @uses buffer, eoln_pos;
	 * In PHP4, this is MUCH faster than using fgets because of a PHP bug.
	 * In PHP5, this is usualy still faster than the following version based on fgets:
	 *
	 * protected function get_line_fgets() {
	 * 	if (!feof($this->file))
	 * 		$this->buffer .= fgets($this->file);
	 * 	else return false;
	 * 	$this->eoln_pos=strlen($this->buffer);
	 * 	return true;
	 * }
	 *
	 * Note for HTML files with super long lines (hundreds of kbytes without single
	 * EOLN) the fgets would be useless - it'd take a lot of memory to read a single line!
	 * For such files, you should modify the code of my function this way:
	 * Replace
	 * 	...eoln_pos=strripos($this->buffer,"\n"))
	 * by something like
	 * 	...eoln_pos=find_row_end()
	 */
	public function get_line()
	{
		while (!feof($this->file))
		{
			// read 8192 bytes from file or one packet
			$new_part = fread($this->file, 8192);
			$this->buffer .= $new_part;
			
			// search eoln from end: found ?
			if (($this->eoln_pos = strripos($this->buffer, "\n")) !== false)
			{
				// eoln found! done, OK...
				return true;
			}
		}
		
		// EOF happened ?
		if (!isset($new_part))
		{
			// EOF right when the function begun? Return EOF!
			return false;
		}
		
		// EOF happened but no EOLN
		$this->eoln_pos = strlen($this->buffer);  // set eoln_pos to EOF...
		
		return true;
	}

	/**
	 * find_tag_and_trim($tag) tries to find the tag in the $this->buffer
	 * and trim the beginning of the buffer till (and including) the $tag
	 * returns false if string not found.
	 * returns true if string found, and the variable $this->buffer contains
	 * string trimmed from the first occurence of $tag
	 */
	protected function find_tag_and_trim($tag)
	{
		$found = false;
		do
		{
			// can you find the tag ?
			if (($pos = stripos($this->buffer, $tag)) !== false)
			{
				$found = true;
				// set the cut $pos(ition) behind $tag
				$pos += strlen($tag);
				// now cut away everything from the beginning till the cut position
				$this->buffer = substr($this->buffer, $pos);
				// and update the counters
				$this->eoln_pos -= $pos;
			}
			// tag not found and eoln found previously?
			else if ($this->eoln_pos > 0)
			{
				// cut away all from beginning till eoln
				$this->buffer = substr($this->buffer, $this->eoln_pos);
				// so we don't have to deal with these lines again
				$this->eoln_pos = 0;
			}
		}
		while (!$found && $this->get_line());

		return $found;
	}

	/**
	 * The same as previous function, but for multiple tags search.
	 * If tag is found, returns the tag index in the $tags array.
	 * If tag is not found, returns number of $tags+1
	 * 
	 * @param array $tags
	 * @return integer
	 */
	protected function find_tags_and_trim($tags)
	{
		$found = false;
		do
		{
			$i = 0;
			// for all the tags do:
			foreach ($tags as $tag)
			{
				// can you find the startag ?
				if (($pos = stripos($this->buffer, $tag)) !== false)
				{
					$found = true;
					// set the cut $pos(ition) behind $tag
					$pos+=strlen($tag);
					// now cut away everything from the beginning till the cut position
					$this->buffer = substr($this->buffer, $pos);
					// and update the counters
					$this->eoln_pos -= $pos;
					break;
				// this tag not found - increment cntr and try another one
				}
				else
				{
					$i++;	 
				}
			}
			
			// tags not found and eoln found previously?
			if (!$found && $this->eoln_pos > 0)
			{
				// cut away all from beginning till eoln
				$this->buffer = substr($this->buffer, $this->eoln_pos);
				$this->eoln_pos = 0;
			}
		}
		while (!$found && $this->get_line());
		
		return $i;
	}

	/**
	 * this functions tries to find the end of table row.
	 * It can handle even rows terminated incorrectly by
	 * </table> instead of </tr>
	 * 
	 * @return integer	The position of the end row tag (</tr> or </table>)
	 *					or false if the tag is not found.
	 */
	protected function find_row_end()
	{
		/**
		 * PHP5 version: in PHP5, strripos can search whole string,
		 * not only 1 char as in PHP4
		 */
		if (($res = stripos($this->buffer, "<table")) !== false ||
			($res = stripos($this->buffer, "</table")) !== false)
		{
			$this->table_end = true;
			return $res;
		}
		
		if (($res = strripos($this->buffer, "</tr")) !== false)
			return $res;
		
		return strripos($this->buffer, "<tr");
		
		/**
		 * PHP4 version: we have to use perl regular expressions...
		 * This is only 0.03sec/100kB slower than PHP5 strripos version
		 *
		
		  $matchCnt=preg_match("/<[\/]?(?:tr|table)(?!.*<[\/]?(tr|table))/si",$this->buffer, $matches, PREG_OFFSET_CAPTURE);
		  if ($matchCnt==1) return $matches[0][1];
		  else return false;
		 */
	}

	/**
	 * get_table_rows tries to fill the buffer with at least one table row (<tr>...<[/]tr>) string.
	 * It then parses the rows using a regular expression, which returns the content of the
	 * table cells in the $this->matches array
	 * Because fread reads whole blocks, it is possible this
	 * 
	 * @return bool
	 */
	protected function get_table_rows()
	{
		// Try to find the starting <tr> tag:
		if (!$this->find_tag_and_trim("<tr"))
			return false;

		// now try to find the last <[/]tr> or <[/]table> tag by searching these
		// tags not followed by the same tags, if not successfull, read the
		// next line of the file. Do it until EOF or table end
		
		while (($lastTagPos = $this->find_row_end()) === false &&
				$this->table_end == false &&
				$this->get_line());

		// if <tr> not found untill EOF, return EOF
		if ($lastTagPos === false)
			return false;

		// $rows is string containing several <tr>...<tr>... ended by <tr>
		$rows = substr($this->buffer, 0, $lastTagPos);
		
		// if HTML charset is not UTF-8
		if (strcasecmp($this->charset, "utf-8") != 0)
		{
			// convert it to UTF-8
			$rows = iconv($this->charset, "UTF-8", $rows);
		}
		
		// Now: get the contents of all the table cells (the texts between
		// <td > and <td > or </td> or <tr> or </tr> tags
		preg_match_all("/<td[^>]*>(?:<[^>]*>)?(.*?)<(?:(?:\/)?td|tr|table)/si", $rows, $this->matches);
		
		$this->buffer = substr($this->buffer, $lastTagPos);
		
		if ($this->eoln_pos > $lastTagPos)
		{
			$this->eoln_pos -= $lastTagPos;
		}
		else
		{
			$this->eoln_pos = 0;
		}
		
		return true;
	}

	/**
	 * Sets charset
	 */
	protected function get_charset()
	{
		// if charset is missng set utf8
		if (!$this->find_tag_and_trim("charset="))
		{
			$this->charset = "utf-8";
		}
		else
		{
			// try to find " 
			if (($quotesPos = strpos($this->buffer, '"')) === false)
			{
				if (($quotesPos = strpos($this->buffer, "'")) === false)
				{
					die("Can't find the quotes after 'charset=...'");
				}
			}
			$this->charset = substr($this->buffer, 0, $quotesPos);
		}
	}

	/**
	 * Parse method
	 * 
	 * @param string $url
	 */
	abstract function parse($url);
}
