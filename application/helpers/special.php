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
 * Class: url_lang
 * URL language helper class.
 * 
 * @package Helper
 */
class special
{
	
	/**
	 * Designed forge elements whitch are marked as required
	 *
	 * Parameters:
	 * @param Forge $form 	   		form forge object
	 * @param string $post_label	string to paste to end of label of element
	 * @param string $class  		name of class to set on element
	 *
	 * @return boolean
	 */
	public static function required_forge_style($form, $post_label = '', $class = '')
	{
		if (isset($form))
		{
			$form_array = $form->as_array();
			foreach ($form_array as $key => $value)
			{
				if (isset($form->$key) && in_array('required', $form->$key->rules()))
				{
					if ($class != '')
					{
						if ($form->$key->class != '')
							$old_class = $form->$key->class . ' ';
						else
							$old_class = '';

						$form->$key->class($old_class . $class);
					}
					if ($post_label != '')
					{
						$old_label = $form->$key->label;
						unset($form->$key->label);
						$form->$key->label($old_label . $post_label);
						unset($old_label);
					}
				}
			}
			unset($form_array);
			return true;
		}
		else
			return false;
	}
	
	/**
	 * Created language flags for multilaguage versions.
	 *
	 * Parameters:
	 * @param array $flags_array 	array of flags
	 * @param string $extension    	images format
	 *
	 * @return boolean
	 */
	public static function create_language_flags($flags_array, $extension = 'jpg')
	{
		$index_page = (Settings::get('index_page')) ? 'index.php/' : '';
		if (is_array($flags_array))
		{
			$return = '';
			foreach ($flags_array as $ind => $val)
			{
				$return .= ' '.html::anchor(
						url::base().$index_page.$ind.'/'.url_lang::current(),
						html::image(array
						(
							'src'	=> 'media/images/icons/flags/'.$ind.'.'.$extension,
							'alt'	=> $ind,
							'title'	=> $val)
						)
				);
			}
			return $return;
		}
		else return false;
	}

	/**
	 * Creates order by link
	 *
	 * @param type $new_order_by			Order by field
	 * @param type $order_by				Order by field
	 * @param type $order_by_direction		Order by direction
	 * @param type $record_per_page			Records per page
	 * @param int $url_array_ofset			URL array offset
	 * @return string						HTML link
	 */
	public static function create_order_by_link(
			$new_order_by = 'users.id', $order_by = 'users.id',
			$order_by_direction = 'ASC', $record_per_page = NULL,
			$url_array_ofset = 0)
	{
		$url_array = explode('/', trim(url::current(), '/'));
		if (count($url_array) < 7) 
		{
			$url_array[3] = 50;
			$url_array[6] = 'page';
			$url_array[7] = 1;
			$url_array_ofset = 0;

		}
		
		if (isset($record_per_page))
			$url_array[3] = (int) $record_per_page;

		$pre_url = $url_array[1 + $url_array_ofset] .'/'.
				$url_array[2+$url_array_ofset].'/'.
				$url_array[3+$url_array_ofset].'/';
		
		if ($new_order_by == $order_by)
		{
			$order_by_direction = $order_by_direction == 'ASC' ? 'DESC' : 'ASC';
		}
		
		return url_lang::base().$pre_url.$new_order_by.'/'.
				$order_by_direction.'/'.$url_array[6+$url_array_ofset].'/'.
				$url_array[7+$url_array_ofset];
	}

	/**
	 * Helper to generate rgb code from numbers
	 * 
	 * @author Michal Kliment
	 * @param int $r
	 * @param int $g
	 * @param int $b
	 * @return string
	 */
	public static function RGB($r, $g, $b)
	{
	    $r = dechex($r);
	    $g = dechex($g);
	    $b = dechex($b);

	    if (strlen($r) < 2)
		{
			$r = '0'.$r;
		}
	    elseif (strlen($r) > 2)
		{
			$r = 'ff';
		}
		
	    if (strlen($g) < 2)
		{
			$g = '0'.$g;
		}
	    elseif (strlen($g) > 2)
		{
			$g = 'ff';
		}
		
	    if (strlen($b) < 2)
		{
			$b = '0'.$b;
		}
	    elseif (strlen($b) > 2)
		{
			$b = 'ff';
		}

	    return $r.$g.$b;
	}
}