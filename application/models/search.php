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
 * Search model is helper for searching throught all database
 * 
 * @author Michal Kliment
 * @package Model
 */
class Search_Model extends Model
{
	/**
	 * Definitions of search rules
	 *
	 * @var array
	 */
	public static $rules = array
	(
		array
		(
			'method' => 'member_name',
			'model' => 'member'
		),
		array
		(
			'method' => 'member_id',
			'model' => 'member'
		),
		array
		(
			'method' => 'member_variable_symbol',
			'model' => 'member'
		),
		array
		(
			'method' => 'member_comment',
			'model' => 'member'
		),
		array
		(
			'method' => 'member_town',
			'model' => 'member'
		),
		array
		(
			'method' => 'member_street',
			'model' => 'member'
		),
		array
		(
			'method' => 'user_name',
			'model' => 'user',
		),
		array
		(
			'method' => 'user_login',
			'model' => 'user',
		),
		array
		(
			'method' => 'user_contact',
			'model' => 'user',
		),
		array
		(
			'method' => 'town_name',
			'model' => 'town'
		),
		array
		(
			'method' => 'device_name',
			'model' => 'device'
		),
		array
		(
			'method' => 'device_mac',
			'model' => 'device'
		),
		array
		(
			'method' => 'device_ip_address',
			'model' => 'device'
		),
		array(
			'method' => 'device_ssid',
			'model' => 'device'
		),
		array
		(
			'method' => 'subnet_name',
			'model' => 'subnet'
		),
		array
		(
			'method' => 'subnet_address',
			'model' => 'subnet'
		),
		array
		(
			'method' => 'link_name',
			'model' => 'link'
		),
	);

	/**
	 * Searchs in members by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_name($keyword)
	{
		return $this->db->query("
				SELECT m.id, m.name AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.name LIKE ? COLLATE utf8_general_ci
		", array
		(
			" (" . __('I') . ")",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in members by ID
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_id($keyword)
	{
		return $this->db->query("
				SELECT m.id, m.id AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.id LIKE ? COLLATE utf8_general_ci
		", array
		(
			" (" . __('I') . ")",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in members by variable symbol
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_variable_symbol($keyword)
	{
		return $this->db->query("
				SELECT m.id, vs.variable_symbol AS value,
					CONCAT(?, vs.variable_symbol) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN accounts a ON a.member_id = m.id
				LEFT JOIN variable_symbols vs ON vs.account_id = a.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE vs.variable_symbol LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Variable symbol') . ": ",
			" (" . __('I') . ")",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in members by comment
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_comment($keyword)
	{
		return $this->db->query("
				SELECT m.id, m.comment AS value,
					CONCAT(?,m.comment) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.comment LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Comment') . ": ",
			" (" . __('I') . ")",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in members by town
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_town($keyword)
	{
		return $this->db->query("
				SELECT m.id,
					CONCAT(town, ' ',IFNULL(quarter,'')) AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE CONCAT(tw.town, ' ',IFNULL(tw.quarter,'')) LIKE ? COLLATE utf8_general_ci
		", " (" . __('I') . ")", "%$keyword%");
	}

	/**
	 * Searchs in members by street and street number
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_street($keyword)
	{
		return $this->db->query("
				SELECT m.id,
					CONCAT(s.street, ' ',ap.street_number) AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE CONCAT(s.street, ' ',ap.street_number) LIKE ? COLLATE utf8_general_ci
		", " (" . __('I') . ")", "%$keyword%");
	}

	/**
	 * Searchs in users by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function user_name($keyword)
	{
		return $this->db->query("
				SELECT u.id,
					CONCAT(u.name,' ',u.surname) AS value,
					CONCAT(?, u.login) AS `desc`,
					CONCAT(?, u.name, ' ',u.surname) AS return_value, 'users/show/' AS link
				FROM users u
				WHERE CONCAT(u.name,' ',u.surname) LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Username') . ": ",
			__('User') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in users by login
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function user_login($keyword)
	{
		return $this->db->query("
				SELECT u.id, u.login AS value,
					CONCAT(?,u.login) AS `desc`,
					CONCAT(?, u.name, ' ',u.surname) AS return_value,
					'users/show/' AS link
				FROM users u WHERE u.login LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Username') . ": ",
			__('User') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in users by contact
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function user_contact($keyword)
	{
		return $this->db->query("
				SELECT u.id, c.value AS value,
					CONCAT(IFNULL(t.translated_term,e.value),': ',c.value) AS `desc`,
					CONCAT(?, u.name, ' ',u.surname) AS return_value,
					'users/show/' AS link
				FROM users u
				LEFT JOIN users_contacts uc ON u.id = uc.user_id
				LEFT JOIN contacts c ON uc.contact_id = c.id
				LEFT JOIN enum_types e ON c.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				WHERE c.value LIKE ? COLLATE utf8_general_ci
		", __('User') . ": ", "%$keyword%");
	}

	/**
	 * Searchs in towns by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function town_name($keyword)
	{
		return $this->db->query("
				SELECT t.id, CONCAT(t.town, ' ',t.quarter) AS value, t.id AS `desc`,
					CONCAT(?,t.zip_code) AS `desc`,
					CONCAT(?,t.town,IF(t.quarter IS NOT NULL AND t.quarter <> '',CONCAT('-',t.quarter),'')) AS return_value,
					'towns/show/' AS link
				FROM towns t
				WHERE CONCAT(t.town, ' ',t.quarter) LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('ZIP code') . ": ",
			__('Town') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in devices by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function device_name($keyword)
	{
		return $this->db->query("
				SELECT d.id, d.name AS value,
					CONCAT(?,u.name,' ',u.surname) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN users u ON d.user_id = u.id
				WHERE d.name LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('User') . ": ",
			__('Device') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in devices by MAC address
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function device_mac($keyword)
	{
		return $this->db->query("
				SELECT d.id, ic.mac AS value,
					CONCAT(?, ic.mac) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN ifaces ic ON ic.device_id = d.id
				WHERE ic.mac LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('MAC address') . ": ",
			__('Device') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in devices by IP address
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function device_ip_address($keyword)
	{
		return $this->db->query("
				SELECT d.id, ip.ip_address AS value,
					CONCAT(?, ip.ip_address) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN ifaces ic ON ic.device_id = d.id
				LEFT JOIN ip_addresses ip ON ip.iface_id = ic.id
				WHERE ip.ip_address LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('IP address') . ": ",
			__('Device') . " ",
			"%$keyword%", "%$keyword%"
		));
	}
	
	/**
	 * Searchs in devices by SSID
	 * 
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function device_ssid($keyword)
	{
		return $this->db->query("
				SELECT d.id, s.wireless_ssid AS value,
					CONCAT(?, s.wireless_ssid) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN ifaces i ON i.device_id = d.id
				JOIN links s ON i.link_id = s.id
				WHERE i.wireless_mode = ? AND s.medium = ? AND
					s.wireless_ssid LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('SSID') . ": ",
			__('Device') . " ",
			Iface_Model::WIRELESS_MODE_AP,
			Link_Model::MEDIUM_AIR,
			"%$keyword%"
		));
	}

	/**
	 * Searchs in subnets by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function subnet_name($keyword)
	{
		return $this->db->query("
				SELECT s.id, s.name AS value,
					CONCAT(?,s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) AS `desc`,
					CONCAT(?,s.name) AS return_value,
					'subnets/show/' AS link
				FROM subnets s
				WHERE s.name LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Address') . ": ",
			__('Subnet') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in subnets by address
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function subnet_address($keyword)
	{
		return $this->db->query("
				SELECT s.id,
					CONCAT(s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) AS value,
					CONCAT(?,s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) AS `desc`,
					CONCAT(?,s.name) AS return_value,
					'subnets/show/' AS link
				FROM subnets s
				WHERE CONCAT(s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Address') . ": ",
			__('Subnet') . " ",
			"%$keyword%"
		));
	}

	/**
	 * Searchs in linkss by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function link_name($keyword)
	{
		return $this->db->query("
				SELECT s.id, s.name AS value,
					CONCAT(?, s.name) AS return_value,
					CONCAT(?, ROUND(s.bitrate / 1048576), ' Mbps') AS `desc`,
					'links/show/' AS link
				FROM links s
				WHERE s.name LIKE ? COLLATE utf8_general_ci
		", array
		(
			__('Link') . ' ',
			__('Bitrate') . ': ',
			"%$keyword%"
		));
	}

}
