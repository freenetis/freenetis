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
require_once APPPATH."libraries/importers/Unicredit/UnicreditImport.php";
require_once APPPATH."libraries/importers/Unicredit/UnicreditSaver.php";


/**
 * Handles importing of all types of bank listings into the database.
 * 
 * @author Tomas Dulik, Jiri Svitak
 * @package Controller
 */
class Import_Controller extends Controller
{
	/**
	 * Types of import documents (supported bank listings)
	 *
	 * @var array
	 */
	private static $file_types = array
	(
		Bank_account_Model::TYPE_RAIFFEISENBANK	=> 'HTML Raiffeisenbank',
		Bank_account_Model::TYPE_FIO			=> 'Fio CSV',
		Bank_account_Model::TYPE_UNICREDIT		=> 'Unicredit CSV'
	);

	/**
	 * Contruct - check if finance is enabled and upload dir is writable
	 */
	public function __construct()
	{
		parent::__construct();
		
		if (!Settings::get('finance_enabled'))
			Controller::error(ACCESS);

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
	public function upload_bank_file($id = NULL)
	{
		if (!isset($id))
			Controller::warning(PARAMETER);

		if (!$this->acl_check_new('Accounts_Controller', 'bank_transfers'))
			Controller::error(ACCESS);

		$bank_acc_model = new Bank_account_Model($id);

		if (!$bank_acc_model->id)
			Controller::error(RECORD);
		
		try
		{
			$ba_drive = Bank_Account_Settings::factory($bank_acc_model->type);
			$ba_drive->load_column_data($bank_acc_model->settings);
		}
		catch (InvalidArgumentException $e)
		{
			$ba_drive = NULL;
		}
			
		if (!$ba_drive || !$ba_drive->can_import_statements())
			Controller::error(RECORD);

		// form	
		$form = new Forge('import/upload_bank_file/' . $id);
		
		$form->group(__('File') . ' - ' . self::$file_types[$bank_acc_model->type]);

		$form->upload('listing', TRUE)
				->label('File with bank transfer listing')
				->rules('required');
		
		if (module::e('notification'))
		{
			if (Settings::get('email_enabled') || Settings::get('sms_enabled'))
			{
				$form->group('Notifications');
			}

			if (Settings::get('email_enabled'))
			{
				$form->checkbox('send_email_notice')
						->value('1')
						->label('Send e-mail notice about received payment to member')
						->checked('checked');
			}

			if (Settings::get('sms_enabled'))
			{
				$form->checkbox('send_sms_notice')
						->value('1')
						->label('Send SMS notice about received payment to member');
			}

			if (module::e('redirection') &&
				$this->acl_check_edit('Messages_Controller', 'message'))
			{
				$form->group('Redirection');

				$form->checkbox('reactivate_debtor_redir')
						->value('1')
						->label('Reactivate debtor redirection after importing');

				$form->checkbox('reactivate_payment_notice_redir')
						->value('1')
						->label('Reactivate payment notice redirection after importing');
			}
		}
		
		$form->submit('Submit');

		// validation
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			switch ($bank_acc_model->type)
			{
					case Bank_account_Model::TYPE_RAIFFEISENBANK:
						$this->import_ebank(
								$id, $form->listing->value,
								Settings::get('email_enabled') &&
								@$form_data['send_email_notice'] == 1,
								Settings::get('sms_enabled') && 
								(@$form_data['send_sms_notice'] == 1),
								@$form_data['reactivate_debtor_redir'] == 1,
								@$form_data['reactivate_payment_notice_redir'] == 1
						);
						break;
					case Bank_account_Model::TYPE_FIO:
						$this->import_fio(
								$id, $form->listing->value,
								Settings::get('email_enabled') &&
								@$form_data['send_email_notice'] == 1,
								Settings::get('sms_enabled') && 
								(@$form_data['send_sms_notice'] == 1),
								@$form_data['reactivate_debtor_redir'] == 1,
								@$form_data['reactivate_payment_notice_redir'] == 1
							);
						break;
					case Bank_account_Model::TYPE_UNICREDIT:
						$this->import_unicredit(
								$id, $form->listing->value,
								Settings::get('email_enabled') &&
								@$form_data['send_email_notice'] == 1,
								Settings::get('sms_enabled') && 
								(@$form_data['send_sms_notice'] == 1),
								@$form_data['reactivate_debtor_redir'] == 1,
								@$form_data['reactivate_payment_notice_redir'] == 1
						);
						break;
					default:
						break;
			}
		}
		
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

	/**
	 * Imports fio bank listing items from specified file.
	 * 
	 * @author Jiri Svitak
	 * @param integer $back_account_id
	 * @param string $file_url
	 * @param boolean $send_emails Send emails as payment accept notification?
	 * @param boolean $send_sms Send SMSs as payment accept notification?
	 * @param boolean $debtor_redir_react Reactivate debtor redirection?
	 * @param boolean $payment_notice_redir_react Reactivate payment notice redirection?
	 */
	private function import_fio($bank_account_id, $file_url, $send_emails, $send_sms,
			$debtor_redir_react, $payment_notice_redir_react)
	{
		try
		{
			// bank account
			$bank_account = new Bank_account_Model($bank_account_id);

			// import
			$statement = Bank_Statement_File_Importer::import(
					$bank_account, $file_url, 'csv', $send_emails, $send_sms
			);
			
			// redirection reactivation
			if ($debtor_redir_react)
			{
				if (is_numeric(Settings::get('big_debtor_boundary')))
				{
					$this->reactivate_redir(Message_Model::BIG_DEBTOR_MESSAGE);
				}
				$this->reactivate_redir(Message_Model::DEBTOR_MESSAGE);
			}
			
			if ($payment_notice_redir_react)
			{
				$this->reactivate_redir(Message_Model::PAYMENT_NOTICE_MESSAGE);
			}
			
			url::redirect('bank_transfers/show_by_bank_statement/'.$statement->id);
		}
		catch (Duplicity_Exception $e)
		{
			$m = __('Import has failed.') . ' '
				. __('Bank statement contains items that were already imported.');
			status::error($m, $e, FALSE);
		}
		catch (Exception $e)
		{
			Log::add_exception($e);
			status::error('Import has failed.', $e);
		}
	}

	/**
	 * Parse ebank account
	 * 
	 * @author Jiri Svitak
	 * @param integer $back_account_id
	 * @param string $file_url
	 * @param boolean $send_emails Send emails as payment accept notification?
	 * @param boolean $send_sms Send SMSs as payment accept notification?
	 * @param boolean $debtor_redir_react Reactivate debtor redirection?
	 * @param boolean $payment_notice_redir_react Reactivate payment notice redirection?
	 */
	private function import_ebank($bank_account_id, $url, $send_emails, $send_sms,
			$debtor_redir_react, $payment_notice_redir_react)
	{
		try
		{
			$db = new Transfer_Model();
			$db->transaction_start();

			$parser = new Parser_Ebanka($send_emails, $send_sms);
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
			$statement->type = self::$file_types[Bank_account_Model::TYPE_RAIFFEISENBANK];
			$statement->save_throwable();

			RB_Importer::$bank_statement_id = $statement->id;
			RB_Importer::$user_id = $this->session->get('user_id');
			RB_IMporter::$time_now = date('Y-m-d H:i:s');

			// safe import is done by transaction processing started in method which called this one
			$parser->parse($url);

			// does imported bank account and bank account on the statement match?
			if ($ba->account_nr != $parser->account_nr ||
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
			
			$db->transaction_commit();
			
			// redirection reactivation
			if ($debtor_redir_react)
			{
				if (is_numeric(Settings::get('big_debtor_boundary')))
				{
					$this->reactivate_redir(Message_Model::BIG_DEBTOR_MESSAGE);
				}
				$this->reactivate_redir(Message_Model::DEBTOR_MESSAGE);
			}
			
			if ($payment_notice_redir_react)
			{
				$this->reactivate_redir(Message_Model::PAYMENT_NOTICE_MESSAGE);
			}
			
			url::redirect('bank_transfers/show_by_bank_statement/'.$statement->id);
		}
		catch (RB_Exception $e)
		{
			$db->transaction_rollback();
			status::error(__('Import has failed.') . ' ' .
					$e->getMessage(), NULL, FALSE
			);
			url::redirect('bank_accounts/show_all');
		}
		catch (Duplicity_Exception $e)
		{
			$db->transaction_rollback();
			status::error(
					__('Import has failed.') . ' ' .
					__('Bank statement contains items that were already imported.') . ' ' .
					$e->getMessage(), NULL, FALSE
			);
		}
		catch (Exception $e)
		{
			$db->transaction_rollback();
			Log::add_exception($e);
			status::error(
					__('Import has failed') . '.<br>' . $e->getMessage(),
					NULL, FALSE
			);
		}
	}

	/**
	 * Imports fio bank listing items from specified file.
	 * 
	 * @author Ondrej Fibich
	 * @param integer $back_account_id
	 * @param string $file_url
	 * @param boolean $send_emails Send emails as payment accept notification?
	 * @param boolean $send_sms Send SMSs as payment accept notification?
	 * @param boolean $debtor_redir_react Reactivate debtor redirection?
	 * @param boolean $payment_notice_redir_react Reactivate payment notice redirection?
	 */
	private function import_unicredit($bank_account_id, $file_url, $send_emails, $send_sms,
			$debtor_redir_react, $payment_notice_redir_react)
	{
		try
		{
			$db = new Transfer_Model();
			$db->transaction_start();
			
			// parse bank listing items
			$data = UnicreditImport::getDataFromFile($file_url);
			// get header
			$header = UnicreditImport::getListingHeader();
			// does match bank account in system with bank account of statement?
			$ba = new Bank_account_Model($bank_account_id);
			
			if ($ba->account_nr != $header['account_nr'] ||
				$ba->bank_nr != $header['bank_nr'])
			{
				$ba_nr = $ba->account_nr.'/'.$ba->bank_nr;
				$listing_ba_nr = $header['account_nr'].'/'.$header['bank_nr'];
				
				throw new UnicreditException(__(
						'Bank account number in listing (%s) header does not match ' .
						'bank account %s in database!', array($listing_ba_nr, $ba_nr)
				));
			}

			// save bank statement
			$statement = new Bank_statement_Model();
			$statement->set_logger(FALSE);
			$statement->bank_account_id = $bank_account_id;
			$statement->user_id = $this->user_id;
			$statement->type = self::$file_types[Bank_account_Model::TYPE_UNICREDIT];
			$statement->from = $header['from'];
			$statement->to = $header['to'];
			$statement->save_throwable();

			// save bank listing items
			$stats = UnicreditSaver::save(
					$data, $bank_account_id, $statement->id,
					$this->user_id, $send_emails, $send_sms
			);

			$db->transaction_commit();
			
			// redirection reactivation
			if ($debtor_redir_react)
			{
				if (is_numeric(Settings::get('big_debtor_boundary')))
				{
					$this->reactivate_redir(Message_Model::BIG_DEBTOR_MESSAGE);
				}
				$this->reactivate_redir(Message_Model::DEBTOR_MESSAGE);
			}
			
			if ($payment_notice_redir_react)
			{
				$this->reactivate_redir(Message_Model::PAYMENT_NOTICE_MESSAGE);
			}
			
			url::redirect('bank_transfers/show_by_bank_statement/'.$statement->id);
		}
		catch (UnicreditException $e)
		{
			$db->transaction_rollback();
			status::error(__('Import has failed.') . ' ' . $e->getMessage(), NULL, FALSE);
		}
		catch (Duplicity_Exception $e)
		{
			$db->transaction_rollback();
			status::error(
					__('Import has failed.') . ' ' .
					__('Bank statement contains items that were already imported.') . ' ' .
					$e->getMessage(), NULL, FALSE
			);
		}
		catch (Exception $e)
		{
			$db->transaction_rollback();
			Log::add_exception($e);
			status::error(
					__('Import has failed') . '.<br>' . $e->getMessage(), NULL, FALSE
			);
		}

	}
	
	/**
	 * Reactivates redirection of a message given by type.
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type Message type
	 * @throws InvalidArgumentException On wrong type
	 */
	private function reactivate_redir($type)
	{
		// member model
		$member_model = new Member_Model();
		$message_model = new Message_Model();
		
		// find message
		$message = new Message_Model($message_model->get_message_id_by_type($type));
		
		if (!$message || !$message->id)
		{
			throw new InvalidArgumentException('Invalid type');
		}
		
		// get all members for messages
		$members = $member_model->get_members_to_messages($type);
		// activate notification
		try
		{
			// notify
			$stats = Notifications_Controller::notify(
					$message, $members, $this->user_id,
					NULL, TRUE, FALSE, FALSE, TRUE
			);
			// info messages
			$info_messages = notification::build_stats_string(
					$stats, TRUE, FALSE, FALSE, TRUE
			);
			// log action
			$name = ORM::factory('user', $this->user_id)->get_full_name();
			$m = __('Redirection "%s" has been reactivated during importing of bank statement by "%s"',
					array(__($message->name), $name));
			Log_queue_Model::info($m, implode("\n", $info_messages));
			// show status to user
			status::info($m . '<br>' . implode('<br>', $info_messages));
		}
		catch (Exception $e)
		{
			self::log_error($e->getMessage(), $e, FALSE);
			$m = __('Redirection "%s" has not been reactivated', $message->name);
			status::warning($m, $e, FALSE);
		}
	}

}
