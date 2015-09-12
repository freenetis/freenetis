<?php
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

require_once APPPATH.'libraries/importers/Duplicity_Exception.php';

/**
 * Subclass for all bank statements importers of new generation (since 1.1).
 * Import is made using import static method that is the only public method of
 * this class (only one that is called).
 *
 * New importers (drivers) should implement this abstract class and then info about
 * them must be added into drivers array in this class.
 * Driver handles checking of format, parsing, saving data into database.
 * On the other hand this class handles opening of file, notifying of affected users,
 * error handling, variable key searching and grouping drivers in order to provide
 * an independent of driver on another parts of system.
 * Drivers should be located at directory defined by DIR constant that
 * contains a relative location to this class.
 *
 * Lifecycle of importer is following:
 *
 * 1. creation
 * 2. set file data (input file)
 * 3. format file checking
 * 4. parse
 * 5. store into database
 * 6. inform users
 * 7. end of life
 *
 * @author Ondrej Fibich
 * @since 1.1
 */
abstract class Bank_Statement_File_Importer
{
	/**
	 * Dir with driver classes
	 */
	const DIR = 'importers';

	/**
	 * Available drivers (bank statements importers).
	 * Each driver contains items:
	 *
	 *  name				Name
	 *  class				Class name in Varaible keys folder
	 *  bank_type			Bank type of this driver (e.g. FIO, UniCredit, ...)
	 *  extensions			Supported extension array
	 *
	 * @var array
	 */
	private static $drivers = array
	(
		/* FIO - JSON - Obtained from FIO bank API  */
		array
		(
			'name'			=> 'FIO JSON API importer',
			'class'			=> 'Json_Fio_Bank_Statement_File_Importer',
			'bank_type'		=> Bank_account_Model::TYPE_FIO,
			'extensions'	=> array('json')
		),
		/* FIO - CSV - Obtained from FIO e-banking  */
		array
		(
			'name'			=> 'FIO CSV importer',
			'class'			=> 'Csv_Fio_Bank_Statement_File_Importer',
			'bank_type'		=> Bank_account_Model::TYPE_FIO,
			'extensions'	=> array('csv')
		)
	);

	/**
	 * Gets available drivers.
	 *
	 * @return array
	 */
	public static function get_drivers()
	{
		return self::$drivers;
	}

	/**
	 * Tries to download statement and then import it.
	 *
	 * This action contains just getting of a file URL from bank settings.
	 * Then this URL with another info is passed to import method which
	 * does the rest of the job.
	 *
	 * @param Bank_account_Model $bank_account Bank account ot which the statement
	 *										   is imported
	 * @param Bank_Account_Settings $settings
     * @param integer $user_id ID of calle user
	 * @param boolean $send_emails Send notification of affected members by e-mail
	 * @param boolean $send_sms Send notification of affected members by sms
	 * @return Bank_statement_Model Stored statement
	 * @throws InvalidArgumentException On invalid bank account settings
	 *									that cannot be use for proper download
	 */
	public static function download(Bank_account_Model $bank_account,
			Bank_Account_Settings $settings, $user_id, $send_emails, $send_sms)
	{
		// get type
		$type = $settings->get_download_statement_type();

		if (empty($type))
		{
			throw new InvalidArgumentException(__('Unset download statement type'));
		}

		// get url
		$url = $settings->get_download_statement_url();

		if (empty($url))
		{
			throw new InvalidArgumentException(__('Unset download statement URL'));
		}

		// obtain driver
		$driver = self::factory($bank_account, $type);
		$acc = $bank_account->account_nr . '/' . $bank_account->bank_nr;

		if (!$driver)
		{
			$m = __('File importer for bank %s is not available', $acc);
			throw new InvalidArgumentException($m);
		}

		// preparation before download
		if (!$driver->before_download($bank_account, $settings))
		{
			throw new Exception(__('Cannot prepare for statement download'));
		}

		// import
		return self::import($bank_account, $url, $type, $user_id, $send_emails, $send_sms);
	}

	/**
	 * Imports a bank statement placed in a file that is given by the filename
	 * to bank account that is given by its database model. Throws error
	 * exceptions with translated error description if any error occures.
	 *
	 * @param Bank_account_Model $bank_account Bank account ot which the statement
	 *										   is imported
	 * @param string $filename Full path to imported file
	 * @param string $ext File extension
     * @param integer $user_id ID of calle user
	 * @param boolean $send_emails Send notification of affected members by e-mail
	 * @param boolean $send_sms Send notification of affected members by sms
	 * @return Bank_statement_Model Stored statement
	 * @throws InvalidArgumentException On invalid file or bank account entity
	 * @throws Exception On any error during parsing or storing of statement
	 */
	public static function import(Bank_account_Model $bank_account,
			$filename, $ext, $user_id, $send_emails = TRUE, $send_sms = FALSE)
	{
		/* obtain driver */
		$driver = self::factory($bank_account, $ext, $user_id);

		$acc = $bank_account->account_nr . '/' . $bank_account->bank_nr;

		if (!$driver)
		{
			$m = __('File importer for bank %s is not available', $acc);
			throw new InvalidArgumentException($m);
		}

		$driver->inform_affected_member_by_email = $send_emails;
		$driver->inform_affected_member_by_sms = $send_sms;

		/* set file data */
		$fd = @file_get_contents($filename);

		if ($fd == FALSE)
		{
			$e = error_get_last();
			$m = __('Cannot read from input file') . ' "' . $filename . '": '
					. (isset($e['message']) ? $e['message'] : '');
			throw new InvalidArgumentException($m);
		}

		$driver->set_file_data($fd);

		/* check format */
		if (!$driver->check_file_data_format())
		{
			$m = __('Invalid input file format in file "%s" caused by: %s',
					array($filename, '<br>' . implode('<br>', $driver->get_errors())));
			throw new Exception($m);
		}

		/* check header of statement */
		$header_data = $driver->get_header_data();

		if ($header_data !== NULL)
        {
            if (!$header_data ||
                $header_data->get_bank_id() != $bank_account->bank_nr ||
                $header_data->get_account_id() != $bank_account->account_nr)
            {
                $an = $header_data->get_account_id() . '/' 
                        . $header_data->get_bank_id();
                $m = __('Bank account number in listing (%s) header does not ' .
                        'match bank account %s in database!', array($an, $acc));
                throw new Exception($m);
            }
        } 

		/* parse file */
		if (!$driver->parse_file_data())
		{
			$m = __('Error during parsing of statement file %s caused by: %s',
					array($filename, '<br>' . implode('<br>', $driver->get_errors())));
			throw new Exception($m);
		}

		/* store result */
		$bank_statement = $driver->store();

		if (!$bank_statement || !$bank_statement->id)
		{
			$m = __('Error during storing of parsed file %s import caused by: %s',
					array($filename, '<br>' . implode('<br>', $driver->get_errors())));
			throw new Exception($m);
		}

		/* inform affected members */
		$driver->notify_affected_members();

		return $bank_statement;
	}

	/**
	 * Creates an instance of file importer driver that is capable of
	 * importing bank statement.
	 *
	 * @param Bank_account_Model $bank_account
	 * @param string $ext File extension
     * @param integer $user_id ID of calle user
	 * @return Bank_Statement_File_Importer Driver or NULL if no suitable
	 *										driver is available.
	 * @throws InvalidArgumentException On invalid file or bank account entity
	 */
	protected static function factory($bank_account, $ext, $user_id)
	{
		// bank account check
		if ($bank_account == NULL || !$bank_account->id)
		{
			throw new InvalidArgumentException(__('Invalid bank account'));
		}

		// find suitable driver
		foreach (self::$drivers as $d)
		{
			if ($d['bank_type'] == $bank_account->type &&
				in_array($ext, $d['extensions']))
			{
				$cn = $d['class'];
				require_once __DIR__ . '/' . self::DIR . '/' . $cn . '.php';
				return new $cn($bank_account, $user_id);
			}
		}

		// not founded
		return NULL;
	}

	/**
	 * Bank account model for importing of statement.
	 *
	 * @var Bank_account_Model
	 */
	private $bank_account = NULL;

    /**
     * ID of calle user to be set as statement owner.
     *
     * @var integer
     */
    private $user_id = NULL;

	/**
	 * Contains parsed file that contains a bank statement
	 *
	 * @var string
	 */
	private $file_data = NULL;

	/**
	 * Error stack that contains translated errors
	 *
	 * @var arrray
	 */
	private $errors = array();

	/**
	 * Array of memeber IDs  that are affected by the content of file data
	 *
	 * @var array
	 */
	private $affected_members = array();

	/**
	 * Inform affected by e-mail notification (ON by default) that contains
	 * information about the received payment.
	 *
	 * @var boolean
	 */
	private $inform_affected_member_by_email = TRUE;

	/**
	 * Inform affected by SMS notification (OFF by default) that contains
	 * information about the received payment.
	 *
	 * @var boolean
	 */
	private $inform_affected_member_by_sms = FALSE;

	/**
	 * Creates new instance od bank statement import.
	 *
	 * @param Bank_account_Model $bam
     * @param integer $user_id ID of calle user
	 */
	protected function __construct(Bank_account_Model $bam, $user_id)
	{
		$this->bank_account = $bam;
        $this->user_id = $user_id;
	}

	/**
	 * Get importer name.
	 *
	 * @return string
	 */
	public function get_importer_name()
	{
		foreach (self::$drivers as $d)
		{
			if ($d['class'] == get_class($this))
			{
				return $d['name'];
			}
		}

		return NULL;
	}


	/**
	 * User ID of caller of this importer. (user who calls it)
	 *
	 * @return integer
	 */
	protected function get_user_id()
	{
		return $this->user_id;
	}

	/**
	 * Gets bank account for which the importer is evaulating imports.
	 *
	 * @return Bank_account_Model
	 */
	protected function get_bank_account()
	{
		return $this->bank_account;
	}

	/**
	 * Sets bank statement file data (content of a file)
	 *
	 * @param string $fd
	 */
	protected function set_file_data($fd)
	{
		$this->file_data = $fd;
	}

	/**
	 * Gets bank statement file data (content of a file)
	 *
	 * @return string
	 */
	protected function get_file_data()
	{
		return $this->file_data;
	}

	/**
	 * Adds given error to the start of the error stack.
	 *
	 * @param string $error Error messsage
	 * @param boolean $translate Should be error message translated before adding?
	 */
	protected function add_error($error, $translate = TRUE)
	{
		$this->errors = array
		(
			$translate ? __($error) : $error
		) + $this->errors;
	}

	/**
	 * Add given exception as error to the start of the error stack.
	 *
	 * @param Exception $e
	 * @param boolean $translate Should be exception message translated before adding?
	 */
	protected function add_exception_error(Exception $e, $translate = TRUE)
	{
		$this->errors = array
		(
			($translate ? __($e->getMessage()) : $e->getMessage()) .
			': ' . nl2br($e->getTraceAsString())
		) + $this->errors;
	}

	/**
	 * Adds member as affected by this parsed statement. Later if statement is
	 * succefully parsed and saved these members are inform by notification.
	 *
	 * @param integer $member_id
	 */
	protected function add_affected_member($member_id)
	{
		$member_id = intval($member_id);

		if (!in_array($member_id, $this->affected_members))
		{
			$this->affected_members[] = $member_id;
		}
	}

	/**
	 * Gets error trace
	 *
	 * @return array
	 */
	protected function get_errors()
	{
		return $this->errors;
	}

	/**
	 * Finds member ID by variable method. If given variable symbol is not
	 * founded in the database and a variable key generator is active and
	 * capable of error correction than the variable key is tried repared
	 * and searched again.
	 *
	 * @staticvar Variable_Symbol_Model $vk_model
	 * @param string $variable_symbol
	 * @param boolean $error_correction May be used error correction for VS?
	 * @return null|integer NULL of not founded, member ID otherwise
	 */
	protected function find_member_by_vs($variable_symbol, $error_correction = TRUE)
	{
		static $vk_model = NULL;

		if (!$vk_model)
		{
			$vk_model = new Variable_Symbol_Model();
		}

		// locate in database
		$member_id = $vk_model->get_member_id($variable_symbol);

		// located
		if ($member_id)
		{
			return $member_id;
		}
		// not located try to detect error if a variable generator is used
		else if ($error_correction)
		{
			$vkg = Variable_Key_Generator::factory();

			if ($vkg)
			{
				if ($vkg->errorCheckAvailable())
				{
					if ($vkg->errorCorrectionAvailable())
					{
						$corrected = $vkg->errorCorrection($variable_symbol);

						if ($corrected['status'])
						{
							$cvs = $corrected['corrected_variable_key'];
							// try to locate and if it is not located
							// then do not any futher error correction
							return $this->find_member_by_vs($cvs, FALSE);
						}
					}
				}
			}
		}

		return NULL;
	}

	/**
	 * This method may be implemented in order to do some action before
	 * downloading of a bank statement.
	 *
	 * Does not do anything by default.
	 *
	 * @param Bank_account_Model $bank_account
	 * @param Bank_Account_Settings $settings
	 * @return boolean Successfully run?
	 * @throws Exception On any error
	 */
	protected function before_download(Bank_account_Model $bank_account,
			Bank_Account_Settings $settings)
	{
		return TRUE;
	}

	/**
	 * Checks whether the file content that is stored into a fileData property
	 * has valid format. This method checks only a format (syntax), semantic
	 * meaning of content is examined later.
	 *
	 * An error in the format may be add into error stack (addError) that is later
	 * displayed to user if this function returns FALSE.
	 *
	 * @return boolean TRUE if format is corrrent, FALSE otherwise
	 */
	protected abstract function check_file_data_format();

	/**
	 * Gets header data of file data format. This method is used for checking
	 * if the bank statement correspondes to bank account in the database.
	 *
	 * Data must be available in any time after calling of check_file_data_format
	 * method.
	 *
	 * An error in the format may be add into error stack (addError) that is later
	 * displayed to user if this function returns FALSE.
     * 
     * If bank statement file not providing any header information NULL can
     * be returned to skip assert for bank account match.
	 *
	 * @return Header_Data|boolean|null
	 */
	protected abstract function get_header_data();

	/**
	 * Parses a file data into a semantic interpretation of its content.
	 * This interpretation must be defined by implementing class.
	 *
	 * An error in the format may be add into error stack (addError) that is later
	 * displayed to user if this function returns FALSE.
	 *
	 * @return boolean TRUE if file data were succesfully parsed, FALSE otherwise
	 */
	protected abstract function parse_file_data();

	/**
	 * Stores data that are obtain via parse_file_data method (stored internally
	 * - so it is in hand of the implemented file importer).
	 *
	 * All transfers are stored in the database and grouped using statement
	 * entity. This statement entity is than returned.
	 *
	 * This method should also set affected members in order to notify them.
	 *
	 * An error in the format may be add into error stack (addError) that is later
	 * displayed to user if this function returns FALSE.
	 *
	 * @param array $stats Statistics about imported statement
	 * @return Bank_statement_Model Bank statement that was stored or NULL
	 *								if bank statement was not stored
	 * @throws Duplicity_Exception On transfers that were already imported
	 */
	protected abstract function store(&$stats = array());

	/**
	 * Informs affected members by email or SMS according to setting variables
	 * inform_affected_member_by_email and inform_affected_member_by_sms.
	 *
	 * @return boolean TRUE if all affected members were notified, FALSE otherwise
	 */
	protected function notify_affected_members()
	{
		if (!module::e('notification'))
		{
			return FALSE;
		}

        try
		{
			$send_email = Notifications_Controller::ACTIVATE;
			$send_sms = Notifications_Controller::ACTIVATE;

			if (!$this->inform_affected_member_by_email)
			{
				$send_email = Notifications_Controller::KEEP;
			}

			if (!$this->inform_affected_member_by_sms)
			{
				$send_sms = Notifications_Controller::KEEP;
			}

			foreach ($this->affected_members as $member_id)
			{
				Message_Model::activate_special_notice(
						Message_Model::RECEIVED_PAYMENT_NOTICE_MESSAGE,
						$member_id, $this->get_user_id(), $send_email, $send_sms
				);
			}

			// status ok
			return TRUE;
		}
		catch (Exception $e)
		{
			$m = 'Error during notifying of affected members of a bank import';
			Log::add_exception($e);
			Log_queue_Model::error($m, $e);
			// failed
			return FALSE;
		}
	}

}

/**
 * Class for storing of header data of file import.
 */
class Header_Data
{
	/**
	 * Bank account ID
	 *
	 * @var integer
	 */
	private $account_id;

	/**
	 * Bank ID
	 *
	 * @var integer
	 */
	private $bank_id;

	/**
	 * Other properties
	 *
	 * @var array
	 */
	private $properties = array();

	/**
	 * Creates new Header_Data
	 *
	 * @param integer $account_id
	 * @param integer $bank_id
	 */
	function __construct($account_id, $bank_id)
	{
		$this->account_id = $account_id;
		$this->bank_id = $bank_id;
	}

	/**
	 * Gets bank account ID
	 *
	 * @return integer
	 */
	public function get_account_id()
	{
		return $this->account_id;
	}

	/**
	 * Gets bank ID
	 *
	 * @return integer
	 */
	public function get_bank_id()
	{
		return $this->bank_id;
	}

	/**
	 * String representation of header data
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->account_id . '/' . $this->bank_id;
	}

	/**
	 * Gets another property
	 *
	 * @param string $name
	 */
	public function __get($name)
	{
		if (isset($this->properties[$name]))
		{
			return $this->properties[$name];
		}

		throw new InvalidArgumentException('Unknown property: ' . $name);
	}

	/**
	 * Sets another property
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->properties[$name] = $value;
	}


}
