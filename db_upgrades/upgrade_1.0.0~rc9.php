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
 * Creates new structure for traffic logging and
 * transform data from previous structure.
 * 
 * @author Ondřej Fibich <ondrej.fibich@google.com>
 * @return boolean
 */
function upgrade_1_0_0_rc9_after()
{
	$db = Database::instance();
	
	// this should not happend, but...
	if (!$db->table_exists('members_traffics') ||
		version_compare(ORM::factory('user')->get_mysql_version(), '5.1.0', '<'))
	{
		Settings::set('ulogd_enabled', 0);
		return true;
	}
	
	// pre delete (if update went wrong previously)
	try
	{
		Members_traffic_Model::destroy_tables();
	}
	catch (Exception $e)
	{
		Log::add_exception($e);
	}
	
	// create tables and basic partitions
	try
	{
		Members_traffic_Model::create_tables();
			
		// days
		$prev = '';
		$current = time();
		$last = strtotime($db->query("
			SELECT DATE_SUB('" . date('Y-m-d', $current) . "', INTERVAL 2 MONTH) AS t
		")->current()->t);

		for ($i = $last; $i <= $current; $i += 86400)
		{
			if (date('Y_m_d', $i) != $prev)
			{
				$db->query("
					ALTER TABLE members_traffics_daily
					ADD PARTITION (
						PARTITION p_" . date('Y_m_d', $i) . "
						VALUES LESS THAN (TO_DAYS('" . date('Y-m-d', $i + 86400) . "')
					) ENGINE = InnoDB)
				");
			}
				
			$prev = date('Y_m_d', $i);
		}

		// monthts
		$prev = '';
		$i = strtotime($db->query("
			SELECT DATE_SUB('" . date('Y-m-d', $current) . "', INTERVAL 2 YEAR) AS t
		")->current()->t);

		while ($i <= $current)
		{
			$prev = date('Y_m_01', $i);
			
			// next month
			$i = strtotime($db->query("
				SELECT DATE_ADD('" . date('Y-m-d', $i) . "', INTERVAL 1 MONTH) AS t
			")->current()->t);
			
			if (date('Y_m_01', $i) != $prev)
			{
				$db->query("
					ALTER TABLE members_traffics_monthly
					ADD PARTITION (
						PARTITION p_$prev
						VALUES LESS THAN (TO_DAYS('" . date('Y-m-01', $i) . "')
					) ENGINE = InnoDB)
				");
			}
		}
	}
	catch (Exception $e)
	{
		Settings::set('ulogd_enabled', 0);
		Log::add_exception($e);
		
		try
		{
			Members_traffic_Model::destroy_tables();
		}
		catch (Exception $e)
		{
			Log::add_exception($e);
		}
		
		return true;
	}
	
	// fill old data
	
	$db->query("
		INSERT INTO members_traffics_daily
			(member_id, upload, download, local_upload, local_download, active, date)
		SELECT member_id,
			IFNULL(SUM(upload), 0),
			IFNULL(SUM(download), 0),
			IFNULL(SUM(local_upload), 0),
			IFNULL(SUM(local_download), 0),
			active, day
		FROM members_traffics
		WHERE DATE_SUB(NOW(), INTERVAL 2 MONTH) <= day
		GROUP BY member_id, TO_DAYS(day)
	");
	
	$db->query("
		INSERT INTO members_traffics_monthly
			(member_id, upload, download, local_upload, local_download, date)
		SELECT member_id,
			IFNULL(SUM(upload), 0),
			IFNULL(SUM(download), 0),
			IFNULL(SUM(local_upload), 0),
			IFNULL(SUM(local_download), 0),
			CONCAT(YEAR(day), '-', MONTH(day), '-00')
		FROM members_traffics
		WHERE DATE_SUB(NOW(), INTERVAL 2 YEAR) <= day
		GROUP BY member_id, YEAR(day), MONTH(day)
	");
	
	$db->query("
		INSERT INTO members_traffics_yearly
			(member_id, upload, download, local_upload, local_download, date)
		SELECT member_id,
			IFNULL(SUM(upload), 0),
			IFNULL(SUM(download), 0),
			IFNULL(SUM(local_upload), 0),
			IFNULL(SUM(local_download), 0),
			CONCAT(YEAR(day), '-00-00')
		FROM members_traffics
		GROUP BY member_id, YEAR(day)
	");
	
	// drop old table
	$db->query("DROP TABLE IF EXISTS members_traffics");
	
	// diasable ulogd
	try
	{
		Ulog2_ct_Model::destroy_functions();
	}
	catch (Exception $e)
	{
		Log::add_exception($e);
	}
	
	// re-enable ulogd if enabled
	if (Settings::get('ulogd_enabled'))
	{		
		try
		{
			Ulog2_ct_Model::create_functions();
		}
		catch (Exception $e)
		{
			Settings::set('ulogd_enabled', 0);
			Log::add_exception($e);
		}
	}
	
	return true;
}

/**
 * Creates new structure for traffic logging.
 * 
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 */
$upgrade_sql['1.0.0~rc9'] = array
(
);
