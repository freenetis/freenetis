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

/**
 * FIO abstract importer that handles storing of transfers. Subclass add method
 * for handling different input file types and format.
 *
 * @author Ondrej Fibich, Jiri Svitak
 * @since 1.1
 */
abstract class Fio_Bank_Statement_File_Importer extends Bank_Statement_File_Importer
{
	/*
	 * Sets last succesfully transfered transaction.
	 * Download of new transaction will start from this transaction.
	 * Transactions are identified by their transaction codes that are stored
	 * in the bank transfer model.
	 *
	 * @Override
	 */
	protected function before_download(Bank_account_Model $bank_account,
			Bank_Account_Settings $settings)
	{
		// get last transaction ID of this bank account that is stored in database
		$bt_model = new Bank_transfer_Model();
		$ltc = $bt_model->get_last_transaction_code_of($bank_account->id);

		if (empty($ltc) || $ltc <= 0)
		{
			$ltc = 0; // no transaction for this account
		}

		// set a start transaction for downloading of next transactions
		$url = $settings->get_download_base_url() . 'set-last-id/'
				. $settings->api_token . '/' . $ltc . '/';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		$response = curl_exec($ch);
		curl_close($ch);

		// done?
		if ($response !== FALSE)
		{
			return TRUE;
		}

		// error in downloading
		$m = __('Setting of the last downloaded transaction has failed');
		throw new Exception($m . ' (' . $response . ')');
	}

	/**
	 * Gets parsed data.
	 *
	 * @return array Contains array of transactions
	 */
	protected abstract function get_parsed_transactions();

	/*
	 * @Override
	 */
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
                $statement->from = $header->dateStart;
                $statement->to = $header->dateEnd;
                $statement->opening_balance = $header->openingBalance;
                $statement->closing_balance = $header->closingBalance;
            }
			$statement->save_throwable();

			/* transactions */

			// preparation of system double-entry accounts
			$suppliers = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::SUPPLIERS);
			$member_fees = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::MEMBER_FEES);
			$operating = ORM::factory('account')->get_account_by_attribute(Account_attribute_Model::OPERATING);

			$account = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK);
			$bank_interests = $ba->get_related_account_by_attribute_id(Account_attribute_Model::BANK_INTERESTS);

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

			// imported transaction codes, to check duplicities
			$transaction_codes = array();

			// saving each bank listing item
			foreach ($this->get_parsed_transactions() as $item)
			{
				// determining in/out type of transfer
				if ($item['castka'] < 0)
				{
                    $counter_ba = $this->get_counter_bank_account(
                            $item['nazev_protiuctu'], $item['protiucet'],
                            $item['kod_banky']
                    );

					// outbound transfer
					// -----------------
					// by default we assume, it is "invoice" (this includes all expenses)
					// double-entry transfer
					$transfer_id = Transfer_Model::insert_transfer(
							$account->id, $suppliers->id, null,
							$counter_ba->member_id, $user_id, null,
							$item['datum'], $now, $item['zprava'], abs($item['castka'])
					);
					// bank transfer
					$bt->clear();
					$bt->set_logger(false);
					$bt->origin_id = $ba->id;
					$bt->destination_id = $counter_ba->id;
					$bt->transfer_id = $transfer_id;
					$bt->bank_statement_id = $statement->id;
					$bt->transaction_code = $item['id_pohybu'];
					$bt->number = $number;
					$bt->constant_symbol = $item['ks'];
					$bt->variable_symbol = $item['vs'];
					$bt->specific_symbol = $item['ss'];
					$bt->save_throwable();
					// stats
					$stats['invoices'] += abs($item['castka']);
					$stats['invoices_nr']++;
				}
				else
				{
					// inbound transfer
					// ----------------

					// interest transfer
					if ($item['typ'] == 'Připsaný úrok')
					{
						// let's create interest transfer
						$transfer_id = Transfer_Model::insert_transfer(
								$bank_interests->id, $account->id, null, null,
								$user_id, null, $item['datum'], $now, $item['typ'],
								abs($item['castka'])
						);
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = null;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = $item['id_pohybu'];
						$bt->number = $number;
						$bt->save_throwable();
						$stats['interests'] += abs($item['castka']);
						$stats['interests_nr']++;
					}
					elseif ($item['typ'] == 'Vklad pokladnou')
					{
						$member_id = $this->find_member_by_vs($item['vs']);

						if (!$member_id)
						{
							// undefined member fee - double-entry incoming transfer
                            $transfer_id = Transfer_Model::insert_transfer(
                                    $member_fees->id, $account->id, null,
                                    null, $user_id, null, $item['datum'], $now,
                                    $item['zprava'], abs($item['castka'])
                            );

							$bt->clear();
							$bt->set_logger(false);
							$bt->origin_id = null;
							$bt->destination_id = $ba->id;
							$bt->transfer_id = $transfer_id;
							$bt->bank_statement_id = $statement->id;
							$bt->transaction_code = $item['id_pohybu'];
							$bt->number = $number;
							$bt->constant_symbol = $item['ks'];
							$bt->variable_symbol = $item['vs'];
							$bt->specific_symbol = $item['ss'];
                            $bt->comment = $item['zprava'];
							$bt->save_throwable();

                            $stats['member_fees'] += abs($item['castka']);
                            $stats['unidentified_nr']++;
						}
						else
						{
							// double-entry incoming transfer
							$transfer_id = Transfer_Model::insert_transfer(
									$member_fees->id, $account->id, null, $member_id,
									$user_id, null, $item['datum'], $now, $item['zprava'],
									abs($item['castka'])
							);
							// incoming bank transfer
							$bt->clear();
							$bt->set_logger(false);
							$bt->origin_id = null;
							$bt->destination_id = $ba->id;
							$bt->transfer_id = $transfer_id;
							$bt->bank_statement_id = $statement->id;
							$bt->transaction_code = $item['id_pohybu'];
							$bt->number = $number;
							$bt->constant_symbol = $item['ks'];
							$bt->variable_symbol = $item['vs'];
							$bt->specific_symbol = $item['ss'];
                            $bt->comment = $item['zprava'];
							$bt->save_throwable();

							// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
							if ($member_id && $member_id != Member_Model::ASSOCIATION)
							{
								$ca = ORM::factory('account')
										->where('member_id', $member_id)
										->find();

								// has credit account?
								if ($ca->id)
								{
									// add affected member for notification
									$this->add_affected_member($member_id);

									// assigning transfer
									$a_transfer_id = Transfer_Model::insert_transfer(
											$account->id, $ca->id, $transfer_id, $member_id,
											$user_id, null, $item['datum'], $now,
											__('Assigning of transfer'), abs($item['castka'])
									);

									// transaction fee
									$fee = $fee_model->get_by_date_type(
											$item['datum'], 'transfer fee'
									);
									if ($fee && $fee->fee > 0)
									{
										$tf_transfer_id = Transfer_Model::insert_transfer(
												$ca->id, $operating->id, $transfer_id,
												$member_id, $user_id, null, $item['datum'],
												$now, __('Transfer fee'), $fee->fee
										);
									}
								}
							}
							// member fee stats
							$stats['member_fees'] += abs($item['castka']);
							$stats['member_fees_nr']++;
						}
					}
					// otherwise we assume that it is member fee
					else
					{
                        $counter_ba = $this->get_counter_bank_account(
                                $item['nazev_protiuctu'], $item['protiucet'],
                                $item['kod_banky']
                        );

						// let's identify member
						$member_id = $this->find_member_by_vs($item['vs']);

						if (!$member_id)
						{
							$stats['unidentified_nr']++;
						}

						// double-entry incoming transfer
						$transfer_id = Transfer_Model::insert_transfer(
								$member_fees->id, $account->id, null, $member_id,
								$user_id, null, $item['datum'], $now, $item['zprava'],
								abs($item['castka'])
						);
						// incoming bank transfer
						$bt->clear();
						$bt->set_logger(false);
						$bt->origin_id = $counter_ba->id;
						$bt->destination_id = $ba->id;
						$bt->transfer_id = $transfer_id;
						$bt->bank_statement_id = $statement->id;
						$bt->transaction_code = $item['id_pohybu'];
						$bt->number = $number;
						$bt->constant_symbol = $item['ks'];
						$bt->variable_symbol = $item['vs'];
						$bt->specific_symbol = $item['ss'];
                        $bt->comment = $item['zprava'];
						$bt->save_throwable();

						// assign transfer? (0 - invalid id, 1 - assoc id, other are ordinary members)
						if ($member_id && $member_id != Member_Model::ASSOCIATION)
						{
							$ca = ORM::factory('account')
									->where('member_id', $member_id)
									->find();

							// has credit account?
							if ($ca->id)
							{
								// add affected member for notification
								$this->add_affected_member($member_id);

								// assigning transfer
								$a_transfer_id = Transfer_Model::insert_transfer(
										$account->id, $ca->id, $transfer_id, $member_id,
										$user_id, null, $item['datum'], $now,
										__('Assigning of transfer'), abs($item['castka'])
								);

								// transaction fee
								$fee = $fee_model->get_by_date_type(
										$item['datum'], 'transfer fee'
								);
								if ($fee && $fee->fee > 0)
								{
									$tf_transfer_id = Transfer_Model::insert_transfer(
											$ca->id, $operating->id, $transfer_id,
											$member_id, $user_id, null, $item['datum'],
											$now, __('Transfer fee'), $fee->fee
									);
								}
								// do not change owner if there is already
								// one (#800)
								if (!$counter_ba->member_id)
								{
									$counter_ba->member_id = $member_id;
									$counter_ba->save_throwable();
								}
							}
						}
						// member fee stats
						$stats['member_fees'] += abs($item['castka']);
						$stats['member_fees_nr']++;
					}

				}

				// add item transaction code to array to check duplicities later
				$transaction_codes[] = $item['id_pohybu'];

				// line number increase
				$number++;
			}

			// let's check duplicities
			$duplicities = $bt->get_transaction_code_duplicities($transaction_codes, $ba->id);

			if (count($duplicities) > count($transaction_codes))
			{
				$dm = __('Duplicate transaction codes') . ': ' . implode(', ', $duplicities);
				throw new Duplicity_Exception($dm);
			}

			// done
			$statement->transaction_commit();

			// return
			return $statement;
		}
		catch (Duplicity_Exception $e)
		{
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
     * Find or create counter bank account with given properties. Existing
     * account is searched only agains number.
     *
     * @param string $name account name for new account
     * @param string $account_nr account number
     * @param string $bank_nr bank number
     * @return Bank_account_Model
     */
    protected function get_counter_bank_account($name, $account_nr, $bank_nr)
    {
        // try to find counter bank account in database
        $ba = ORM::factory('bank_account')->where(array
        (
            'account_nr'	=> $account_nr,
            'bank_nr'		=> $bank_nr
        ))->find();

        // counter bank account does not exist? let's create new one
        if (!$ba->id)
        {
            $ba = Bank_account_Model::create($name, $account_nr, $bank_nr);
        }

        return $ba;
    }

}
