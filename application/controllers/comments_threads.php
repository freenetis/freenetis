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
 * Handles relation between comment and other table in database, which has to
 * contains column comments_thread_id.
 * 
 * @see Comments_Controller
 * @author Michal Kliment
 * @package	Controller
 */
class Comments_threads_Controller extends Controller
{
	/**
	 * Adds new comment thread to type and foreign key
	 *
	 * @author Michal Kliment
	 * @param string $type
	 * @param integer $fk_id
	 */
	public function add($type = NULL, $fk_id = NULL)
	{
		// bad parameter
		if (!$type || !$fk_id || !is_numeric($fk_id))
			Controller::warning(PARAMETER);

		// creates model name
		$model = ucfirst($type) . '_Model';

		// this model doesn't exist
		if (!class_exists($model))
			Controller::error(RECORD);

		$object = new $model($fk_id);

		// record doesn't exist or doesn't support comment thread
		if (!$object->id || !$object->property_exists('comments_thread_id'))
			Controller::error(RECORD);

		// comment thread doesn't exist
		if (!$object->comments_thread_id)
		{
			$comments_thread = new Comments_thread_Model();
			$comments_thread->type = $type;
			$comments_thread->save();

			$object->comments_thread_id = $comments_thread->id;
			$object->save();
		}

		url::redirect('comments/add/' . $object->comments_thread_id);
	}

}
