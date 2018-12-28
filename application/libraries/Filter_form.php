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
 * This is library for filter form
 * 
 * @author Michal Kliment
 * @version 1.0
 */
class Filter_form
{
	/**
	 * Template to show filter form
	 * @var string
	 */
	protected $template = 'filter_form_template';

	/**
	 * Array of all filters
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Array of all filter's values
	 * @var array
	 */
	protected $values = array();

	/**
	 * Array of all filter's types
	 * @var array
	 */
	protected $types = array();

	/**
	 * Array of all filter's operations
	 * @var array
	 */
	protected $operations = array();

	/**
	 * Array of all filter's tables
	 * @var array
	 */
	protected $tables = array();

	/**
	 * Array of boolean values whether filter is default
	 * @var array
	 */
	protected $default = array();

	/**
	 * Count of default filters
	 * @var integer
	 */
	protected $default_count = 0;
	
	/**
	 *Array with states (on/off) of filters
	 * @var array
	 */
	protected $states = array();
	
	/**
	 * Default query for filter form
	 * @var integer
	 */
	protected $default_query_id = NULL;
	
	/**
	 * Base URL of filter form
	 * 
	 * @var string 
	 */
	protected $base_url = NULL;
	
	/**
	 * State of possibility of add new query
	 * 
	 * @var boolean
	 */
	protected $can_add = FALSE;
	
	/**
	 * Indicates whether the filter form configuration was loaded from database.
	 *
	 * @var boolean
	 */
	protected $loaded_from_saved_query = FALSE;
	
	/**
	 * Indicates whether the filter form configuration that was loaded from
	 * database is default.
	 *
	 * @var boolean
	 */
	protected $loaded_from_default_saved_query = FALSE;

	/**
	 *  Definition of constants
	 */
	const OPER_CONTAINS = 1;
	const OPER_CONTAINS_NOT = 2;
	const OPER_IS = 3;
	const OPER_IS_NOT = 4;
	const OPER_EQUAL = 5;
	const OPER_EQUAL_NOT = 6;
	const OPER_SMALLER = 7;
	const OPER_SMALLER_OR_EQUAL = 8;
	const OPER_GREATER = 9;
	const OPER_GREATER_OR_EQUAL = 10;
	const OPER_BIT_IS = 11;
	const OPER_BIT_IS_NOT = 12;
	const OPER_NETWORK_IS_IN = 13;
	const OPER_NETWORK_IS_NOT_IN = 14;
	const OPER_IS_EMPTY = 15;
	const OPER_IS_NOT_EMPTY = 16;

	/**
	 * Array with definition of all operations
	 * @var array
	 */
	protected $opers = array
	(
		self::OPER_CONTAINS => array
		(
			'name' => 'contains',
			'sql' => "LIKE '%{VALUE}%' COLLATE utf8_general_ci",
		),
		self::OPER_CONTAINS_NOT => array
		(
			'name' => 'contains not',
			'sql' => "NOT LIKE '%{VALUE}%' COLLATE utf8_general_ci",
		),
		self::OPER_IS => array
		(
			'name' => 'is',
			'sql' => "LIKE '{VALUE}' COLLATE utf8_general_ci"
		),
		self::OPER_IS_NOT => array
		(
			'name' => 'is not',
			'sql' => "NOT LIKE '{VALUE}' COLLATE utf8_general_ci" ,
		),
		self::OPER_EQUAL => array
		(
			'name' => '=',
			'sql' => "= '{VALUE}'",
		),
		self::OPER_EQUAL_NOT => array
		(
			'name' => '!=',
			'sql' => "<> '{VALUE}'",
		),
		self::OPER_SMALLER => array
		(
			'name' => '<',
			'sql' => "< '{VALUE}'",
		),
		self::OPER_SMALLER_OR_EQUAL => array
		(
			'name' => '<=',
			'sql' => "<= '{VALUE}'",
		),
		self::OPER_GREATER => array
		(
			'name' => '>',
			'sql' => "> '{VALUE}'",
		),
		self::OPER_GREATER_OR_EQUAL => array
		(
			'name' => '>=',
			'sql' => ">= '{VALUE}'",
		),
		self::OPER_BIT_IS => array
		(
			'name' => 'is',
			'sql' => "& {VALUE} > 0",
		),
		self::OPER_BIT_IS_NOT => array
		(
			'name' => 'is not',
			'sql' => "& {VALUE} = 0",
		),
		self::OPER_NETWORK_IS_IN => array
		(
			'name' => 'is in',
			'pattern' => '/^(?P<VALUE1>((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9]))\/(?P<VALUE2>(3[0-2])|(2[0-9])|(1[0-9])|([0-9]))$/',
			'sql' => "& (0xffffffff<<(32-{VALUE2}) & 0xffffffff) = inet_aton('{VALUE1}')",
			'function' => 'inet_aton'
		),
		self::OPER_NETWORK_IS_NOT_IN => array
		(
			'name' => 'is not in',
			'pattern' => '/^(?P<VALUE1>((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9])\.((25[0-5])|(2[0-4][0-9])|(1[0-9][0-9])|([1-9][0-9])|[0-9]))\/(?P<VALUE2>(3[0-2])|(2[0-9])|(1[0-9])|([0-9]))$/',
			'sql' => "& (0xffffffff<<(32-{VALUE2}) & 0xffffffff) <> inet_aton('{VALUE1}')",
			'function' => 'inet_aton'
		),
		self::OPER_IS_EMPTY => array
		(
			'name' => 'is empty',
			'sql' => 'LIKE ""',
			'null' => TRUE
		),
		self::OPER_IS_NOT_EMPTY => array
		(
			'name' => 'is not empty',
			'sql' => 'NOT LIKE ""',
			'null' => TRUE
		)
	);

	/**
	 * Array with definition of types and its operations
	 * @var array
	 */
	protected $operation_types = array
	(
		'combo' => array
		(
			self::OPER_IS,
			self::OPER_IS_NOT,
			self::OPER_CONTAINS,
			self::OPER_CONTAINS_NOT
		),
		'select' => array
		(
			self::OPER_IS,
			self::OPER_IS_NOT
		),
		'text' => array
		(
			self::OPER_CONTAINS,
			self::OPER_CONTAINS_NOT,
			self::OPER_IS,
			self::OPER_IS_NOT,
			self::OPER_IS_EMPTY,
			self::OPER_IS_NOT_EMPTY
		),
		'number' => array
		(
			self::OPER_EQUAL,
			self::OPER_EQUAL_NOT,
			self::OPER_SMALLER,
			self::OPER_SMALLER_OR_EQUAL,
			self::OPER_GREATER,
			self::OPER_GREATER_OR_EQUAL
		),
		'bit' => array
		(
			self::OPER_BIT_IS,
			self::OPER_BIT_IS_NOT
		),
		'date' => array
		(
			self::OPER_EQUAL,
			self::OPER_EQUAL_NOT,
			self::OPER_SMALLER,
			self::OPER_SMALLER_OR_EQUAL,
			self::OPER_GREATER,
			self::OPER_GREATER_OR_EQUAL
		),
		'select_number' => array
		(
			self::OPER_IS,
			self::OPER_IS_NOT,
			self::OPER_EQUAL,
			self::OPER_EQUAL_NOT,
			self::OPER_SMALLER,
			self::OPER_SMALLER_OR_EQUAL,
			self::OPER_GREATER,
			self::OPER_GREATER_OR_EQUAL
		),
		'network_address' => array
		(
			self::OPER_IS,
			self::OPER_IS_NOT,
			self::OPER_CONTAINS,
			self::OPER_CONTAINS_NOT,
			self::OPER_NETWORK_IS_IN,
			self::OPER_NETWORK_IS_NOT_IN
		)
	);	

	/**
	 * Array with definition of minlengths of types
	 * @var array
	 */
	protected $minlengths = array
	(
		'combo' => 0,
		'select' => 0,
		'text' => 1,
		'bit' => 0,
		'date' => 1,
		'select_number' => 0,
		'network_address' => 1
	);

	/**
	 * Array with definition of return type of type (key or value)
	 * @var array
	 */
	protected $returns = array
	(
		'combo' => 'value',
		'select' => 'key',
		'text' => 'value',
		'bit' => 'key',
		'number' => 'value',
		'date' => 'value',
		'select_number' => 'key',
		'network_address' => 'value'
	);
	
	/**
	 * Boolean value whether it is first load of filters (#442)
	 * @var boolean
	 */
	protected $first_load = FALSE;

	/**
	 * Constructor, sets table name and compiles values from $_GET
	 *
	 * @author Michal Kliment
	 * @param string $table
	 */
	public function  __construct($table = '', $base_url = '')
	{
		$this->table = $table;
		
		$this->base_url = ($base_url != '') ? $base_url : url_lang::current(2);

		$this->template = new View ($this->template);

		$this->types = array();
		$this->operations = array();
		$this->values = array();
		
		// create query model
		$this->query_model = new Filter_query_Model();
		
		// loads all queries belongs to current url
		$this->queries = $this->query_model->get_all_queries_by_url($this->base_url);
		foreach ($this->queries as $query)
		{
			// find default query
			if ($query->default)
				$this->default_query_id = $query->id;
		}
		
		$query = Input::instance()->get('query');
		
		// load query from database (because of #895 can be from different URL)
		if ($query && is_numeric($query))
		{
			$loaded_query = new Filter_query_Model($query);
			
			if ($loaded_query && $loaded_query->id) {
				
				$data = json_decode($loaded_query->values, TRUE);

				$on = @$data["on"];
				$types = @$data["types"];
				$operations = @$data["opers"];
				$values = @$data["values"];
				$tables = @$data["tables"];

				$this->loaded_from_saved_query = TRUE;
				
			}
			else
			{
				$on = $types = $operations = $values = $tables = NULL;
				status::warning('Invalid saved query');
			}
			
			$this->first_load = FALSE;
		}
		// load query from URL
		else
		{
			$on = Input::instance()->get('on');
			$types = Input::instance()->get('types');
			$operations = Input::instance()->get('opers');
			$values = Input::instance()->get('values');
			$tables = Input::instance()->get('tables');
			
			$this->can_add = TRUE;
			$this->loaded_from_saved_query = FALSE;
			
			$this->first_load = FALSE;
		}

		$this->keys = Input::instance()->get('keys');
		$this->vals = Input::instance()->get('vals');

		// query is empty, use default from database
		if (!$on && !$types && !$operations && !$values && !$tables && $this->default_query_id)
		{
			$data = json_decode($this->queries[$this->default_query_id]->values, TRUE);

			$on = @$data["on"];
			$types = @$data["types"];
			$operations = @$data["opers"];
			$values = @$data["values"];
			$tables = @$data["tables"];
			
			$this->can_add = FALSE;
			$this->loaded_from_saved_query = TRUE;
			$this->loaded_from_default_saved_query = TRUE;
			
			$this->first_load = TRUE;
		}

		// load data
		if (count($values))
		{
			$this->tables = $tables;
			
			$offset = 0;
			for ($i=0;$i<=max(array_keys($values));$i++)
			{
				if (isset($on[$i]) && is_array($values[$i]))
				{	
					$this->states[$i-$offset] = $on[$i];
					$this->values[$i-$offset] = array_map("trim", $values[$i]);
					$this->types[$i-$offset] = $types[$i];
					$this->operations[$i-$offset] = $operations[$i];
				}
				else
					$offset++;
			}
		}
		else
		{
			$this->can_add = FALSE;
			
			$this->first_load = TRUE;
		}
	}

	/**
	 *  Automatic loads filters from $_GET
	 *
	 * @author Michal Kliment
	 * @return int
	 */
	public function autoload()
	{	
		$loaded = 0;
		foreach ($this->types as $i => $type)
		{
			$loaded++;

			$filter = new Filter($type, isset($this->tables[$type]) ? $this->tables[$type] : '');
			
			if (isset($this->keys[$filter->name]))
			{
				$this->values[$i] = $this->keys[$filter->name][array_search($this->values[$i], $this->vals[$filter->name])];
			}

			$this->filters[$type] = $filter;
		}
		return $loaded;
	}

	/**
	 * Adds new filter to filter form
	 *
	 * @author Michal Kliment
	 * @param string $name
	 * @return Filter object
	 */
	public function add($name, $table = NULL)
	{
		if (!$table)
		{
			$table = $this->table;
		}
		else
		{
			$name .= '/'.$table;
		}
		
		$filter = new Filter($name, $table);
		
		$this->filters[$name] = $filter;
		
		return $filter;
	}

	/**
	 * Loads default filter's values
	 *
	 * @author Michal Kliment
	 * @return int
	 */
	private function load_default()
	{
		if (!count ($this->values))
		{
			foreach ($this->filters as $filter)
			{
				foreach ($filter->default as $default)
				{
					$this->states[] = TRUE;
					$this->values[] = (is_array($default['value'])) ? $default['value'] : array($default['value']);
					$this->types[] = $filter->name;
					$this->operations[] = $default['oper'];
					$this->default[] = 1;
					$this->default_count++;
				}
			}
		}
		return $this->default_count;
	}

	/**
	 *  Renders filter form as HTML
	 *
	 * @author Michal Kliment
	 * @return string
	 */
	public function html()
	{
		// load default filter's values
		$this->load_default();
		
		$types = $this->types;		
		$type_options = array();
		$states = $this->states;
		$operations = $this->operations;
		$operation_options = array();
		$values = $this->values;
		$tables = $this->tables;
		$default = $this->default;
		$keys = array();
		
		$js_data = array();
		
		// iterate all filters
		foreach ($this->filters as $filter)
		{			
			$type_options[$filter->name] = $filter->label;
			
			$tables[$filter->name] = $filter->table;
		
			// save data for javascript
			
			$js_data[$filter->name]["returns"] = $this->returns[$filter->type];
			
			if ($filter->values)
			{
				foreach ($filter->values as $key => $value)
					$js_data[$filter->name]["values"][] = array($key, $value);
			}
			else
				$js_data[$filter->name]["values"] = '';
			
			foreach ($this->operation_types[$filter->type] as $operation_type)
				$js_data[$filter->name]["operations"][$operation_type] = __($this->opers[$operation_type]['name']);
			
			$js_data[$filter->name]["callback"] = $filter->callback;
			
			$js_data[$filter->name]["classes"] = (is_array($filter->class)) ? $filter->class : array('all' => $filter->class);
			
			$js_data[$filter->name]["css_classes"] = $filter->css_class;
		}
		
		foreach ($this->opers as $i => $operation)
			$operation_options[$i] = __($operation['name']);
		
		// add one extra empty filter
		$types[]		= NULL;
		$states[]		= 0;
		$operations[]	= NULL;
		$values[]		= NULL;
		$default[]		= NULL;
		
		$this->template->base_url = $this->base_url;
		$this->template->types = $types;
		$this->template->type_options = $type_options;
		$this->template->states = $states;
		$this->template->operations = $operations;
		$this->template->operation_options = $operation_options;
		$this->template->values = $values;
		$this->template->tables = $tables;
		$this->template->default = $default;
		$this->template->keys = $keys;
		$this->template->classes = $filter->css_class;
		
		$this->template->js_data = $js_data;
		
		$this->template->queries = $this->queries;
		
		$this->template->can_add = $this->can_add;
		
		return $this->template->render();
	}

	/**
	 *  Returns SQL query (only part after WHERE) to use in model  methods
	 *
	 * @param mixed $approved_keys		Array which defined columns which should be used in query.
	 * @author Michal Kliment
	 * @return string
	 */
	public function as_sql ($approved_keys = FALSE)
	{
		// loads default filter's values
		$this->load_default();
		
		$offset = 0;
		
		$queries = array();
		
		foreach ($this->types as $i => $type)
		{
			if (is_array($approved_keys) && !in_array($type, $approved_keys))
			{ // not allowed => continue
				continue;
			}
			
			if (!array_key_exists($type, $this->filters))
			{
				throw new InvalidArgumentException('Invalid option: ' . $type);
			}
			
			$filter = $this->filters[$type];
			
			$sub_queries = array();
			
			$values = array();
			if ($this->returns[$filter->type] == 'key')
			{
				foreach ($this->values[$i] as $value)
				{
					$values[] = $value;
					
					if (isset($filter->values[$value]))
						$values[] = $filter->values[$value];
				}
			}
			else
				$values = $this->values[$i];
					
			$notquery = false;
			
			foreach ($values as $value)
			{
				if (!isset($this->opers[$this->operations[$i]]))
					continue;
				
				$sql = $this->opers[$this->operations[$i]]['sql'];

				if (strpos($this->opers[$this->operations[$i]]['name'], 'not'))
						$notquery = true;
				
				if (isset($this->opers[$this->operations[$i]]['pattern']))						
				{
					if (!preg_match(
							$this->opers[$this->operations[$i]]['pattern'],
							Database::instance()->escape_str($value), $matches
						))
					{
						continue;
					}

					foreach ($matches as $key => $value)
						$sql = str_replace('{'.$key.'}', Database::instance()->escape_str($value), $sql);
				}

				$table_pom = mb_strlen($filter->table) ? $filter->table . '.' : '';
				$name_pom = (strpos($filter->name, '/') == FALSE ? $filter->name : substr($filter->name, 0, strpos($filter->name, '/')));
				
				$column = Database::instance()->escape_column(Database::instance()->escape_str($table_pom . $name_pom));

				if (isset($this->opers[$this->operations[$i]]['function']))
					$query = $this->opers[$this->operations[$i]]['function']. "($column)";	
				else
					$query = $column;

				$query .= " ". str_replace("{VALUE}", Database::instance()->escape_str($value), $sql);

				$sub_queries[] = $query;
			}
			
			if ($notquery)
				$operator = 'AND';
			else
				$operator = 'OR';
			
			$queries[] = "(".implode(" $operator ", $sub_queries).")";
		}
		
		return implode(" AND ", $queries);
	}

	/**
	 *
	 * @return array
	 */
	public function as_array()
	{
		// loads default filter's values
		$this->load_default();

		$data = array();
		foreach ($this->types as $i => $type)
		{
			$filter = $this->filters[$type];

			$value = array_map('trim', $this->values[$i]);

			if ($this->returns[$filter->type] == 'key' &&
				arr::search($value, $filter->values) !== FALSE)
			{
				$value = arr::search($value, $filter->values);
			}

			$data[] = array
			(
				'key'	=> $filter->name,
				'value'	=> $value,
				'op'	=> $this->operations[$i]
			);
		}

		return $data;
	}
	
	/**
	 * Indicates whether the filter form configuration was loaded from database.
	 * 
	 * @return boolean
	 */
	public function is_loaded_from_saved_query()
	{
		return $this->loaded_from_saved_query;
	}
	
	/**
	 * Indicates whether the filter form configuration that was loaded from 
	 * database is default.
	 * 
	 * @return boolean
	 */
	public function is_loaded_from_default_saved_query()
	{
		return $this->loaded_from_default_saved_query;
	}
	
	/**
	 * Indicates whether it is first load
	 * 
	 * @return boolean
	 */
	public function is_first_load()
	{
		return $this->first_load;
	}
	
	/**
	 * Returns base URL of the filter form.
	 * 
	 * @return string URL
	 */
	public function get_base_url()
	{
		return $this->base_url;
	}

	/**
	 *  Prints filter form as HTML
	 *
	 * @author Michal Kliment
	 * @return string
	 */
	public function  __toString()
	{
		return $this->html();
	}
}
