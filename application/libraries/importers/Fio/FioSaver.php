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
 * Saves Fio bank listing items into Freenetis database structure.
 *
 * @author Jiri Svitak
 */
class FioSaver
{

	/**
	 * Saves Fio bank listing items into Freenetis database structure.
	 *
	 * @author Jiri Svitak
	 * @param object $data
	 * @param integer $bank_account_id
	 * @param integer $bank_statement_id
	 * @param integer $user_id
	 * @return array	Stats
	 */
	public static function save($data, $bank_account_id, $bank_statement_id, $user_id)
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
		$transfer = new Transfer_Model();
		$bt = new Bank_transfer_Model();
		$member_model = new Member_Model();
		$fee_model = new Fee_Model();
		$variable_symbol_model = new Variable_Symbol_Model();

		// statistics preparation
		$stats["unidentified_nr"] = 0;
		$stats["invoices"] = 0;
		$stats["invoices_nr"] = 0;
		$stats["member_fees"] = 0;
		$stats["member_fees_nr"] = 0;
		$stats["interests"] = 0;
		$stats["interests_nr"] = 0;
		$stats["deposits"] = 0;
		$stats["deposits_nr"] = 0;

		// miscellaneous preparation
		$now = date("Y-m-d H:i:s");
		$number = 0;

		// imported transaction codes, to check duplicities
		$transaction_codes = array();

		// saving each bank listing item
		foreach ($data as $item)
		{
			// convert date of transfer to international format
			$date_arr = explode(".", $item["datum"]);
			$timestamp = mktime(0, 0, 0, $date_arr[1], $date_arr[0], $date_arr[2]);
			$datetime = date("Y-m-d", $timestamp);

			// try to find counter bank account in database
			$counter_ba = ORM::factory('bank_account')
					->where(array
					(
						'account_nr' => $item["protiucet"],
						'bank_nr' => $item["kod_banky"]
					))->find();

			// counter bank account does not exist? let's create new one
			if (!$counter_ba->id)
			{
				$counter_ba->clear();
				$counter_ba->set_logger(FALSE);
				$counter_ba->name = $item["nazev_protiuctu"];
				$counter_ba->account_nr = $item["protiucet"];
				$counter_ba->bank_nr = $item["kod_banky"];
				$counter_ba->member_id = NULL;
				$counter_ba->save_throwable();
			}

			// determining in/out type of transfer
			if ($item['castka'] < 0)
			{
				// outbound transfer
				// -----------------
				// by default we assume, it is "invoice" (this includes all expenses)
				// double-entry transfer
				$transfer_id = Transfer_Model::insert_transfer(
						$account->id, $suppliers->id, null,
						$counter_ba->member_id, $user_id, null,
						$datetime, $now, $item["zprava"], abs($item["castka"])
				);
				// bank transfer
				$bt->clear();
				$bt->set_logger(false);
				$bt->origin_id = $ba->id;
				$bt->destination_id = $counter_ba->id;
				$bt->transfer_id = $transfer_id;
				$bt->bank_statement_id = $bank_statement_id;
				$bt->transaction_code = $item["id_pohybu"];
				$bt->number = $number;
				$bt->constant_symbol = $item["ks"];
				$bt->variable_symbol = $item["vs"];
				$bt->specific_symbol = $item["ss"];
				$bt->save();
				// stats
				$stats["invoices"] += abs($item["castka"]);
				$stats["invoices_nr"]++;
			}
			else
			{
				// inbound transfer
				// ----------------

				// interest transfer
				if ($item["typ"] == "Připsaný úrok")
				{
					// let's create interest transfer
					$transfer_id = Transfer_Model::insert_transfer(
							$bank_interests->id, $account->id, null, null,
							$user_id, null, $datetime, $now, $item["typ"],
							abs($item["castka"])
					);
					$bt->clear();
					$bt->set_logger(false);
					$bt->origin_id = null;
					$bt->destination_id = $ba->id;
					$bt->transfer_id = $transfer_id;
					$bt->bank_statement_id = $bank_statement_id;
					$bt->transaction_code = $item["id_pohybu"];
					$bt->number = $number;
					$bt->save();
					$stats["interests"] += abs($item["castka"]);
					$stats["interests_nr"]++;
				}
				elseif ($item["typ"] == "Vklad pokladnou")
				{
					$member = $variable_symbol_model->where('variable_symbol',(int) $item["vs"])->find()->account->member;
					if (!$member->id)
					{
						// let's create interest transfer
						$transfer_id = Transfer_Model::insert_transfer(
								$cash->id, $account->id, null, null,
								$user_id, null, $datetime, $now, $item["typ"],
								abs($item["castka"])
						);
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = null;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $bank_statement_id;
						$bt->transaction_code = $item["id_pohybu"];
						$bt->number = $number;
						$bt->constant_symbol = $item["ks"];
						$bt->variable_symbol = $item["vs"];
						$bt->specific_symbol = $item["ss"];
						$bt->save();
						$stats["deposits"] += abs($item["castka"]);
						$stats["deposits_nr"]++;
					}
					else
					{
						$member_id = $member->id;
					
						// double-entry incoming transfer
						$transfer_id = Transfer_Model::insert_transfer(
								$member_fees->id, $account->id, null, $member_id,
								$user_id, null, $datetime, $now, $item["zprava"],
								abs($item["castka"])
						);
						// incoming bank transfer
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = $counter_ba->id;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $bank_statement_id;
						$bt->transaction_code = $item["id_pohybu"];
						$bt->number = $number;
						$bt->constant_symbol = $item["ks"];
						$bt->variable_symbol = $item["vs"];
						$bt->specific_symbol = $item["ss"];
						$bt->save();

						// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
						if ($member_id > 1)
						{
							$ca = ORM::factory("account")
									->where('member_id', $member_id)
									->find();

							// assigning transfer
							$a_transfer_id = Transfer_Model::insert_transfer(
									$account->id, $ca->id, $transfer_id, $member_id,
									$user_id, null, $datetime, $now,
									__("Assigning of transfer"), abs($item["castka"])
							);

							// transaction fee
							$fee = $fee_model->get_by_date_type(
									$datetime, 'transfer fee'
							);
							if ($fee && $fee->fee > 0)
							{
								$tf_transfer_id = Transfer_Model::insert_transfer(
										$ca->id, $operating->id, $transfer_id,
										$member_id, $user_id, null, $datetime,
										$now, __("Transfer fee"), $fee->fee
								);
							}
							$counter_ba->member_id = $member_id;
							$counter_ba->save_throwable();
						}
						// member fee stats
						$stats["member_fees"] += abs($item["castka"]);
						$stats["member_fees_nr"]++;
					}
				}
				// otherwise we assume that it is member fee
				else
				{
					// let's identify member
					$member = $variable_symbol_model->where('variable_symbol',(int) $item["vs"])->find()->account->member;
					if (!$member->id)
					{
						$member_id = null;
						$stats["unidentified_nr"]++;
					}
					else
					{
						$member_id = $member->id;
					}
					
					// double-entry incoming transfer
					$transfer_id = Transfer_Model::insert_transfer(
							$member_fees->id, $account->id, null, $member_id,
							$user_id, null, $datetime, $now, $item["zprava"],
							abs($item["castka"])
					);
					// incoming bank transfer
					$bt->clear();
					$bt->set_logger(false);
					$bt->origin_id = $counter_ba->id;
					$bt->destination_id = $ba->id;
					$bt->transfer_id = $transfer_id;
					$bt->bank_statement_id = $bank_statement_id;
					$bt->transaction_code = $item["id_pohybu"];
					$bt->number = $number;
					$bt->constant_symbol = $item["ks"];
					$bt->variable_symbol = $item["vs"];
					$bt->specific_symbol = $item["ss"];
					$bt->save();

					// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
					if ($member_id > 1)
					{
						$ca = ORM::factory("account")
								->where('member_id', $member_id)
								->find();

						// assigning transfer
						$a_transfer_id = Transfer_Model::insert_transfer(
								$account->id, $ca->id, $transfer_id, $member_id,
								$user_id, null, $datetime, $now,
								__("Assigning of transfer"), abs($item["castka"])
						);

						// transaction fee
						$fee = $fee_model->get_by_date_type(
								$datetime, 'transfer fee'
						);
						if ($fee && $fee->fee > 0)
						{
							$tf_transfer_id = Transfer_Model::insert_transfer(
									$ca->id, $operating->id, $transfer_id,
									$member_id, $user_id, null, $datetime,
									$now, __("Transfer fee"), $fee->fee
							);
						}
						$counter_ba->member_id = $member_id;
						$counter_ba->save_throwable();
					}
					// member fee stats
					$stats["member_fees"] += abs($item["castka"]);
					$stats["member_fees_nr"]++;
				}

			}

			// add item transaction code to array to check duplicities later
			$transaction_codes[] = $item["id_pohybu"];

			// line number increase
			$number++;
		}

		// let's check duplicities
		$duplicities = $bt->get_transaction_code_duplicities($transaction_codes, $bank_account_id);
		if (count($duplicities) > count($transaction_codes))
		{
			//$filtered_duplicities = array_diff($duplicities, $transaction_codes);
			throw new Duplicity_Exception();
		}

		return $stats;
	}

}
