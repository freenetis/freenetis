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
 * An interface may be assigned to several IP addresses (commonly just one),
 * and belongs to a device. Two interfaces may be connected using a segment.
 * 
 * Interface is specified by a type:
 * 
 * - wireless (wifi device in mode of AP/client and with specifications: antena, ..)
 * - ethernet
 * - port (link layer - without MAC)
 * - bridge
 * - VLAN
 * - internal (special type of interface for configuration of switches)
 * 
 * @package Model
 * 
 * @property integer $id
 * @property integer $type
 * @property integer $device_id
 * @property integer $link_id
 * @property string $mac
 * @property string $name
 * @property integer $number
 * @property string $comment
 * @property integer $wireless_mode
 * @property integer $wireless_antenna
 * @property integer $port_mode
 * @property Link_Model $link
 * @property Device_Model $device
 * @property ORM_Iterator $ifaces_vlans
 * @property ORM_Iterator $ip_addresses
 * @property ORM_Iterator $parents
 * @property ORM_Iterator $childrens
 */
class Iface_Model extends ORM
{
	protected $belongs_to = array('link', 'device');
	protected $has_many = array
	(
		'ifaces_vlans', 'ip_addresses',
		'ifaces_relationships',
	);
	
	/** Wireless type of iface */
	const TYPE_WIRELESS = 1;
	/** Ethernet type of iface */
	const TYPE_ETHERNET = 2;
	/** Interface is port (link layer - without MAC) */
	const TYPE_PORT = 3;
	/** Bridge interface */
	const TYPE_BRIDGE = 4;
	/** Virtual interface */
	const TYPE_VLAN = 5;
	/** Special type of interface for configuration of switches */
	const TYPE_INTERNAL = 6;
	/** Virtual AP over a wireless iface */
	const TYPE_VIRTUAL_AP = 7;
	
	/** AP mode of wireless iface */
	const WIRELESS_MODE_AP = 1;
	/** Client mode of wireless iface */
	const WIRELESS_MODE_CLIENT = 2;
	
	/** Directional type of antenna */
	const WIRELESS_ANTENNA_DIRECTIONAL = 1;
	/** Omnidirectional type of antenna */
	const WIRELESS_ANTENNA_OMNIDIRECTIONAL = 2;
	/** Sectional type of antenna */
	const WIRELESS_ANTENNA_SECTIONAL = 3;
	
	/** Const for mode access */
	const PORT_MODE_ACCESS = 1;
	/** Const for mode trunk */
	const PORT_MODE_TRUNK = 2;
	/** Const for mode hybrid */
	const PORT_MODE_HYBRID = 3;
	
	/** Const for type tagged */
	const PORT_VLAN_TAGGED = 1;
	/** Const for type untagged */
	const PORT_VLAN_UNTAGGED = 2;
	
	/**
	 * Name of iface types
	 *
	 * @var array
	 */
	private static $types = array
	(
		self::TYPE_WIRELESS => 'Wireless',
		self::TYPE_ETHERNET => 'Ethernet',
		self::TYPE_PORT		=> 'Port',
		self::TYPE_BRIDGE	=> 'Bridge',
		self::TYPE_VLAN		=> 'VLAN',
		self::TYPE_INTERNAL	=> 'Internal',
		self::TYPE_VIRTUAL_AP  => 'Virtual AP'
	);
	
	/**
	 * Shortcuts of iface types
	 *
	 * @var array
	 */
	private static $default_names = array
	(
		self::TYPE_WIRELESS => 'wlan',
		self::TYPE_ETHERNET => 'eth',
		self::TYPE_PORT		=> 'port',
		self::TYPE_BRIDGE	=> 'bridge',
		self::TYPE_VLAN		=> 'vlan',
		self::TYPE_INTERNAL	=> 'internal',
		self::TYPE_VIRTUAL_AP  => 'virtual ap'
	);
	
	/**
	 * Models of wirelless iface
	 *
	 * @var array
	 */
	private static $wireless_modes = array
	(
		self::WIRELESS_MODE_AP		=> 'AP',
		self::WIRELESS_MODE_CLIENT	=> 'client'
	);
	
	/**
	 * Antenas types array
	 *
	 * @var array
	 */
	private static $wireless_antennas = array
	(
		self::WIRELESS_ANTENNA_DIRECTIONAL		=> 'Directional',
		self::WIRELESS_ANTENNA_OMNIDIRECTIONAL	=> 'Omnidirectional',
		self::WIRELESS_ANTENNA_SECTIONAL		=> 'Sectional'
	);
	
	/**
	 * Human format for port modes
	 * 
	 * @var array 
	 */
	private static $port_modes = array
	(
		self::PORT_MODE_ACCESS	=> 'Access',
		self::PORT_MODE_TRUNK	=> 'Trunk',
		self::PORT_MODE_HYBRID	=> 'Hybrid'
	);
	
	/**
	 * Human format for port VLAN types
	 * 
	 * @var array 
	 */
	private static $port_vlan_types = array
	(
		self::PORT_VLAN_TAGGED		=> 'Tagged',
		self::PORT_VLAN_UNTAGGED	=> 'Untagged'
	);
	
	/**
	 * Boolean value if iface type can have link
	 * 
	 * @var array 
	 */
	private static $type_has_link = array
	(
		self::TYPE_WIRELESS => TRUE,
		self::TYPE_ETHERNET => TRUE,
		self::TYPE_PORT		=> TRUE,
		self::TYPE_BRIDGE	=> FALSE,
		self::TYPE_VLAN		=> FALSE,
		self::TYPE_INTERNAL	=> FALSE,
		self::TYPE_VIRTUAL_AP  => TRUE
	);
	
	/**
	 * Link with given mediums which may conect interface with type given as key.
	 * 
	 * @author Ondřej Fibich
	 * @var array
	 */
	private static $type_has_link_with_medium = array
	(
		self::TYPE_WIRELESS => array
		(
			Link_Model::MEDIUM_ROAMING, Link_Model::MEDIUM_AIR
		),
		self::TYPE_ETHERNET => array
		(
			Link_Model::MEDIUM_ROAMING, Link_Model::MEDIUM_CABLE
		),
		self::TYPE_PORT		=> array
		(
			Link_Model::MEDIUM_ROAMING, Link_Model::MEDIUM_CABLE,
			Link_Model::MEDIUM_MULTI_FIBER, Link_Model::MEDIUM_SINGLE_FIBER
		),
		self::TYPE_BRIDGE	=> array(),
		self::TYPE_VLAN		=> array(),
		self::TYPE_INTERNAL	=> array(),
		self::TYPE_VIRTUAL_AP  => array
		(
			Link_Model::MEDIUM_ROAMING, Link_Model::MEDIUM_AIR
		),
	);
	
	/**
	 * Boolean value if iface type can have mac address
	 * 
	 * @var array 
	 */
	private static $type_has_mac_address = array
	(
		self::TYPE_WIRELESS => TRUE,
		self::TYPE_ETHERNET => TRUE,
		self::TYPE_PORT		=> FALSE,
		self::TYPE_BRIDGE	=> TRUE,
		self::TYPE_VLAN		=> FALSE,
		self::TYPE_INTERNAL	=> TRUE,
		self::TYPE_VIRTUAL_AP  => TRUE
	);
	
	/**
	 * Boolean value if iface type can have IP address
	 * 
	 * @var array 
	 */
	private static $type_has_ip_address = array
	(
		self::TYPE_WIRELESS => TRUE,
		self::TYPE_ETHERNET => TRUE,
		self::TYPE_PORT		=> FALSE,
		self::TYPE_BRIDGE	=> TRUE,
		self::TYPE_VLAN		=> TRUE,
		self::TYPE_INTERNAL	=> TRUE,
		self::TYPE_VIRTUAL_AP  => TRUE
	);
	
	/**
	 * List of interface types that can be connected to interface with type
	 * given by key
	 * 
	 * @var array 
	 */
	private static $can_connect_to = array
	(
		self::TYPE_WIRELESS => array(self::TYPE_WIRELESS),
		self::TYPE_ETHERNET => array(self::TYPE_ETHERNET, self::TYPE_PORT),
		self::TYPE_PORT		=> array(self::TYPE_ETHERNET, self::TYPE_PORT),
		self::TYPE_BRIDGE	=> array(),
		self::TYPE_VLAN		=> array(),
		self::TYPE_INTERNAL	=> array(),
		self::TYPE_VIRTUAL_AP  => array(self::TYPE_WIRELESS)
	);
	
	/**
	 * List of interface types of which can be interface type child
	 * 
	 * @var array
	 */
	private static $can_be_child_of = array
	(
		self::TYPE_VLAN => array
		(
			self::TYPE_WIRELESS,
			self::TYPE_ETHERNET,
			self::TYPE_BRIDGE,
			self::TYPE_INTERNAL
		),
		self::TYPE_WIRELESS => array(self::TYPE_BRIDGE),
		self::TYPE_ETHERNET => array(self::TYPE_BRIDGE),
		self::TYPE_VIRTUAL_AP  => array(self::TYPE_WIRELESS)
	);

	/**
	 * Boolean value if wireless antenna type can have azimuth
	 * 
	 * @var array
	 */
	private static $wireless_antenna_has_azimuth = array
	(
		self::WIRELESS_ANTENNA_DIRECTIONAL		=> TRUE,
		self::WIRELESS_ANTENNA_OMNIDIRECTIONAL	=> FALSE,
		self::WIRELESS_ANTENNA_SECTIONAL		=> TRUE
	);
	
	/**
	 * Tests if type can have link
	 * 
	 * @author Michal Kliment
	 * @param type $type
	 * @return boolean 
	 */
	public static function type_has_link($type)
	{
		if (isset(self::$type_has_link[$type]))
			return self::$type_has_link[$type];
		else
			return FALSE;
	}
	
	/**
	 * Gets whole array of indicator of available link
	 * 
	 * @return array
	 */
	public static function get_types_has_link()
	{
		return self::$type_has_link;
	}
	
	/**
	 * Gets link mediums types for interface type
	 * 
	 * @param integer $type	Iface type
	 * @return array		Link medium constants 
	 */
	public static function get_type_has_link_with_medium($type)
	{
		if (isset(self::$type_has_link_with_medium[$type]))
			return self::$type_has_link_with_medium[$type];
		else
			return array();
	}
	
	/**
	 * Gets link mediums for interface type
	 * 
	 * @param array $type	Iface type
	 * @return array		Link medium array with translated names 
	 */
	public static function get_types_has_link_with_medium($type)
	{
        $mediums = Iface_Model::get_type_has_link_with_medium($type);
        $arr_mediums = array();

        foreach ($mediums as $medium)
        {
            $arr_mediums[$medium] = Link_Model::get_medium_type($medium);
        }
        
        return $arr_mediums;
	}
	
	/**
	 * Tests if type can have mac address
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @return boolean 
	 */
	public static function type_has_mac_address($type)
	{
		if (isset(self::$type_has_mac_address[$type]))
			return self::$type_has_mac_address[$type];
		else
			return FALSE;
	}
	
	/**
	 * Tests if type can have IP address
	 * 
	 * @author Ondrej Fibich
	 * @param integer $type
	 * @return boolean 
	 */
	public static function type_has_ip_address($type)
	{
		if (isset(self::$type_has_ip_address[$type]))
			return self::$type_has_ip_address[$type];
		else
			return FALSE;
	}
	
	/**
	 * Gets whole array of indicator of available MAC
	 * 
	 * @return array
	 */
	public static function get_types_has_mac_address()
	{
		return self::$type_has_mac_address;
	}
	
	/**
	 * Gets array of iface type to which the given type of interface may connect
	 *
	 * @param integer $type		One of ifaces types
	 * @return array
	 */
	public static function get_can_connect_to($type)
	{
		if (array_key_exists($type, self::$can_connect_to))
		{
			return self::$can_connect_to[$type];
		}
		
		return array();
	}
	
	/**
	 * Gets array of iface type of which the given interface may be child
	 * 
	 * @param type $type	One of ifaces types
	 * @return type 
	 */
	public static function get_can_be_child_of($type)
	{
		if (array_key_exists($type, self::$can_be_child_of))
		{
			return self::$can_be_child_of[$type];
		}
		
		return array();
	}

	/**
	 * Tests if wireless antenna can have azimuth
	 * 
	 * @author Michal Kliment
	 * @param integer $wireless_antenna
	 * @return boolean 
	 */
	public static function wireless_antenna_has_azimuth($wireless_antenna)
	{
		if (isset(self::$wireless_antenna_has_azimuth[$wireless_antenna]))
			return self::$wireless_antenna_has_azimuth[$wireless_antenna];
		else
			return TRUE;
	}
	
	/**
	 * Returns type of current interface
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @return string 
	 */
	public static function get_type($type)
	{
		if (is_numeric($type) && array_key_exists($type, self::$types))
		{
			return __(self::$types[$type]);
		}
		
		return NULL;
	}
	
	/**
	 * Returns all types of interfaces
	 * 
	 * @author Michal Kliment
	 * @return array
	 */
	public static function get_types()
	{
		return array_map('__', self::$types);
	}
	
	/**
	 * Returns necessary types of interfaces
	 * 
	 * @see Devices_Controller#add
	 * @author Ondrej Fibich
	 * @return array
	 */
	public static function get_necessary_types()
	{
		$ntypes = self::$types;
		
		unset($ntypes[self::TYPE_BRIDGE]);
		unset($ntypes[self::TYPE_VLAN]);
		unset($ntypes[self::TYPE_INTERNAL]);
		unset($ntypes[self::TYPE_VIRTUAL_AP]);
		
		return array_map('__', $ntypes);
	}
	
	/**
	 * Returns name or MAC address of device
	 * 
	 * @author David Raska
	 * @return string
	 */
	public function __toString()
	{
		if (!$this || !$this->id)
		{
			return '';
		}
		
		$name = $this->name;
		$mac = $this->mac;
		
		if (!empty($name) && !empty($mac))
		{
			return $name.' ('.$mac.')';
		}
		
		if (empty($name) && empty($name))
		{
			return strval($this->id);
		}
		
		if (empty($name))
		{
			return $mac;
		}
		else
		{
			return $name;
		}
		
		return '';
	}
	
	/**
	 * Returns default name of current type of interface
	 * 
	 * @author Michal Kliment
	 * @param integer $type
	 * @return string 
	 */
	public static function get_default_name($type)
	{
		if (!empty($type) && array_key_exists($type, self::$default_names))
		{
			return self::$default_names[$type];
		}
		
		return NULL;
	}
	
	/**
	 * Returns all default names of interfaces
	 * 
	 * @author Michal Kliment
	 * @return array
	 */
	public static function get_default_names()
	{
		return self::$default_names;
	}
	
	/**
	 * Returns mode of current wireless interface
	 * 
	 * @author Michal Kliment
	 * @param integer $mode
	 * @return string 
	 */
	public static function get_wireless_mode($mode = NULL)
	{
		if (array_key_exists($mode, self::$wireless_modes))
		{
			return __(self::$wireless_modes[$mode]);
		}
		
		return NULL;
	}
	
	/**
	 * Returns all modes of wireless interfaces
	 * 
	 * @author Michal Kliment
	 * @param integer $mode
	 * @return string 
	 */
	public static function get_wireless_modes()
	{
		return array_map('__', self::$wireless_modes);
	}
	
	/**
	 * Returns antenna of current wireless interface
	 * 
	 * @author Michal Kliment
	 * @param integer $antenna
	 * @return string 
	 */
	public static function get_wireless_antenna($antenna)
	{
		if (array_key_exists($antenna, self::$wireless_antennas))
		{
			return __(self::$wireless_antennas[$antenna]);
		}
		
		return NULL;
	}
	
	/**
	 * Returns all antennas of wireless interfaces
	 * 
	 * @author Michal Kliment
	 * @param integer $mode
	 * @return string 
	 */
	public static function get_wireless_antennas()
	{
		return array_map('__', self::$wireless_antennas);
	}
	
	/**
	 * Return human format for given const of mode
	 * 
	 * @author Michal Kliment
	 * @param integer $mode
	 * @return boolean 
	 */
	public static function get_port_mode($mode)
	{
		if (isset(self::$port_modes[$mode]))
			return __(self::$port_modes[$mode]);
		else
			return FALSE;
	}
	
	/**
	 * Return human format for modes
	 * 
	 * @author Michal Kliment
	 * @return array
	 */
	public static function get_port_modes()
	{
		return array_map('__', self::$port_modes);
	}
	
	/**
	 * Return human format for port VLAN types
	 * 
	 * @author Michal Kliment
	 * @return array
	 */
	public static function get_port_vlan_types()
	{
		return array_map('__', self::$port_vlan_types);
	}
	
	/**
	 * Checks whether the given new MAC of the given iface is unique in all
	 * subnets that are in relation with the iface over his IP addresses. 
	 * 
	 * This function should not be use during adding of an iface!
	 * 
	 * @param int $iface_id
	 * @param string $mac
	 * @return bool
	 */
	public function is_mac_unique($iface_id, $mac)
	{
		$iface = new Iface_Model($iface_id);
		
		if ($iface->id)
		{
			if ($iface->mac == $mac)
			{
				return TRUE; // edit with same MAC
			}

			return $this->db->query("
				SELECT COUNT(*)	AS count
				FROM subnets s
				JOIN ip_addresses ip ON ip.subnet_id = s.id
				JOIN ifaces i ON ip.iface_id = i.id
				WHERE s.id IN (
					SELECT s2.id FROM subnets s2
					JOIN ip_addresses ip2 ON ip2.subnet_id = s2.id
					JOIN ifaces i2 ON i2.id = ip2.iface_id
					WHERE i2.id = ? 
				) AND i.mac = ?
			", $iface_id, $mac)->current()->count <= 0;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Get count of ifaces of device
	 * 
	 * @param integer $device_id
	 * @return integer
	 */
	public function count_ifaces_of_device($device_id)
	{
		return $this->db->where('device_id', $device_id)->count_records('ifaces');
	}
	
	/**
	 * Function gets all interfaces.
	 * 
	 * @param $limit_from
	 * @param $limit_results
	 * @param $order_by
	 * @param $order_by_direction
	 * @return Mysql_Result
	 */
	public function get_all_ifaces(
			$limit_from = 0, $limit_results = 20, $order_by = 'id',
			$order_by_direction = 'asc', $filter_sql = '')
	{
		$where = '';
		// order by direction check
		if (strtolower($order_by_direction) != 'desc')
		{
			$order_by_direction = 'asc';
		}
		// filter
		if ($filter_sql != '')
		{
			$where = "WHERE $filter_sql";
		}
		// query
		return $this->db->query("
				SELECT * FROM
				(
					SELECT i.*, d.name AS device_name, l.name AS link_name,
						u.name AS user_name, u.surname AS user_surname,
						m.name AS member_name
					FROM ifaces i
					JOIN devices d ON d.id = i.device_id
					LEFT JOIN links l ON l.id = i.link_id
					LEFT JOIN users u ON d.user_id = u.id
					LEFT JOIN members m ON u.member_id = m.id
				) i
				$where
				ORDER BY " . $this->db->escape_column($order_by) . " $order_by_direction
				LIMIT " . intval($limit_from) . ", " . intval($limit_results) . "
		");
	}
	
	/**
	 * Function counts all interfaces.
	 * 
	 * @return integer
	 */
	public function count_all_ifaces($filter_sql = '') 
	{
		$where = '';
		// filter
		if ($filter_sql != '')
		{
			$where = "WHERE $filter_sql";
		}
		// query
		$result = $this->db->query("
			SELECT COUNT(*) AS total FROM
			(
				SELECT i.*, d.name AS device_name, l.name AS link_name,
					u.name AS user_name, u.surname AS user_surname,
					m.name AS member_name
				FROM ifaces i
				JOIN devices d ON d.id = i.device_id
				LEFT JOIN links l ON l.id = i.link_id
				LEFT JOIN users u ON d.user_id = u.id
				LEFT JOIN members m ON u.member_id = m.id
			) i
			$where
		");
		
		if ($result && $result->current())
			return $result->current()->total;
		else
			return 0;
	}

	/**
	 * Returns all interfaces of device
	 *
	 * @author Michal Kliment
	 * @param int $device_id
	 * @param mixed $type		Array of types or a single type
	 * @return Mysql_Result
	 */
	public function get_all_ifaces_of_device($device_id, $type = NULL)
	{	
		$where = '';
		
		if ($type)
		{
			if (is_array($type) && count($type))
			{
				$where = 'AND i.type IN(' . implode(',', $type) . ')';
			}
			else
			{
				$where = 'AND i.type = ' . intval($type);
			}
		}
		
		return $this->db->query("
			SELECT
				i.id, i.id AS iface_id, i.link_id, l.name AS link_name,
				i.mac, i.name, i.comment, i.type, i.number, i.port_mode, l.bitrate,
				ci.id AS connected_to_iface_id, ci.name AS connected_to_iface_name,
				cd.id AS connected_to_device_id, cd.name AS connected_to_device_name,
				COUNT(DISTINCT cd.id) AS connected_to_devices_count,
				GROUP_CONCAT(DISTINCT cd.name SEPARATOR ', \\n') AS connected_to_devices,
				l.wireless_norm, l.wireless_frequency, i.wireless_mode,
				l.wireless_channel_width, l.wireless_ssid, ir.parent_iface_id,
				pi.name AS parent_name, v.tag_802_1q, l.medium,
				v.tag_802_1q AS port_vlan_tag_802_1q, pv.name AS port_vlan
			FROM ifaces i
			LEFT JOIN ifaces_relationships ir ON ir.iface_id = i.id
			LEFT JOIN ifaces pi ON ir.parent_iface_id = pi.id
			LEFT JOIN links l ON i.link_id = l.id
			LEFT JOIN ifaces ci ON ci.link_id = l.id AND ci.id <> i.id AND
			(
				i.type NOT IN(?, ?) OR
				(
					i.type IN(?, ?) AND
					(
						i.wireless_mode = ? AND ci.wireless_mode = ?
					) OR i.wireless_mode = ?
				)
			)
			LEFT JOIN devices cd ON ci.device_id = cd.id
			LEFT JOIN ifaces_vlans iv ON iv.iface_id = i.id
			LEFT JOIN vlans v ON iv.vlan_id = v.id
			LEFT JOIN ifaces_vlans piv ON piv.iface_id = i.id
				AND piv.port_vlan IS NOT NULL AND piv.port_vlan = 1
			LEFT JOIN vlans pv ON piv.vlan_id = pv.id
			WHERE i.device_id = ? $where
			GROUP BY i.id
			ORDER BY i.number, i.type, i.name
		", array
		(
			self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP,
			self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP,
			self::WIRELESS_MODE_CLIENT,
			self::WIRELESS_MODE_AP,
			self::WIRELESS_MODE_AP,
			$device_id
		));
	}
	
	/**
	 * Returns all VLAN ifaces of device
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @return type 
	 */
	public function get_all_vlan_ifaces_of_device($device_id)
	{
		return $this->db->query("
			SELECT
				vi.id, vi.name, i.id AS iface_id, i.name AS iface_name,
				v.id AS vlan_id, v.name AS vlan_name, tag_802_1q
			FROM ifaces vi
			JOIN ifaces_relationships ir ON ir.iface_id = vi.id
			JOIN ifaces i ON ir.parent_iface_id = i.id
			JOIN ifaces_vlans iv ON iv.iface_id = vi.id
			JOIN vlans v ON iv.vlan_id = v.id
			WHERE vi.device_id = ? AND vi.type IN(?, ?)
			ORDER BY tag_802_1q
		", array($device_id, self::TYPE_VLAN, self::TYPE_VIRTUAL_AP));
	}
	
	/**
	 * Returns all wireless interfaces of device
	 * 
	 * @author Michal Kliment
	 * @param type $device_id
	 * @return type 
	 */
	public function get_all_wireless_ifaces_of_device($device_id)
	{
		return $this->db->query("
			SELECT
				wi.id, wi.name, wi.mac, wi.link_id, l.name AS link_name,
				l.wireless_ssid, wi.wireless_mode, wi.type,
				cd.id AS connected_to_device_id, cd.name AS connected_to_device_name,
				ci.id AS connected_to_iface_id, ci.name AS connected_to_iface_name,
				COUNT(*) AS connected_to_devices_count,
				GROUP_CONCAT(cd.name SEPARATOR ', \\n') AS connected_to_devices
			FROM ifaces wi
			LEFT JOIN links l ON wi.link_id = l.id
			LEFT JOIN ifaces ci ON ci.link_id = l.id AND ci.id <> wi.id AND
				(
					(wi.wireless_mode = ? AND ci.wireless_mode = ?) OR
					wi.wireless_mode = ?
				)
			LEFT JOIN devices cd ON ci.device_id = cd.id
			WHERE wi.device_id = ? AND wi.type IN(?, ?)
			GROUP BY wi.id
		", array
		(
			self::WIRELESS_MODE_CLIENT,
			self::WIRELESS_MODE_AP,
			self::WIRELESS_MODE_AP,
			$device_id,
			self::TYPE_WIRELESS,
			self::TYPE_VIRTUAL_AP
		));
	}
	
	/**
	 * Gets ifaces of parent iface which is given by ID
	 * 
	 * @param integer $parent_iface_id
	 * @return Mysql_Result
	 */
	public function get_virtual_ap_ifaces_of_parent($parent_iface_id = NULL)
	{
		if ($parent_iface_id === NULL && $this->loaded)
		{
			$parent_iface_id = $this->id;
		}
		
		return $this->db->query("
			SELECT i.*
			FROM ifaces i
			JOIN ifaces_relationships ir ON ir.iface_id = i.id
			WHERE ir.parent_iface_id = ? AND i.type = ?
		", $parent_iface_id, Iface_Model::TYPE_VIRTUAL_AP);
	}
	
	/**
	 * Gets array of ifaces grouped by device for dropdown
	 *
	 * @param integer $device_id	Only iface of one device?
	 * @param array $restrict_types Array of allowed types
	 * @author Ondřej fibich
	 * @return array
	 */
	public function select_list_grouped_by_device($device_id = NULL, 
			$restrict_types = array())
	{
		$where = '';
		
		if (is_numeric($device_id))
		{
			$where = 'WHERE d.id = ' . intval($device_id);
		}
		
		if (is_array($restrict_types) && count($restrict_types))
		{
			$where .= empty($where) ? 'WHERE ' : ' AND ';
			$where .= ' i.type IN (' . implode(',', $restrict_types) . ')';
		}
		
		$ifaces = $this->db->query("
				SELECT i.id, COALESCE(d.name, '') AS device_name,
					CONCAT(u.surname, ' ', u.name, ' - ', u.login) AS user_name,
					COALESCE(i.name, '') AS name,
					COALESCE(i.mac, '') AS mac
				FROM ifaces i
				LEFT JOIN devices d ON d.id = i.device_id
				JOIN users u ON u.id = d.user_id
				$where
				ORDER BY IF(u.id <> ?, 1, 0), user_name,				
					IF(ISNULL(d.name) OR LENGTH(d.name) = 0, 1, 0), d.name,
					IF(ISNULL(i.name) OR LENGTH(i.name) = 0, 1, 0), i.name
		", User_Model::MAIN_USER)->result()->result_array();
		
		$result = array();
		
		foreach ($ifaces as $iface)
		{
			$name = $iface->name . (!empty($iface->mac) ? ' (' . $iface->mac . ')' : '');
			$result[$iface->user_name . ' - ' . $iface->device_name][$iface->id] = $name;
		}
		
		return $result;
	}
	
	/**
	 * Counts items by wireless mode and link
	 * 
	 * @author Michal Kliment
	 * @param integer $wmode
	 * @param integer $link_id
	 * @param integer $iface_id		If set this interface is avoided [optional]
	 * @return boolean
	 */
	public function count_items_by_mode_and_link($wmode, $link_id, $iface_id = NULL)
	{
		$where = '';
		if ($iface_id)
			$where = 'AND i.id <> '.intval($iface_id);
			
		$result = $this->db->query("
				SELECT COUNT(*) AS total
				FROM ifaces i
				WHERE i.wireless_mode = ? AND i.link_id = ? $where
		", $wmode, $link_id);
		
		if ($result && $result->current())
		{
			return (bool) $result->current()->total;
		}
		
		return false;
	}
	
	/**
	 * Gets interface connected to interface via link.
	 * Only first founded is returned 
	 *
	 * @param Iface_Model $iface
	 * @return Iface_Model
	 */
	public function get_iface_connected_to_iface($iface = NULL)
	{
		if ($iface === NULL)
		{
			$iface = $this;
		}
		
		if ($iface && $iface->id)
		{
			$can_connect = self::get_can_connect_to($iface->type); 
			
			foreach ($iface->link->ifaces as $connected_iface)
			{
				// only if can connect to this type, is not self and if wlan then
				// prserve rules AP -> client, client -> AP
				if ($connected_iface->id != $iface->id &&
					in_array($connected_iface->type, $can_connect) && (
							$iface->type != self::TYPE_WIRELESS ||
							$iface->wireless_mode != $connected_iface->wireless_mode
					))
				{
					return $connected_iface;
				}
			}
		}
			
		return NULL;
	}
	
	/**
	 * Gets all interfaces connected to interface via link.
	 *
	 * @param Iface_Model $iface
	 * @return Iface_Model
	 */
	public function get_ifaces_connected_to_iface($iface = NULL)
	{
		if ($iface === NULL)
		{
			$iface = $this;
		}
		
		$ifaces = array();
		
		if ($iface && $iface->id)
		{
			$can_connect = self::get_can_connect_to($iface->type); 
			
			foreach ($iface->link->ifaces as $connected_iface)
			{
				// only if can connect to this type, is not self and if wlan then
				// prserve rules AP -> client, client -> AP
				if ($connected_iface->id != $iface->id &&
					in_array($connected_iface->type, $can_connect) && (
							$iface->type != self::TYPE_WIRELESS ||
							$iface->wireless_mode != $connected_iface->wireless_mode
					))
				{
					$ifaces[] = $connected_iface;
				}
			}
		}
			
		return $ifaces;
	}
	
	/**
	 * Tries to find best suitable interface for connecting of a new device of user.
	 * 
	 * @author Ondrej Fibich
	 * @param integer $user_id		User identificator
	 * @param integer $type			Type of interface for connection
	 * @param array $gps			location of device - default member AP [optional]
	 * @param string $filter_sql	Filter SQL
	 * @param integer $wmode		Wireless mode of interface which try to connect [optional]
	 * @return object				Suitable iface or null
	 */
	public function get_iface_for_connecting_to_iface($user_id, $type,
			$gps = array(), $filter_sql = '', $wmode = NULL)
	{
		$can_connect = self::get_can_connect_to($type);
		$user = new User_Model($user_id);
		$where = '';
		
		if (!empty($filter_sql))
		{
			$where = 'WHERE ' . $filter_sql;
		}
		
		// select oposite mode
		if ($wmode == Iface_Model::WIRELESS_MODE_AP)
		{
			$wmode = Iface_Model::WIRELESS_MODE_CLIENT;
		}
		else
		{
			$wmode = Iface_Model::WIRELESS_MODE_AP;
		}
		
		if ($user->id && count($can_connect))
		{			
			$usearch = array(User_Model::ASSOCIATION, $user->id);
			
			// find current user interfaces and select oponent by link
			$from_current = $this->db->query("
					SELECT iface_id, device_id
					FROM
					(
						SELECT i2.id AS iface_id, d2.id AS device_id,
							d2.user_id AS user, ip.subnet_id AS subnet, d2.type,
							d2.name AS device_name, ap2.street_id AS street,
							ap2.town_id AS town, ap2.street_number
						FROM devices d
						JOIN ifaces i ON i.device_id = d.id
						JOIN links l ON i.link_id = l.id
						JOIN ifaces i2 ON i2.link_id = l.id AND i2.id <> i.id AND
						(
							i2.type NOT IN(?, ?) OR
							(
								i2.type IN(?, ?) AND i2.wireless_mode = ?
							)
						)
						LEFT JOIN ip_addresses ip ON ip.iface_id = i2.id
						JOIN devices d2 ON i2.device_id = d2.id
						JOIN address_points ap2 ON ap2.id = d2.address_point_id
						WHERE i.type = ? AND i2.type IN(" . implode(',', $can_connect) . ") AND
							d.user_id = ? AND d2.user_id IN (" . implode(',', $usearch) . ")
					) df
					$where
					ORDER BY user
					LIMIT 1
			", array
			(
				self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP,
				self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP,
				$wmode, $type, $user->id
			));
		
			if ($from_current->current())
			{
				return $from_current->current();
			}
			
			// get GPS
			if (is_array($gps) && count($gps) == 2) // gps from params
			{
				$gps['x'] = gps::degrees2real($gps['x']);
				$gps['y'] = gps::degrees2real($gps['y']);
			}
			else // gps from member address point
			{
				$gps_ap = $user->member->address_point->get_gps_coordinates();
				$gps['x'] = $gps_ap ? $gps_ap->gpsx : 0;
				$gps['y'] = $gps_ap ? $gps_ap->gpsy : 0;
			}
			
			// default gps
			$order_by = '';
			
			// valid GPS?
			if ($gps['x'] > 0 && $gps['y'] > 0)
			{
				$order_by = "SQRT(POW(" . $gps['x'] . " - X(df.gps), 2) +
								POW(" . $gps['y'] . " - Y(df.gps), 2)) ASC,";
			}
			
			// find nearest in net (using GPS)
			return $this->db->query("
					SELECT iface_id, device_id
					FROM ((
							SELECT iface_id, device_id, priority
							FROM
							(
								SELECT i2.id AS iface_id, d2.id AS device_id, 2 AS priority,
									d2.user_id AS user, ip2.subnet_id AS subnet, d2.type,
									ap2.gps, d2.name AS device_name, ap2.street_id AS street,
									ap2.town_id AS town, ap2.street_number
								FROM devices d2
								JOIN ifaces i2 ON i2.device_id = d2.id AND
								(
									i2.type NOT IN(?, ?) OR
									(
										i2.type IN(?, ?) AND i2.wireless_mode = ?
									)
								)
								LEFT JOIN ip_addresses ip2 ON ip2.iface_id = i2.id
								JOIN address_points ap2 ON d2.address_point_id = ap2.id
								WHERE i2.type IN(" . implode(',', $can_connect) . ") AND
									d2.user_id IN (" . implode(',', $usearch) . ") AND
									X(ap2.gps) IS NOT NULL AND Y(ap2.gps) IS NOT NULL
							) df
							$where
							ORDER BY $order_by user DESC
							LIMIT 1
						) UNION (
							SELECT iface_id, device_id, priority
							FROM
							(
								SELECT i2.id AS iface_id, d2.id AS device_id, 2 AS priority,
									d2.user_id AS user, ip2.subnet_id AS subnet, d2.type,
									ap2.gps, d2.name AS device_name, ap2.street_id AS street,
									ap2.town_id AS town, ap2.street_number
								FROM devices d2
								JOIN ifaces i2 ON i2.device_id = d2.id AND
								(
									i2.type NOT IN(?, ?) OR
									(
										i2.type IN(?, ?) AND i2.wireless_mode = ?
									)
								)
								LEFT JOIN ip_addresses ip2 ON ip2.iface_id = i2.id
								JOIN address_points ap2 ON d2.address_point_id = ap2.id
								WHERE i2.type IN(" . implode(',', $can_connect) . ") AND
									X(ap2.gps) IS NOT NULL AND Y(ap2.gps) IS NOT NULL
							) df
							$where
							ORDER BY $order_by user ASC
							LIMIT 1
						)
					) i
					ORDER BY i.priority DESC
					LIMIT 1
			", array
			(
				self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP,
				self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP, $wmode,
				self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP,
				self::TYPE_WIRELESS, self::TYPE_VIRTUAL_AP, $wmode
			))->current();
		}
		
		return NULL;
	}
	
	/**
	 * Checks whether VLAN exists on ports or VLAN ifaces
	 * 
	 * @author Michal Kliment
	 * @param type $vlan_id
	 * @return boolean
	 */
	public function vlan_exists($vlan_id)
	{		
		foreach ($this->ifaces_vlans as $ifaces_vlan)
		{
			if ($ifaces_vlan->vlan_id == $vlan_id)
				return TRUE;
		}
		
		return FALSE;
	}
	
	/**
	 * Returns untagged VLAN of port (if exists)
	 * 
	 * @author Michal Kliment
	 * @return null
	 */
	public function get_untagged_vlan()
	{
		foreach ($this->ifaces_vlans as $ifaces_vlan)
		{
			if (!is_null($ifaces_vlan->tagged) && $ifaces_vlan->tagged == 0 &&
				!is_null($ifaces_vlan->vlan) && $ifaces_vlan->vlan)
			{
				return $ifaces_vlan->vlan;
			}
		}
		
		return NULL;
	}
	
	/**
	 * Checks whether iface is in bridge
	 * 
	 * @author Michal Kliment
	 * @return boolean
	 */
	public function is_in_bridge()
	{
		foreach ($this->ifaces_relationships as $ifaces_relationship)
		{
			if ($ifaces_relationship->parent_iface->type == Iface_Model::TYPE_BRIDGE)
				return TRUE;
		}
		
		return FALSE;
	}
	
}
