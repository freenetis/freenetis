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
 * Comments thread form comments and connecting to any other table
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property integer $id
 * @property string $type
 * @property ORM_Iterator $comments
 */
class Comments_thread_Model extends ORM
{
	protected $has_many = array('comments');
	
	protected $has_one = array
	(
		'account',
		'job',
		'connection_request',
		'log_queue',
		'request'
	);

	/**
	 * Return parent object of comment thread
	 *
	 * @author Michal Kliment
	 * @return ORM object
	 */
	public function get_parent()
	{
		foreach ($this->has_one as $name)
		{
			// returns first match
			if ($this->$name && $this->$name->id)
				return $this->$name;
		}

		// cannot find parent
		return NULL;
	}

}

