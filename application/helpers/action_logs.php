<?php defined('SYSPATH') OR die('No direct access allowed.');
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
 * Action logs helper class.
 *
 * @author David Raska
 */
class action_logs {

	/**
	 * Helper for showing last modification date and details of object
	 * 
	 * @param	ORM object
	 * @param	int		object ID
	 * @return	string
	 */
	public static function object_last_modif($orm, $object_id)
	{
		// Action logs enabled?
		if (!Settings::get('action_logs_active') || !is_object($orm))
		{
			return '';
		}
		
		$table = $orm->get_table_name();
		
		$log_model = new Log_Model();
		
		if ($log_model->count_all_object_logs($table, $object_id) == 0)
		{
			//no modification in last 30 days
			return	html::image(array
					(
						'src' => 'media/images/icons/action_logs_none.png'
					)).
					'&nbsp'.__('No changes in last 30 days');
		}
		
		// get last modification time of object
		$time = $log_model->get_object_last_modification($table, $object_id);
		
		if (!Controller::instance()->acl_check_view('Logs_Controller', 'logs'))
		{
			// only last modification date
			$html = html::image(array
					(
						'src' => 'media/images/icons/action_logs.png',
						'title' => __('Last changed date')
					)).
					'&nbsp<span title="'.__('Last changed date').'">'.$time.'</span>';
		}
		else
		{
			// link to details
			$html = html::anchor('logs/show_object/'.$table.'/'.$object_id,
					html::image(array
					(
						'src' => 'media/images/icons/action_logs.png',
						'title' => __('Show object action logs')
					))).
					'&nbsp<span title="'.__('Last changed date').'">'.$time.'</span>';
		}
		
		return $html;
	}

}