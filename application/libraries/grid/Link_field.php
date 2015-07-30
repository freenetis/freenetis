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
 * Action link grid field for displaying link with data item.
 * 
 * @author Ondřej Fibich
 * @method Link_Field name(string $name)
 * @method Link_Field data_name(string $name)
 * @method Link_Field script(string $script)
 * @method Link_Field url(string $url)
 */
class Link_Field extends Field
{	
	/**
	 * Name of column 
	 *
	 * @var string
	 */
	public $name = 'id';
	
	/**
	 * Name of data column 
	 *
	 * @var string
	 */
	public $data_name = 'id';
	
	/**
	 * URL to action
	 *
	 * @var string
	 */
	public $url;
	
	/**
	 * Extra script
	 *
	 * @var string
	 */
	public $script = null;
	
	
	/**
	 * Contruct of field, label is set and internacionalized from name
	 *
	 * @param string $name		Name of field
	 */
	public function __construct($name)
	{
		$this->name = $name;
		$this->label = __(utf8::ucfirst(inflector::humanize($name)));
	}
	
	/**
	 * Link of field, label is set and internacionalized from data name
	 *
	 * @param string $url
	 * @param string $data_name	Name of data field, if empty name is set as data name
	 */
	public function link($url, $data_name = NULL)
	{
		if (!text::starts_with($url, url::base()))
		{
			$this->url = url_lang::base() . $url;
		}
		else
		{
			$this->url = $url;
		}
		
		if (empty($data_name))
		{
			$data_name = $this->name;
		}
		
		$this->data_name = $data_name;
		
		if (!empty($data_name))
		{
			$this->label = __(utf8::ucfirst(inflector::humanize($data_name)));
		}
		
		return $this;
	}
	
	/**
	 * Renders field
	 *
	 * @return string
	 */
	public function render()
	{
		return $this->label;
	}
	
}
