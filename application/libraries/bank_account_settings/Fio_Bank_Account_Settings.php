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
 * Bank setting for FIO accounts.
 */
class Fio_Bank_Account_Settings extends Bank_Account_Settings
{
	/**
	 * API token has defined length
	 */
	const API_TOKEN_LENGTH = 49;
	
	/*
	 * @Override
	 */
	public function can_import_statements()
	{
		return TRUE;
	}
	
	/*
	 * @Override
	 */
	public function can_download_statements_automatically()
	{
		return isset($this->enable_download_statements_automatically) &&
			$this->enable_download_statements_automatically;
	}
	
	/*
	 * @Override
	 */
	public function get_download_statement_type()
	{
		return 'json';
	}
	
	/*
	 * @Override
	 */
	public function get_download_base_url()
	{
		return 'https://fioapi.fio.cz/v1/rest/';
	}
	
	/*
	 * @Override
	 */
	public function get_download_statement_url()
	{
		if (!isset($this->api_token) || empty($this->api_token))
		{
			throw new InvalidArgumentException(__('Invalid API token'));
		}
		
		return $this->get_download_base_url() . 'last/'
			 . $this->api_token . '/transactions.'
			 . $this->get_download_statement_type();
	}
	
	/*
	 * @Override
	 */
	public function get_column_fields()
	{
		return array
		(
			// Enable auto
			'enable_download_statements_automatically' => array
			(
				'name'		=> __('Enable download of statements automatically'),
				'type'		=> self::FIELD_TYPE_BOOL,
			),
			// API token
			'api_token'	=> array
			(
				'name'		=> __('Token for API'),
				'help'		=> __('This token can be obtain in the administration of this bank account'),
				'type'		=> self::FIELD_TYPE_STRING,
			)
		);
	}	
}
