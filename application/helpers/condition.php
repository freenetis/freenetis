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
}
