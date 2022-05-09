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

require dirname(__FILE__) . '/Fio_Bank_Statement_File_Importer.php'; 

/**
 * FIO importer for statements in JSON format that are obtained from the FIO API.
 * 
 * @author Ondrej Fibich
 * @since 1.1
 */
class Json_Fio_Bank_Statement_File_Importer extends Fio_Bank_Statement_File_Importer
{
	/**
	 * Reprezentation of import.
	 *
	 * @var array
	 */
	private $data = NULL;
	
	/**
	 * Parsed reprezentation of import (transactions without header).
	 *
	 * @var array
	 */
	private $parsed_transactions = NULL;
	
	/*
	 * @Override
	 */
	protected function check_file_data_format()
	{
		// check JSON format
		$json = json_decode($this->get_file_data(), TRUE);
		
		if (!$json)
		{
			$d = mb_substr($this->get_file_data(), 0, 150);
			$this->add_error(__('Invalid file format (json_decode failed): %s', $d), FALSE);
			return FALSE; // invalid
		}
		
		// check content fields
		if (!is_array($json) ||
			!array_key_exists('accountStatement', $json) ||
			!array_key_exists('info', $json['accountStatement']) ||
			!array_key_exists('accountId', $json['accountStatement']['info']) ||
			!array_key_exists('bankId', $json['accountStatement']['info']) ||
			!array_key_exists('transactionList', $json['accountStatement']))
		{
			$d = mb_substr($this->get_file_data(), 0, 150);
			$this->add_error(__('Invalid JSON file document structure: %s', $d), FALSE);
			return FALSE;
		}
		
		// stored parsed data
		$this->data = $json;
		
		// ok
		return TRUE;
	}

	/*
	 * @Override
	 */
	protected function get_header_data()
	{
		$hd = new Header_Data(
				$this->data['accountStatement']['info']['accountId'],
				$this->data['accountStatement']['info']['bankId']
		);
		
		$hd->currency = $this->data['accountStatement']['info']['currency'];
		$hd->iban = $this->data['accountStatement']['info']['iban'];
		$hd->bic = $this->data['accountStatement']['info']['bic'];
		$hd->openingBalance = $this->data['accountStatement']['info']['openingBalance'];
		$hd->closingBalance = $this->data['accountStatement']['info']['closingBalance'];
		$hd->dateStart = date('Y-m-d H:i:s', strtotime($this->data['accountStatement']['info']['dateStart']));
		$hd->dateEnd = date('Y-m-d H:i:s', strtotime($this->data['accountStatement']['info']['dateEnd']));
		$hd->idFrom = $this->data['accountStatement']['info']['idFrom'];
		$hd->idTo = $this->data['accountStatement']['info']['idTo'];
		$hd->idLastDownload = $this->data['accountStatement']['info']['idLastDownload'];
		
		return $hd;
	}

	/*
	 * @Override
	 */
	protected function parse_file_data()
	{
		$this->parsed_transactions = array();
		
		if (empty($this->data['accountStatement']['transactionList']) ||
			!array_key_exists('transaction', $this->data['accountStatement']['transactionList']))
		{ // no transactions available
			return TRUE;
		}
		
		foreach ($this->data['accountStatement']['transactionList']['transaction'] as $t)
		{
			// array keys corresponds to old Fio CSV parser
			$this->parsed_transactions[] = array
			(
				'datum'				=> date('Y-m-d', strtotime($t['column0']['value'])),
				'id_pohybu'			=> $t['column22']['value'] ?? '',
				'id_pokynu'			=> $t['column17']['value'] ?? '',
				'kod_banky'			=> $t['column3']['value'] ?? '',
				'ks'				=> $t['column4']['value'] ?? '',
				'mena'				=> $t['column14']['value'] ?? '',
				'nazev_banky'		=> $t['column12']['value'] ?? '',
				'nazev_protiuctu'	=> empty($t['column10']['value']) ?
											$t['column7']['value'] :
											$t['column10']['value'],
				'castka'			=> $t['column1']['value'] ?? '',
				'protiucet'			=> $t['column2']['value'] ?? '',
				'provedl'			=> $t['column9']['value'] ?? '',
				'prevod'			=> NULL, // not available
				'ss'				=> $t['column6']['value'] ?? '',
				'typ'				=> $t['column8']['value'] ?? '',
				'upresneni'			=> $t['column22']['value'] ?? '',
				'identifikace'		=> $t['column7']['value'] ?? '',
				'vs'				=> ltrim($t['column5']['value'] ?? '', '0'),
				'zprava'			=> $t['column16']['value'] ?? '',
			);
		}
		
		return TRUE;
	}

	/*
	 * @Override
	 */
	protected function get_parsed_transactions()
	{
		return $this->parsed_transactions;
	}
	
}
