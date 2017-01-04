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
	// Date, accout, amount
	const REGEX_DATA = "@(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2}).+(\w{2}\d{2}(\d{4})(\d{16})).* (\d+,\d{2}) (.+)\.@";
	// Counter account
	const REGEX_CA = "@ (\d{4})/([\d-]+)@";
	const REGEX_NO_CA = "@Popis transakcie: ()(.*)@";
	// Variable, specific, constant symbol
	const REGEX_VS = "@VS ?(\d*)@";
	const REGEX_SS = "@SS ?(\d*)@";
	const REGEX_CS = "@KS ?(\d*)@";
	// Current balance
	const REGEX_BALANCE = "@aktualny zostatok:[^0-9]*((\d+[ ]?)\d+,\d{2}) (.+)@";
	// Message
	const REGEX_MSG = "@Informacia pre prijemcu: (.*)@";


	protected function check_file_data_format()
	{
		$emails = implode('', $this->get_file_data());

		// Date, account, amount
		$match_data = preg_match_all(self::REGEX_DATA, $emails, $data);

		$accounts = array();

		foreach ($data[2] as $e)
		{
			$accounts[] = $e;
		}

		if (count(array_unique($accounts)) > 1)
		{
			$this->add_error(__('E-mails contains more than one destination account: %s', implode(', ', array_unique($accounts))), FALSE);
		}

		return count(array_unique($accounts)) <= 1;
	}

	protected function get_header_data()
	{
		$emails = $this->get_file_data();

		unset ($emails[self::LAST_DOWNLOAD_SETTINGS_KEY]);

		if (count($emails) == 0)
		{
			return NULL;
		}

		// Newest e-mail
		preg_match(self::REGEX_DATA,
			$emails[0],
			$mN);

		// Current balance
		preg_match(self::REGEX_BALANCE,
			$emails[0],
			$mC);

		// Oldest e-mail
		preg_match(self::REGEX_DATA,
			$emails[count($emails) - 1],
			$mO);

		$hd = new Header_Data(
			$mN[4],	//account ID
			$mN[3]	//bank ID
		);

		$hd->from = DateTime::createFromFormat('j.n.Y G:i', $mO[1])->format('Y-m-d H:i:s');
		$hd->to = DateTime::createFromFormat('j.n.Y G:i', $mN[1])->format('Y-m-d H:i:s');
		$hd->closingBalance = floatval(str_replace(array(' ', ','), array('', '.'), $mC[1]));

		return $hd;
	}

	protected function parse_file_data()
	{
		$emails = $this->get_file_data();

		$this->last_download_datetime = $emails[self::LAST_DOWNLOAD_SETTINGS_KEY];

		foreach ($emails as $email)
		{
			$match_data = preg_match(self::REGEX_DATA,
				$email,
				$data);

			$match_ca = preg_match(self::REGEX_CA,
				$email,
				$ca);

			if ($match_ca == 0)
			{
				preg_match(self::REGEX_NO_CA,
					$email,
					$ca);
			}

			$match_vs = preg_match(self::REGEX_VS,
				$email,
				$vs);

			$match_ss = preg_match(self::REGEX_SS,
				$email,
				$ss);

			$match_cs = preg_match(self::REGEX_CS,
				$email,
				$cs);

			preg_match(self::REGEX_MSG,
				$email,
				$msg);

			if (!$match_data)
			{
				continue;
			}

			$this->data[] = array
			(
				'datetime' 	=>	DateTime::createFromFormat('j.n.Y G:i', $data[1])->format('Y-m-d H:i:s'),
				'iban'		=>	$data[2],
				'bank'		=>	$data[3],
				'account'	=>	$data[4],
				'amount'	=>	floatval(str_replace(',', '.', $data[5])),
				'counter_account'=>	trim(@$ca[2]),
				'counter_bank'	=>	trim(@$ca[1]),
				'currency'	=>	$data[6],
				'vs'		=>	@$vs[1],
				'ss'		=>	@$ss[1],
				'ks'		=>	@$cs[1],
				'message'	=>	trim(@$msg[1])
			);
		}

		return TRUE;
	}

	protected function do_download(Bank_account_Model $bank_account,
					Bank_Account_Settings $settings, $url)
	{
		$key = self::LAST_DOWNLOAD_SETTINGS_KEY;

		$last_download = $settings->$key;

		if (empty($last_download))
		{
			$last_download = 0;
		}

		$hostname = $settings->get_download_statement_url();
		$inbox = @imap_open($hostname, $settings->imap_name, $settings->imap_password);

		$all_mails = array();

		if ($inbox === FALSE)
		{
			$m = __('Cannot connect to IMAP server');
			throw new Exception($m . ' (' . implode(" > ", imap_errors()) . ')');
		}

		if ($last_download > 0)
		{
			$last_download_prev = $last_download - (60 * 60 * 24);	// - 1 day
			$emails = imap_search($inbox, 'SINCE "'.date('j F Y', $last_download_prev).'"');
		}
		else
		{
			$emails = imap_search($inbox, 'ALL');
		}

		$first = TRUE;

		$all_mails[self::LAST_DOWNLOAD_SETTINGS_KEY] = 0;

		if ($emails)
		{
			// Sort from newest mail
			rsort($emails);

			foreach ($emails as $email_number)
			{
				$struct = imap_fetchstructure($inbox, $email_number);
				$header = imap_headerinfo($inbox, $email_number);

				if ($first)
				{
					$all_mails[self::LAST_DOWNLOAD_SETTINGS_KEY] = $header->udate;
					$first = FALSE;
				}

				if (intval($header->udate) <= intval($last_download))
				{
					break;
				}

				// fetch body with FT_PEEK disable setting message read
				$body = imap_fetchbody($inbox, $email_number, 1, FT_PEEK);

				$body = $this->decode_body($body, $struct);

				if ($body === FALSE)
				{
					continue;
				}

				preg_match(self::REGEX_DATA,
					$body,
					$data);

				if (!$data)
				{
					continue;
				}
				else
				{
					// make message read, we want to mark it as imported
					@imap_fetchbody($inbox, $email_number, 1);
				}

				array_unshift($all_mails, $body);
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
					break;
			}

			// Convert text to UTF-8
			if (isset($struct->parameters))
			{
				foreach ($struct->parameters as $p)
				{
					if ($p->attribute == "charset" &&
						\strtolower($p->value) != "utf-8")
					{
						$body = iconv($p->value, "UTF-8//TRANSLIT", $body);
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
