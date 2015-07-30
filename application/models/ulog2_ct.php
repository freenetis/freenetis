<?php

defined('SYSPATH') or die('No direct script access.');
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
 * Model form ulog.
 * 
 * @author Michal Kliment
 * @package Model
 */
class Ulog2_ct_Model extends Model
{

	/**
	 * Creates function to ulogd
	 * 
	 * @author Michal Kliment
	 */
	public static function create_functions()
	{
		$db = Database::instance();
		$db->query("DROP FUNCTION IF EXISTS `UPDATE_CT`");
		$db->query("
				CREATE FUNCTION `UPDATE_CT`(
					`_orig_ip_saddr` INT UNSIGNED,
					`_orig_ip_daddr` INT UNSIGNED,
					`_orig_ip_protocol` TINYINT(3) UNSIGNED,
					`_orig_l4_sport` INT(5),
					`_orig_l4_dport` INT(5),
					`_orig_raw_pktlen` BIGINT,
					`_orig_raw_pktcount` BIGINT,
					`_reply_ip_daddr` INT UNSIGNED,
					`_reply_l4_dport` INT(5),
					`_reply_raw_pktlen` BIGINT,
					`_reply_raw_pktcount` BIGINT,
					`_icmp_code` TINYINT(3),
					`_icmp_type` TINYINT(3),
					`_flow_start_sec` INT(10),
					`_flow_end_sec` INT(10))
					RETURNS bigint(20) unsigned
					READS SQL DATA
				BEGIN
					DECLARE _ip_address VARCHAR (255);
					DECLARE _member_id, _is_local, _upload, _download, _local_upload, _local_download INT;

					SET _ip_address = ip2str(_orig_ip_saddr);

					SET _upload = _orig_raw_pktlen/1024;
					SET _download = _reply_raw_pktlen/1024;
					
					SELECT IF(COUNT(*) > 0, 1, 0) INTO _is_local
					FROM local_subnets ls
					WHERE (_orig_ip_daddr >> 24 & 255 | _orig_ip_daddr >> 8 & 65280 | _orig_ip_daddr << 8 & 16711680 | _orig_ip_daddr << 24 & 4278190080) & INET_ATON(netmask) = INET_ATON(network_address);

					SET _local_upload = _is_local * _upload;
					SET _local_download = _is_local * _download;

					SELECT IFNULL(u.member_id, ip.member_id) INTO _member_id
					FROM ip_addresses ip
					LEFT JOIN ifaces i ON ip.iface_id = i.id
					LEFT JOIN devices d ON i.device_id = d.id
					LEFT JOIN users u ON d.user_id = u.id
					WHERE INET_ATON(ip.ip_address) = INET_ATON(_ip_address);

					INSERT INTO ip_addresses_traffics (ip_address, download, upload, local_download, local_upload, member_id)
					VALUES(_ip_address, _download, _upload, _local_download, _local_upload, _member_id)
					ON DUPLICATE KEY
					UPDATE download = download + _download, upload = upload + _upload, local_download = local_download + _local_download, local_upload = local_upload + _local_upload;
					
					INSERT INTO members_traffics_daily (member_id, download, upload, local_download, local_upload, date)
					VALUES(_member_id, _download, _upload, _local_download, _local_upload, CURDATE())
					ON DUPLICATE KEY
					UPDATE download = download + _download, upload = upload + _upload, local_download = local_download + _local_download, local_upload = local_upload + _local_upload;
					
					INSERT INTO members_traffics_monthly (member_id, download, upload, local_download, local_upload, date)
					VALUES(_member_id, _download, _upload, _local_download, _local_upload, DATE_FORMAT(CURDATE(), '%Y-%m-00'))
					ON DUPLICATE KEY
					UPDATE download = download + _download, upload = upload + _upload, local_download = local_download + _local_download, local_upload = local_upload + _local_upload;
					
					INSERT INTO members_traffics_yearly (member_id, download, upload, local_download, local_upload, date)
					VALUES(_member_id, _download, _upload, _local_download, _local_upload, DATE_FORMAT(CURDATE(), '%Y-00-00'))
					ON DUPLICATE KEY
					UPDATE download = download + _download, upload = upload + _upload, local_download = local_download + _local_download, local_upload = local_upload + _local_upload;
					
					REPLACE `config` (`name`, `value`) VALUES ('logging_state', NOW());
					
					RETURN _member_id;
				END
			");
		$db->query("DROP FUNCTION IF EXISTS `insert_rand`");
		$db->query("
				CREATE FUNCTION `insert_rand`() RETURNS bigint(20) unsigned
					READS SQL DATA
				BEGIN
				SET @tnow=now();
					INSERT INTO ulog2_ct
					       (orig_ip_daddr,
        					orig_l4_dport,
        					reply_l4_dport,
        					flow_start_sec,
        					flow_end_sec
    					)
    					VALUES (
        					rand()*4294967295,
        					rand()*80,
        					rand()*65535,
        					unix_timestamp(@tnow - interval (rand()*60) second),
        					unix_timestamp(@tnow)
    					);
    					RETURN LAST_INSERT_ID();
					END
			");
		$db->query("DROP FUNCTION IF EXISTS `ip2str`");
		$db->query("
				CREATE FUNCTION `ip2str`(ip INT UNSIGNED) RETURNS varchar(15) CHARSET latin1
					DETERMINISTIC
					RETURN concat(ip & 255, \".\", ip>>8 & 255, \".\", ip>>16 & 255, \".\", ip>>24)
			");
		$db->query("DROP FUNCTION IF EXISTS `swap_endian`");
		$db->query("
				CREATE FUNCTION `swap_endian`(ip INT UNSIGNED) RETURNS int(10) unsigned
					DETERMINISTIC
					RETURN ((ip & 255)<<24 | (ip & 65280)<<8 | (ip>>8 & 65280) | ip>>24)
			");
	}

	/**
	 * Destroy function to ulogd
	 * 
	 * @author Michal Kliment
	 */
	public static function destroy_functions()
	{
		$db = Database::instance();
		$db->query("DROP FUNCTION IF EXISTS `UPDATE_CT`");
		$db->query("DROP FUNCTION IF EXISTS `insert_rand`");
		$db->query("DROP FUNCTION IF EXISTS `ip2str`");
		$db->query("DROP FUNCTION IF EXISTS `swap_endian`");
	}

	/**
	 * Checks pre requirements for ulogd
	 *
	 * @author Michal Kliment
	 * @return boolean
	 */
	public function check_pre_requirements()
	{
		$user_model = new User_Model();

		return ($user_model->function_exists('UPDATE_CT') &&
				$user_model->function_exists('insert_rand') &&
				$user_model->function_exists('ip2str') &&
				$user_model->function_exists('swap_endian'));
	}

}
