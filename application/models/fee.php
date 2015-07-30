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
 * Defines fees
 * 
 * @package Model
 * 
 * @property integer $id
 * @property boolean $readonly
 * @property double $fee
 * @property date $from
 * @property date $to
 * @property integer $type_id
 * @property Enum_type_Model $enum_type
 * @property name $name
 * @property integer $special_type_id
 * @property ORM_Iterator $members_fees
 */
class Fee_Model extends ORM
{
	/** special type constants */
	const MEMBERSHIP_INTERRUPT = 1;

	protected $belongs_to = array('type' => 'enum_type');
	protected $has_many = array('members_fees');

	/**
	 * Returns all fees with translated type names
	 *
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @return Mysql_Result object
	 */
	public function get_all_fees($order_by = 'from', $order_by_direction = 'asc')
	{
		return $this->db->query("
				SELECT fees.id, fees.readonly, fees.special_type_id, fees.type_id,
					fees.fee, fees.from, fees.to, fees.name,
					IFNULL(translations.translated_term, enum_types.value) as type
				FROM fees
				LEFT JOIN enum_types on enum_types.id = fees.type_id
				LEFT JOIN translations on translations.original_term = enum_types.value
					and translations.lang = ?
				ORDER BY fees.readonly DESC, fees.special_type_id ASC, fees.from ASC
		", Config::get('lang'));
	}

	/**
	 * Funkce vrací poplatek typu $type, který byl platný k datu $datetime
	 * 
	 * @author Tomas Dulik
	 * @param $datetime datum a čas, SQL typ DATETIME
	 * @param $type - textový typ z tabulky enum_types, např. "transfer fee"
	 * @return object obsahující řádek tabulky fees
	 */
	public function get_by_date_type($datetime, $type)
	{
		$result = $this->db->query("
				SELECT fees.*
				FROM fees
				JOIN enum_types ON fees.type_id = enum_types.id
					AND enum_types.value = ?
					AND fees.from <= ?
					AND fees.to >= ?
		", $type, $datetime, $datetime);

		if ($result && $result->count() > 0)
		{
			return $result->current();
		}

		return null;
	}

	/**
	 * Returns all fees belongs to fee type
	 *
	 * @author Michal Kliment
	 * @param numeric $fee_type_id
	 * @return Mysql_Result object
	 */
	public function get_all_fees_by_fee_type_id($fee_type_id)
	{
		return $this->db->query("
				SELECT f.id, f.readonly, f.special_type_id, f.fee, f.from, f.to,
					IFNULL(translations.translated_term, enum_types.value) as type, f.name
				FROM fees f
				LEFT JOIN enum_types on enum_types.id = f.type_id
				LEFT JOIN translations on translations.original_term = enum_types.value
					and translations.lang = ?
				WHERE f.type_id = ?
				ORDER BY f.readonly DESC, f.special_type_id ASC, f.from ASC
		", Config::get('lang'), $fee_type_id);
	}

	/**
	 * Returns fee by member, date and type
	 *
	 * @author Michal Kliment
	 * @param numeric $member_id
	 * @param date $date
	 * @param numeric $type
	 * @return Mysql_Result object
	 */
	public function get_fee_by_member_date_type($member_id, $date, $type)
	{
		$result = $this->db->query("
				SELECT f.*
				FROM members_fees mf
				LEFT JOIN fees f ON mf.fee_id = f.id
				LEFT JOIN enum_types et ON f.type_id = et.id
				WHERE mf.member_id = ?
					AND et.value = ?
					AND mf.activation_date <= ?
					AND mf.deactivation_date >= ?
				ORDER BY mf.priority LIMIT 0,1
		", $member_id, $type, $date, $date);

		if ($result && $result->count() > 0)
		{
			return $result->current();
		}

		return null;
	}

	/**
	 * Returns default fee by date (for member with ID = 1 = association)
	 *
	 * @author Michal Kliment
	 * @param date $date
	 * @param numeric $type
	 * @return Mysql_Result object
	 */
	public function get_default_fee_by_date_type($date, $type)
	{
		return $this->get_fee_by_member_date_type(1, $date, $type);
	}

	/**
	 * Returns regular member fee of member in date
	 *
	 * @author Michal Kliment
	 * @param int $member_id
	 * @param string $date
	 * @return int
	 */
	public function get_regular_member_fee_by_member_date($member_id, $date)
	{
		$fee = $this->get_fee_by_member_date_type($member_id, $date, 'regular member fee');

		if ($fee && $fee->id)
		{
			return $fee->fee;
		}

		$default_fee = $this->get_default_fee_by_date_type($date, 'regular member fee');

		if ($default_fee && $default_fee->id)
			return $default_fee->fee;

		return 0;
	}
	
	/**
	 * Gets transfer fee by date
	 * 
	 * @author Michal Kliment
	 * @param string $date
	 * @return integer 
	 */
	public function get_transfer_fee_by_date($date)
	{
		$default_fee = $this->get_default_fee_by_date_type($date, 'transfer fee');

		if ($default_fee && $default_fee->id)
			return $default_fee->fee;

		return 0;
	}

	/**
	 * Returns fee by special type
	 *
	 * @author Michal Kliment
	 * @param numeric $special_type
	 * @return Mysql_Result object
	 */
	public function get_by_special_type($special_type)
	{
		return $this->db->query("
				SELECT f.*
				FROM fees f
				WHERE special_type_id = ?
		", $special_type)->current();
	}

	/**
	 * Helper method for finding min/max from/to date of fees
	 *
	 * @author Michal Kliment
	 * @param integer $type
	 * @param integer $col
	 * @param string $$order_by_direction
	 * @return Mysql_Result object
	 */
	private function _get_date_fee($type, $col, $order_by_direction)
	{
		if ($this->has_column($col))
		{
			// order by direction check
			if (strtolower($order_by_direction) != 'desc')
			{
				$order_by_direction = 'asc';
			}
			// query
			$result = $this->db->query("
					SELECT f.$col FROM fees f
					LEFT JOIN enum_types et ON f.type_id = et.id
					WHERE et.value = ?
					ORDER BY f.$col $order_by_direction LIMIT 0,1
			", array($type));

			if ($result && $result->count() > 0)
			{
				return $result->current()->$col;
			}
		}
		return null;
	}

	/**
	 * Returns max to date of fees
	 *
	 * @author Michal Kliment
	 * @param integer $type
	 * @return string
	 */
	public function get_max_todate_fee_by_type($type)
	{
		return $this->_get_date_fee($type, 'to', 'DESC');
	}

	/**
	 * Returns min from date of fees
	 *
	 * @author Michal Kliment
	 * @param integer $type
	 * @return string
	 */
	public function get_min_fromdate_fee_by_type($type)
	{
		return $this->_get_date_fee($type, 'from', 'ASC');
	}

}
