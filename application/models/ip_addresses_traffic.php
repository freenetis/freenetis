<?php defined('SYSPATH') or die('No direct script access.');
/*
 * This file is part of open source system FreenetIS
 * and it is release under GPLv3 licence.
 * 
 * More info about licence can be found:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * 
 * More info about project can be found:
 * http://www.freenetis.org/
 * 
 */

/**
 * Traffics of IP address.
 * 
 * @author Michal Kliment
 * @package Model
 * 
 * @property string $ip_address
 * @property integer $upload
 * @property integer $download
 * @property integer $member_id
 */
class Ip_addresses_traffic_Model extends ORM
{
	/**
	 * Definition of order columns
	 * 
	 * @var array
	 */
	private static $ORDER_COLUMNS = array
	(
		'local_upload',
		'local_download',
		'foreign_upload',
		'foreign_download',
		'upload',
		'download',
		'member_id'
	);
	
	/**
	 * Returns all traffics of ip addresses
	 *
	 * @author Michal Kliment
	 * @param integer $sql_offset
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return MySQL_Result object
	 */
	public function get_all_ip_addresses_traffics(
			$limit_from = 0, $limit_results = 100,
			$order_by = 'download', $order_by_direction = 'DESC',
			$filter_sql = '')
	{
        // order by check
        if (!in_array($order_by, self::$ORDER_COLUMNS))
                $order_by = 'download';
	
	// order by direction check
	if (strtolower($order_by_direction) != 'desc')
	{
		$order_by_direction = 'asc';
	}
	
	$where = '';
	if ($filter_sql != '')
		$where = 'WHERE '.$filter_sql;
	
		// query
		return $this->db->query("
				SELECT * FROM
				(
					SELECT
						t.ip_address,
						t.local_upload,
						t.local_download,
						(t.upload - t.local_upload) AS foreign_upload,
						(t.download - t.local_download) AS foreign_download,
						t.upload,
						t.download,
						(t.upload+t.download) AS total,
						m.id AS member_id,
						m.name AS member_name
					FROM ip_addresses_traffics t
					LEFT JOIN members m ON t.member_id = m.id
				) as q
				$where
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}

	/**
	 * Counts all traffics of ip addresses
	 *
	 * @author Michal Kliment
	 * @return integer
	 */
	public function count_all_ip_addresses_traffics($filter_sql = '')
	{
		$where = '';
		if ($filter_sql != '')
			$where = 'WHERE '.$filter_sql;
		
		return $this->db->query("
				SELECT COUNT(*) AS count FROM
				(
					SELECT
						t.ip_address,
						t.local_upload,
						t.local_download,
						(t.upload - t.local_upload) AS foreign_upload,
						(t.download - t.local_download) AS foreign_download,
						t.upload,
						t.download,
						(t.upload+t.download) AS total,
						m.id AS member_id,
						m.name AS member_name
					FROM ip_addresses_traffics t
					LEFT JOIN members m ON t.member_id = m.id
				) as q
				$where
		")->current()->count;
	}

	/**
	 * Cleans (truncate table) traffics of ip addresses
	 *
	 * @author Michal Kliment
	 */
	public function clean_ip_addresses_traffics()
	{
		$this->db->query("TRUNCATE ip_addresses_traffics;");
	}

	/**
	 * Inserts new traffics of ip addresses, from ulogd table
	 *
	 * @author Michal Kliment
	 */
	public function insert_ip_addresses_traffics()
	{
		$this->db->query("
				INSERT INTO ip_addresses_traffics
				SELECT ip2str(orig_ip_saddr) AS ip_address,
					SUM(orig_raw_pktlen/1024) AS upload,
					SUM(reply_raw_pktlen/1024) AS download, NULL AS member_id
				FROM ulog2_ct
				GROUP BY orig_ip_saddr;
		");
	}

	/**
	 * Updates member's ids of ip addresses
	 *
	 * @author Michal Kliment
	 */
	public function update_member_ids()
	{
		// first for ip addresses of ifaces
		$this->db->query("
				UPDATE ip_addresses_traffics t, ip_addresses ip, ifaces i, devices d, users u
				SET t.member_id = u.member_id
				WHERE t.ip_address = ip.ip_address AND ip.iface_id = i.id AND
					i.device_id = d.id AND d.user_id = u.id
		");

		// now for ip addresses which are not in ip_addresses table and have owner
		$this->db->query("
				UPDATE ip_addresses_traffics t, subnets s, subnets_owners so, members m
				SET t.member_id = m.id
				WHERE t.member_id IS NULL AND
					inet_aton(t.ip_address) & inet_aton(s.netmask) = inet_aton(s.network_address) AND
					s.id = so.subnet_id AND so.member_id = m.id
		");
	}
}
