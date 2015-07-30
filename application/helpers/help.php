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
 * Helper used for user help hint, which are displayed in system.
 *
 * @package    Helper
 * @author     Jiri Svitak
 */
class help
{
	/**
	 * Shows help circle with hint message. Useful for fields in forms and tables.
	 * 
	 * @author Jiri Svitak
	 * @param string	$message	message key, used in translation array in i18n file help.php
	 * @param array		$args		arguments to translation
	 * @return string				HTML
	 */
	public static function hint($message, $args = array())
	{
		$help_text = url_lang::lang('help.'.$message, $args);
		
		return html::image(array
		(
			'src'	=> 'media/images/icons/help_small.png',
			'alt'	=> $help_text,
			'title'	=> $help_text,
			'class'	=> 'help_hint'
		));
	}

}
