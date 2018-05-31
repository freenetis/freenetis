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
 * Helper for conditions used for Action_conditional_field of Grouped_action_field.
 * 
 * Conditions are boolean functions which are called before displaying action,
 * if false is returned from condition, nothing is displayed.
 * Conditions gets two params: first is data row second is name of action field.
 * 
 * @author Ondřej Fibich
 */
class condition
{
	/**
	 * Check if current row is owned by logged user.
	 * Data row has to have user_id column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_own($item)
	{
		return (Session::instance()->get('user_id') == $item->user_id);
	}
	
	/**
	 * Check if current row is locked.
	 * Data row has to have locked column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_locked($item)
	{
		return ($item->locked > 0);
	}
	
	/**
	 * Check if the log queue is uncloded.
	 * 
	 * @param object $item Data row
	 * @return boolean
	 */
	public static function is_log_queue_unclosed($item)
	{
		return ($item->state != Log_queue_Model::STATE_CLOSED);
	}
	
	/**
	 * Check if current row is not locked.
	 * Data row has to have locked column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_not_locked($item)
	{
		return ($item->locked == 0);
	}
	
	/**
	 * Check if current row is locked or filled.
	 * Data row has to have locked and filled column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_locked_or_filled($item)
	{
		return ($item->locked > 0 || $item->filled > 0);
	}
	
	/**
	 * Check if current row is not locked and not filled.
	 * Data row has to have locked and filled column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_not_locked_and_not_filled($item)
	{
		return ($item->locked == 0 && $item->filled == 0);
	}
	
	/**
	 * Check if current row is not read only.
	 * Data row has to have readonly column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_not_readonly($item)
	{
		return (!$item->readonly);
	}
	
	/**
	 * Check if special type id of item is not membership interrupt.
	 * Data row has to have special_type_id column.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function special_type_id_is_not_membership_interrupt($item)
	{
		return ($item->special_type_id != Fee_Model::MEMBERSHIP_INTERRUPT);
	}
	
	/**
	 * Check if current row is default vlan.
	 *
	 * @author David Raska
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_not_default_vlan($item)
	{
		return ($item->port_vlan != Vlan_Model::DEFAULT_VLAN_TAG);
	}
	
	/**
	 * Check if connection is undecided.
	 *
	 * @author Ondřej Fibich
	 * @param object $item	Data row
	 * @return boolean
	 */
	public static function is_connection_request_undecided($item)
	{
		return ($item->state == Connection_request_Model::STATE_UNDECIDED);
	}
	
	/**
	 * Check whether the approval of applicant can be made without submited
	 * registration or applicant has submited the registration.
	 * 
	 * @param object $item Member object
	 * @return boolen
	 */
	public static function is_applicant_registration($item)
	{
		return Settings::get('self_registration_enable_approval_without_registration') ||
				$item->registration;
	}
	
	/**
	 * Is item in the new state?
	 * 
	 * @param object $item
	 * @return boolena
	 */
	public static function is_item_new($item)
	{
		return ($item->state == Vote_Model::STATE_NEW);
	}
	
	/**
	 * Checks whether the given notification message may be activated automatically.
	 * 
	 * @param object $item
	 * @return boolean
	 */
	public static function is_message_automatical_config($item)
	{
		return Message_Model::can_be_activate_automatically($item->type);
	}
	
	/**
	 * Checks if notification message may be activated directly
	 * 
	 * @param object $item
	 * @return boolean
	 */
	public static function is_activatable_directlty($item)
	{
		$message = new Message_Model($item->id);
		
		return Message_Model::can_be_activate_directly($message->type);
	}
	
	/**
	 * Checks whether the given notification message is user message.
	 * 
	 * @param object $item
	 * @return boolean
	 */
	public static function is_message_type_of_user($item)
	{
		return ($item->type == Message_Model::USER_MESSAGE);
	}
	
	/**
	 * Checks whether the given bank account has capability for automatical
	 * sownload of bank statements.
	 * 
	 * @param object $item
	 * @return boolean
	 */
	public static function is_automatical_down_of_statement_available($item)
	{
		try
		{
			$bas = Bank_Account_Settings::factory($item->type);
			$bas->load_column_data($item->settings);
			return $bas->can_download_statements_automatically();
		}
		catch (InvalidArgumentException $e)
		{
			return FALSE;
		}
	}
	
	/**
	 * Checks whether the given bank account has capability for importing of
	 * bank statement.
	 * 
	 * @param object $item
	 * @return boolean
	 */
	public static function is_import_of_statement_available($item)
	{
		try
		{
			$bas = Bank_Account_Settings::factory($item->type);
			return $bas->can_import_statements();
		}
		catch (InvalidArgumentException $e)
		{
			return FALSE;
		}
	}

	/**
	 * Checks whether given member is former and has left before X years.
	 *
	 * @param object $item member query item
	 * @return boolean
	 */
	public static function is_former_for_more_than_limit_years($item)
	{
		$Xyears = Settings::get('member_former_limit_years');
		$date_Xyears_before = date('Y-m-d', strtotime('-' . $Xyears . ' years'));
		return ($item->type == Member_Model::TYPE_FORMER)
			&& ($item->leaving_date <= $date_Xyears_before);
	}
	
}
