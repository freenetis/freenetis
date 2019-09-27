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

require_once APPPATH.'libraries/importers/Duplicity_Exception.php';


/**
 * Saves Raiffeisenbank (new XML FORMAT) listing items into Freenetis database structure.
 * 
 * @author Jakub Juračka, 2019
 */
class RB_saver
{

	/**
	 * Saves Raiffeisenbank (new XML FORMAT) listing items into Freenetis database structure.
	 *
	 * @param object $data
	 * @param integer $bank_account_id
	 * @param integer $bank_statement_id
	 * @param integer $user_id
	 * @param boolean $send_emails
	 * @param boolean $send_sms
	 * @return array	Stats
	 */
	public static function save($data, $bank_account_id, $bank_statement_id, $user_id,
                                     $send_emails, $send_sms)
	{
		// preparation of system double-entry accounts
		$suppliers = ORM::factory('account')
				->where('account_attribute_id', Account_attribute_Model::SUPPLIERS)
				->find();
		
		$member_fees = ORM::factory('account')
				->where('account_attribute_id', Account_attribute_Model::MEMBER_FEES)
				->find();
		
		$operating = ORM::factory('account')
				->where('account_attribute_id', Account_attribute_Model::OPERATING)
				->find();
		
		$cash = ORM::factory('account')
				->where('account_attribute_id', Account_attribute_Model::CASH)
				->find();

		$ba = new Bank_account_Model($bank_account_id);
		$account = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK);
		$bank_interests = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK_INTERESTS);

		// model preparation
		$bt = new Bank_transfer_Model();
		$member_model = new Member_Model();
		$fee_model = new Fee_Model();
		$vs_model = new Variable_Symbol_Model();
		$counter_ba = new Bank_account_Model();

		// statistics preparation
		$stats['unidentified_nr'] = 0;
		$stats['invoices'] = 0;
		$stats['invoices_nr'] = 0;
		$stats['member_fees'] = 0;
		$stats['member_fees_nr'] = 0;
		$stats['interests'] = 0;
		$stats['interests_nr'] = 0;
		$stats['deposits'] = 0;
		$stats['deposits_nr'] = 0;

		// miscellaneous preparation
		$now = date('Y-m-d H:i:s');
		$number = 0;
                
		// saving each bank listing item
		foreach ($data as $item)
		{
			// convert date of transfer to international format
			$datetime = $item['datetime'];
                        $datetime = str_replace('T', ' ', $datetime);
                        ////////////////////////////////////
			
			// check for duplicities
			$dupli_object = new stdClass();
			$dupli_object->variable_symbol = $item['vs'];
			$dupli_object->number = $number;
			$dupli_object->date_time = $datetime;
			$dupli_object->comment = $item['text'];
			
			if ($bt->get_duplicities($dupli_object)->count() > 0)
			{
				throw new Duplicity_Exception();
			}
			
			/* Step one - find or create bank account */
			
			$counter_ba_id = NULL;
			$counter_ba_id_added = FALSE;

			if (mb_strpos($item['text'], 'ZÁLOHA') === FALSE &&
				mb_strpos($item['text'], 'VKLAD HOTOVOSTI') === FALSE &&
				mb_strpos($item['text'], 'Výběr dne') === FALSE)
			{
				// try to find counter bank account in database
				$counter_ba->where(array
				(
					'account_nr'	=> $item['acc_num'],
					'bank_nr'	=> $item['bank_code']
				))->find();

				// counter bank account does not exist? let's create new one
				if (!$counter_ba->id)
				{
					$counter_ba->clear();
					$counter_ba->set_logger(FALSE);
					$counter_ba->name = $item['name'];
					$counter_ba->account_nr = $item['acc_num'];
					$counter_ba->bank_nr = $item['bank_code'];
					$counter_ba->member_id = NULL;
					$counter_ba->save_throwable();

					$counter_ba_id_added = TRUE;
				}

				$counter_ba_id = $counter_ba->id;
			}
			
			// outbound ////////////////////////////////////////////////////////
			if ($item['amount'] < 0)
			{
				// outbound transfer
				// -----------------
				// by default we assume, it is "invoice" (this includes all expenses)
				// double-entry transfer
				$transfer_id = Transfer_Model::insert_transfer(
						$account->id, $suppliers->id, null,
						$counter_ba->member_id, $user_id, null,
						$datetime, $now, $item['text'], abs($item['amount'])
				);
				// bank transfer
				$bt->clear();
				$bt->set_logger(false);
				$bt->origin_id = $ba->id;
				$bt->destination_id = $counter_ba_id;
				$bt->transfer_id = $transfer_id;
				$bt->bank_statement_id = $bank_statement_id;
				$bt->number = $number;
                                $bt->transaction_code = $item['transaction_id'];
				$bt->constant_symbol = $item['ks'];
				$bt->variable_symbol = $item['vs'];
				$bt->specific_symbol = $item['ss'];
				$bt->save();
				// stats
				$stats['invoices'] += abs($item['amount']);
				$stats['invoices_nr']++;
			}
			// inbound /////////////////////////////////////////////////////////
			else
			{
				/* Step two - get account */

				$m_account_id = NULL;
				$member_id = NULL;

				$m_account = $vs_model->where('variable_symbol', $item['vs'])
						->find()->account;

				if ($m_account->id && $m_account->member_id)
				{
					$m_account_id = $m_account->id;
					$member_id = $m_account->member_id;
				}

				/* Step three - create transfers to association account */

				// double-entry incoming transfer
				$transfer_id = Transfer_Model::insert_transfer(
						$member_fees->id, $account->id, null, $member_id,
						$user_id, null, $datetime, $now, $item['text'],
						abs($item['amount'])
				);
				// incoming bank transfer
				$bt->clear();
				$bt->set_logger(false);
				$bt->origin_id = $counter_ba_id;
				$bt->destination_id = $ba->id;
				$bt->transfer_id = $transfer_id;
				$bt->bank_statement_id = $bank_statement_id;
				$bt->number = $number;
                                $bt->transaction_code = $item['transaction_id'];
				$bt->constant_symbol = $item['ks'];
				$bt->variable_symbol = $item['vs'];
				$bt->specific_symbol = $item['ss'];
				$bt->save_throwable();

				/** Step four - create transfers to associated member (if found) */

				if (!empty($member_id))
				{
					$a_transfer_id = Transfer_Model::insert_transfer(
							$account->id, $m_account_id, $transfer_id, $member_id,
							$user_id, null, $datetime, $now,
							__('Assigning of transfer'), abs($item['amount'])
					);

					// transaction fee
					$fee = $fee_model->get_by_date_type($datetime, 'transfer fee');

					if ($fee && $fee->fee > 0)
					{
						$tf_transfer_id = Transfer_Model::insert_transfer(
								$m_account_id, $operating->id, $transfer_id,
								$member_id, $user_id, null, $datetime,
								$now, __('Transfer fee'), $fee->fee
						);
					}

					if ($counter_ba_id_added)
					{
						$counter_ba->member_id = $member_id;
						$counter_ba->save_throwable();
					}
				}
				else
				{
					$stats['unidentified_nr']++;
				}

				// member fee stats
				$stats['member_fees'] += abs($item['amount']);
				$stats['member_fees_nr']++;

				/** Send payment notification */

				try
				{
					Message_Model::activate_special_notice(
							Message_Model::RECEIVED_PAYMENT_NOTICE_MESSAGE,
							$member_id, $user_id,
							$send_emails, $send_sms
					);
				}
				catch (Exception $e)
				{
					Log::add_exception($e);
				}
			}

			// line number increase
			$number++;
		}

		return $stats;
	}
}
