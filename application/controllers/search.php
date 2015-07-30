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
	 * @author Michal Kliment
	 * @param string $keyword
	 */
	private function search($keyword = NULL)
	{
		$keywords = explode(" ", trim($keyword));

		$keys = array();

		// finds all possible variations
		for ($i = count($keywords); $i > 0; $i--)
			$keys = arr::merge($keys, arr::variation($keywords, $i));

		$search_model = new Search_Model();

		$sums = array();
		$counts = array();
		$total_counts = array();
		$values = array();

		// foreach all search rules
		foreach (Search_Model::$rules as $rule)
		{
			// foreach variations
			foreach ($keys as $key)
			{
				if (isset($total_counts[$rule['model']]))
					$total_counts[$rule['model']]++;
				else
					$total_counts[$rule['model']] = 1;

				$result = $search_model->{$rule['method']}($key);

				foreach ($result as $row)
				{
					// test how much are texts similar
					similar_text(url::title($row->value, " "), url::title($key, " "), $percent);
					if (!isset($sums[$rule['model']][$row->id]))
					{
						$sums[$rule['model']][$row->id] = 0;
						$counts[$rule['model']][$row->id] = 0;
						$values[$rule['model']][$row->id] = $row;
					}
					$sums[$rule['model']][$row->id] += $percent;
					$counts[$rule['model']][$row->id]++;
				}
			}
		}

		$result_sums = array();
		$result_counts = array();

		// transforms to 1-dimensional array
		foreach ($sums as $model => $model_sums)
		{
			foreach ($model_sums as $id => $sum)
			{
				$result_sums[] = $sum;
				$result_counts[] = $counts[$model][$id] / $total_counts[$model];
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
	public function simple(
			$keyword = NULL, $limit_results = 20, $page_word = 'page', $page = 1)
	{
		//$profiler = new Profiler();
		// bad parameter
		if (!$keyword)
			Controller::warning(PARAMETER);

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
			$to = count($this->results) - 1;

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

		//echo $profiler->render(TRUE);
	}

	/**
	 * Ajax searching (for whisper)
	 *
	 * @author Michal Kliment
	 * @param integer $count
	 */
	public function ajax($count = 100)
	{
		$this->search($this->input->get('q'));

		$counter = 0;

		// prints all results
		foreach ($this->results as $result)
		{
			$counter++;
			?>
			<a href="<?php echo url_lang::base() . $result->link . $result->id ?>" class="whisper_search_result">
				<b><?php echo $result->return_value ?></b><br />
				<i><?php echo $result->desc ?></i>
			</a>
			<?php
			if ($counter == $count)
				break;
		}

		// no results
		if (!$counter)
		{
			?>
			<div class="whisper_search_result"><?php echo __('No items found.') ?></div>
			<?php
		}
	}

}
