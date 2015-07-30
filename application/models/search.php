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
	 * Definitions of search rules, each item contains:
	 * 
	 * - method: that defines which method of search model should be use for searching
	 * - model: that defines where searched property belongs
	 * - weight: that defines importance of the property in system, these field
	 *   is used for searching of results. Weight should be a non-negative
	 *   number from interval (0, 10>
	 * - ignore_special_threatment: that defines whether weight can be changed
	 *   if property equals to searched keyword [optional, default FALSE]
	 * - variation_enabled: enable variations of keyword for searching this 
	 *   property, used because performance reasons [optional, default FALSE]
	 * - access: access array with AXO value and AXO section required for 
	 *   searching this property
	 *
	 * @var array
	 */
	public static $rules = array
	(
		array
		(
			'method' => 'member_name',
			'model' => 'member',
			'weight' => 5,
			'variation_enabled' => TRUE,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_id',
			'model' => 'member',
			'weight' => 0.1,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_variable_symbol',
			'model' => 'member',
			'weight' => 0.1,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_comment',
			'model' => 'member',
			'weight' => 0.5,
			'variation_enabled' => TRUE,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_town',
			'model' => 'member',
			'weight' => 0.4,
			'ignore_special_threatment' => TRUE,
			'variation_enabled' => TRUE,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_street',
			'model' => 'member',
			'weight' => 0.6,
			'variation_enabled' => TRUE,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_street_number',
			'model' => 'member',
			'weight' => 0.1,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_organization_identifier',
			'model' => 'member',
			'weight' => 0.1,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'member_vat_organization_identifier',
			'model' => 'member',
			'weight' => 0.1,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'user_name',
			'model' => 'user',
			'weight' => 1,
			'variation_enabled' => TRUE,
			'access' => array('Users_Controller', 'show')
		),
		array
		(
			'method' => 'user_login',
			'model' => 'user',
			'weight' => 0.5,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'user_contact',
			'model' => 'user',
			'weight' => 0.1,
			'access' => array('Members_Controller', 'members')
		),
		array
		(
			'method' => 'town_name',
			'model' => 'town',
			'weight' => 1,
			'variation_enabled' => TRUE,
			'access' => array('Address_points_Controller', 'town')
		),
		array
		(
			'method' => 'device_name',
			'model' => 'device',
			'weight' => 1,
			'variation_enabled' => TRUE,
			'access' => array('Devices_Controller', 'devices')
		),
		array
		(
			'method' => 'device_mac',
			'model' => 'device',
			'weight' => 0.5,
			'access' => array('Devices_Controller', 'devices')
		),
		array
		(
			'method' => 'device_ip_address',
			'model' => 'device',
			'weight' => 0.5,
			'access' => array('Devices_Controller', 'devices')
		),
		array(
			'method' => 'device_ssid',
			'model' => 'device',
			'weight' => 0.5,
			'access' => array('Devices_Controller', 'devices')
		),
		array
		(
			'method' => 'subnet_name',
			'model' => 'subnet',
			'weight' => 1,
			'variation_enabled' => TRUE,
			'access' => array('Subnets_Controller', 'subnet')
		),
		array
		(
			'method' => 'subnet_address',
			'model' => 'subnet',
			'weight' => 1,
			'access' => array('Subnets_Controller', 'subnet')
		),
		array
		(
			'method' => 'link_name',
			'model' => 'link',
			'weight' => 1,
			'variation_enabled' => TRUE,
			'access' => array('Links_Controller', 'link')
		),
	);
	
	/**
	 * Comparator for rules by weight (highest is first)
	 * 
	 * @param mixed $a
	 * @param mixed $b
	 * @return integer
	 */
	private static function cmp_rule_weight($a, $b)
	{
		return ($a['weight'] < $b['weight']) ? 
					1 : (($a['weight'] == $b['weight']) ? 0 : -1);
	}

	/**
	 * Returns sorted rules by their weight (highest is first)
	 * 
	 * @return array Sorted rules
	 */
	public static function get_rules_sorted_by_weight()
	{
		// get rules (clone)
		$rules = self::$rules;
		$rules_count = count($rules);
		
		// remove items with restricted access
		for ($i = 0; $i < $rules_count; $i++)
		{
			$axo = NULL;
			
			if (isset($rules[$i]['access']) &&
				is_array($rules[$i]['access']) &&
				count($rules[$i]['access']) >= 2)
			{
				$axo = $rules[$i]['access'];
			}
			
			if ($axo && !Controller::instance()->acl_check_view($axo[0], $axo[1]))
			{
				unset($rules[$i]); // remove rule
			}
		}
		
		// sort rules
		usort($rules, 'Search_Model::cmp_rule_weight');
		
		// return values
		return $rules;
	}

	/**
	 * Searchs in members by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_name($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
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
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function member_id($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT m.id, m.id AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.id LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
		(
			" (" . __('I') . ")",
			"$keyword%"
		));
	}

	/**
	 * Searchs in members by variable symbol
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_variable_symbol($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
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
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE vs.variable_symbol LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
		(
			__('Variable symbol') . ": ",
			" (" . __('I') . ")",
			"$keyword%"
		));
	}

	/**
	 * Searchs in members by comment
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_comment($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT m.id, m.comment AS value,
					CONCAT(?,m.comment) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.comment LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function member_town($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT m.id,
					CONCAT(town, IF(quarter IS NOT NULL, CONCAT(' ', quarter), '')) AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE CONCAT(tw.town, IF(tw.quarter IS NOT NULL, CONCAT(' ', tw.quarter), '')) LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, " (" . __('I') . ")", "%$keyword%");
	}

	/**
	 * Searchs in members by street and street number
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_street($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
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
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, " (" . __('I') . ")", "%$keyword%");
	}

	/**
	 * Searchs in members by street and street number
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_street_number($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT m.id,
					ap.street_number AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE ap.street_number LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, " (" . __('I') . ")", "$keyword%");
	}
	
	/**
	 * Searchs in members by organization identifier
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_organization_identifier($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT m.id, m.organization_identifier AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.organization_identifier LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
		(
			" (" . __('I') . ")",
			"$keyword%"
		));
	}
	
	/**
	 * Searchs in members by VAT organization identifier
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function member_vat_organization_identifier($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT m.id, m.vat_organization_identifier AS value,
					CONCAT('ID ',m.id,', ',s.street,' ',ap.street_number,', ',tw.town,IF(tw.quarter IS NOT NULL,CONCAT('-',tw.quarter),'')) AS `desc`,
					CONCAT(IFNULL(t.translated_term,e.value),' ',m.name,IF(mf.id IS NOT NULL,?,'')) AS return_value,
					'members/show/' AS link FROM members m
				JOIN enum_types e ON m.type = e.id
				LEFT JOIN translations t ON e.value LIKE t.original_term AND t.lang = 'cs'
				JOIN address_points ap ON m.address_point_id = ap.id
				LEFT JOIN streets s ON ap.street_id = s.id
				JOIN towns tw ON ap.town_id = tw.id
				LEFT JOIN membership_interrupts mi ON m.id = mi.member_id
				LEFT JOIN members_fees mf ON mi.members_fee_id = mf.id AND (mf.activation_date < CURDATE() AND mf.deactivation_date > CURDATE())
				WHERE m.vat_organization_identifier LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
		(
			" (" . __('I') . ")",
			"$keyword%"
		));
	}

	/**
	 * Searchs in users by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function user_name($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT u.id,
					CONCAT(u.name,' ',u.surname) AS value,
					CONCAT(?, u.login) AS `desc`,
					CONCAT(?, u.name, ' ',u.surname) AS return_value, 'users/show/' AS link
				FROM users u
				WHERE CONCAT(u.name,' ',u.surname) LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function user_login($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT u.id, u.login AS value,
					CONCAT(?,u.login) AS `desc`,
					CONCAT(?, u.name, ' ',u.surname) AS return_value,
					'users/show/' AS link
				FROM users u WHERE u.login LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function user_contact($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
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
				ORDER BY ABS(LENGTH(c.value) - " . mb_strlen($keyword) . ")
		".$sql_limit, __('User') . ": ", "%$keyword%");
	}

	/**
	 * Searchs in towns by name
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @return MySQL_Result object
	 */
	public function town_name($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT t.id, 
					CONCAT(t.town, IF(t.quarter IS NOT NULL, CONCAT(' ', t.quarter), '')) AS value,
					CONCAT(?,t.zip_code) AS `desc`,
					CONCAT(?,t.town,IF(t.quarter IS NOT NULL AND t.quarter <> '',CONCAT('-',t.quarter),'')) AS return_value,
					'towns/show/' AS link
				FROM towns t
				WHERE CONCAT(t.town, IF(t.quarter IS NOT NULL, CONCAT(' ', t.quarter), '')) LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function device_name($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT d.id, d.name AS value,
					CONCAT(?,u.name,' ',u.surname) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN users u ON d.user_id = u.id
				WHERE d.name LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function device_mac($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT d.id, ic.mac AS value,
					CONCAT(?, ic.mac) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN ifaces ic ON ic.device_id = d.id
				WHERE ic.mac LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function device_ip_address($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT d.id, ip.ip_address AS value,
					CONCAT(?, ip.ip_address) AS `desc`,
					CONCAT(?, d.name) AS return_value,
					'devices/show/' AS link
				FROM devices d
				JOIN ifaces ic ON ic.device_id = d.id
				LEFT JOIN ip_addresses ip ON ip.iface_id = ic.id
				WHERE ip.ip_address LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function device_ssid($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
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
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function subnet_name($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT s.id, s.name AS value,
					CONCAT(?,s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) AS `desc`,
					CONCAT(?,s.name) AS return_value,
					'subnets/show/' AS link
				FROM subnets s
				WHERE s.name LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function subnet_address($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT s.id,
					CONCAT(s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) AS value,
					CONCAT(?,s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) AS `desc`,
					CONCAT(?,s.name) AS return_value,
					'subnets/show/' AS link
				FROM subnets s
				WHERE CONCAT(s.network_address,'/',32-log2((~inet_aton(s.netmask) & 0xffffffff) + 1)) LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
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
	public function link_name($keyword, $limit = null)
	{
		$sql_limit = '';
		if ($limit)
		{
			$sql_limit = ' LIMIT '.$limit;
		}
		
		return $this->db->query("
				SELECT s.id, s.name AS value,
					CONCAT(?, s.name) AS return_value,
					CONCAT(?, ROUND(s.bitrate / 1048576), ' Mbps') AS `desc`,
					'links/show/' AS link
				FROM links s
				WHERE s.name LIKE ? COLLATE utf8_general_ci
				ORDER BY ABS(LENGTH(value) - " . mb_strlen($keyword) . ")
		".$sql_limit, array
		(
			__('Link') . ' ',
			__('Bitrate') . ': ',
			"%$keyword%"
		));
	}

}
