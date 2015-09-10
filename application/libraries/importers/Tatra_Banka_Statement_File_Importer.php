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
 * Tatra banka abstract importer that handles storing of transfers. Subclass add method
 * for handling different input file types and format.
 *
 * @author David Raška
 */
abstract class Tatra_Banka_Statement_File_Importer extends Bank_Statement_File_Importer
{
	const LAST_DOWNLOAD_SETTINGS_KEY = 'last_download';

	protected $data = array();

	protected $last_download_datetime;

	protected function store(&$stats = array())
	{
		$statement = new Bank_statement_Model();
		$ba = $this->get_bank_account();
		$user_id = $this->get_user_id();

		try
		{
			/* header */

			$statement->transaction_start();
			$header = $this->get_header_data();

			// bank statement
			$statement->bank_account_id = $ba->id;
			$statement->user_id = $this->get_user_id();
			$statement->type = $this->get_importer_name();

			if ($header != NULL)
			{
				$statement->from = $header->from;
				$statement->to = $header->to;
				$statement->closing_balance = $header->closingBalance;
			}
			$statement->save_throwable();

			/* transactions */

			// preparation of system double-entry accounts
			$members_fees = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::MEMBER_FEES);
			$operating = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::OPERATING);
			$account = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK);

			// model preparation
			$bt = new Bank_transfer_Model();
			$fee_model = new Fee_Model();

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
			foreach ($this->data as $item)
			{
				// let's identify member
				$member_id = $this->find_member_by_vs(self::remove_zeros($item['vs']));

				if ($item['counter_account'] == 'VKLAD V HOTOVOSTI')
				{
					if (!$member_id)
					{
						// undefined member fee - double-entry incoming transfer
						$transfer_id = Transfer_Model::insert_transfer(
							$members_fees->id, $account->id, null, null,
							$user_id, null, $item['datetime'], $now, $item['message'],
							abs($item['amount'])
						);

						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = null;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = NULL;
						$bt->number = $number;
						$bt->constant_symbol = self::remove_zeros($item['ks']);
						$bt->variable_symbol = self::remove_zeros($item['vs']);
						$bt->specific_symbol = self::remove_zeros($item['ss']);
						$bt->comment = $item['message'];
						$bt->save_throwable();

						// member fee stats
						$stats['member_fees'] += abs($item['amount']);
						$stats['unidentified_nr']++;
					}
					else
					{
						// double-entry incoming transfer
						$transfer_id = Transfer_Model::insert_transfer(
								$members_fees->id, $account->id, null, $member_id, $user_id,
								null, $item['datetime'], $now, "",
								abs($item['amount'])
						);

						//incoming bank transfer
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = null;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = null;
						$bt->number = $number;
						$bt->constant_symbol = self::remove_zeros($item['ks']);
						$bt->variable_symbol = self::remove_zeros($item['vs']);
						$bt->specific_symbol = self::remove_zeros($item['ss']);
						$bt->comment = $item['message'];
						$bt->save_throwable();

						if ($member_id && $member_id != Member_Model::ASSOCIATION)
						{
							$ca = ORM::factory('account')
									->where('member_id', $member_id)
									->find();

							if ($ca->id)
							{
								// add affected member for notification
								$this->add_affected_member($member_id);

								// assign transfer
								$a_transfer_id = Transfer_Model::insert_transfer(
									$account->id, $ca->id, $transfer_id, $member_id,
									$user_id, null, $item['datetime'], $now,
									__('Assigning of transfer'), abs($item['amount'])
								);

								// transaction fee
								$fee = $fee_model->get_by_date_type($item['datetime'], 'transfer fee');
								if ($fee && $fee->fee > 0)
								{
									$tf_transfer_id = Transfer_Model::insert_transfer(
										$ca->id, $operating->id, $transfer_id,
										$member_id, $user_id, null, $item['datetime'],
										$now, __('Transfer fee'), $fee->fee
									);

								}
							}
						}

						// member fee stats
						$stats['member_fees'] += abs($item['amount']);
						$stats['member_fees_nr']++;
					}
				}
				else
				{
					// try to find counter bank account in database
					$counter_ba = ORM::factory('bank_account')->where(array
					(
						'account_nr'	=>	$item['counter_account'],
						'bank_nr'		=>	$item['counter_bank']
					))->find();

					// counter bank account does not exist? let's create new one
					if (!$counter_ba->id)
					{
						$counter_ba->clear();
						$counter_ba->set_logger(FALSE);
						$counter_ba->name = ($item['counter_bank'] == '' ? $item['counter_account'] : self::remove_zeros($item['counter_account']).'/'.$item['counter_bank']);
						$counter_ba->account_nr = ($item['counter_bank'] == '' ? $item['counter_account'] : self::remove_zeros($item['counter_account']));
						$counter_ba->bank_nr = $item['counter_bank'];
						$counter_ba->member_id = NULL;
						$counter_ba->save_throwable();
					}

					if (!$member_id)
					{
						$stats['unidentified_nr']++;
					}

					// double-entry incoming transfer
					$transfer_id = Transfer_Model::insert_transfer(
									$members_fees->id, $account->id, null, $member_id,
									$user_id, null, $item['datetime'], $now, $item['message'],
									abs($item['amount'])
					);

					// incoming bank transfer
					$bt->clear();
					$bt->set_logger(FALSE);
					$bt->origin_id = $counter_ba->id;
					$bt->destination_id = $ba->id;
					$bt->transfer_id = $transfer_id;
					$bt->bank_statement_id = $statement->id;
					$bt->transaction_code = NULL;
					$bt->number = $number;
					$bt->constant_symbol = self::remove_zeros($item['ks']);
					$bt->variable_symbol = self::remove_zeros($item['vs']);
					$bt->specific_symbol = self::remove_zeros($item['ss']);
					$bt->comment = $item['message'];
					$bt->save_throwable();

					// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
					if ($member_id && $member_id != Member_Model::ASSOCIATION)
					{
						$ca = ORM::factory('account')->where('member_id', $member_id)->find();

						// has credit account?
						if ($ca->id)
						{
							// add affected member for notification
							$this->add_affected_member($member_id);

							// assign transfer
							$a_transfer_id = Transfer_Model::insert_transfer(
											$account->id, $ca->id, $transfer_id, $member_id,
											$user_id, null, $item['datetime'], $now,
											__('Assigning of transfer'), abs($item['amount'])
							);

							// transaction fee
							$fee = $fee_model->get_by_date_type($item['datetime'], 'transfer fee');
							if ($fee && $fee->fee > 0)
							{
								$tf_transfer_id = Transfer_Model::insert_transfer(
												$ca->id, $operating->id, $transfer_id,
												$member_id, $user_id, null, $item['datetime'],
												$now, __('Transfer fee'), $fee->fee
								);

							}

							if (!$counter_ba->member_id)
							{
								$counter_ba->member_id = $member_id;
								$counter_ba->save_throwable();
							}
						}
					}

					// member fee stats
					$stats['member_fees'] += abs($item['amount']);
					$stats['member_fees_nr']++;
				}

				$number++;
			}

			// let's check duplicities
			$duplicities = $bt->get_transactions_duplicities($ba->id);

			if ($duplicities)
			{
				$dm = __('Duplicate transactions') . ': ' . implode('; ', $duplicities);
				throw new Duplicity_Exception($dm);
			}

			$settings = Bank_Account_Settings::factory($this->get_bank_account()->type);
			$settings->load_column_data($this->get_bank_account()->settings);

			$key = self::LAST_DOWNLOAD_SETTINGS_KEY;
			$settings->$key = $this->last_download_datetime;

			$ba->settings = $settings->get_column_data();

			$ba->save();

			// done
			$statement->transaction_commit();

			// return
			return $statement;
		}
		catch (Duplicity_Exception $e)
		{
			$statement->transaction_rollback();

			throw $e;
		}
		catch (Exception $e)
		{
			$statement->transaction_rollback();
			Log::add_exception($e);
			$this->add_exception_error($e);

			return NULL;
		}
	}

	/**
	 * Removes zeros on beginning of string (symbols or account)
	 *
	 * @param $value
	 * @return string
	 */
	protected static function remove_zeros($value)
	{
		$count = preg_match_all('/[0-]*(\d+-?\d+)/', $value, $result);

		return ($count == 1 ? $result[1][0] : $value);
	}
}