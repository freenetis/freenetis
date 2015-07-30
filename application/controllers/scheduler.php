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

require_once APPPATH . 'libraries/importers/Fio/FioConnection.php';
require_once APPPATH . 'libraries/importers/Fio/FioConfig.php';
require_once APPPATH . 'libraries/importers/Fio/FioImport.php';
require_once APPPATH . 'libraries/importers/Fio/FioSaver.php';


/**
 * Controller performs specified actions at specified time.
 * This part of system has to be added to CRON. 
 * 
 * @package Controller
 */
class Scheduler_Controller extends Controller
{	
	/**
	 * Log scheduler error
	 *
	 * @param string $method
	 * @param Exception $e 
	 */
	private static function log_error($method, Exception $e)
	{
		// get humanize method
		$method = str_replace('|', ' ' . __('or') . ' ', $method);
		
		// logg
		Log::add('error', Kohana::lang(
				'core.scheduler_exception', get_class($e),
				$e->getMessage(), $method,
				$e->getLine()
		));
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

		// daily actions
		
		if ((date('H:i') == '00:00'))
		{
			
			try
			{
				ORM::factory('member')->update_lock_status();
			}
			catch (Exception $e)
			{
				self::log_error('update_lock_status', $e);
			}

			try
			{
				if (Settings::get('ulogd_enabled') == '1')
				{
					self::members_traffic_partitions_daily();
				}
			}
			catch (Exception $e)
			{
				self::log_error('members_traffic_partitions_daily', $e);
			}

			try
			{
				// first day in month (per month)
				if (date('m') == '01' && Settings::get('ulogd_enabled') == '1')
				{
					self::members_traffic_partitions_montly();
				}
			}
			catch (Exception $e)
			{
				self::log_error('members_traffic_partitions_daily', $e);
			}

			try
			{
				if (Settings::get('action_logs_active') == '1')
				{
					self::logs_partitions_daily();
				}
			}
			catch (Exception $e)
			{
				self::log_error('action_logs_active', $e);
			}
		}

		/*
		if (date('H:i') == "01:00")
		{
			if (Settings::get('fio_import_daily') == '1')
			{
				self::fio_import_daily();
			}
			catch (Exception $e)
			{
				self::log_error('fio_import_daily', $e);
			}
		}
		*/
		
		// send emails

		try
		{
			self::send_quened_emails();
		}
		catch (Exception $e)
		{
			self::log_error('send_quened_emails', $e);
		}

		// SMS should be down there because they can take a while
		
		try
		{
			if (Sms::enabled())
			{
				//send quened SMS
				self::send_quened_sms();

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
				self::update_allowed_subnets();
			}
		}
		catch (Exception $e)
		{
			self::log_error('update_allowed_subnets', $e);
		}
		
		// update local subnets
		self::update_local_subnets();
		
		// send notification from monitoring
		self::monitoring_notification();

		// update ulogd
		try
		{
			if (Settings::get('ulogd_enabled'))
			{
				self::update_ulogd();
			}
		}
		catch (Exception $e)
		{
			self::log_error('update_ulogd', $e);
		}
		
		// set state of module (last activation time)
		Settings::set('cron_state', date('Y-m-d H:i:s'));
	}

	/**
	 * @author Jiri Svitak
	 */
//	public function fio_import_daily()
//	{
//		//try
//		{
//			//$db = new Transfer_Model();
//			//$db->transaction_start();
//
//			$ba = ORM::factory('bank_account')->where("bank_nr", Settings::get("fio_bank_account"))->find();
//			
//			$bs_model = new Bank_statement_Model();
//			$bs = $bs_model->get_last_statement($ba->id);
//			
//			if (count($bs) > 0)
//			{
//				$fromDate = $bs->current()->to;
//			}
//			else
//			{
//				$fromDate = "2000-01-01";
//			}
//
//			$toDate = date::decrease_day(date('Y'), date('m'), date('d'));
//
//			// correct date format for FIO
//			$fromDate = date('d.m.Y', strtotime($fromDate));
//			$toDate = date('d.m.Y', strtotime($toDate));			
//
//			$downloadConfig = new FioConfig(Settings::get("fio_user"),
//					Settings::get("fio_password"),
//					Settings::get("fio_account_number"),
//					Settings::get("fio_view_name"));
//
//			$connection = new FioConnection($downloadConfig);
//
//			$csvData = $connection->getCSV($fromDate, $toDate);
//			$csvData = iconv('cp1250', 'UTF-8', $csvData);
//
//			echo '<br>';
//			echo $csvData;die();
//
//			$data = FioParser::parseCSV($csvData);
//			FioImport::correctData($data);
//			$header = FioImport::getListingHeader();
//			
//			echo '<br>';
//			echo $data;
//
//			FioSaver($data);
//
//			//$db->transaction_commit();
//
//		}
//		/*
//		catch(Exception $e)
//		{
//			$db->transaction_rollback();
//			throw $e;
//		}
//		 *
//		 */
//	}


	/**
	 * Manage partitions of log table.
	 * Add partition for current day and removes 31 days old partition.
	 * 
	 * @see Logs_Controller
	 * @author Ondřej Fibich
	 */
	private static function logs_partitions_daily()
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
	private static function members_traffic_partitions_daily()
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
	private static function members_traffic_partitions_montly()
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
					$sms_model->user_id = NULL;
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
	private static function update_ulogd()
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
					date('Y-m-d'), Settings::get('ulogd_active_type')
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
					date('Y-m-d')
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
				Settings::get('allowed_subnets_update_last') +
				Settings::get('allowed_subnets_update_interval')
			) < time())
		{
			ORM::factory('message')
					->activate_unallowed_connecting_place_message(User_Model::MAIN_USER);
		}
	}
	
	/**
	 * Sent e-mails from queue
	 * 
	 * @author Michal Kliment
	 */
	private static function send_quened_emails()
	{
		$email_queue_model = new Email_queue_Model();
		
		$email_queue = $email_queue_model->get_current_queue();
		
		if (!count($email_queue))
			return; // do not connect to SMPT server for no reason (fixes #336)
		
		$swift = email::connect();
		
		foreach ($email_queue as $email)
		{
			// Build recipient lists
			$recipients = new Swift_RecipientList;
			$recipients->addTo($email->to);
			
			// Build the HTML message
			$message = new Swift_Message($email->subject, $email->body, "text/html");
			
			// Send
			if (Config::get('unit_tester') || 
				$swift->send($message, $recipients, $email->from))
			{
				$email->state = Email_queue_Model::STATE_OK;
			}
			else
				$email->state = Email_queue_Model::STATE_FAIL;
				
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
	private static function update_local_subnets()
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
	private static function monitoring_notification()
	{
		// prepare models
		$email_queue_model = new Email_queue_Model();
		$monitor_host_model = new Monitor_host_Model();
		
		try
		{
			$monitor_host_model->transaction_start();
			
			// find maximal diff between down time and last attempt time
			$max_down_diff = $monitor_host_model
				->get_max_state_changed_diff(Monitor_host_Model::STATE_DOWN);

			/**
			 * following code generate sequence of diffs
			 * for each diff and host will be sent notification once time
			 */
			$down_diffs = array();

			$increase = 3;
			$growth = 5;

			$i = 0;

			$max = 5;

			while ($i <= $max_down_diff)
			{
				$down_diffs[] = $i;

				$i += $increase;

				if ($i >= $max)
				{			
					$increase = $max;

					$max *= $growth;
				}
			}

			for($i=0;$i<count($down_diffs)-1;$i++)
			{	
				/**
				 * find all down hosts in interval between this diff and next diff
				 * (exclude hosts about which has been already sent notification
				 * in this interval)
				 */
				$down_hosts = $monitor_host_model->get_all_hosts_by_state(
						Monitor_host_Model::STATE_DOWN, $down_diffs[$i], $down_diffs[$i+1]);

				// for each send e-mail
				foreach ($down_hosts as $down_host)
				{	
					$email_queue_model->push(
						Settings::get('email_default_email'),
						Settings::get('monitoring_email_to'),
						__('Monitoring error').': '.__('Host').' '.$down_host->name.' '.__('is unreachable'),
						__('Host').' '
							.html::anchor(url_lang::base().'devices/show/'.$down_host->device_id, $down_host->name)
							.' '.__('is unreachable since').' '.strftime("%c", strtotime($down_host->state_changed_date))
					);

					$monitor_host_model->update_host_notification_date($down_host->id);
				}
			}
			
			// maximal value from sequence of diff for which will sent notification
			// about hosts which returned from down state
			$max_returned_diff = 30;
			
			/**
			 * following code generate sequence of diffs
			 * for each diff and host will be sent notification once time
			 */
			$returned_diffs = array();

			$increase = 3;
			$growth = 5;

			$i = 0;

			$max = 5;

			while ($i <= $max_returned_diff)
			{
				$returned_diffs[] = $i;

				$i += $increase;

				if ($i >= $max)
				{			
					$increase = $max;

					$max *= $growth;
				}
			}
			
			for($i=0;$i<count($returned_diffs)-1;$i++)
			{	
				/**
				* find all hosts which returned from down state in interval
				* between this diff and next diff (exclude hosts about which
				* has been already sent notification in this interval)
				*/
				$returned_hosts = $monitor_host_model->get_all_hosts_by_state(
						Monitor_host_Model::STATE_UP, $returned_diffs[$i], $returned_diffs[$i+1]);

				// for each send e-mail
				foreach ($returned_hosts as $returned_host)
				{	
					$email_queue_model->push(
						Settings::get('email_default_email'),
						Settings::get('monitoring_email_to'),
						__('Monitoring notice').': '.__('Host').' '.$returned_host->name.' '.__('is again reachable'),
						__('Host').' '
							.html::anchor(url_lang::base().'devices/show/'.$returned_host->device_id, $returned_host->name)
							.' '.__('is again reachable since').' '.strftime("%c", strtotime($returned_host->state_changed_date))
					);

					$monitor_host_model->update_host_notification_date($returned_host->id);
				}
			}
			
			$monitor_host_model->transaction_commit();
		}
		catch (Exception $e)
		{
			$monitor_host_model->transaction_rollback();
			Log::add_exception($e);
		}
		
	}

}
