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
 * Bank setting for Tatra banka accounts.
 */
class Tatrabanka_Bank_Account_Settings extends Bank_Account_Settings
{
	const ENC_NONE = 1;
	const ENC_STARTTLS = 2;
	const ENC_SSLTLS = 3;

	const DEFAULT_MAILBOX = 'INBOX';

	private $encrypt = array
	(
		self::ENC_NONE => '/notls',
		self::ENC_STARTTLS => '/tls',
		self::ENC_SSLTLS => '/ssl',
	);

	private $port = array
	(
		self::ENC_NONE => 143,
		self::ENC_STARTTLS => 143,
		self::ENC_SSLTLS => 993,
	);

	/*
	 * @Override
	 */
	public function can_import_statements()
	{
		return FALSE;
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
		return 'txt';
	}

	/*
	 * @Override
	 */
	public function get_download_statement_url()
	{
		if (!isset($this->imap_server) ||empty($this->imap_server) ||
			!isset($this->imap_port) ||empty($this->imap_port) ||
			!isset($this->imap_mailbox) ||empty($this->imap_mailbox) ||
			!array_key_exists($this->imap_encryption, $this->encrypt) ||
			!array_key_exists($this->imap_encryption, $this->port))
		{
			throw new InvalidArgumentException(__('Invalid IMAP server settings'));
		}

		$encrypt = $this->encrypt[$this->imap_encryption];
		$port = $this->port[$this->imap_encryption];

		$hostname =
			'{'.
			$this->imap_server.
			':'.
			(empty($this->imap_port) ? $port : $this->imap_port).
			'/imap'.
			$encrypt.
			'}'.
			(empty($this->imap_mailbox) ? self::DEFAULT_MAILBOX : $this->imap_mailbox);

		return $hostname;
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
			// IMAP settings
			'imap_server' => array
			(
				'name'		=> __('IMAP server'),
				'type'		=> self::FIELD_TYPE_STRING,
			),
			'imap_port' => array
			(
				'name'		=> __('IMAP server port'),
				'type'		=> self::FIELD_TYPE_INT,
				'help'		=> 'imap_port'
			),
			'imap_encryption' => array
			(
				'name'		=> __('Encryption'),
				'type'		=> self::FIELD_TYPE_DROPDOWN,
				'options'	=> array
				(
					self::ENC_NONE => 'None',
					self::ENC_STARTTLS => 'STARTTLS',
					self::ENC_SSLTLS => 'SSL/TLS'
				)
			),
			'imap_mailbox' => array
			(
				'name'		=> __('Mailbox name'),
				'type'		=> self::FIELD_TYPE_STRING,
				'help'		=> 'imap_mailbox'
			),
			// IMAP credentials
			'imap_name'	=> array
			(
				'name'		=> __('IMAP username'),
				'type'		=> self::FIELD_TYPE_STRING,
			),
			'imap_password'	=> array
			(
				'name'		=> __('IMAP password'),
				'type'		=> self::FIELD_TYPE_STRING,
			)
		);
	}
}
