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
 * Handles changing of viewing language. 
 * 
 * @author	Michal Kliment
 * @package Controller
 */
class Languages_Controller extends Controller
{
	/**
	 * Names of available languages in system.
	 * Keys are shortcuts values are descriptors
	 *
	 * @var array
	 */
	private $lang_names = array
	(
	    'cs' => 'Česky',
	    'en' => 'English',
	);

	/**
	 * Index redirects to change language
	 */
	public function index()
	{
		url::redirect('languages/change');
	}

	/**
	 * Function to change language
	 * 
	 * @author Michal Kliment
	 */
	public function change()
	{
		// back to previous page
		if (url_lang::previous() != '' && url_lang::previous() != url_lang::current())
		{
			$uri = url_lang::previous();
		}
		else
		// there is no previous page
		{
			$uri = 'login';
		}

		$index_page = (Settings::get('index_page')) ? 'index.php/' : '';

		$view = new View('main');
		$view->title = __('Change language');
		$view->content = new View('languages/change');
		$view->content->langs = $this->lang_names;
		$view->content->uri = $uri;
		$view->content->index_page = $index_page;
		$view->render(TRUE);
	}
    
}
