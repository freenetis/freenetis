<?php defined('SYSPATH') or die('No direct access allowed.');
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

$lang = array
(
	// Class errors
	'error_format'  => 'Your error message string must contain the string {message} .',
	'invalid_rule'  => 'Invalid validation rule used: %s',

	// General errors
	'unknown_error' => 'Unknown validation error while validating the %s field.',
	'required'      => 'Pole %s musí být vyplněno.',
	'min_length'    => 'Pole %s musí obsahovat min. %d znaků.',
	'min_value'     => 'Pole %s musí mít hodnotu minimálně %d.',
	'max_length'    => 'Pole %s musí obsahovat max. %d znaků.',
	'max_value'     => 'Pole %s musí mít hodnotu maximálně %d.',
	'exact_length'  => 'Pole %s musí obsahovat přesně %d znaků.',
	'in_array'      => 'Pole %s musí být vybráno ze seznamu.',
	'matches'       => 'Pole %s se musí shodovat s polem %s.',
	'valid_url'     => 'Pole %s musí obsahovat platnou URL.',
	'valid_email'   => 'Pole %s musí obsahovat platnou emailovou adresu.',
	'valid_emails'  => 'Pole %s musí obsahovat platné emailové adresy oddělené čárkou.',
	'valid_ip'      => 'Pole %s musí obsahovat platnou IP adresu.',
	'valid_type'    => 'Pole %s musí obsahovat pouze %s.',
	'range'         => 'Pole %s musí být v definovaném rozsahu.',
	'regex'         => 'The %s field does not match accepted input.',
	'depends_on'    => 'The %s field depends on the %s field.',

	// Upload errors
	'user_aborted'  => 'The %s file was aborted during upload.',
	'invalid_type'  => 'Soubor nemá povolený typ souboru.',
	'max_size'      => 'The %s file you uploaded was too large. The maximum size allowed is %s.',
	'max_width'     => 'The %s file you uploaded was too big. The maximum allowed width is %spx.',
	'max_height'    => 'The %s file you uploaded was too big. The maximum allowed height is %spx.',
	'min_width'     => 'The %s file you uploaded was too small. The minimum allowed width is %spx.',
	'min_height'    => 'The %s file you uploaded was too small. The minimum allowed height is %spx.',

	// Field types
	'alpha'         => 'písmena abecedy',
	'alpha_dash'    => 'alphabetical, dash, and underscore',
	'date_interval'	=> 'Datum není v platném intervalu',
	'digit'         => 'číslice',
	'numeric'       => 'číslo',
	'gps'			=> 'Chybný formát GPS souřadnice',
	'preg_regex'	=> 'Chybný formát regulárního výrazu',

	'phone'   => 'číslo ve středoevropském tvaru',
	'suffix' => 'příponu',

	'valid_suffix' => 'Přípona musí začínat lomítkem a musí končit lomítkem.',
);
