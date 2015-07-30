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
 * GPS coordinate helper
 *
 * @author Ondřej Fibich
 * @package Helper
 */
class gps
{
	const REGEX = "/^([0-9]+)°([0-9]+)′([0-9]+(\.[0-9]+)?)″$/";

	private static $STR_REPLACE_SUBJECT = array(',', '"', '\'');
	private static $STR_REPLACE_REPLACEMENT = array('.', '″', '′');

	/**
	 * Render real coordinates to form:
	 * degreesX°minuteX'secondX"N  degreesY°minuteY'secondY"E
	 * 
	 * @param double $coordinateX     Coordinate X to render
	 * @param double $coordinateY     Coordinate Y to render
	 * @param bool $use_html_entity   If it is true °'" are return as HTML entity
	 * @return string                 Rendered coordinates
	 */
	public static function degrees($coordinateX, $coordinateY, $use_html_entity)
	{
		return self::real2degrees($coordinateX, $use_html_entity) . "N, " .
				self::real2degrees($coordinateY, $use_html_entity) . "E";
	}

	/**
	 * Render coordinate from MySQL point __toString()
	 * 
	 * @return string $coordinates    Rendered coordinates
	 * @param  bool $use_html_entity  If it is true °'" are return as HTML entity
	 */
	public static function degrees_from_str($coordinates, $use_html_entity)
	{
		if (mb_eregi("^[0-9]+\.[0-9]+ [0-9]+\.[0-9]+$", $coordinates))
		{
			$coordinates = explode(' ', $coordinates);
			return gps::degrees($coordinates[0], $coordinates[1], $use_html_entity);
		}
		return "";
	}

	/**
	 * Check if degrees coordinate is valid
	 * 
	 * @param string $degrees_coordinate  Coordinate in form: degreesX°minuteX'secondX"
	 * @return bool
	 */
	public static function is_valid_degrees_coordinate($degrees_coordinate)
	{
		$degrees_coordinate = str_replace(
				self::$STR_REPLACE_SUBJECT,
				self::$STR_REPLACE_REPLACEMENT,
				$degrees_coordinate
		);

		return preg_match(self::REGEX, $degrees_coordinate) == 1;
	}

	/**
	 * Render real coordinate to form: degrees°minute'second"
	 * 
	 * @param double $coordinate     Coordinate to render
	 * @param bool $use_html_entity  If it is true °'" are return as HTML entity
	 * @return string                Rendered coordinate
	 */
	public static function real2degrees($coordinate, $use_html_entity)
	{
		$coordinate = doubleval($coordinate);
		$use_html_entity = ($use_html_entity === true);

		if ($coordinate <= 0)
		{
			return "0";
		}

		$degrees = (int) $coordinate;
		$degrees .= ( $use_html_entity ? "&deg;" : "°");

		// round 12 - because of floating point effect (for example 0.1)
		$minutes = (round(($coordinate - (int) $coordinate), 15) * 60);

		$degrees .= (int) $minutes;
		$degrees .= $use_html_entity ? "&prime;" : "′";

		// round 12 - because of floating point effect (for example 0.1)
		$degrees .= round(round(($minutes - (int) $minutes) * 60, 15), 3);
		$degrees .= $use_html_entity ? "&Prime;" : "″";

		return $degrees;
	}

	/**
	 * Transform string coordinate in degrees to real
	 * 
	 * @param string $degrees_coordinate  Coordinate in form: degreesX°minuteX'secondX"
	 * @return double                     Coordinate
	 */
	public static function degrees2real($degrees_coordinate)
	{
		$degrees_coordinate = str_replace(
				self::$STR_REPLACE_SUBJECT,
				self::$STR_REPLACE_REPLACEMENT,
				$degrees_coordinate
		);

		if (preg_match(self::REGEX, $degrees_coordinate, $regs))
		{
			return intval($regs[1]) + intval($regs[2]) / 60 + doubleval($regs[3]) / 3600;
		}

		return -1.0;
	}

}
