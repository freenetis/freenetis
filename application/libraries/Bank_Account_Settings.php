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
 * Bank account settings enables to store different types of settings to
 * bank account (e.g. API key) in order to type of the bank account.
 * 
 * Types of bank account are specified at Bank_Account_Model database entity.
 *
 * @author Ondrej Fibich
 * @see Bank_Account_Model
 */
abstract class Bank_Account_Settings
{
	/**
	 * Dir with driver classes
	 */
	const DIR = 'bank_account_settings';
	
	// Type constants
	
	/** Integer type */
	const FIELD_TYPE_INT = 'integer';
	/** Integer type */
	const FIELD_TYPE_BOOL = 'boolean';
	/** String type */
	const FIELD_TYPE_STRING = 'string';
	/** Dropdown type */
	const FIELD_TYPE_DROPDOWN = 'dropdown';
	/** Dateselect type */
	const FIELD_TYPE_DATESELECT = 'dateselect';
	
	/** Data of settings */
	private $data = array();
	
	/**
	 * Creates bank account setting for bank account with given type.
	 * If an new bank account type will be added, it must be added also
	 * to this method.
	 * 
	 * @param integer $type type of bank account (from ORM model)
	 * @return BankAccountSettings An instance of driver
	 * @throws InvalidArgumentException On unknown type
	 */
	public static function factory($type)
	{
		// class path dir
		$cp_dir = dirname(__FILE__) . '/' . self::DIR . '/';
		
		// require class and return it
		switch ($type)
		{
			case Bank_account_Model::TYPE_FIO:
				require_once $cp_dir . 'Fio_Bank_Account_Settings.php';
				return new Fio_Bank_Account_Settings();
			case Bank_account_Model::TYPE_UNICREDIT:
				require_once $cp_dir . 'Unicredit_Bank_Account_Settings.php';
				return new Unicredit_Bank_Account_Settings();
			case Bank_account_Model::TYPE_RAIFFEISENBANK:
				require_once $cp_dir . 'Raiffeisenbank_Bank_Account_Settings.php';
				return new Raiffeisenbank_Bank_Account_Settings();
			case Bank_account_Model::TYPE_TATRABANKA:
				require_once $cp_dir . 'Tatrabanka_Bank_Account_Settings.php';
				return new Tatrabanka_Bank_Account_Settings();
		}
		
		// invalid type
		throw new InvalidArgumentException('Unknown driver for type: ' . $type);
	}
	
	/**
	 * Can be bank statements automatically downloaded (using API) in this
	 * type of bank?
	 * 
	 * @return boolean
	 */
	public abstract function can_download_statements_automatically();
	
	/**
	 * Gets download statement type (e.g. csv, json).
	 * Valid only if download is enabled.
	 * Tells what file type is espected as result of download.
	 * This type is later use for detection of importer for statement.
	 * 
	 * Override this method in order to support auto downloading of statements.
	 * 
	 * @see Bank_Account_Settings#can_download_statements_automatically()
	 * @return string
	 */
	public function get_download_statement_type()
	{
		return NULL;
	}
	
	/**
	 * Gets base download URL (e.g. http://mbank/)
	 * Valid only if download is enabled.
	 * 
	 * Override this method in order to support auto downloading of statements.
	 * 
	 * @see Bank_Account_Settings#can_download_statements_automatically()
	 * @return string URL string
	 * @throws InvalidArgumentException On invalid settings (e.g. API token)
	 */
	public function get_download_base_url()
	{
		return NULL;
	}
	
	/**
	 * Gets download URL for statement (e.g. http://mbank/json/transactions)
	 * Valid only if download is enabled.
	 * The statment for the bank account is downloaded from this URL.
	 * 
	 * This method may use get_download_base_url() method for obtaining
	 * of base URL path.
	 * 
	 * Override this method in order to support auto downloading of statements.
	 * 
	 * @see Bank_Account_Settings#can_download_statements_automatically()
	 * @return string URL string
	 * @throws InvalidArgumentException On invalid settings (e.g. API token)
	 */
	public function get_download_statement_url()
	{
		return NULL;
	}
	
	/**
	 * Can be bank statements imported in this type of bank?
	 * 
	 * @return boolean
	 */
	public abstract function can_import_statements();
	
	/**
	 * Gets fields array (key is a name of field and value contains a another
	 * array with fields type, name, help, rules, etc.)
	 * 
	 * @return array Fields array
	 */
	public abstract function get_column_fields();
	
	/**
	 * Gets settings data in JSON format. This method is used for retrieving 
	 * new value in order to store it into a database table.
	 * 
	 * @return array Settings
	 */
	public function get_column_data()
	{
		return json_encode($this->data);
	}
	
	/**
	 * Loads data settings from a given JSON data.
	 * 
	 * @param string JSON data
	 */
	public function load_column_data($json)
	{
		// init columns
		$columns = $this->get_column_fields();
		
		foreach ($columns as $column => $type)
		{
			$this->data[$column] = NULL;
		}
		
		// laod values (only if not empty)
		if (!empty($json))
		{
			$data = json_decode($json, TRUE);

			if (!$data)
			{
				$m = 'Wrong data settings in the database: ' . $json;
				throw new InvalidArgumentException($m);
			}

			foreach ($data as $column => $value)
			{
				$this->$column = $value; // call setter
			}
		}
	}
	/**
	 * Checks if the column exists in the column data.
	 * 
	 * @param string $column Column name
	 * @return boolean
	 */
	public function __isset($column)
	{
		return array_key_exists($column, $this->get_column_fields());
	}

	/**
	 * Gets value from the column data.
	 * 
	 * @param string $column Column name
	 * @throws InvalidArgumentException On invalid column
	 */
	public function __get($column)
	{
		$columns = $this->get_column_fields();
		
		if (!array_key_exists($column, $columns))
		{
			throw new InvalidArgumentException('Column not founded: ' . $column);
		}
		
		return $this->data[$column];
	}
	
	/**
	 * Sets value from the column data.
	 * 
	 * @param string $column Column name
	 * @param mixed $data
	 * @throws InvalidArgumentException On invalid column or invalid data format
	 */
	public function __set($column, $data)
	{
		$columns = $this->get_column_fields();
		
		if (!array_key_exists($column, $columns))
		{
			throw new InvalidArgumentException('Column not founded: ' . $column);
		}
		
		if (!empty($data) && isset($columns[$column]['type']))
		{
			switch ($columns[$column]['type'])
			{
				case self::FIELD_TYPE_INT:
					if (!preg_match('/^[0-9]+$/', $data))
					{
						$m = 'Invalid integer format: ' . data;
						throw new InvalidArgumentException($m);
					}
					$data = intval($data);
					break;

				case self::FIELD_TYPE_STRING:
					$data = strval($data);
					break;
				
				case self::FIELD_TYPE_BOOL:
					break;

				case self::FIELD_TYPE_DROPDOWN:
					break;

				case self::FIELD_TYPE_DATESELECT:
					break;
			}
			
			$this->data[$column] = $data;
		}
		else
		{
			$this->data[$column] = NULL;
		}
	}
	
}
