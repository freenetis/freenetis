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
 * Controller performs specified actions at specified time.
 * This part of system has to be added to CRON. 
 * 
 * @package Controller
 */
class Scheduler_Controller extends Controller
{
	// Constants that represents count of minutes in which an action is
	// performed. These constants are not directly written in code because
	// of avoiding of overlaping of action in order to performance issues
	// (action should not be started in the same time).
	
	/** Activation Minute for member fees (long action but triggered only monthly) */
	const AM_MEMBER_FEES			= '03';
	/** Activation Minute for entrance fees (take some time but triggered only monthly) */
	const AM_ENTRANCE_FEES		= '05';
	/** Activation Minute for device fees (not oftenly used) */
	const AM_DEVICE_FEES			= '06';
	/** Activation Minute for bank statements downloading (long action) */
	const AM_BANK_STATEMENTS		= '07';
	/** Activation Minute for connection test (daily action) */
	const AM_CONNECTION_TEST	= '09';
	/** Activation Minute for former member message */
	const AM_FORMER_MEMBER		= '09';
	/** Activation Minute for interrupted member message */
	const AM_INTERRUPTED_MEMBER	= '09';
	/** Activation Minute for notification (long action) */
	const AM_NOTIFICATION		= '10';
    /** Activation Minute for Vtiger synchronization - each hour (long action) */
    const AM_VTIGER_SYNC        = '30';
	
	/**
	 * Log scheduler error
	 *
	 * @param string $method
	 * @param Exception $e 
	 * @param boolean $log_queue Also log to log queue (database)?
	 */
	private static function log_error($method, Exception $e, $log_queue = TRUE)
	{
		// get humanize method
		$method = str_replace('|', ' ' . __('or') . ' ', $method);
		$text = Kohana::lang(
			'core.scheduler_exception', get_class($e),
			$e->getMessage(), $method,
			$e->getLine()
		);
		// log to database
		if ($log_queue)
		{
			Log_queue_Model::error($text, $e->getTraceAsString());
		}
		// log to file
		Log::add('error', $text);
	}
	
	/** Time at calling of the controller */
	private $t;
	
	/**
	 * Set up time
	 */
	public function __construct()
	{
		parent::__construct();
		$this->t = time();
	}
	
	/**
	 * Redirect to index
	 */
	public function index()
	{
		url::redirect(url::base());
	}

	/**
	 * This function should be regularly launched by scheduler.
	 * Scheduler can be bash script run by cron.
	 * 
	 * @author Jiri Svitak
	 */
	public function run()
	{
		// script needs to be run from its server machine
		
		if (server::remote_addr() != server::server_addr())
		{
			echo 'access denied';
			die();
		}
		
		// CRON failure detection (#754)
		$last_active = intval(Settings::get('cron_last_active'));
		
		if ($last_active > 0) // not for the first time
		{
			if (($this->t - $last_active) > 110) // diff higher than 110 seconds => log failure
			{
				$data = array
				(
					'from'	=> date('Y-m-d H:i:s', $last_active),
					'to'	=> date('Y-m-d H:i:s', $this->t)
				);

				$desc = __(
					'CRON (scheduler) was offline from %s to %s (%s) some ' .
					'system operation may not be performed', array
					(
						$data['from'], $data['to'],
						($this->t - $last_active) . ' ' . __('seconds')
					)
				);
				
				Log_queue_Model::ferror(__('CRON failure'), $desc);
			}
		}
		
		Settings::set('cron_last_active', $this->t);

		// daily actions
		
		if (date('H:i', $this->t) == '00:00')
		{
			try
			{
				if (Settings::get('ulogd_enabled') == '1')
				{
					$this->members_traffic_partitions_daily();
				}
			}
			catch (Exception $e)
			{
				self::log_error('members_traffic_partitions_daily', $e);
			}

			try
			{
				// first day in month (per month)
				if (date('d', $this->t) == '01' && Settings::get('ulogd_enabled') == '1')
				{
					$this->members_traffic_partitions_montly();
				}
			}
			catch (Exception $e)
			{
				self::log_error('members_traffic_partitions_monthly', $e);
			}

			try
			{
				if (Settings::get('action_logs_active') == '1')
				{
					$this->logs_partitions_daily();
				}
			}
			catch (Exception $e)
			{
				self::log_error('action_logs_active', $e);
			}
		}
		
		/* Each day at 5:xx */
		if (date('H:i', $this->t) == '05:' . self::AM_CONNECTION_TEST)
		{
			try
			{
				$this->update_applicant_connection_test();
			}
			catch (Exception $e)
			{
				self::log_error('update_applicant_connection_test', $e);
			}
		}
		
		// send emails

		try
		{
			if (Settings::get('email_enabled'))
				$this->send_quened_emails();
		}
		catch (Exception $e)
		{
			self::log_error('send_quened_emails', $e);
		}

		// SMS should be down there because they can take a while
		
		try
		{
			if (Settings::get('sms_enabled') && Sms::enabled())
			{
				//send quened SMS
				$this->send_quened_sms();

				//receive SMS
				self::receive_sms();
			}
		}
		catch (Exception $e)
		{
			self::log_error('send_quened_sms|receive_sms', $e);
		}

		// update allowed subnets
		
		try
		{
			if (Settings::get('allowed_subnets_enabled'))
			{
				$this->update_allowed_subnets();
			}
		}
		catch (Exception $e)
		{
			self::log_error('update_allowed_subnets', $e);
		}
		
		// update local subnets
		$this->update_local_subnets();
		
		if (Settings::get('monitoring_enabled'))
		{
			// send notification from monitoring
			$this->monitoring_notification();
		}

		// update ulogd
		try
		{
			if (Settings::get('ulogd_enabled'))
			{
				$this->update_ulogd();
			}
		}
		catch (Exception $e)
		{
			self::log_error('update_ulogd', $e);
		}
		
		// interuption message activation and notification on interuption
		// start and end (each day after midnight)
		$this->update_and_redirect_interrupted_members();
		
		// former members message activation (each day after midnight)
		$this->update_and_redirect_former_members();
		
		// bank statements import
		$this->bank_statements_import();
		
		// automatic notification messages activation
		$this->notification_activation();
		
		// fee deduction (monthly)
		if (Settings::get('finance_enabled'))
		{
			$this->fee_deduction();
		}
		
		//synchronize with vtiger CRM
		$this->vtiger_sync();
		
		// set state of module (last activation time)
		Settings::set('cron_state', date('Y-m-d H:i:s'));
	}
	
	/**
	 * Auto download of statements of bank accounts of association
	 * 
	 * @author Ondrej Fibich
	 */
	private function bank_statements_import()
	{
		$error_prefix = __('Error during automatic import of bank statements');
		
		try
		{
			// models and data
			$bank_account_model = new Bank_account_Model();
			$baad_model = new Bank_accounts_automatical_download_Model();
			
			// for each bank account of association
			$accounts = $bank_account_model->get_assoc_bank_accounts();
			
			// try for each account
			foreach ($accounts as $account)
			{
				// get settings
				try
				{
					$settings = Bank_Account_Settings::factory($account->type);
					$settings->load_column_data($account->settings);
				}
				catch (InvalidArgumentException $e) // no settings
				{
					continue;
				}
				
				// check if enabled
				if (!$settings->can_download_statements_automatically())
				{
					continue;
				}
				
				// bank account
				$bank_account_model->find($account->id);
				$rules = $baad_model->get_bank_account_settings($account->id);
				
				// activate flags
				$activate = $a_email = $a_sms = FALSE;
				// get all rules that match
				$filtered_rules = Time_Activity_Rule::filter_rules(
						$rules, self::AM_BANK_STATEMENTS, $this->t
				);				
				// check all rules if redir/email/sms should be activated now
				foreach ($filtered_rules as $rule)
				{
					$activate = TRUE;
					$a_email = $a_email || $rule->email_enabled;
					$a_sms = $a_sms || $rule->sms_enabled;
				}
				// global options
				$a_email = $a_email && Settings::get('email_enabled');
				$a_sms = $a_sms && Settings::get('sms_enabled');
				// any rule?
				if (!$activate)
				{
					continue;
				}
				// import 
				try
				{
					// download&import
					$bs = Bank_Statement_File_Importer::download(
							$bank_account_model, $settings, $a_email, $a_sms
					);
					
					// inform
					if ($bs)
					{
						$tc = $bs->bank_transfers->count();
						$aurl = '/bank_transfers/show_by_bank_statement/' . $bs->id;
						
						$args = array
						(
							($tc > 0) ? html::anchor($aurl, $bs->id) : __('empty', array(), 1),
							$tc, 
							$bank_account_model->name . ' - ' .
							$bank_account_model->account_nr . '/' .
							$bank_account_model->bank_nr
						);
						
						$m = __('Bank statement (%s) with %d transfers for bank account '
								. '"%s" has been automatically downloaded and imported',
								$args);
						
						Log_queue_Model::info($m);
						
						// if empty => delete
						if (!$tc)
						{
							$bs->delete();
						}
					}
				}
				catch (Exception $e)
				{
					$m = $error_prefix . ': ' . $bank_account_model->account_nr
						. '/' . $bank_account_model->bank_nr
						. ' ' . __('caused by') . ': ' . $e->getMessage();
					self::log_error($m, $e, FALSE);
					Log_queue_Model::error($m, $e);
				}
			}
		}
		catch (Exception $e)
		{
			self::log_error($error_prefix, $e, FALSE);
			Log_queue_Model::error($error_prefix, $e);
		}
	}
			
	/**
	 * Auto activation of notification messages
	 * 
	 * @author Ondrej Fibich
	 */
	private function notification_activation()
	{
		if (module::e('notification'))
		{		
			$error_prefix = __('Error during automatic activation of notification messages');

			try
			{
				// models and data
				$member_model = new Member_Model();
				$messages_aa = new Messages_automatical_activation_Model();
				$messages = ORM::factory('message')->find_all();

				// for each message
				foreach ($messages as $message)
				{
					// auto activation possible?
					if (!Message_Model::can_be_activate_automatically($message->type))
					{
						continue; // No!
					}
					// find rules
					$rules = $messages_aa->get_message_settings($message->id);
					// activate flags
					$a_redir = $a_email = $a_sms = FALSE;
					$report_to = array();
					// get all rules that match
					$filtered_rules = Time_Activity_Rule::filter_rules(
							$rules, self::AM_NOTIFICATION, $this->t
					);
					// check all rules if redir/email/sms should be activated now
					foreach ($filtered_rules as $rule)
					{
						$a_redir = $a_redir || $rule->redirection_enabled;
						$a_email = $a_email || $rule->email_enabled;
						$a_sms = $a_sms || $rule->sms_enabled;
						$any = $rule->redirection_enabled || $rule->email_enabled || $rule->sms_enabled;

						if ($any && $rule->send_activation_to_email)
						{
							$report_to += explode(',', $rule->send_activation_to_email);
						}
					}
					// global options
					$a_redir = $a_redir && Settings::get('redirection_enabled');
					$a_email = $a_email && Settings::get('email_enabled');
					$a_sms = $a_sms && Settings::get('sms_enabled');
					// do not do next if nothing should be made
					if (!$a_redir && !$a_email && !$a_sms)
					{
						continue;
					}
					// get all members for messages
					$members = $member_model->get_members_to_messages($message->type);
					// activate notification
					try
					{
						// notify
						$stats = Notifications_Controller::notify(
								$message, $members, NULL, NULL, $a_redir,
								$a_email, $a_sms, $a_redir
						);
						// info messages
						$info_messages = notification::build_stats_string(
								$stats, $a_redir, $a_email, $a_sms, $a_redir
						);
						// log action
						if (count($info_messages))
						{
							$m = __('Notification message "%s" has been automatically activated',
									__($message->name));
							Log_queue_Model::info($m, implode("\n", $info_messages));
							// send report
							if (count($report_to))
							{
								$this->send_message_activation_report($report_to, $m, $info_messages, $members);
							}
						}
					}
					catch (Exception $e)
					{
						self::log_error('notification_activation', $e, FALSE);
						Log_queue_Model::error($e->getMessage(), $e);
					}
				}
			}
			catch (Exception $e)
			{
				self::log_error('notification_activation', $e, FALSE);
				Log_queue_Model::error($error_prefix, $e);
			}
		}
	}

	private function send_message_activation_report($to_emails, $description,
			$actions, $activated_members)
	{
		$email_view = new View('email_templates/notification_activation_report');
		$email_view->header = $description;
		$email_view->actions = $actions;
		$email_view->affected_members = $activated_members;
		$email_subject = Settings::get('email_subject_prefix') . ': ' . $description;
		$email_body = $email_view->render();

		$from = Settings::get('email_default_email');
		$email_model = new Email_queue_Model();

		foreach ($to_emails as $email)
		{
			$email_model->push($from, $email, $email_subject, $email_body);
		}
	}

	/**
	 * Deduct all fees automatically if enabled
	 * 
	 * @author Ondrej Fibich
	 */
	private function fee_deduction()
	{
		$day_of_deduct = date::get_deduct_day_to(date('m', $this->t), date('Y', $this->t));
		
		if (Settings::get('deduct_fees_automatically_enabled') &&
			intval(date('j', $this->t)) == intval($day_of_deduct))
		{
			// preparations
			$association = new Member_Model(Member_Model::ASSOCIATION);
			$user_id = $association->get_main_user();
			
			// members fees (at deduct date 3 minutes after midnight)
			if (date('H:i', $this->t) == '00:' . self::AM_MEMBER_FEES)
			{
				try
				{
					// perform
					$c = Transfers_Controller::worker_deduct_members_fees(
							date('m', $this->t), date('Y', $this->t), $user_id
					);
					// info to logs
					$m = __('Member fees deducted automatically (in sum %d)', $c);
					Log_queue_Model::info($m);
				}
				catch (Exception $e)
				{
					$m = __('Error during automatical deduction of members fees,' .
							'please use manual deduction of fees');
					self::log_error($m, $e, FALSE);
					Log_queue_Model::error($m, $e);
				}
			}
			
			// entrance fees (at deduct date 5 minutes after midnight)
			if (date('H:i', $this->t) == '00:' . self::AM_ENTRANCE_FEES)
			{
				try
				{
					// perform
					$c = Transfers_Controller::worker_deduct_entrance_fees(
							date('m', $this->t), date('Y', $this->t), $user_id
					);
					// info to logs
					$m = __('Entrance fees deducted automatically (in sum %d)', $c);
					Log_queue_Model::info($m);
				}
				catch (Exception $e)
				{
					$m = __('Error during automatical deduction of entrance fees,' .
							'please use manual deduction of fees');
					self::log_error($m, $e, FALSE);
					Log_queue_Model::error($m, $e);
				}
			}
			
			// device deduct fees (at deduct date 7 minutes after midnight)
			if (date('H:i', $this->t) == '00:' . self::AM_DEVICE_FEES)
			{
				try
				{
					// perform
					$c = Transfers_Controller::worker_deduct_devices_fees(
							date('m', $this->t), date('Y', $this->t), $user_id
					);
					// info to logs
					$m = __('Device fees deducted automatically (in sum %d)', $c);
					Log_queue_Model::info($m);
				}
				catch (Exception $e)
				{
					$m = __('Error during automatical deduction of device fees,' .
							'please use manual deduction of fees');
					self::log_error($m, $e, FALSE);
					Log_queue_Model::error($m, $e);
				}
			}
		}
	}

	/**
	 * Manage partitions of log table.
	 * Add partition for current day and removes 31 days old partition.
	 * 
	 * @see Logs_Controller
	 * @author Ondřej Fibich
	 */
	private function logs_partitions_daily()
	{
		$model_log = new Log_Model();
		// remove log partition
		$model_log->remove_old_partitions();
		// add partition for today
		$model_log->add_partition();
	}

	/**
	 * Manage partitions of members_traffic_daily table.
	 * Add partition for current day and removes old partition.
	 * 
	 * @author Ondřej Fibich
	 */
	private function members_traffic_partitions_daily()
	{
		$model_members_traffic_daily = new Members_traffic_Model();
		// remove log partition
		$model_members_traffic_daily->remove_daily_old_partitions();
		// add partition for today
		$model_members_traffic_daily->add_daily_partition();
	}

	/**
	 * Manage partitions of members_traffic_montly table.
	 * Add partition for current month and removes old partition.
	 * 
	 * @author Ondřej Fibich
	 */
	private function members_traffic_partitions_montly()
	{
		$model_members_traffic_montly = new Members_traffic_Model();
		// remove log partition
		$model_members_traffic_montly->remove_monthly_old_partitions();
		// add partition for today
		$model_members_traffic_montly->add_monthly_partition();
	}

	/**
	 * Function sends SMS messages from db queue.
	 * Sends 30 SMS messages by one call.
	 * 
	 * @author Ondrej Fibich, Roman Sevcik
	 */
	private function send_quened_sms()
	{
		// gets unsended SMS
		$unsent_messages = ORM::factory('sms_message')->where(array
		(
			'type'	=> Sms_message_Model::SENT,
			'state' => Sms_message_Model::SENT_UNSENT
		))->where('send_date < CURRENT_TIMESTAMP')->limit(30)->find_all();
		
		// no SMS unsended => bye bye!
		if (!count($unsent_messages))
		{
			return;
		}
		
		$sms_drivers = array();

		// send all SMS
		foreach ($unsent_messages as $m)
		{
			// enabled operator?
			if (!Phone_operator_Model::is_sms_enabled_for($m->receiver))
			{
				$m->state = Sms_message_Model::SENT_FAILED;
				$m->message = 'Phone operator of number is not enabled for sending SMS';
				$m->save();
				continue;
			}
			
			// init driver if not initialized before
			if (!array_key_exists($m->driver, $sms_drivers))
			{
				$sms = Sms::factory($m->driver);

				// wrong driver
				if (!$sms)
				{
					$m->state = Sms_message_Model::SENT_FAILED;
					$m->message = __('Unknown driver');
					$m->save();
					continue;
				}

				// gets variables from config
				$user = Settings::get('sms_user' . $m->driver);
				$password = Settings::get('sms_password' . $m->driver);
				$hostname = Settings::get('sms_hostname' . $m->driver);
				$test_mode = (Settings::get('sms_test_mode' . $m->driver) == 1);

				// sets config vars to driver
				$sms->set_hostname($hostname);
				$sms->set_user($user);
				$sms->set_password($password);
				$sms->set_test($test_mode);
				
				// save driver
				$sms_drivers[$m->driver] = $sms;
			}
			
			// prepare message
			$text = htmlspecialchars_decode($m->text);
			
			// sends SMS
			if ($sms_drivers[$m->driver]->send($m->sender, $m->receiver, $text))
			{
				$m->state = Sms_message_Model::SENT_OK;
				$m->message = $sms_drivers[$m->driver]->get_status();
			}
			else
			{
				$m->state = Sms_message_Model::SENT_FAILED;
				$m->message = $sms_drivers[$m->driver]->get_error();
			}

			// save message
			$m->save();
		}
	}

	/**
	 * Function receive SMS messages from GSM gw.
	 * 
	 * @author Ondrej Fibich, Roman Sevcik
	 */
	private function receive_sms()
	{
		$active_drivers = Sms::get_active_drivers();
		
		// for each active driver
		foreach ($active_drivers as $key => $driver)
		{
			// create driver
			$sms = Sms::factory($key);
			
			if (!$sms)
			{
				continue;
			}
			
			// sets vars to driver
			$sms->set_hostname(Settings::get('sms_hostname' . $key));
			$sms->set_user(Settings::get('sms_user' . $key));
			$sms->set_password(Settings::get('sms_password' . $key));
			
			// receive
			if ($sms->receive())
			{
				// get messages
				$messages = $sms->get_received_messages();
				
				foreach ($messages as $message)
				{
					$recipient = Settings::get('sms_sim_card_number' . $key);
					
					if ($recipient == null || $recipient == '')
					{
						$recipient = Settings::get('sms_sender_number');
					}

					// save message to database
					$sms_model = new Sms_message_Model();
					$sms_model->user_id = 1;
					$sms_model->stamp = $message->date;
					$sms_model->send_date = $message->date;
					$sms_model->text = htmlspecialchars($message->text);
					$sms_model->sender = $message->sender;
					$sms_model->receiver = $recipient;
					$sms_model->driver = $key;
					$sms_model->type = Sms_message_Model::RECEIVED;
					$sms_model->state = Sms_message_Model::RECEIVED_UNREAD;
					$sms_model->save();
				}
			}
		}
	}

	/**
	 * Update ulogd
	 * 
	 * @author Michal Kliment
	 */
	private function update_ulogd()
	{
		// it's time to update
		if ((
				Settings::get('ulogd_update_last') +
				Settings::get('ulogd_update_interval')
			) < time())
		{
			$members_traffic_model = new Members_traffic_Model();
			
			// finding of count of active members
			$ulogd_active_count = Settings::get('ulogd_active_count');

			// count is in percents
			if (substr($ulogd_active_count, -1) == '%')
			{
				// finds total members to ulogd
				$total = ORM::factory('member')->count_all_members_to_ulogd();
				
				// calculates count of active members from percents
				$ulogd_active_count = (int) round(
						((int) substr($ulogd_active_count, 0, -1)) / 100 * $total
				);
			}
			
			// finding of avarage
			$avg = $members_traffic_model->avg_daily_traffics(
					date('Y-m-d', $this->t), Settings::get('ulogd_active_type')
			);

			if (($ulogd_active_min = Settings::get('ulogd_active_min')) != '')
			{
				$min = doubleval($ulogd_active_min);

				switch (substr($ulogd_active_min, -2, 1))
				{
					case 'k':
						// do nothing
						break;
					case 'M':
						$min *= 1024;
						break;
					case 'G':
						$min *= 1024 * 1024;
						break;
					case 'T':
						$min *= 1024 * 1024 * 1024;
						break;
					default:
						$min /= 1024;
						break;
				}

				if ($avg < $min)
					$avg = $min;
			}
			
			// updates active members
			$members_traffic_model->update_active_members(
					$avg, $ulogd_active_count,
					Settings::get('ulogd_active_type'),
					date('Y-m-d', $this->t)
			);
		
			// updates variable
			Settings::set('ulogd_update_last', time());
		}
	}

	/**
	 * Updates allowed subnets
	 *
	 * @author Michal Kliment
	 */
	private function update_allowed_subnets()
	{
		// it's time to update
		if (Settings::get('allowed_subnets_enabled') &&
			(
				strtotime(Settings::get('allowed_subnets_update_state')) +
				intval(Settings::get('allowed_subnets_update_interval'))
			) < time())
		{
			// activate
			ORM::factory('message')->activate_unallowed_connecting_place_message(
					User_Model::ASSOCIATION
			);
			// set last update info
			Settings::set('allowed_subnets_update_state', date('Y-m-d H:i:s'));
		}
	}

	/**
	 * Update former members and redirect them or if enabled devices of todays
	 * former members are deleted.
	 *
	 * @author Ondrej Fibich
	 */
	private function update_and_redirect_former_members()
	{
		if (date('H:i', $this->t) == '00:' . self::AM_FORMER_MEMBER)
		{
			$member_model = new Member_Model();
			
			try
			{
				// get message
				$message = ORM::factory('message')->get_message_by_type(
						Message_Model::FORMER_MEMBER_MESSAGE
				);
				// gets today former members
				$today_former_members = $member_model->get_today_former_members();
				// adds new former members
				$member_model->add_today_former_members();
				// gets all former members
				$former_members = $member_model->get_all_former_members();
				// remove devices of todays members if enabled
				if (Settings::get('former_member_auto_device_remove'))
				{
					try
					{
						$mids = array();
						// get member IDs
						foreach ($today_former_members as $m)
						{
							$mids[] = $m->member_id;
						}
						// delete
						$member_model->delete_members_devices($mids);
					}
					catch (Exception $e)
					{
						self::log_error('update_and_redirect_former_members: device delete', $e);
					}
				}
				// only if notification enabled
				if (module::e('notification'))
				{
					// redirect all former members
					Notifications_Controller::notify(
							$message, $former_members, NULL, NULL,
							TRUE, FALSE, FALSE, TRUE
					);
					// inform new former members (email and sms)
			//		Notifications_Controller::notify(
			//				$message, $today_former_members, NULL, NULL,
			//				FALSE, TRUE, TRUE, FALSE, FALSE, TRUE
			//		);
				}
			}
			catch (Exception $e)
			{
				self::log_error('update_and_redirect_former_members', $e);
			}
		}
	}

	/**
	 * Notify new iterrupted members and redirect all of them.
	 *
	 * @author Ondrej Fibich
	 */
	private function update_and_redirect_interrupted_members()
	{
		if (date('H:i', $this->t) == '00:' . self::AM_INTERRUPTED_MEMBER &&
			Settings::get('membership_interrupt_enabled') &&
			module::e('notification'))
		{
			$m_model = new Member_Model();
			
			try
			{
				// get messages
				$i_message = ORM::factory('message')->get_message_by_type(
						Message_Model::INTERRUPTED_MEMBERSHIP_MESSAGE
				);
				$bi_message = ORM::factory('message')->get_message_by_type(
						Message_Model::INTERRUPTED_MEMBERSHIP_BEGIN_NOTIFY_MESSAGE
				);
				$ei_message = ORM::factory('message')->get_message_by_type(
						Message_Model::INTERRUPTED_MEMBERSHIP_END_NOTIFY_MESSAGE
				);
				// get members
				$interr_members = $m_model->get_interrupted_members_on(
						date('Y-m-d', $this->t)
				);
				$begin_interr_members = $m_model->get_interrupted_members_on(
						date('Y-m-d', $this->t), 2
				);
				$end_interr_members = $m_model->get_interrupted_members_on(
						date('Y-m-d', strtotime('-1 day', $this->t)), 3
				);
				// redirect all interrupt members
				Notifications_Controller::notify(
						$i_message, $interr_members, NULL, NULL,
						TRUE, FALSE, FALSE, TRUE, FALSE, FALSE, FALSE
				);
				// inform interrupted members which is interupted from today
			/*	Notifications_Controller::notify(
						$bi_message, $begin_interr_members, NULL, NULL,
						FALSE, TRUE, TRUE, FALSE, FALSE, FALSE, TRUE
				);
				// inform interrupted members which was interupted yesterday
				Notifications_Controller::notify(
						$ei_message, $end_interr_members, NULL, NULL,
						FALSE, TRUE, TRUE, FALSE, FALSE, FALSE, TRUE
				);
			*/
			}
			catch (Exception $e)
			{
				self::log_error('update_and_redirect_former_members', $e);
			}
		}
	}

	/**
	 * If  self member registration is enabled then this rutine denies test
	 * connections of applicants after count of days stored in settings property 
	 * 'applicant_connection_test_duration'. Connections will not be denyed
	 * if the applicant became member or submit member registration.
	 *
	 * @author Ondrej Fibich
	 */
	private function update_applicant_connection_test()
	{
		if (Settings::get('self_registration') &&
			(Settings::get('applicant_connection_test_duration') > 0))
		{
			ORM::factory('message')
					->activate_test_connection_end_message(User_Model::ASSOCIATION);
		}
	}
	
	/**
	 * Sent e-mails from queue
	 * 
	 * @author Michal Kliment
	 */
	private function send_quened_emails()
	{
		$email_queue_model = new Email_queue_Model();
		
		$email_queue = $email_queue_model->get_current_queue();
		
		if (!count($email_queue))
			return; // do not connect to SMTP server for no reason (fixes #336)
		
		$swift = email::connect();
		
		foreach ($email_queue as $email)
		{
			// Build recipient lists
			$recipients = new Swift_RecipientList;
			$recipients->addTo($email->to);

			if (strpos($email->subject, 'Oznámení o přijaté platbě') !== FALSE)
			{
				$recipients->addBcc('ucdokl@pvfree.net');
			}
			if (strpos($email->subject, 'Ukončení členství s přeplatkem') !== FALSE)
			{
				$recipients->addBcc('rada@pvfree.net');
				$recipients->addBcc('pokladnik@pvfree.net');
			}
			
			if (strpos($email->subject, 'Ukončení členství podle Stanov') !== FALSE)
			{
				$recipients->addBcc('rada@pvfree.net');
			}
			
			if (strpos($email->subject, 'Ukončení členství na vlastní žádost') !== FALSE)
			{
				$recipients->addBcc('rada@pvfree.net');
			}
			
			if (strpos($email->subject, 'Oznámení o započetí přerušení členství') !== FALSE)
			{
				$recipients->addBcc('rada@pvfree.net');
			}
			
			// Build the HTML message
			$message = new Swift_Message($email->subject, $email->body, 'text/html');
			
			// Send
			if (Config::get('unit_tester') || 
				$swift->send($message, $recipients, $email->from))
			{
				$email->state = Email_queue_Model::STATE_OK;
			}
			else
			{
				$email->state = Email_queue_Model::STATE_FAIL;
			}
				
			$email_queue->access_time = date('Y-m-d H:i:s');
			$email->save();
		}
		
		$swift->disconnect();
	}
	
	/**
	 * Updates local subnets from web
	 * 
	 * @author Michal Kliment
	 * @return type 
	 */
	private function update_local_subnets()
	{
		// it's time to update
		if ((
				Settings::get('local_subnets_update_last') +
				Settings::get('local_subnets_update_interval')
			) < time())
		{
			define('URL', "http://software77.net/geo-ip/?DL=1");

			$country = new Country_Model(Settings::get('default_country'));

			// default country is not set
			if (!$country->id)
				return;

			// read file with all subnets
			$file = @gzfile(URL);

			// bad file
			if (!$file)
				return;

			// select only subnets belongs to default counrty
			$lines = preg_grep("/\"".$country->country_iso."\"/", $file);

			$local_subnet_model = new Local_subnet_Model();

			$subnets = array();
			foreach ($lines as $line)
			{
				// this shouldn't be happen
				if (!preg_match ("/\"([0-9]+)\"\,\"([0-9]+)\"\,\"([a-zA-Z]+)\"\,\"([0-9]+)\"\,\"([A-Z]+)\"\,\"([A-Z]+)\"\,\"([a-zA-Z \(\)\:;\'\.\-]+)\"/", $line, $matches))
					continue;

				$network_address = long2ip($matches[1]);

				$netmask = long2ip(~($matches[2]-$matches[1]));

				$subnets[] = array
				(
					'network_address' => $network_address,
					'netmask' => $netmask
				);
			}

			// no subnets
			if (!count($subnets))
				return;
			
			// do it in transaction
			try
			{
				$local_subnet_model->transaction_start();

				// deletes all subnets
				$local_subnet_model->delete_subnets();

				// adds new subnets
				$local_subnet_model->add_subnets($subnets);

				$local_subnet_model->transaction_commit();
			}
			catch (Exception $e)
			{
				$local_subnet_model->transaction_rollback();
				self::log_error('update_local_subnets', $e);
			}
			
			// updates variable
			Settings::set('local_subnets_update_last', time());
		}
	}
	
	/**
	 * Send notification of down hosts or hosts which returned from down state
	 * 
	 * @author Michal Kliment
	 */
	private function monitoring_notification()
	{
		if (module::e('monitoring') && (
				Settings::get('monitoring_notification_update_last') +
				Settings::get('monitoring_notification_interval')*60
			) < time())
		{
			// prepare models
			$monitor_host_model	= new Monitor_host_Model();
			$message_model		= new Message_Model();

			try
			{
				$monitor_host_model->transaction_start();

				// find message for host down
				$monitoring_host_down_message = $message_model->
						where('type', Message_Model::MONITORING_HOST_DOWN)->find();

				// find all down hosts
				$down_hosts = $monitor_host_model->get_all_hosts_by_state(
						Monitor_host_Model::STATE_DOWN);

				foreach ($down_hosts as $down_host)
				{
					Message_Model::send_email(
							$monitoring_host_down_message,
							Settings::get('monitoring_email_to'),
							$down_host
					);
				}

				// find message for host up
				$monitoring_host_up_message = $message_model->
						where('type', Message_Model::MONITORING_HOST_UP)->find();

				// find all up hosts
				$up_hosts = $monitor_host_model->get_all_hosts_by_state(
						Monitor_host_Model::STATE_UP);

				foreach ($up_hosts as $up_host)
				{
					Message_Model::send_email(
							$monitoring_host_up_message,
							Settings::get('monitoring_email_to'),
							$up_host
					);
				}

				$monitor_host_model->transaction_start();
			}
			catch (Exception $e)
			{
				$monitor_host_model->transaction_rollback();
				self::log_error('monitoring_notification', $e);
			}

			Settings::set('monitoring_notification_update_last', time());
		}
	}

	/**
	 * Synchronize members and users to vtiger CRM.
	 * Runs every 30 minutes if Vtiger integration is enabled in settings.
	 * 
	 * @author Jan Dubina
	 */
	private function vtiger_sync()
	{
		if (Settings::get('vtiger_integration') &&
            date('i', $this->t) == self::AM_VTIGER_SYNC)
		{
			try 
			{
				Members_Controller::vtiger_sync();
			} 
			catch (Exception $e) 
			{
				self::log_error('vtiger_synchronization', $e);
			}
		}
	}
}
