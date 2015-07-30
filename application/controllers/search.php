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
 * Controller handles search throught whole system.
 *
 * @author	Michal Kliment
 */
class Search_Controller extends Controller
{
	/**
	 * Array with results of search
	 *
	 * @var array
	 */
	private $results = array();

	/**
	 * Only redirects to method simple
	 *
	 * @author Michal Kliment
	 */
	public function index()
	{
		url::redirect('search/simple/' . $this->input->get('keyword'));
	}

	/**
	 * Base method, makes search.
	 * Search result is placed to property $results
	 *
	 * @author Ondrej Fibich, Michal Kliment
	 * @param string $keyword
	 * @param integer $limit Limit of results
	 */
	private function search($keyword = NULL, $limit = NULL)
	{
		// trim and remove unrequired cars from keyword
		$keyword = trim($keyword);
		$mkeyword = preg_replace("/[\[\]!\"#$%&'()*+,\/:;<=>?@\^`{|}~-]/", ' ', $keyword);
		
		// separate keywords
		$keywords = explode(' ', preg_replace("/\s+/", ' ', $mkeyword));

		// variations - if keyword contains many words (> 3) then only create
		// variations that contains all words of keyword but only sorted in many
		// ways. If keyword do not contain many words then make all variations.
		// If keyword contains more then 5 words then disable variations at all.
		$variation_keys = array();

		if (count($keywords) <= 3) // all
		{
			for ($i = count($keywords); $i > 0; $i--)
			{
				$variation_keys = arr::merge($variation_keys, arr::variation($keywords, $i));
			}
		}
		else if (count($keywords) > 5) // only keyword
		{
			$variation_keys = array($mkeyword);
		}
		else // only mixed keyword
		{
			$variation_keys = arr::variation($keywords, count($keywords));
		}
		

		// search model
		$search_model = new Search_Model();

		// search variables
		$search_rules = Search_Model::get_rules_sorted_by_weight();
		$sums = array();
		$counts = array();
		$total_counts = array();
		$values = array();
		
		// no rules => empty result
		if (!count($search_rules))
		{
			$this->results = array();
			return;
		}
		
		// each rule should get oportunity to be searched (reserved limits)
		$result_limit = !empty($limit) ? ceil($limit / count($search_rules)) : NULL;
		
		$total_count = 0;
		
		// foreach all search rules
		foreach ($search_rules as $rule)
		{
			// networks is disabled
			if (!Settings::get('networks_enabled') && (
					$rule['model'] == 'device' ||
					$rule['model'] == 'subnet' ||
					$rule['model'] == 'link'
				))
			{
				continue;
			}
			
			// search only in keyword by default
			$searched_keys = array($keyword);
			
			// variation enabled?
			if (isset($rule['variation_enabled']) && $rule['variation_enabled'])
			{
				$searched_keys = $variation_keys;
			}
			
			// foreach variations
			foreach ($searched_keys as $key)
			{
				if (isset($total_counts[$rule['model']]))
				{
					$total_counts[$rule['model']]++;
				}
				else
				{
					$total_counts[$rule['model']] = 1;
				}

				$result = $search_model->{$rule['method']}($key, $result_limit);

				foreach ($result as $row)
				{
					$titled_value = url::title($row->value, ' ');
					$percent = 0;
					
					// test how much are texts similar
					similar_text($titled_value, url::title($key, ' '), $percent);
					
					// rating and informations about this result was not registered yet
					if (!isset($sums[$rule['model']][$row->id]))
					{
						$sums[$rule['model']][$row->id] = 0;
						$counts[$rule['model']][$row->id] = 0;
						$values[$rule['model']][$row->id] = $row;
					}
					
					// weight to percentage
					$weight = $rule['weight'];
					
					// special treatment if value is same as keyword
					if (!isset($rule['ignore_special_threatment']) ||
						!$rule['ignore_special_threatment'])
					{
						// keyword match with special threatment for 
						// login (we do want to mess tha members)
						if (strtolower(trim($keyword)) == strtolower(trim($row->value)) &&
							$rule['method'] != 'user_login')
						{
							$weight = 3.5;
						}
						// modified keyword match
						if (strtolower(trim($mkeyword)) == strtolower(trim($row->value)))
						{
							$weight = 2.5;
						}
						// special treatment if titled value is same as titled modified keyword
						else if (url::title($mkeyword) == $titled_value)
						{
							$weight = 2;
						}
					}
					// special threatment for number results weight if they are equal
					if ($rule['method'] == 'member_id' &&
						intval($keyword) == intval($row->value))
					{
						// member ID is equal => increase weight of this result
						$weight = 6;
					}
					else if ($rule['method'] == 'member_variable_symbol' &&
							 intval($keyword) == intval($row->value))
					{
						// variable key equals to key?
						$weight = 6;
					}
					
					// add rating about the current result
					$sums[$rule['model']][$row->id] += $percent * $weight;
					$counts[$rule['model']][$row->id]++;
					
					$total_count++;
					
					// end if we have already enought results
					if (!empty($limit) && $total_count >= $limit)
					{
						break 3;
					}
				}
			}
		}

		$result_sums = array();

		// transforms to 1-dimensional array
		foreach ($sums as $model => $model_sums)
		{
			foreach ($model_sums as $id => $sum)
			{
				$result_sums[] = $sum;
				$this->results[] = $values[$model][$id];
			}
		}
		
		// sorts results
		array_multisort($result_sums, SORT_DESC, $this->results, SORT_DESC);
	}

	/**
	 * Simple searching, uses method search
	 *
	 * @author Michal Kliment
	 * @param string $keyword
	 * @param integer $limit_results
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function simple($keyword = NULL, $limit_results = 20,
			$page_word = 'page', $page = 1)
	{
		// bad parameter
		if (!$keyword)
		{
			Controller::warning(PARAMETER);
		}

		// searching
		$this->search($keyword);

		$pagination = new Pagination(array
		(
			'base_url'			=> Config::get('lang') . '/search/simple/'
								. $keyword . '/' . $limit_results,
			'uri_segment'		=> 'page',
			'total_items'		=> count($this->results),
			'items_per_page'	=> $limit_results
		));

		$from = ($page - 1) * $limit_results;
		$to = $page * $limit_results;

		if ($to >= count($this->results))
		{
			$to = count($this->results) - 1;
		}

		$view = new View('main');
		$view->keyword = $keyword;
		$view->title = $keyword . ' - ' . __('Searching');
		$view->content = new View('search/simple');
		$view->content->keyword = $keyword;
		$view->content->total_items = count($this->results);
		$view->content->pagination = $pagination;
		$view->content->results = $this->results;
		$view->content->from = $from;
		$view->content->to = $to;
		$view->render(TRUE);
	}

	/**
	 * Ajax searching (for whisper)
	 *
	 * @author Michal Kliment
	 * @param integer $count do not change contans unless you know what it will cause!!
	 */
	public function ajax($count = 150)
	{
		$keyword = $this->input->get('q');
		$this->search($keyword, $count);

		$view = new View('search/ajax');
		$view->total_items = count($this->results);
		$view->results = $this->results;
		$view->keyword = $keyword;
		$view->render(TRUE);
	}

}
