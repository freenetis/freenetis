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

require dirname(__FILE__) . '/Tatra_Banka_Statement_File_Importer.php';

/**
 * Parser for Tatra banka E-mail statements
 *
 * @author David Raška
 */
class Txt_Tatra_Banka_Statement_File_Importer extends Tatra_Banka_Statement_File_Importer
{
	protected function check_file_data_format()
	{
		$emails = implode('', $this->get_file_data());

		// Date, account, amount
		$match_data = preg_match_all("@(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}).+(\w{2}\d{2}(\d{4})(\d{16})).* (\d+,\d{2}) (.+)\.@",
			$emails,
			$m1);

		// Counter account
		$match_counter = preg_match_all("@ (\d{4})/([\d-]+)@",
			$emails,
			$m2);

		// Variable, specific, constant symbol
		$match_symbols = preg_match_all("@/VS(\d*)/SS(\d*)/KS(\d*)@",
			$emails,
			$m3);

		$accounts = array();

		foreach ($m1[2] as $e)
		{
			$accounts[] = $e;
		}

		return ($match_data == $match_symbols) && ($match_symbols == $match_counter) && count(array_unique($accounts)) == 1;
	}

	protected function get_header_data()
	{
		$emails = $this->get_file_data();

		// Newest e-mail
		preg_match("@(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}).+(\w{2}\d{2}(\d{4})(\d{16})).* (\d+,\d{2}) (.+)\.@",
			$emails[0],
			$mN);

		// Current balance
		preg_match("@aktualny zostatok: (\d+,\d{2}) (.+)@",
			$emails[0],
			$mC);

		// Oldest e-mail
		preg_match("@(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}).+(\w{2}\d{2}(\d{4})(\d{16})).* (\d+,\d{2}) (.+)\.@",
			$emails[count($emails) - 1],
			$mO);

		$hd = new Header_Data(
			$mN[4],	//account ID
			$mN[3]	//bank ID
		);

		$hd->from = DateTime::createFromFormat('j.n.Y G:i', $mO[1])->format('Y-m-d H:i:s');
		$hd->to = DateTime::createFromFormat('j.n.Y G:i', $mN[1])->format('Y-m-d H:i:s');
		$hd->closingBalance = floatval(str_replace(',', '.', $mC[1]));

		return $hd;
	}

	protected function parse_file_data()
	{
		$emails = $this->get_file_data();

		foreach ($emails as $email)
		{
			$match_data = preg_match("@(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}).+(\w{2}\d{2}(\d{4})(\d{16})).* (\d+,\d{2}) (.+)\.@",
				$email,
				$m1);

			$match_counter = preg_match("@ (\d{4})/([\d-]+)@",
				$email,
				$m2);

			$match_symbols = preg_match("@/VS(\d*)/SS(\d*)/KS(\d*)@",
				$email,
				$m3);

			if (!$match_data || !$match_symbols || !$match_counter)
			{
				continue;
			}

			$this->data[] = array(
				'datetime' 	=>	DateTime::createFromFormat('j.n.Y G:i', $m1[1])->format('Y-m-d H:i:s'),
				'iban'		=>	$m1[2],
				'bank'		=>	$m1[3],
				'account'	=>	$m1[4],
				'amount'	=>	floatval(str_replace(',', '.', $m1[5])),
				'counter_account'=>	$m2[2],
				'counter_bank'	=>	$m2[1],
				'currency'	=>	$m1[6],
				'vs'		=>	$m3[1],
				'ss'		=>	$m3[2],
				'ks'		=>	$m3[3]
			);
		}

		return TRUE;
	}

	protected function do_download(Bank_account_Model $bank_account,
					Bank_Account_Settings $settings, $url)
	{
		$hostname = $settings->get_download_statement_url();
		$inbox = @imap_open($hostname, $settings->imap_name, $settings->imap_password, OP_READONLY);

		$all_mails = array();

		if ($inbox === FALSE)
		{
			$m = __('Cannot connect to IMAP server');
			throw new Exception($m . ' (' . implode(" > ", imap_errors()) . ')');
		}

		$emails = imap_search($inbox, 'ALL');

		if ($emails)
		{
			rsort($emails);

			foreach ($emails as $email_number)
			{
				$struct = imap_fetchstructure($inbox, $email_number);

				$body = imap_fetchbody($inbox,$email_number, 1);

				$body = $this->decode_body($body, $struct);

				if ($body === FALSE)
				{
					continue;
				}

				preg_match("@(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}).+(\w{2}\d{2}(\d{4})(\d{16})).* (\d+,\d{2}) (.+)\.@",
					$body,
					$m1);

				preg_match("@ (\d{4})/([\d-]+)@",
					$body,
					$m2);

				preg_match("@/VS(\d*)/SS(\d*)/KS(\d*)@",
					$body,
					$m3);


				if (!$m1 || !$m2 || !$m3)
				{
					continue;
				}

				$all_mails[] = $body;
			}
		}

		imap_close($inbox);

		return $all_mails;
	}

	protected function decode_body($body, $struct)
	{
		if ($struct->type == TYPETEXT)	// text body
		{
			// Decode text
			switch ($struct->encoding)
			{
				case ENC7BIT:
					break;
				case ENC8BIT:
					break;
				case ENCBINARY:
					throw new Exception("Unsupported IMAP encoding: ENCBINARY");
					break;
				case ENCBASE64:
					$body = base64_decode($body);
					break;
				case ENCQUOTEDPRINTABLE:
					$body = quoted_printable_decode($body);
					break;
				case ENCOTHER:
					throw new Exception("Unsupported IMAP encoding: ENCOTHER");
					break;
			}

			// Convert text to UTF-8
			if (isset($struct->parameters))
			{
				foreach ($struct->parameters as $p)
				{
					if ($p->attribute == "charset")
					{
						$body = iconv($p->value, "UTF-8", $body);
						break;
					}
				}
			}
		}
		else if ($struct->type == TYPEMULTIPART)	// multipart body
		{
			foreach ($struct->parts as $p)
			{
				if ($p->type == TYPETEXT)
				{
					$body = $this->decode_body($body, $p);
					break;
				}
			}
		}
		else
		{
			return FALSE;
		}

		return $body;
	}
}