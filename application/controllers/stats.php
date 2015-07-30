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
 * Controller display statistic from different parts of system.
 *
 * @author Michal Kliment
 * @package Controller
 */
class Stats_Controller extends Controller
{

	/**
	 * Links
	 *
	 * @var string
	 */
	private $links;

	/**
	 * Construct function
	 * Generates menu
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
		parent::__construct();
		
		$array[] = html::anchor(
				'stats/members_increase_decrease',
				__('Increase and decrease of members')
		);
		
		$array[] = html::anchor('stats/members_growth', __('Growth of members'));
	
		$array[] = html::anchor(
				'stats/incoming_member_payment', __('Incoming member payment')
		);
		
		$this->links = implode(' | ', $array);
	}

	/**
	 * Index function
	 * Redirects to function with increase of members
	 * 
	 * @author Michal Kliment
	 */
	public function index()
	{
		url::redirect('stats/members_increase_decrease');
	}

	/**
	 * Function to show increase of members in each month of being of association
	 * 
	 * @author Michal Kliment
	 */
	public function members_increase_decrease()
	{
		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		// creates instance of member ID=1 (Association)
		$association = new Member_Model(1);
		$first_entrance_date = date_parse($association->entrance_date);

		// creates new member model
		$member_model = new Member_Model();

		$last_entrance = $member_model->orderby('entrance_date', 'desc')
				->find_all()
				->current();
		
		$last_entrance_date = $last_entrance && $last_entrance->entrance_date != ''
				? $last_entrance->entrance_date : date("Y-m-d");

		$filter_form = new Filter_form('m');
		
		$filter_form->add('date')
				->label(__('Date'))
				->type('date')
				->default(Filter_form::OPER_GREATER_OR_EQUAL, $association->entrance_date)
				->default(Filter_form::OPER_SMALLER_OR_EQUAL, $last_entrance_date)
				->class('without_days');
		
		$filter_form->add('increase')
				->type('number');
		
		$filter_form->add('decrease')
				->type('number');

		$month = $first_entrance_date['month'];
		$year = $first_entrance_date['year'];

		$counts = array();

		$dates = $member_model->get_all_entrance_and_leaving_dates(
				$filter_form->as_sql()
		);

		foreach ($dates as $date)
		{
			$parse_date = date_parse($date->date);

			if (!isset($counts[$parse_date['year']][$parse_date['month']]))
			{
				$counts[$parse_date['year']][$parse_date['month']] = array
				(
					'increase' => 0,
					'decrease' => 0
				);
			}

			$counts[$parse_date['year']][$parse_date['month']]['increase'] += $date->increase;
			$counts[$parse_date['year']][$parse_date['month']]['decrease'] += $date->decrease;
		}

		// grid with lis of users
		$grid = new Grid('members', null, array
		(
				'separator' => '<br /><br />',
				'use_paginator' => false,
				'use_selector' => false,
		));

		$grid->field('date');
		
		$grid->field('increase')
				->class('right');
		
		$grid->field('decrease')
				->class('right');

		$grid->datasource($dates);

		$view = new View('main');
		$view->google_jsapi_enabled = TRUE;
		$view->content = new View('stats/members_increase_decrease');
		$view->content->js_data_array_str = '';

		$year = min(array_keys($counts));
		$month = min(array_keys($counts[$year]));

		$max_year = max(array_keys($counts));
		$max_month = max(array_keys($counts[$max_year]));
		$max_date = date('Y-m-d', mktime(
				0, 0, 0, $max_month, count(date::days($max_month)), $max_year
		));

		while (($date = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year))) <= $max_date)
		{
			if (isset($counts[$year][$month]))
				$view->content->js_data_array_str .= "['$month/$year', "
					. $counts[$year][$month]['increase'] . ", "
					. $counts[$year][$month]['decrease'] . "],";
			else
				$view->content->js_data_array_str .= "['$month/$year', 0, 0],";

			// iterate to next month
			$month++;
			if ($month == 13)
			{
				$month = 1;
				$year++;
			}
		}
		
		$breadcrumbs = breadcrumbs::add()
				->text('Stats')
				->text('Increase and decrease of members');

		$view->title = __('Increase and decrease of members');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content->link_back = $this->links;
		$view->content->filter_form = $filter_form;
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to show growth of members in each month of being of association
	 * 
	 * @author Michal Kliment
	 */
	public function members_growth()
	{
		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		// creates new member model
		$member_model = new Member_Model();
		// finds all members
		$members = $member_model->orderby('entrance_date')->find_all();

		// creates instance of member ID=1 (Association)
		$association = new Member_Model(1);
		$association_entrance_date = date_parse($association->entrance_date);

		$entrances = array();
		$leavings = array();
		$max = 0;

		foreach ($members as $member)
		{
			// parses date from datetime to array
			$entrance_date = date_parse(date::month($member->entrance_date));
			// someone just entranced this month
			if (isset($entrances[$entrance_date['year']][$entrance_date['month']]))
				$entrances[$entrance_date['year']][$entrance_date['month']]++;
			else
			// this is first member who entranced this month
				$entrances[$entrance_date['year']][$entrance_date['month']] = 1;
			// only if leaving date is not empty
			if ($member->leaving_date != '0000-00-00')
			{
				// parses date from datetime to array
				$leaving_date = date_parse(date::month($member->leaving_date));
				// someone just leaved this month
				if (isset($leavings[$leaving_date['year']][$leaving_date['month']]))
					$leavings[$leaving_date['year']][$leaving_date['month']]++;
				else
				// this is first member who leaved this month
					$leavings[$leaving_date['year']][$leaving_date['month']] = 1;
			}
		}

		$labels = array();
		$values = array();
		$month = array();
		$x = 0;

		// we draw graph from first month of association´s foundation year to current month of current year
		for ($i = $association_entrance_date['year']; $i <= date("Y"); $i++)
		{
			for ($j = 1; $j <= 12; $j++)
			{
				// we draw label only for first month of year
				$labels[$x] = ($j == 1) ? $i : ' ';

				// cycle just ran, uses previously value
				if ($x)
				{
					$values[$x] = $values[$x - 1];
					if (isset($entrances[$i][$j]))
						$values[$x] += $entrances[$i][$j];
					if (isset($leavings[$i][$j]))
						$values[$x] -= $leavings[$i][$j];
				}
				else
				// first run of cycle
					$values[$x] = (isset($entrances[$i][$j])) ? $entrances[$i][$j] : 0;

				// finding max, important for drawing graph
				if ($values[$x] > $max)
					$max = $values[$x];

				if ((
						$i > $association_entrance_date['year'] &&
						$i < date("Y")
					) || (
						$i == date("Y") &&
						$j <= date("m")
					) || (
						$i == $association_entrance_date['year'] &&
						$j >= $association_entrance_date['month']
					))
				{
					$months[$x] = __('' . Date::$months[$j]) . ' ' . $i;
				}

				$x++;
			}
		}
		
		$breadcrumbs = breadcrumbs::add()
				->text('Stats')
				->text('Growth of members');

		$view = new View('main');
		$view->title = __('Growth of members');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('stats/members_growth');
		$view->content->link_back = $this->links;
		$view->content->labels = $labels;
		$view->content->values = $values;
		$view->content->months = $months;
		$view->content->max = $max;
		$view->render(TRUE);
	}

	/**
	 * Function to show graph of imcoming member payment
	 * 
	 * @author Michal Kliment
	 * @param integer $start_year
	 * @param integer $end_year
	 */
	public function incoming_member_payment($start_year = NULL, $end_year = NULL)
	{

		// access control
		if (!$this->acl_check_edit('Settings_Controller', 'system'))
			Controller::error(ACCESS);

		// form is posted
		if (isset($_POST) && $_POST)
		{
			$start_year = $_POST['start_year'];
			$end_year = $_POST['end_year'];

			if ($end_year < $start_year)
				$end_year = $start_year;

			// redirect to this method with correct parameters
			url::redirect(
					'stats/incoming_member_payment/' .
					$start_year . '/' . $end_year
			);
		}

		$values = array();
		$labels = array();
		$months = array();
		$x = 0;
		$max = 0;

		// creates instance of member ID=1 (Association)
		$association = new Member_Model(1);
		$association_entrance_date = date_parse($association->entrance_date);

		// start year is not set, use date of creation of association
		if (!$start_year)
			$start_year = $association_entrance_date['year'];

		// end year is not set, use current year
		if (!$end_year)
			$end_year = date("Y");

		$years = array();

		for ($i = $association_entrance_date['year']; $i <= date("Y"); $i++)
			$years[$i] = $i;

		$transfer_model = new Transfer_Model();
		$amounts = $transfer_model->get_all_monthly_amounts_of_incoming_member_payment();

		// gets all amount of member payment by months
		$arr_amounts = array();
		foreach ($amounts as $amount)
			$arr_amounts[$amount->year][(int) $amount->month] = $amount->amount;

		// we draw graph 
		for ($i = $start_year; $i <= $end_year; $i++)
		{
			for ($j = 1; $j <= 12; $j++)
			{
				// draw label only 12 times
				if ($x % ($end_year - $start_year + 1) == 0)
				// we draw label only for first month of year
					$labels[$x] = $j . ' / ' . substr($i, 2, 2);
				else
					$labels[$x] = ' ';

				$values[$x] = (isset($arr_amounts[$i][$j])) ? $arr_amounts[$i][$j] : 0;

				// finding max, important for drawing graph
				if ($values[$x] > $max)
					$max = $values[$x];

				if ((
						$i > $association_entrance_date['year'] &&
						$i < date("Y")
					) || (
						$i == date("Y") &&
						$j <= date("m")
					) || (
						$i == $association_entrance_date['year'] &&
						$j >= $association_entrance_date['month']
					))
				{
					$months[$x] = __('' . date::$months[$j]) . ' ' . $i;
				}

				$x++;
			}
		}

		// round max
		$max = (substr($max, 0, 2) + 1) . num::null_fill(0, strlen($max) - 2);

		// calculation of correct rates of axes
		$y_count = substr($max, 0, 2);

		while ($y_count > 25)
			$y_count /= 2;

		$x_rate = num::decimal_point(round(100 / (($end_year - $start_year + 1) * 12 - 1), 5));
		$y_rate = num::decimal_point(round(100 / $y_count, 5));
		
		$breadcrumbs = breadcrumbs::add()
				->text('Stats')
				->text('Incoming member payment in the period')
				->disable_translation()
				->text($start_year . '-' . $end_year);

		$view = new View('main');
		$view->title = __('Incoming member payment');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('stats/incoming_member_payment');
		$view->content->x_rate = $x_rate;
		$view->content->y_rate = $y_rate;
		$view->content->link_back = $this->links;
		$view->content->labels = $labels;
		$view->content->values = $values;
		$view->content->months = $months;
		$view->content->max = $max;
		$view->content->start_year = $start_year;
		$view->content->end_year = $end_year;
		$view->content->years = $years;
		$view->render(TRUE);
	}

}
