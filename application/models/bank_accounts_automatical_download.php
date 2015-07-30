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
 * Auto activation settings for downloading of bank statements.
 * 
 * @author Onřej Fibich
 * @package Model
 * @property integer $id
 * @property integer $bank_account_id
 * @property Bank_account_Model $bank_account
 * @property integer $type
 * @property mixed $attribute An attribute to settings with type by type field.
 * @property boolean $email_enabled
 * @property boolean $sms_enabled
 */
class Bank_accounts_automatical_download_Model extends Time_Activity_Rule
{
	
	/**
	 * Gets all setting rules of the given bank account.
	 * 
	 * @param integer $bank_account_id
	 * @return array[Bank_accounts_automatical_activation_Model]
	 */
	public function get_bank_account_settings($bank_account_id)
	{
		return $this->where('bank_account_id', $bank_account_id)->find_all();
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