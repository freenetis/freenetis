<?php

defined('SYSPATH') or die('No direct script access.');
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
 * Handles export of data from system tp user in CSV or XLS format.
 * 
 * @package Controller
 */
class Export_Controller extends Controller
{

	/**
	 * Function exports list of items to csv file.
	 * Rows are separated by newlines and it columns by semicolon.
	 * 
	 * @author Jiri Svitak, Ondřej Fibich
	 * @param string $content	Content of export
	 * @param string $encoding	By default the result is encoded in utf-8 
	 *							and encoding can change this
	 * @param mixed $id			ID of item
	 */
	public function csv($content = null, $encoding = null, $id = null)
	{
		$encodings = array
		(
			'utf-8'			=> 'UTF-8',
			'windows-1250'	=> 'WINDOWS-1250'
		);
		
		// display form only if required
		$form_display = !isset($encodings[$encoding]);

		if ($form_display)
		{
			$form = new Forge();

			$form->set_attr('class', 'form nopopup');

			$form->dropdown('encoding')
					->options($encodings)
					->selected($encoding);

			$form->submit('Submit');
		}

		// form is validate
		if (!$form_display || $form->validate())
		{
			if ($form_display)
			{
				$form_data = $form->as_array();
				$encoding = $form_data['encoding'];
			}

			// each content has specific query
			switch ($content)
			{
				// export for members with filter
				case 'members':

					if (!$this->acl_check_view('Members_Controller', 'members'))
					{
						Controller::error(ACCESS);
					}

					$filter_form = new Filter_form('m');
					$filter_form->autoload();

					$member = new Member_Model();

					try
					{
						$items = $member->get_all_members_to_export($filter_form->as_sql());
					}
					catch (Exception $e)
					{
						$items = array();
					}

					$filename = __('Members') . '.csv';

					break;

				// export for items of subnet
				case 'subnets':

					$subnet_model = new Subnet_Model($id);

					if ($subnet_model->id == 0)
					{
						Controller::error(RECORD);
					}

					if (!$this->acl_check_view('Devices_Controller', 'subnet'))
					{
						Controller::error(ACCESS);
					}

					$items = $subnet_model->get_items_of_subnet($id);
					$filename = $subnet_model->name . '.csv';

					break;

				// auto export for all tables
				default:

					if (!$this->acl_check_view('Settings_Controller', 'system'))
					{
						Controller::error(ACCESS);
					}

					if (empty($content))
					{
						Controller::warning(PARAMETER, __('Bad parameter for export'));
					}

					$filename = __(utf8::ucfirst($content)) . '.csv';

					$content = inflector::singular($content);

					if (!Kohana::auto_load($content . '_Model'))
					{
						Controller::warning(PARAMETER, __('Bad parameter for export'));
					}

					try
					{
						$all = ORM::factory($content)->find_all();
						$items = array();

						foreach ($all as $one)
						{
							$items[] = $one->as_array();
						}

						unset($all);
					}
					catch (Exception $e)
					{
						Controller::warning(PARAMETER, __('Bad parameter for export'));
					}

					break;
			}

			// empty result?
			if (!count($items))
			{
				status::error('Invalid data - no data available');
			}
			else
			{
				/* Generate file */

				// set content header
				header('Content-type: application/csv');
				header('Content-Disposition: attachment; filename="' . $filename . '"');

				// get headers
				foreach ($items[0] as $key => $value)
				{
					// translation of column titles
					$field = __(utf8::ucfirst(inflector::humanize($key)));
					// file cannot start with ID, otherwise excel
					// and openoffice think that the file is invalid
					if ($field == 'ID')
					{
						$field = __('Number');
					}
					// character encoding
					if ($encoding != 'utf-8')
					{
						$field = iconv('utf-8', $encoding, $value);
					}
					// output
					echo '"' . $field . '";';
				}

				echo "\n";

				// for each data row
				foreach ($items as $line)
				{
					// this foreach writes line
					foreach ($line as $key => $value)
					{				
						// character encoding
						if ($encoding != 'utf-8')
						{
							$field = iconv('utf-8', $encoding, $value);
						}
						// output
						echo '"' . $value . '";';
					}
					echo "\n";
				}

				// do not display view
				die();
			}
		}
		
		$title = __('Export');

		$view = new View('main');
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form;
		$view->render(TRUE);
	}
	
	/*
	 * Function returns organization logo for export
	 * 
	 * @author David Raska
	 */
	public function logo()
	{		
		$logo = Settings::get('registration_logo');
		
		if (!empty($logo) && file_exists($logo))
		{
			download::force($logo);
		}
	}

}
