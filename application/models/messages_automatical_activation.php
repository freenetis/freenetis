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
 * Auto activation settings of notifiation messages.
 * 
 * @author Onřej Fibich
 * @package Model
 * @property integer $id
 * @property integer $message_id
 * @property Message_Model $message
 * @property integer $type
 * @property mixed $attribute An attribute to settings with type by type field.
 * @property boolean $redirection_enabled
 * @property boolean $email_enabled
 * @property boolean $sms_enabled
 * @property string $send_activation_to_email
 */
class Messages_automatical_activation_Model extends Time_Activity_Rule
{
	
	/**
	 * Gets all setting rules of the given message.
	 * 
	 * @param integer $message_id
	 * @return array[Messages_automatical_activation_Model]
	 */
	public function get_message_settings($message_id)
	{
		return $this->where('message_id', $message_id)->find_all();
	}
	
	/*
	 * @Override
	 */
	public function get_attributes()
	{
		$attrs = explode('/', $this->attribute);
		
		// add missing
		for ($i = count($attrs); $i < self::get_type_attributes_count($this->get_type()); $i++)
		{
			$attrs[] = NULL;
		}
		
		return $attrs;
	}

	/*
	 * @Override
	 */
	public function get_type()
	{
		return $this->type;
	}
	
}