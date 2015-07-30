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
 * Helper for creating bread crumbs menu.
 *
 * @example
 * 
 * Code:
 * 
 * echo breadcrumbs::add()
 *	->link('members', 'Members', access_check_from_controller)
 *	->disable_translation()
 *	->text('Edit')
 *  ->html();
 * 
 * Result with Czech language:
 * 
 *  <a href="http://freenetis.slfree.net/members">Členové</a> » Edit
 * 
 * @author Ondřej Fibich
 * @package Helper
 */
class breadcrumbs
{
    /**
	 * Creates new breadcrumbs and return it
	 * 
	 * @param bool $use_translation	Use traslations for texts?
	 * @return breadcrumbs
	 */
	public static function add($use_translation = true)
	{
		return new breadcrumbs($use_translation);
	}
	
	/**
	 * Array of breadcrumbs items
	 *
	 * @var array
	 */
	private $items;
	
	/**
	 * Idicator for enabling traslations of texts
	 *
	 * @var bool
	 */
	private $use_translation;
	
	/**
	 * Contruct of breadcrumbs
	 * 
	 * @param bool $use_translation	Use traslations for texts?
	 */
	public function __construct($use_translation = true)
	{
		$this->items = array();
		$this->use_translation = ($use_translation === true);
	}
	
	/**
	 * Return text reprezentation of breadcrumbs menu
	 *
	 * @return string
	 */
	public function html()
	{
		return implode(' » ', $this->items);
	}
	
	/**
	 * Auto-render on echo
	 */
	public function __toString()
	{
		return $this->html();
	}
	
	/**
	 * Creates new text item of breadcrumbs menu
	 *
	 * @chainable
	 * @param type $url		URL for link without base
	 * @param type $text	Text for link
	 * @param bool $access	Acces enabled
	 * @return breadcrumbs 
	 */
	public function link($url, $text, $access = true)
	{
		// default
		$html = $text;
		// translaction
		if ($this->use_translation) 
		{
			$html = url_lang::lang('texts.' . $text);
		}
		// access
		if ($access)
		{
			$html = html::anchor(url_lang::base() . $url, $html);
		}
		// add item
		$this->items[] = $html;
		// chain
		return $this;
	}
	
	/**
	 * Creates new text item of breadcrumbs menu
	 *
	 * @chainable
	 * @param string $text	Text of new item
	 * @return breadcrumbs 
	 */
	public function text($text)
	{
		// translaction
		if ($this->use_translation) 
		{
			$text = url_lang::lang('texts.' . $text);
		}
		// item
		$this->items[] = $text;
		// chain
		return $this;
	}
	
	/**
	 * Disable translation
	 *
	 * @chainable
	 * @return breadcrumbs 
	 */
	public function disable_translation()
	{
		// disable
		$this->use_translation = false;
		// chain
		return $this;
	}
	
	/**
	 * Disable translation
	 *
	 * @chainable
	 * @return breadcrumbs 
	 */
	public function enable_translation()
	{
		// enable
		$this->use_translation = true;
		// chain
		return $this;
	}

}
