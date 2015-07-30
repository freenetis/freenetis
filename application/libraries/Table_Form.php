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
 * Table_Form is a component implementing simple form with <table> layout,
 * with the individual form fields enclosed in <td> tags.
 * 
 * @author Tomas Dulik, Michal Kliment
 * @see Table_Form_Item
 * @see table_form view
 */
class Table_Form
{

	protected $uri, $method, $form_def, $form_val;
	public $view;

	/**
	 * The constructor creates a table form.
	 * 
	 * @param $uri - URI which will be used in the form "action" attribute
	 * @param $method - possible values: "get" or "post"
	 * @param $form_def - array of form items (defines the whole form). An item can be:\n
	 * - 'tr' - will render a new table row, e.g. as </tr><tr>
	 * - 'td' - will render an empty table cell, e.g. as </td><td>
	 * - instance of Table_Form_Item which represents form fields, e.g.
	 * 	- input field
	 * 	- submit button
	 * 	- selection box
	 * 	- hidden fields
	 * @param $view - optional argument. If not given, then the default "table_form" view will be used.
	 * 				Otherwise, user can implement a view of his own and pass it by this parameter.
	 */
	public function __construct($uri = NULL, $method = NULL, $form_def = NULL, $view = NULL)
	{
		if (isset($view))
		{
			$this->view = $view;
		}
		else
		{
			$this->view = new View('table_form');
		}
		
		$this->view->uri = $uri;
		$this->view->form_def = $this->form_def = $form_def;
		$this->view->method = $this->method = $method;
		$this->view->form_val = $this->form_val = $this->values();
	}

	/**
	 * function values() returns an array of (name=>value) items, where 
	 * - "name" is the name of an form input field
	 * - "value" is the value inserted by the user into the input field
	 * @return unknown_type
	 */
	public function values()
	{
		$input = Kohana::$instance->input;
		$method = $this->method;
		
		if (isset($this->form_val))
		{
			return $this->form_val;
		}
		else
		{
			$values = array();
			
			if ($input->$method("submit", true))
			{
				foreach ($this->form_def as $value)
				{
					if (is_object($value) && $value->name != "submit")
					{
						if (($getval = $input->$method($value->name, true)) != "")
						{
							$values[$value->name] = $getval;
						}
					}
				}
			}

			return $values;
		}
	}

	/**
	 * Gets SQL filter where condition
	 * 
	 * @param string
	 */
	public function get_sql_where_condition()
	{
		if (!isset($this->form_val))
		{
			$this->form_val = $this->values();
		}
		
		$where = '';
		
		if (count($this->form_val) > 0)
		{
			$where .= 'WHERE ';
		}
		
		$first = true;
		
		foreach ($this->form_val as $key => $value)
		{
			if ($key != 'submit')
			{
				if (!$first)
				{
					$where .= ' AND ';
				}
				else
				{
					$first=false;
				}
				
				$where .= $key . ' LIKE \'%' . trim($value) . '%\' COLLATE utf8_general_ci';
			}
		}
		
		return $where;
	}

}
