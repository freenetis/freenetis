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
 * Helper for modules that contains method for getting the state of a module, 
 * the availibility of a module, etc.
 * 
 * @author  David Raška
 * @package Helper
 */
class module
{
	/**
	 * Check if the module is enabled or not.
	 * 
	 * @author Ondrej Fibich
	 * @param string $module_name
	 * @return boolean
	 * @throws On unknown module
	 */
	public static function e($module_name)
	{
		return Settings_Controller::isModuleEnabled($module_name);
	}
	
	/*
	 * Function generates state icons for module
	 * 
	 * @param $module
	 * @return HTML code
	 */
	public static function get_state($module = null, $add_text = false)
	{
		if ($module)
		{
			$module_config = $module . '_state';
			
			$last_time_text = Settings::get($module_config);

			$last_time_int = strtotime($last_time_text);
			
			$diff_text = date::timespan($last_time_int, FALSE, 'days,hours,minutes,seconds');
			
			$diff_int = time() - $last_time_int;

			$diff_int = $diff_int / 60;
			
			$timeout = Settings::get('module_status_timeout');
			
			//inactive when time_diff > module_status_timeout
			if ($diff_int < $timeout)
			{
				$result = html::image(array
				(
					'src'	=> 'media/images/states/active.png',
					'style' => 'margin-right: 0.5em',
					'title'	=> __('active')
				));
				$text = ' - '.__('last actualization at');
			}
			else
			{
				$since = '';
				
				if (!empty($last_time_text))
				{
					$since = ' '.  strtolower(__('since')).' '.$last_time_text;
				}
				else
				{
					$add_text = FALSE;
				}
				
				$result = html::image(array
				(
					'src'	=> 'media/images/states/inactive.png',
					'style' => 'margin-right: 0.5em',
					'title'	=> __('inactive').$since
				));
				$text = ' - '.__('last active at');
			}
			
			if ($add_text)
			{
				$hours = $diff_text['hours'];
				$minutes = $diff_text['minutes'];
				$seconds = $diff_text['seconds'];
				$hours = (strlen($hours) == 1) ? '0'.$hours : $hours;
				$minutes = (strlen($minutes) == 1) ? '0'.$minutes : $minutes;
				$seconds = (strlen($seconds) == 1) ? '0'.$seconds : $seconds;
				
				$text_diff = $diff_text['days'].'d - '.$hours.'h '.$minutes.'m '.$seconds.'s';
				
				$result .= __(str_replace('_', ' ', $module)).' '.$text.' '
						.$last_time_text.' ('.$text_diff.')';
			}
			
			return $result;
		}
		
		return '';
	}
}
