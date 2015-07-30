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

require_once APPPATH."libraries/importers/Raiffeisenbank/RB_Importer.php";
require_once APPPATH."libraries/importers/Raiffeisenbank/Parser_Ebanka.php";
require_once APPPATH."libraries/importers/Fio/FioImport.php";
require_once APPPATH."libraries/importers/Fio/FioSaver.php";


/**
 * Handles importing of all types of bank listings into the database.
 * 
 * @author Tomas Dulik, Jiri Svitak
 * @package Controller
 */
class Import_Controller extends Controller
{
	// static constants of supported bank listing types
	const HTML_RAIFFEISENBANK = 1;
	const CSV_FIO = 2;

	private static $types = array();

	/**
	 * Contruct - check if upload dir is writable
	 */
	public function __construct()
	{
		parent::__construct();

		// supported bank listings
		self::$types = array();
		self::$types[self::HTML_RAIFFEISENBANK] = 'HTML Raiffeisenbank';
		self::$types[self::CSV_FIO] = 'CSV Fio';

                //if (file_exists('config.php'))
                //{echo 'config.php'; die();}

		if (!is_writable('upload'))
		{
			Controller::error(WRITABLE, __(
					'Directory "upload" is not writable, change access rights.'
			));
		}

	}

	/**
	 * Automatic redirect
	 */
	public function index()
	{
		url::redirect(url::base());
	}

	/**
	 * Uploads bank files.
	 *
	 * @author Jiri Svitak
	 * @param integer $id
	 */
	public function upload_bank_file($id = null)
	{
		if (!isset($id))
			Controller::warning(PARAMETER);

		if (!$this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			Controller::error(ACCESS);

		$bank_acc_model = new Bank_account_Model($id);

		if ($bank_acc_model->id == 0)
			Controller::error(RECORD);

		// form	
		$form = new Forge('import/upload_bank_file/' . $id);
		
		$form->group('File type');

		$form->dropdown('type')
				->label('File type')
				->options(self::$types)
				->rules('required')
				->style('width:200px');

		$form->upload('listing', TRUE)
				->label('File with bank transfer listing')
				->rules('required');
		
		$form->checkbox('clean_whitelist')
				->value('1')
				->label(__('Clean temporary whitelist').' '.help::hint('clean_temp_whitelist'))
				->checked('checked');
		
		$form->submit('Submit');

		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			switch ($form_data['type'])
			{
					case self::HTML_RAIFFEISENBANK:
						$this->parse_ebank_account($id, $form->listing->value, $form_data['clean_whitelist']);
						break;
					case self::CSV_FIO:
						$this->import_fio($id, $form->listing->value, $form_data['clean_whitelist']);
						break;
					default:
						break;
			}
		}
		else
		{
			// breadcrubs
			$breadcrumbs = breadcrumbs::add()
					->link('members/show/1', 'Profile of association',
							$this->acl_check_view('Members_Controller', 'members'))
					->link('bank_accounts/show_all', 'Bank accounts',
							$this->acl_check_view('Accounts_Controller', 'bank_accounts'))
					->text($bank_acc_model->name)
					->text('Upload bank transfers listing')
					->html();
			
			// view
			$title = __('Upload bank transfers listing');
			$view = new View('main');
			$view->title = $title;
			$view->breadcrumbs = $breadcrumbs;
			$view->content = new View('form');
			$view->content->form = $form->html();
			$view->content->headline = $title;
			$view->render(TRUE);
		}
	}

	/**
	 * Imports fio bank listing items from specified file.
	 * 
	 * @author Jiri Svitak
	 * @param string $file_url
	 */
	private function import_fio($bank_account_id, $file_url, $clean_whitelist)
	{
		try
		{
			$db = new Transfer_Model();
			$db->transaction_start();

			// parse bank listing items
			$data = FioImport::getDataFromFile($file_url);
			// get header
			$header = FioImport::getListingHeader();
			// does match bank account in system with bank account of statement?
			$ba = new Bank_account_Model($bank_account_id);
			if ($ba->account_nr != $header["account_nr"] &&
				$ba->bank_nr != $header["bank_nr"])
			{
				$ba_nr = $ba->account_nr."/".$ba->bank_nr;
				$listing_ba_nr = $header["account_nr"]."/".$header["bank_nr"];
				throw new FioException(__(
						"Bank account number in listing (%s) header does not match " .
						"bank account %s in database!", array($listing_ba_nr, $ba_nr)
				));
			}

			// save bank statement
			$statement = new Bank_statement_Model();
			$statement->set_logger(FALSE);
			$statement->bank_account_id = $bank_account_id;
			$statement->user_id = $this->session->get('user_id');
			$statement->type = self::$types[self::CSV_FIO];
			$statement->from = $header["from"];
			$statement->to = $header["to"];
			$statement->opening_balance = $header["opening_balance"];
			$statement->closing_balance = $header['closing_balance'];
			$statement->save_throwable();

			// save bank listing items
			$stats = FioSaver::save(
					$data, $bank_account_id, $statement->id,
					$this->session->get('user_id')
			);

			// clean temporary whitelist, members should have payed, now
			// they are no longer protected from redirection messages by whitelisting			
			if ($clean_whitelist)
			{
				$ip_model = new Ip_address_Model();
				$ip_model->clean_temporary_whitelist();
				
				$users_contacts_model = new Users_contacts_Model();
				$users_contacts_model->clean_temporary_whitelist();
			}

			$db->transaction_commit();
		}
		catch (FioException $e)
		{
			$db->transaction_rollback();
			status::error(__('Import has failed.') . ' ' .
					$e->getMessage(),
					FALSE
			);
			url::redirect('bank_accounts/show_all');
		}
		catch (Duplicity_Exception $e)
		{
			$db->transaction_rollback();
			status::error(
					__('Import has failed.') . ' ' .
					__('Bank statement contains items that were already imported.') . ' ' .
					$e->getMessage(),
					FALSE
			);
			url::redirect('bank_accounts/show_all');
		}
		catch (Exception $e)
		{
			$db->transaction_rollback();
			Log::add_exception($e);
			status::error(
					__('Import has failed') . '.<br>' . $e->getMessage(),
					FALSE
			);
			url::redirect('bank_accounts/show_all');
		}

		url::redirect('bank_transfers/show_by_bank_statement/'.$statement->id);
	}

	/**
	 * Parse ebank account
	 * 
	 * @author Jiri Svitak
	 * @param integer $account_id	ID of the account whose data will be parsed.
	 * @param string $url			URL containing the file to parse
	 */
	private function parse_ebank_account($bank_account_id, $url, $clean_whitelist)
	{
		try
		{
			$db = new Transfer_Model();
			$db->transaction_start();

			$parser = new Parser_Ebanka();
			if (isset($bank_account_id))
			{
				$ba = new Bank_account_Model($bank_account_id);
				RB_Importer::$parsed_bank_acc = $ba;
			}
			else
			{
				throw new RB_Exception("Nebyl nastaven bankovní účet k importu!");
			}

			$statement = new Bank_statement_Model();
			$statement->set_logger(FALSE);
			$statement->bank_account_id = $bank_account_id;
			$statement->user_id = $this->session->get('user_id');
			$statement->type = self::$types[self::HTML_RAIFFEISENBANK];
			$statement->save_throwable();

			RB_Importer::$bank_statement_id = $statement->id;
			RB_Importer::$user_id = $this->session->get('user_id');
			RB_IMporter::$time_now = date("Y-m-d H:i:s");

			// safe import is done by transaction processing started in method which called this one
			$parser->parse($url);

			// does imported bank account and bank account on the statement match?
			if ($ba->account_nr != $parser->account_nr &&
				$ba->bank_nr != $parser->bank_nr)
			{
				$ba_nr = $ba->account_nr."/".$ba->bank_nr;
				$listing_ba_nr = $parser->account_nr."/".$parser->bank_nr;
				throw new RB_Exception(__(
						"Bank account number in listing (%s) header does not match " .
						"bank account %s in database!", array($listing_ba_nr, $ba_nr)
				));
			}

			// save statement's from and to
			$statement->from = $parser->from;
			$statement->to = $parser->to;
			// save statement number
			$statement->statement_number = $parser->statement_number;
			// save starting and ending balance
			$statement->opening_balance = $parser->opening_balance;
			$statement->closing_balance = $parser->closing_balance;
			$statement->save_throwable();

			// clean temporary whitelist, members should have payed, now
			// they are no longer protected from redirection messages by whitelisting			
			if ($clean_whitelist)
			{
				$ip_model = new Ip_address_Model();
				$ip_model->clean_temporary_whitelist();
				
				$users_contacts_model = new Users_contacts_Model();
				$users_contacts_model->clean_temporary_whitelist();
			}
			$db->transaction_commit();
		}
		
		catch (RB_Exception $e)
		{
			$db->transaction_rollback();
			status::error(__('Import has failed.') . ' ' .
					$e->getMessage(),
					FALSE
			);
			url::redirect('bank_accounts/show_all');
		}
		catch (Duplicity_Exception $e)
		{
			$db->transaction_rollback();
			status::error(
					__('Import has failed.') . ' ' .
					__('Bank statement contains items that were already imported.') . ' ' .
					$e->getMessage(),
					FALSE
			);
			url::redirect('bank_accounts/show_all');
		}
		catch (Exception $e)
		{
			$db->transaction_rollback();
			Log::add_exception($e);
			status::error(
					__('Import has failed') . '.<br>' . $e->getMessage(),
					FALSE
			);
			url::redirect('bank_accounts/show_all');
		}

		url::redirect('bank_transfers/show_by_bank_statement/'.$statement->id);

	}

}
