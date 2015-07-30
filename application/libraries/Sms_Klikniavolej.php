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
 * SMS driver for gate Klikniavolej.cz
 * 
 * @see http://www.klikniavolej.cz/
 * @author Roman Sevcik, Ondrej Fibich
 */
class Sms_Klikniavolej extends Sms
{	
	/**
	 * Last ID
	 *
	 * @var integer
	 */
    private $last_id = FALSE;
	
	/**
	 * Count of parts of sended SMS
	 *
	 * @var integer
	 */
    private $parts;
	
	/**
	 * Price of sended SMS
	 *
	 * @var double
	 */
    private $billed;
	
	/**
	 * Test state of driver?
	 *
	 * @var bool
	 */
    private $test = FALSE;
	
	/**
	 * Error report or FALSE if there is no error.
	 *
	 * @var mixed
	 */
	private $error = FALSE;
	
	/**
	 * Status of message or FALSE if there is no sended message.
	 *
	 * @var mixed
	 */
	private $status = FALSE;

	/**
	 * Construct cannot be called from outside
	 */
    protected function __construct()
    {
    }

	/**
	 * Test if connection to server is OK
	 *
	 * @return bool
	 */
    public function test_conn()
    {
		return $this->get_last_id();
    }

	/**
	 * Try to send SMS messages
	 *
	 * @param string $sender	Sender of message
	 * @param string $recipient	Recipier of message
	 * @param string $message	Text of message
	 * @return boolean			FALSE on error TRUE on success 
	 */
	public function send($sender, $recipient, $message)
    {
		if ($this->get_last_id())
		{
			$id = $this->last_id;
		}
		else
		{
			return false;
		}

		/*
		 * This function is based on code of Radek Hulan
		 * http://myego.cz/item/php-skript-pro-posilani-sms-z-klikniavolej-cz
		 */
		$vars = array
		(
			'user'		=> $this->user,
			'number'	=> $recipient,
			'sender'	=> $sender,
			'text'		=> $message,
			'encoding'	=> 'ascii',
			'test'		=> $this->test ? 1 : 0,
			'id'		=> ($id + 1),
			'hash'		=> sha1($this->user . ':' . ($id + 1) . ':' . sha1($this->password)),
			'flash'		=> 0
		);
		
		$data = http_build_query($vars);

		$url = 'http://' . $this->hostname . '/smsgateway.pl';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = explode(';', $response);

		if ($response[0] == 'OK')
		{
			$this->status = $response[1];
			$this->parts = $response[2];
			$this->billed = $response[3];
			$this->error = FALSE;
			return true;
		}
		else
		{
			$this->status = FALSE;
			$this->error = @$response[1];
			return false;
		}
	}

	/**
	 * Try to receive SMS messages.
	 * This driver cannot receive any SMS messages.
	 *
	 * @return boolean		FALSE on error TRUE on success 
	 */
    public final function receive()
    {
		return FALSE;
    }
	
	/**
	 * Gets recieved messages after receive
	 * 
	 * @return array
	 */
	public function get_received_messages()
	{
		return array();
	}

	/**
	 * Gets error report
	 *
	 * @return mixed	Error report or FALSE on no error
	 */
    public function get_error()
	{
		if ($this->error === FALSE)
		{
			return FALSE;			
		}
		
		switch ($this->error)
		{
			case '00':
				return 'SMS message send';
			case '01':
				return 'Wrong gateway connection informations';
			case '02':
				return 'Wrong phone number or identification of user';
			case '03':
				return 'Wrong phone number of reciever';
			case '04':
				return 'Wrong or missing arguments';
			case '05':
				return 'Text of message is too long';
			case '06':
				return 'Lack of credit';
			case '07':
				return 'Cannot sent message to reciever';
			case '08':
				return 'During sending an error appear';
			case '09':
				return 'Param id with same value was used for another message before';
			default:
				return 'Unknown error';
		}
	}

	/**
	 * Gets state of message
	 *
	 * @return mixed	State or FALSE on no error
	 */
    public function get_status()
    {
		if ($this->status === FALSE)
		{
			return FALSE;
		}
		
        return 'SMS zpráva odeslána, počet částí: '.
				$this->parts.', cena: '.$this->billed.'Kč';
    }

	/**
	 * Sets test (no SMS are sended, just states are made)
	 *
	 * @param bool $test
	 */
    public function set_test($test)
    {
        $this->test = ($test === TRUE);
    }
	
	/**
	 * Sets hostname of gate.
	 * Disable set of hostname.
	 *
	 * @param string $hostname
	 * @deprecated Do not use
	 */
	public function set_hostname($hostname)
	{
	}

	/**
	 * Gets last id from server ans set it to property with status.
	 *
	 * @return bool		FALSE on error TRUE on success 
	 */
    protected function get_last_id()
	{
		$id = 't' . time();

		/*
		 * This function is based on code of Radek Hulan
		 * http://myego.cz/item/php-skript-pro-posilani-sms-z-klikniavolej-cz
		 */
		$vars = array
		(
			'user'	=> $this->user,
			'id'	=> $id,
			'hash'	=> sha1($this->user . ':' . $id . ':' . sha1($this->password)),
		);

		$data = http_build_query($vars);

		$url = 'http://' . $this->hostname . '/smsmaxid.pl';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 180);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = explode(';', $response);

		if ($response[0] == 'OK')
		{
			$this->status = $response[1];
			$this->last_id = $response[2];
			$this->error = FALSE;
			return true;
		}
		else
		{
			$this->error = @$response[1];
			return false;
		}
	}
}
