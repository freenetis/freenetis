<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * The translation of database items or enum types
 * 
 * @author Michal Kliment, Tomas Dulik
 * @package Model
 * 
 * @property integer $id
 * @property string $original_term
 * @property string $translated_term
 * @property string $lang
 */
class Translation_Model extends ORM
{
	/**
	 * Get translation for term
	 * @param string $term
	 * @return string
	 */
	public function get_translation($term)
	{
		$result = $this
				->where('original_term', $term)
				->where('lang', Config::get('lang'))
				->find();
		
		if ($result && !empty($result->translated_term))
			return $result->translated_term;
		else
			return $term;

	}

}
			