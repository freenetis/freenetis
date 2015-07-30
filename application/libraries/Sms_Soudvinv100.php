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
 * SMS driver for Soundwin V100
 * 
 * @author Roman Sevcik, Ondrej Fibich
 */
class Sms_Soudvinv100 extends Sms
{
	/**
	 * Recieved messages
	 *
	 * @var array
	 */
    private $messages = array();
	
	/**
	 * Status of message or FALSE if there is no sended message.
	 *
	 * @var mixed
	 */
	private $status = FALSE;
	
	/**
	 * Error report or FALSE if there is no error.
	 *
	 * @var mixed
	 */
	private $error = FALSE;

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
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://' . $this->hostname);
		curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($curl);
		curl_close($curl);

		if ($result == '' || $result === null)
		{
			$this->status = FALSE;
			$this->error = 0;
			return false;
		}
		else
		{
			$this->status = 0;
			$this->error = FALSE;
			return true;
		}
	}

	/**
	 * Gets recieved messages after receive
	 * 
	 * @return array
	 */
	public function get_received_messages()
    {
        return $this->messages;
    }

	/**
	 * Try to send SMS messages
	 *
	 * @param string $sender	Sender of message (not used)
	 * @param string $recipient	Recipier of message
	 * @param string $message	Text of message
	 * @return boolean			FALSE on error TRUE on success 
	 */
	public function send($sender, $recipient, $message)
	{
		if (!$this->test_conn())
		{
			return false;
		}
		
		if (!text::starts_with($recipient, '00'))
		{
			$recipient .= '00';
		}

		$vars = array
		(
			'sms_number'	=> $recipient,
			'max_digit'		=> $message
		);

		$data = http_build_query($vars);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://' . $this->hostname . '/do_gsm_sms.cgi');
		curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
		curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($curl);
		curl_close($curl);

		if ($result == '' || $result === null)
		{
			$this->status = FALSE;
			$this->error = 1;
			return false;
		}
		else
		{
			$this->status = 0;
			$this->error = FALSE;
			return true;
		}
	}

	/**
	 * Try to receive SMS messages
	 *
	 * @return boolean		FALSE on error TRUE on success 
	 */
    public final function receive()
    {
		if (!$this->test_conn())
		{
			return false;
		}

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://' . $this->hostname . '/sms.message');
		curl_setopt($curl, CURLOPT_USERPWD, $this->user . ':' . $this->password);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);

		$result = curl_exec($curl);
		curl_close($curl);

		if ($result == '' || $result === null)
		{
			$this->error = 1;
			$this->status = FALSE;
			return false;
		}
		else
		{
			$messages = explode('\n', $result);

			$sms = array();
			$c = 0;

			foreach ($messages as $m)
			{
				$message = substr($m, strpos($m, 'From:'));

				if (stristr($message, 'From:') != FALSE &&
					stristr($message, 'Date:') != FALSE &&
					stristr($message, 'Message:') != FALSE)
				{
					$sub_message = explode('Date:', $message);

					$from = str_replace('+', '', $sub_message[0]);

					$from = trim($from);
					$sender = substr($from, 5);
					$date = substr($sub_message[1], 0, 17);
		
					$text = substr(
							substr($sub_message[1], 26), 0,
							strlen(substr($sub_message[1], 26)) - 2
					);

					$sms[$c]->sender = $sender;
					$sms[$c]->date = $date;
					$sms[$c]->text = $text;
					$c++;
				}
			}

			$this->messages = $sms;

			$this->status = 1;
			$this->error = FALSE;
			return true;
		}
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
			case '0':
				return 'Wrong gateway connection informations';
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
		
		switch ($this->status)
		{
			case '0':
				return 'SMS message send';
			case '1':
				return 'SMS message recieved';
			default:
				return 'Unknown error';
		}
	}
	
}
