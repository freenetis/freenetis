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
 * Link is providing connection between two interfaces. Type is given by
 * medium (e.g. wifi, cable). Link may be specified by attributed such as
 * bitrate, duplex, GPS line, etc. Moreover ink of wifi type may be also
 * specified by several other attributes (norm, frequency, channel, ssid, etc.).
 * 
 * @package Model
 * 
 * @property integer $id
 * @property string $name
 * @property integer $medium
 * @property integer $bitrate
 * @property integer $duplex
 * @property string $comment
 * @property blob $gps
 * @property string $wireless_ssid
 * @property integer $wireless_norm
 * @property integer $wireless_frequency
 * @property integer $wireless_channel
 * @property integer $wireless_channel_width
 * @property integer $wireless_polarization
 * @property ORM_Iterator $ifaces
 */
class Link_Model extends ORM
{
	protected $has_many = array('ifaces');
	
	// mediums
	const MEDIUM_ROAMING			= 1;
	const MEDIUM_AIR				= 2;
	const MEDIUM_CABLE				= 3;
	const MEDIUM_SINGLE_FIBER		= 4;
	const MEDIUM_MULTI_FIBER		= 5;
	
	// constants of norms
	const NORM_802_11_A		= 1;
	const NORM_802_11_B		= 2;
	const NORM_802_11_G		= 3;
	const NORM_802_11_N		= 4;
	const NORM_802_11_B_G	= 5;
	
	// constants of polarizations
	const POLARIZATION_HORIZONTAL			= 1;
	const POLARIZATION_VERTICAL				= 2;
	const POLARIZATION_CIRCULAR				= 3;
	const POLARIZATION_BOTH					= 4;
	const POLARIZATION_HORIZONTAL_VERTICAL	= 5;
	
	/**
	 * Array of norms, keys of array are equal to constants of norms
	 * 
	 * @var array 
	 */
	private static $wireless_norms = array
	(
		self::NORM_802_11_A		=> '802.11a',
		self::NORM_802_11_B		=> '802.11b',
		self::NORM_802_11_G		=> '802.11g',
		self::NORM_802_11_N		=> '802.11n',
		self::NORM_802_11_B_G	=> '802.11b/g'
	);
	
	/**
	 * Array of polarizations, keys of array are equal to constants of polarizations
	 * 
	 * @var array 
	 */
	private static $wireless_polarizations = array
	(
		self::POLARIZATION_HORIZONTAL			=> 'horizontal',
		self::POLARIZATION_VERTICAL				=> 'vertical',
		self::POLARIZATION_CIRCULAR				=> 'circular',
		self::POLARIZATION_BOTH					=> 'both',
		self::POLARIZATION_HORIZONTAL_VERTICAL	=> 'horizontal and vertical'
	);
	
	/**
	 * Array of max bitrates
	 * 
	 * @var array
	 */
	private static $wireless_max_bitrates = array
	(
		self::NORM_802_11_A		=> 54,
		self::NORM_802_11_B		=> 11,
		self::NORM_802_11_G		=> 54,
		self::NORM_802_11_N		=> 600,
		self::NORM_802_11_B_G	=> 54
	);
	
	/**
	 * Medium types
	 *
	 * @var array
	 */
	private static $medium_types = array
	(
		self::MEDIUM_ROAMING		=> 'roaming',
		self::MEDIUM_AIR			=> 'air',
		self::MEDIUM_CABLE			=> 'cable',
		self::MEDIUM_SINGLE_FIBER	=> 'fiber optical single-mode',
		self::MEDIUM_MULTI_FIBER	=> 'fiber optical multi-mode'
	);
	
	/**
	 * Max allowed interfaces in link by type
	 * 
	 * @var array
	 */
	private static $ifaces_count = array
	(
		Iface_Model::TYPE_WIRELESS	=> PHP_INT_MAX,
		Iface_Model::TYPE_ETHERNET	=> 2,
		Iface_Model::TYPE_PORT		=> 2,
		Iface_Model::TYPE_INTERNAL	=> 0,
		Iface_Model::TYPE_VLAN		=> 0,
		Iface_Model::TYPE_BRIDGE		=> 0,
		Iface_Model::TYPE_VIRTUAL_AP => PHP_INT_MAX
	);

	/**
	 * Returns norm name by given norm id
	 * 
	 * @author Michal Kliment
	 * @param integer $norm
	 * @return string 
	 */
	public static function get_wireless_norm ($norm)
	{
		if (array_key_exists($norm, self::$wireless_norms))
		{
			return self::$wireless_norms[$norm];
		}
		
		return NULL;
	}
	
	/**
	 * Returns all norm names
	 * 
	 * @author Michal Kliment
	 * @return array 
	 */
	public static function get_wireless_norms ()
	{		
		return self::$wireless_norms;
	}
	
	/**
	 * Returns polarization name by given polarization id
	 * 
	 * @author Michal Kliment
	 * @param integer $polarization
	 * @return string 
	 */
	public static function get_wireless_polarization ($polarization)
	{
		if (array_key_exists($polarization, self::$wireless_polarizations))
		{
			return __(self::$wireless_polarizations[$polarization]);
		}
		
		return NULL;
	}
	
	/**
	 * Returns all polarization names
	 * 
	 * @author Michal Kliment
	 * @return array 
	 */
	public static function get_wireless_polarizations()
	{
		return array_map('__', self::$wireless_polarizations);
	}
	
	/**
	 * Returns max bitrate of norm
	 * 
	 * @author Michal Kliment
	 * @param integer $norm
	 * @return integer 
	 */
	public static function get_wireless_max_bitrate($norm = NULL)
	{
		if (array_key_exists($norm, self::$wireless_max_bitrates))
		{
			return self::$wireless_max_bitrates[$norm];
		}
		
		return NULL;
	}
	
	/**
	 * Returns all max bitrates of norms
	 * 
	 * @author Michal Kliment
	 * @param integer $norm
	 * @return integer 
	 */
	public static function get_wireless_max_bitrates()
	{
		return self::$wireless_max_bitrates;
	}
	
	/**
	 * Returns medium type by medium type id
	 * 
	 * @author Michal Kliment
	 * @param integer $type_id
	 * @return string 
	 */
	public static function get_medium_type($medium_type_id)
	{
		if (isset(self::$medium_types[$medium_type_id]))
		{
			return __(self::$medium_types[$medium_type_id]);
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * Returns maximum number of interfaces in link
	 * 
	 * @author David Raška
	 * @param integer $iface_type
	 * @return integer
	 */
	public static function get_max_ifaces_count ($iface_type)
	{
		if (array_key_exists($iface_type, self::$ifaces_count))
		{
			return self::$ifaces_count[$iface_type];
		}
		
		return NULL;
	}
	
	/**
	 * Returns medium types
	 * 
	 * @author Michal Kliment
	 * @return array 
	 */
	public static function get_medium_types()
	{		
		return array_map('__', self::$medium_types);
	}

	/**
	 * Counts all links
	 *
	 * @param array $filter_sql
	 * @return integer
	 */
	public function count_all_links($filter_sql = '')
	{
		$where = '';
		
		// filter
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
			SELECT COUNT(*) AS total
			FROM
			(
				SELECT s.id, s.name, s.medium, s.duplex, s.comment, s.bitrate,
					IFNULL(s.wireless_ssid,'') AS ssid, s.wireless_norm,
					(SELECT COUNT(*) FROM ifaces WHERE link_id = s.id) as items_count
				FROM links s
			) s
			$where
		", Config::get('lang'))->current()->total;
	}
	
	/**
	 * Gets all linkss
	 *
	 * @param integer $limit_from
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param array $filter_values
	 * @return Mysql_Result
	 */
	public function get_all_links(
			$limit_from = 0, $limit_results = 20,
			$order_by = 'id', $order_by_direction = 'asc',
			$filter_sql = '')
	{
		$where = '';
		
		if ($filter_sql != '')
			$where = "WHERE $filter_sql";
		
		// query
		return $this->db->query("
			SELECT * FROM
			(
				SELECT s.id, s.name, s.medium, s.duplex, s.comment, s.bitrate,
					IFNULL(s.wireless_ssid,'') AS ssid, s.wireless_norm,
					(SELECT COUNT(*) FROM ifaces WHERE link_id = s.id) as items_count
				FROM links s
			) s
			$where
			ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
			LIMIT " .intval($limit_from) . ", " . intval($limit_results) . "
		", Config::get('lang'));			
	}

	/**
	 * Returns roaming link. This function changes current object values.
	 *
	 * @author Michal Kliment
	 * @return integer
	 */
	public function get_roaming()
	{
		$roaming = $this->where('medium', self::MEDIUM_ROAMING)->find();
		return ($roaming && $roaming->id) ? $roaming->id : NULL;
	}
	
	/**
	 * Returns all items (interfaces) which belong to given link
	 * 
	 * @author Michal Kliment
	 * @param integer $link_id
	 * @return Mysql_Result 
	 */
	public function get_items($link_id = NULL)
	{
		if (!$link_id)
		{
			$link_id = $this->id;
		}
		
		return $this->db->query("
			SELECT i.*, d.name AS device_name, m.name AS member_name,
				m.id AS member_id, i.wireless_mode
			FROM ifaces i
			JOIN devices d ON i.device_id = d.id
			JOIN users u ON d.user_id = u.id
			JOIN members m ON u.member_id = m.id
			WHERE i.link_id = ?
			ORDER BY i.wireless_mode ASC, i.name ASC, i.type ASC
		", $link_id);
	}
	
	/**
	 * Returns all links according to given type of iface
	 * 
	 * @author Ondřej Fibich
	 * @param integer $type
	 * @return array 
	 */
	public function select_list_by_iface_type($type)
	{
		$mediums = Iface_Model::get_type_has_link_with_medium($type);
		
		if (count($mediums))
		{
			return $this->in('medium', $mediums)->select_list('id', 'name');
		}
		else
		{
			return $this->select_list('id', 'name');
		}
	}
}
