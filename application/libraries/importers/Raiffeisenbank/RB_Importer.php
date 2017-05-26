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

require_once "RB_Statistics.php";
require_once APPPATH."libraries/importers/Duplicity_Exception.php";

/**
 * Raiffeisenbank importer, saves listing items into Freenetis database
 */
class RB_Importer
{

	/**
	 * Parsed bank account
	 *
	 * @var ORM
	 */
	public static $parsed_bank_acc;
	
	/**
	 * Bank statement id
	 *
	 * @var integer
	 */
	public static $bank_statement_id;
	
	/**
	 * User id
	 *
	 * @var integer
	 */
	public static $user_id;
	
	/**
	 * Datetime
	 *
	 * @var string
	 */
	public static $time_now;

	/**
	 * Funkce store_transfers_ebanka se používá jako callback funkce pro Parser_Ebanka.
	 * Třída Parser_Ebanka tuto funkci volá s každou načtenou položkou výpisu.
	 * Jednotlivé položky se pak uvnitř této funkce ukládají do databáze.
	 * Viz http://wiki.freenetis.slfree.net/index.php/Soubor:Hospodareni.pdf
	 *
	 * @author Tomas Dulik, Jiri Svitak
	 * @param data - objekt s následujícími položkami:
	 *  parsed_acc_nr => 184932848 //cislo parsovaneho uctu
	 *  parsed_acc_bank_nr=> 2400	//cislo banky parsovaneho uctu
	 *  number => 1 					//cislo vypisu
	 *  date_time => 2008-03-25 05:40  //datum a cas
	 *  comment => Rozpis polozek uveden v soupisu prevodu
	 *  name => CESKA POSTA, S.P.
	 *  account_nr => 160987123
	 *  account_bank_nr = 0300
	 *  type => Příchozí platba
	 *  variable_symbol => 9081000001
	 *  constant_symbol => 998
	 *  specific_symbol => 9876543210
	 *  amount => 720.00
	 *  fee => -6.90
	 * @param integer $notification_email State of e-mail notification
	 * @param integer $notification_sms State of SMS notification
	 *
	 */
	public function store_transfer_ebanka(
			$data = null, $notification_email = Notifications_Controller::KEEP,
			$notification_sms = Notifications_Controller::KEEP)
	{
		// param check
		if (!$data || !is_object($data))
		{
			Controller::warning(PARAMETER);
		}
		
		/** zde jsou statické objekty, jejichž instance tvořím jen jednou u importu prvního řádku
		 * výpisu (šetříme paměť...)
		 * */
		static $acc_model, $bank_acc_model, $member_model, $fee_model, $parsed_acc;
		static $bank_interests, $bank_fees, $time_deposits_interests, $time_deposits;
		static $suppliers, $operating, $member_fees, $cash;
		static $first_pass = true;

		if ($first_pass)
		{   // dostavame prvni radek vypisu?
			$this->stats = new RB_Statistics();
			self::$time_now = date("Y-m-d H:i:s");
			$member_model = new Member_Model(); // vytvorime vsechny instance, ktere potrebujeme i pro dalsi radky
			$acc_model = new Account_Model();
			$bank_acc_model = new Bank_account_Model();
			$fee_model = new Fee_Model();
			$ebank_nrs = array("2400", "5500");
			
			if (!isset(self::$parsed_bank_acc))
			{ // mame jiz parsovany ucet v DB?
				//  (tato promenna bude nastavena pouze pokud se parsuje
				//  ucet zvoleny v gridu uzivatelem)
				// parsovany ucet dopredu nezname. Je v parsovanem vypisu?
				//  (je, pokud to neni transparentni vypis ebanky)
				// this should never happen! Freenetis should not create some new accounts
				// without admin's aware! Bank account of association should always
				// already be in the database (jsvitak)
				echo "parsed_bank_acc not set";
				die();

				if (isset($data->parsed_acc_nr) && isset($data->parsed_acc_bank_nr))
				{
					if (in_array($data->parsed_acc_bank_nr, $ebank_nrs))
					// u ebanky probehla zmena kodu banky...
						$bank_nr = "5500 (2400)";
					else
						$bank_nr = $data->parsed_acc_bank_nr;

					self::$parsed_bank_acc = ORM::factory('bank_account')
							->where(array
							(
								'account_nr' => $data->parsed_acc_nr,
								'bank_nr' => $bank_nr
							))->find();

					if (!self::$parsed_bank_acc->id)
					{ // parsovany ucet zatim neexistuje?
						// tak si ho vytvorime
						$acc_name = "$data->parsed_acc_nr/$bank_nr";
						
						$parsed_acc = Account_Model::create(
								Account_attribute_Model::BANK, $acc_name, 1
						);
						
						self::$parsed_bank_acc = Bank_account_Model::create(
								$acc_name, $data->parsed_acc_nr, $bank_nr, 1
						);
						
						$parsed_acc->add(self::$parsed_bank_acc);
					}
				}
				else
				{ // if (isset($data->parsed_acc_nr) ... ve výpisu není číslo parsovaného účtu = kritická chyba
					status::error('The parsed account is unknown.');
					return;
				}
			}
			else if (isset($data->parsed_acc_nr) && isset($data->parsed_acc_bank_nr) &&
					!(
						$data->parsed_acc_nr == self::$parsed_bank_acc->account_nr && // cisla uctu odpovidaji
						(
							$data->parsed_acc_bank_nr == self::$parsed_bank_acc->bank_nr || // cisla bank odpovidaji nebo
							in_array($data->parsed_acc_bank_nr, $ebank_nrs) && // jsou obe 2400 nebo 5500
							in_array(self::$parsed_bank_acc->bank_nr, $ebank_nrs)
						)
					))
			{
				throw new Kohana_User_Exception('Chyba', 'Importovaný výpis není z vybraného účtu!');
			}

			// @todo tato chyba nema byt Exception, ale normalni hlášení
			if (!isset($parsed_acc))
			{
				$parsed_acc = self::$parsed_bank_acc->get_related_account_by_attribute_id(
						Account_attribute_Model::BANK
				);

				if ($parsed_acc === FALSE)
				// tohle by normálně nemělo nastat.
				// může se to stát pouze pokud někdo smaže vazbu bank. účet sdružení
				// s podvojným účtem přes tabulku accounts_bank_accounts
				{
					throw new Kohana_User_Exception(
							'Kritická chyba',
							'V tabulce accounts_bank_accounts chybí vazba ' .
							'bankovního a podvojného účtu sdružení'
					);
				}
			}
			
			// Teď potřebujeme najít nebo vytvořit speciální podvojné účty k parsovanému bank. učtu:
			$spec_accounts = array
			(
				Account_attribute_Model::BANK_INTERESTS				=> array
				(
					"bank_interests",
					"Úroky z $parsed_acc->name",
				),
				Account_attribute_Model::TIME_DEPOSITS_INTERESTS	=> array
				(
					"time_deposits_interests",
					"Úroky z termín. vkladů $parsed_acc->name",
				),
				Account_attribute_Model::TIME_DEPOSITS				=> array
				(
					"time_deposits",
					"Termínované vklady $parsed_acc->name",
				),
				Account_attribute_Model::BANK_FEES					=> array
				(
					"bank_fees",
					"Poplatky z $parsed_acc->name",
				)
			);

			foreach ($spec_accounts as $accnt_attr => $name)
			{
				$spec_acc = self::$parsed_bank_acc->get_related_account_by_attribute_id(
						$accnt_attr
				);

				if (!$spec_acc || !$spec_acc->id)
				{  // pokud spec. ucet neexistuje, pak si jej vytvorime
					$spec_acc = Account_Model::create($accnt_attr, $name[1], 1);
					$spec_acc->add(self::$parsed_bank_acc);
					$spec_acc->save();
				}
				${$name[0]} = $spec_acc;
			}

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

			if (!$suppliers->id || !$member_fees->id || !$operating->id)
			{
				throw new Kohana_User_Exception(
						'Kritická chyba',
						'V DB chybí účet member_fees, suppliers nebo operating'
				);
			}

			$first_pass = FALSE;
		}
		else
		{
			$this->stats->linenr++;
		}

		if (!empty($data->fee))
		{
			$fee = abs($data->fee);
			$this->stats->bank_fees+= - $data->fee;
			$this->stats->bank_fees_nr++;
		}

		// ********************** Tak a jdeme tvořit transakce *********************
		$vs = trim($data->variable_symbol);
		
		if (empty($data->amount))
		{
			// ****** Bankovní poplatky: ebanka má v řádku výpisu pouze poplatek, ale castka==0
			// vytvoříme transakci "bankovní poplatek z 221000 (bank. účty) na 549001 (bank. poplatky)
			//a bankovní transakci z parsovaného účtu na null. Přiřadíme ji sdružení (member_id=1).
			if (empty($data->comment))
				$data->comment = $data->type;
			
			if ($data->fee < 0)
			{
				$this->create_transfers(
						$parsed_acc, $bank_fees, $fee, self::$parsed_bank_acc, null, $data, 1
				);
			}
			else // poplatek>0 - storno poplatku (stalo se 1x v celé historii našeho sdružení)
			{
				$this->create_transfers(
						$bank_fees, $parsed_acc, $fee, self::$parsed_bank_acc, null, $data, 1
				);
			}
		}
		// castka je nenulova:
		else if (empty($data->fee) && stripos($data->type, "rok") !== FALSE)
		{
			// *****  úroky u ebanky: amount!=0, fee==0, type je "Úrok", "Kladný úrok", "Převedení úroku po kapitalizaci TV"
			// Vytvoříme transakci z 644000 nebo 655000 (uroky) na 221000
			// a bankovní transakci z null na parsovaný účet. Přiřadíme ji sdružení (member_id=1)
			if (empty($vs))   // běžný úrok? (644000)
			{
				$this->create_transfers(
						$bank_interests, $parsed_acc, $data->amount, null, self::$parsed_bank_acc, $data, 1
				);
			}
			else	// úrok z termínovaného vkladu (655000)
			{
				$this->create_transfers(
						$time_deposits_interests, $parsed_acc, $data->amount, null, self::$parsed_bank_acc, $data, 1
				);
			}

			$this->stats->interests += $data->amount;
			$this->stats->interests_nr++;
		}
		else
		{
			// ****** nejběžnější případ:
			// - členský příspěvek, platba faktury dodavatelum, termín. vklad, výběr hotovosti   ******
			// Nejdriv zkusím najít majitele bankovního protiúčtu
			$ks = trim($data->constant_symbol);
			$term_vklad = ($ks == "968");
			$member_model->clear();
			$member = $member_model;
			
			if (!$term_vklad && $data->amount > 0 && !empty($vs))
			{ //u čl. příspěvků zkusíme najít původce:
				// členský příspěvek nebo příjem z faktury odběrateli
				// @todo zpracování jiných typů VS u člen. příspěvků (např. ID+CRC16)
				// uvedl člen u teto platby jako variabilni symbol (VS) svůj telefon ?

				$variable_symbol_model = new Variable_Symbol_Model();
				$member = $variable_symbol_model->where('variable_symbol',$vs)->find()->account->member;
				
				if (!$member->id)
				{
					$member = $member_model;
					$this->stats->unidentified_transfers++;
				}
			}
			// else { // if platba přijaté faktury - majitele účtu najdeme dle VS na faktuře, až budeme mít modul přijatých faktur}
			// ***Tady si vytvorime instanci účtu clena (nebo dodavatele) z prave nacteneho vypisu:
			$bank_acc = $bank_acc_model->where(array
					(
						'account_nr' => $data->account_nr,
						'bank_nr' => $data->account_bank_nr
					))->find();

			if (!$bank_acc->id)
			{  // bank. ucet clena neexistuje, tak si ho vytvorime
				$bank_acc->clear();
				$bank_acc->set_logger(FALSE);
				//term. vklad je vždy způsoben sdružením
				$member_idd = ($term_vklad ? 1 : $member->id);
				$bank_acc->member_id = $member_idd != 0 ? $member_idd : NULL;
				$bank_acc->name = $data->name;
				$bank_acc->account_nr = $data->account_nr;
				$bank_acc->bank_nr = $data->account_bank_nr;
				$bank_acc->save();
				$this->stats->new_bank_accounts++;
				//tuto vazbu bych tvořil jen pokud bych chtěl evidovat pohyby na bank. účtech členů
				// $bank_acc->add_account($member_fees);
			}
			if ($data->amount < 0)
			{
				$amount = abs($data->amount);
				if ($term_vklad)
				{ // převod peněz na účet term. vkladu
					$id = $this->create_transfers(
							$parsed_acc, $time_deposits, $amount,
							self::$parsed_bank_acc, $bank_acc,
							$data, $member->id, null
					);
					$this->stats->time_deposits+=$amount;
					$this->stats->time_deposits_nr++;
				}
				else
				{
					if (stripos($data->type, "hotovost") !== FALSE)
					{ // výběr do pokladny ?
						$id = $this->create_transfers(
								$parsed_acc, $cash, $amount,
								self::$parsed_bank_acc, null,
								$data, $member->id, null
						);
						$this->stats->cash_drawn+=$amount;
						$this->stats->cash_drawn_nr++;
					}
					else
					{
						// úhrada faktury - z 221000 (bank. účet) na 321000 (dodavatelé)
						// pokud se předtím nepodařilo najít majitele dle VS
						if (!$member->id && $bank_acc->member_id)
						{ // zkusím ho vzít odsud
							$member = $member_model->find($bank_acc->member_id);
						}

						$id = $this->create_transfers(
								$parsed_acc, $suppliers, $amount,
								self::$parsed_bank_acc, $bank_acc,
								$data, $member->id
						);

						$this->stats->invoices+=$amount;
						$this->stats->invoices_nr++;
					}
				} // if ($term_vklad) ... else
				if (!empty($fee))
				{
					// je tam bankovní poplatek - vytvoříme:
					// - podvojnou transakci z 221000 (bank. účty) na 549001 (bank. poplatky)
					// - bankovní transakci z parsovaného účtu na null
					$data->comment = "Bank. poplatek" . (!empty($data->comment) ? " ($data->comment)" : "");
					
					$this->create_transfers(
							$parsed_acc, $bank_fees, $fee,
							self::$parsed_bank_acc, null,
							$data, $member->id, $id
					);
				}
			}
			else
			{  // $data->amount > 0
				if ($term_vklad)
				{ // stažení peněz z účtu term. vkladu
					$id = $this->create_transfers(
							$time_deposits, $parsed_acc,
							$data->amount, $bank_acc, self::$parsed_bank_acc,
							$data, $member->id, null
					);
					$this->stats->time_deposits_drawn+=$data->amount;
					$this->stats->time_deposits_drawn_nr++;
				}
				else
				{
					// členský příspěvek - vytvoříme:
					// - podvojnou transakci z 684000 na 221000
					// - bankovní transakci z bank. účtu člena na bank. účet sdružení
					$id = $this->create_transfers(
							$member_fees, $parsed_acc, $data->amount,
							$bank_acc, self::$parsed_bank_acc,
							$data, $member->id
					);

					if (!empty($fee))
					{
						// bankovní poplatek - vytvoříme:
						// - podvojnou transakci z 221000 (bank. účty) na 549001 (bank. poplatky)
						// - bankovní transakci z parsovaného účtu na null
						$data->comment = "Bank. poplatek" . (!empty($data->comment) ? " ($data->comment)" : "");
						$this->create_transfers(
								$parsed_acc, $bank_fees, $fee,
								self::$parsed_bank_acc, null, $data,
								$member->id, $id
						);
						// naše správní rada si vymyslela, že poplatek budeme dotovat z operačního účtu
						// (pokud máte ve správní radě rozumnější lidi, tak tento řádek zakomentujte :-)
						$this->create_transfer(
								$operating, $parsed_acc, 
								abs($data->fee), $data->date_time,
								"Bank. poplatek hrazený sdružením",
								$member->id, $id
						);
					}

					if ($member->id)
					{ // původce je známý?
						// **** převedeme peníze členovi na jeho účet s kreditem
						// ten účet ale musíme najít nebo vytvořit:
						$credit_acc = $acc_model->where(array
								(
									'member_id'				=> $member->id,
									'account_attribute_id'	=> Account_attribute_Model::CREDIT)
								)->find();

						if (!$credit_acc->id)
						{
							$credit_acc->clear();
							$credit_acc->set_logger(FALSE);
							$credit_acc->account_attribute_id = Account_attribute_Model::CREDIT;
							$credit_acc->member_id = $member->id;
							/**
							 * @todo Jirka pri tvorbe uctu jako jmeno uctu pouziva
							 * prijmeni jmeno majitele. To se mi tady nechce programovat,
							 * protoze se jen tezko muze stat, ze by kreditni ucet neexistoval
							 */
							$credit_acc->name = $member->name;
							$credit_acc->save();
						}

						$this->create_transfer(
								$parsed_acc, $acc_model, $data->amount,
								$data->date_time, "Přiřazení platby",
								$member->id, $id
						);

						// teď se podíváme, jestli v té době sdružení
						// účtovalo poplatek za zpracování platby:
						$fee = $fee_model->get_by_date_type(
								$data->date_time, 'transfer fee'
						);

						if (is_object($fee) && $fee->id)   // ano? Pak poplatek strhneme z účtu
						{
							$this->create_transfer(
									$credit_acc, $operating, $fee->fee,
									$data->date_time, "Transakční poplatek",
									$member->id, $id
							);
						}
						
						/**
						 * Send information about received payment to member
						 * 
						 * @author Ondřej Fibich
						 * @since 1.1
						 */
						try
						{
							Message_Model::activate_special_notice(
									Message_Model::RECEIVED_PAYMENT_NOTICE_MESSAGE,
									$member->id, self::$user_id,
									$notification_email, $notification_sms
							);
						}
						catch (Exception $e)
						{
							Log::add_exception($e);
						}
					}  // if (is_object($member) && $member->id)
					$this->stats->member_fees_nr++;
					$this->stats->member_fees+=$data->amount;
				} // if ($term_vklad) ... else {
			}  // else {	// $data->amount > 0
		} // else { 	// ****** castka!=0 && poplatek !=0
	}

	/**
	 * Creates transfer
	 *
	 * @param Account_Model $src
	 * @param Account_Model $dst
	 * @param double $amount
	 * @param string $datetime
	 * @param string $text
	 * @param integer $member_id
	 * @param integer $prev_id
	 * @return integer	ID of created transfer or FALSe on error
	 */
	private static function create_transfer(
			$src, $dst, $amount, $datetime,
			$text, $member_id = null, $prev_id = null)
	{
		// safe transfer saving
		return Transfer_Model::insert_transfer(
				$src->id, $dst->id, $prev_id, $member_id,
				self::$user_id, null, $datetime,
				self::$time_now, $text, $amount
		);
	}

	/**
	 * Create transfers
	 *
	 * @param Account_Model $src_acc
	 * @param Account_Model $dst_acc
	 * @param double $amount
	 * @param type $src_bank_acc
	 * @param type $dst_bank_acc
	 * @param type $data
	 * @param type $member_id
	 * @param type $prev_id
	 * @return integer
	 */
	private static function create_transfers(
			$src_acc, $dst_acc, $amount, $src_bank_acc,
			$dst_bank_acc, $data, $member_id = null, $prev_id = null)
	{
		// duplicity check - in case of duplicity all already imported items are storned
		$bank_transfer = new Bank_transfer_Model();
		$bank_transfer->set_logger(FALSE);
		$dups = $bank_transfer->get_duplicities($data);

		if ($dups->count() > 0)
			throw new Duplicity_Exception();

		// safe transfer saving
		$transfer_id = Transfer_Model::insert_transfer(
				$src_acc->id, $dst_acc->id, $prev_id, $member_id,
				self::$user_id, null, $data->date_time, self::$time_now,
				$data->comment, $amount
		);
		// bank transfer saving
		$bank_transfer->clear();
		$bank_transfer->transfer_id = $transfer_id;
		$bank_transfer->origin_id = isset($src_bank_acc) ? $src_bank_acc->id : null;
		$bank_transfer->destination_id = isset($dst_bank_acc) ? $dst_bank_acc->id : null;
		$bank_transfer->bank_statement_id = self::$bank_statement_id;
		$bank_transfer->number = $data->number;
		$bank_transfer->variable_symbol = $data->variable_symbol;
		$bank_transfer->constant_symbol = $data->constant_symbol;
		$bank_transfer->specific_symbol = $data->specific_symbol;
		$bank_transfer->save_throwable();

		return $transfer_id;
	}

}

