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
 * Controller performs actions over invoices.
 *
 * @author Michal Kliment
 * @package Controller
 */
class Invoices_Controller extends Controller
{
	/**
	 * Index redirects to invoices show all
	 */
	public function index()
	{
		url::redirect('invoices/show_all');
	}

	/**
	 * Shows all invoices table
	 * 
	 * @author Michal Kliment
	 * @param integer $limit_results
	 * @param string $order_by
	 * @param string $order_by_direction
	 * @param integer $page_word
	 * @param integer $page
	 */
	public function show_all(
			$limit_results = 200, $order_by = 'id', $order_by_direction = 'ASC',
			$page_word = null, $page = 1)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		// gets new selector
		if (is_numeric($this->input->get('record_per_page')))
			$limit_results = (int) $this->input->get('record_per_page');

		// parameters control
		$allowed_order_type = array
		(
			'id', 'supplier', 'invoice_nr', 'var_sym', 'con_sym', 'date_inv',
			'date_due', 'vat', 'order_nr', 'currency'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';

		$invoice_model = new Invoice_Model();

		$total_invoices = $invoice_model->count_all();

		if (($sql_offset = ($page - 1) * $limit_results) > $total_invoices)
			$sql_offset = 0;

		$invoices = $invoice_model->get_all_invoices(
				$sql_offset, (int) $limit_results,
				$order_by, $order_by_direction
		);

		// create grid
		$grid = new Grid('invoices', '', array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'uri_segment'				=> 'page',
				'total_items'				=> $total_invoices,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results
		));

		// access control
		if ($this->acl_check_new('Accounts_Controller', 'invoices'))
		{
			$grid->add_new_button('invoices/add', __('Add new invoice'));
			$grid->add_new_button('invoices/import', __('Import new invoice'));
		}

		$grid->order_field('id');
		
		$grid->order_field('supplier');
		
		$grid->order_field('invoice_nr')
				->label('Invoice number');
		
		$grid->order_field('var_sym')
				->label('Variable symbol');
		
		$grid->order_field('con_sym')
				->label('Constant symbol');
		
		$grid->order_field('date_inv')
				->label('Date of issue');
		
		$grid->order_field('date_due')
				->label('Due date');
		
		$actions = $grid->grouped_action_field();
		
		// access control
		if ($this->acl_check_view('Accounts_Controller', 'invoices'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('invoices/show');
		}

		// access control
		if ($this->acl_check_edit('Accounts_Controller', 'invoices'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('invoices/edit');
		}

		// access control
		if ($this->acl_check_delete('Accounts_Controller', 'invoices'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('invoices/delete')
					->class('delete_link');
		}

		$grid->datasource($invoices);

		$view = new View('main');
		$view->title = __('List of all invoices');
		$view->breadcrumbs = __('Invoices');
		$view->content = new View('show_all');
		$view->content->headline = __('List of all invoices');
		$view->content->table = $grid;
		$view->render(TRUE);
	}

	/**
	 * Shows one invoice
	 * 
	 * @author Michal Kliment
	 * @param integer $invoice_id	id of inoice to show
	 */
	public function show($invoice_id = NULL)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);
		
		$invoice = new Invoice_Model($invoice_id);

		if (!$invoice_id || !$invoice->id)
			url::redirect('invoices');

		$this->session->del('ssInvoice_item_id');

		$supplier = new Member_Model($invoice->supplier_id);

		$invoice_item_model = new Invoice_item_Model();
		$invoice_items = $invoice_item_model->get_items_of_invoice($invoice_id);

		// redirect to adding new invoice items
		if ($_POST)
		{
			$item_count = (int) $_POST['item_count'];
			url::redirect('invoice_items/add/' . $invoice_id . '/' . $item_count);
		}

		// create grid
		$grid = new Grid('devices', NULL, array
		(
				'use_paginator'	=> false,
				'use_selector'	=> false
		));


		$grid->order_field('id');
		
		$grid->order_field('name');
		
		$grid->order_field('code');
		
		$grid->order_field('quantity');
		
		$grid->order_field('price');
		
		$grid->order_field('price_vat');
		
		$actions = $grid->grouped_action_field();
		
		// access control
		if ($this->acl_check_view('Accounts_Controller', 'invoices'))
		{
			$actions->add_action('id')
					->icon_action('show')
					->url('invoice_items/show');
		}

		// access control
		if ($this->acl_check_edit('Accounts_Controller', 'invoices'))
		{
			$actions->add_action('id')
					->icon_action('edit')
					->url('invoice_items/edit');
		}

		// access control
		if ($this->acl_check_delete('Accounts_Controller', 'invoices'))
		{
			$actions->add_action('id')
					->icon_action('delete')
					->url('invoice_items/delete')
					->class('delete_link');
		}

		$grid->datasource($invoice_items);

		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text(__('Invoice') . ' (' . $invoice->id . ')');

		$view = new View('main');
		$view->title = __('Show invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('invoices/show');
		$view->content->headline = __('Show invoice');
		$view->content->invoice = $invoice;
		$view->content->supplier = $supplier;
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to import invoice
	 * 
	 * @author Michal Kliment
	 * @param integer $invoice_template_id	id of type of invoice template (type)
	 */
	public function import($invoice_template_id = null)
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text('Import new invoice');

		// invoice type is set up
		if ($invoice_template_id)
		{
			$invoice_template = new Invoice_template_Model($invoice_template_id);

			// invoice template (type) doesn't exist
			if (!$invoice_template->id)
				url::redirect('invoices/import');

			// if xml flag is seted up file format will be xml otherwise be html
			$type = ($invoice_template->xml) ? 'xml' : 'html';

			// file is uploaded
			if ($_FILES)
			{
				$form = new Validation($_FILES);
				
				$form->add_rules(
						'file', 'upload::valid', 'upload::type[' . $type . ']',
						'upload::size[1M]'
				);

				if ($form->validate())
				{
					// saving file
					$filename = upload::save('file');

					$doc = new DOMDocument;

					// xml file will be load directly
					if ($invoice_template->xml)
						$doc->load($filename);

					// parse xml from html
					else
					{
						// open html file
						$file = fopen($filename, 'r');
						$text = fread($file, filesize($filename));

						// charset must be utf-8
						if ($invoice_template->charset != 'utf-8')
							$text = iconv($invoice_template->charset, 'UTF-8', $text);

						// get only part from begin tag to end tag
						@preg_match(
								'/(' . $invoice_template->begin_tag . '.+' .
								$invoice_template->end_tag . ')/sim',
								$text, $match
						);

						// load xml string
						$doc->loadXML($match[1]);
						fclose($file);
					}

					$xpath = new DOMXPath($doc);

					$supplier_id = null;

					// supplier is entered just in invoice_template
					if ($invoice_template->supplier_id)
						$supplier_id = $invoice_template->supplier_id;
					// get supplier by organization id
					else
					{
						$org_id = $xpath->evaluate($invoice_template->org_id);
						if ($org_id)
						{
							$member_model = new Member_Model();
							$supplier = $member_model
									->where('organization_identifier', $org_id)
									->find();
							$supplier_id = $supplier->id;
						}
					}

					// supplier is now known
					if ($supplier_id)
					{
						// create new invoice
						$invoice = new Invoice_Model();
						$invoice->supplier_id = $supplier_id;
						$invoice->invoice_nr = $xpath->evaluate($invoice_template->invoice_nr);
						$invoice->var_sym = $xpath->evaluate($invoice_template->var_sym);
						$invoice->con_sym = $xpath->evaluate($invoice_template->con_sym);
						$invoice->date_inv = $xpath->evaluate($invoice_template->date_inv);
						$invoice->date_due = $xpath->evaluate($invoice_template->date_due);
						$invoice->date_vat = $xpath->evaluate($invoice_template->date_vat);
						$invoice->vat = $xpath->evaluate($invoice_template->vat);
						$invoice->order_nr = $xpath->evaluate($invoice_template->order_nr);
						$invoice->currency = $xpath->evaluate($invoice_template->currency);

						$invoice->save();

						unlink($filename);

						url::redirect('invoices/show/' . $invoice->id);
					}

					// supplier cannot be found
					unlink($filename);
					status::error('Error - supplier cannot be found.');
					url::redirect('invoices/import/' . $invoice_template->id);
				}
				else
				{
					status::error('Error - bad file.');
					url::redirect('invoices/import/' . $invoice_template->id);
				}
			}

			$view = new View('main');
			$view->title = __('Import new invoice');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('invoices/import');
			$view->content->invoice_template_id = $invoice_template->id;
			$view->render(TRUE);
		}
		else
		{
			$invoice_template_model = new Invoice_template_Model();
			$invoice_templates = $invoice_template_model->get_all_invoice_templates();

			$types = array();

			foreach ($invoice_templates as $invoice_template)
				$types[$invoice_template->id] = $invoice_template->name;

			$form = new Forge('invoices/import');

			$form->group('Select type');
			
			$form->dropdown('type')
					->options($types)->rules('required');

			$form->submit('Next');

			if ($form->validate())
			{
				$form_data = $form->as_array();
				url::redirect('invoices/import/' . $form_data['type']);
			}

			$view = new View('main');
			$view->title = __('Import new invoice');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Import new invoice');
			$view->content->link_back = '';
			$view->content->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Adds new invoice
	 * 
	 * @author Michal Kliment
	 */
	public function add()
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		$arr_members = ORM::factory('member')->select_list('id', 'name');

		// creates form

		$this->form = new Forge('invoices/add');

		$this->form->group('')
				->label(__('Basic information'));
		
		$this->form->dropdown('supplier_id')
				->label('Supplier')
				->options($arr_members)->rules('required')
				->style('width:200px');
		
		$this->form->input('invoice_nr')
				->label('Invoice number')
				->rules('required|length[3,40]|valid_numeric');
		
		$this->form->input('var_sym')
				->label('Variable symbol')
				->rules('required|length[3,40]|valid_numeric');
		
		$this->form->input('con_sym')
				->label('Constant symbol')
				->rules('length[1,40]|valid_numeric');
		
		$this->form->date('date_inv')
				->label('Date of issue')
				->years(date('Y') - 100, date('Y'))
				->rules('required');
		
		$this->form->date('date_due')
				->label('Due date')
				->years(date('Y') - 100, date('Y'))
				->rules('required');
		
		$this->form->date('date_vat')
				->years(date('Y') - 100, date('Y'))
				->rules('required');
		
		$this->form->radio('vat')
				->label('VAT')
				->options(arr::bool())
				->default(1);
		
		$this->form->input('order_nr')
				->label('Order number')
				->rules('required|length[3,40]|valid_numeric');
		
		$this->form->input('currency')
				->rules('required|length[3,3]');

		$this->form->submit('Add');

		//if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			// creates new Invoice
			$invoice = new Invoice_Model();

			$invoice->supplier_id = $form_data['supplier_id'];
			$invoice->invoice_nr = $form_data['invoice_nr'];
			$invoice->var_sym = $form_data['var_sym'];
			$invoice->con_sym = $form_data['con_sym'];
			$invoice->date_inv = date("Y-m-d", $form_data['date_inv']);
			$invoice->date_due = date("Y-m-d", $form_data['date_due']);
			$invoice->date_vat = date("Y-m-d", $form_data['date_vat']);
			$invoice->vat = $form_data['vat'];
			$invoice->order_nr = $form_data['order_nr'];
			$invoice->currency = $form_data['currency'];

			// succes
			if ($invoice->save())
			{
				status::success('Invoice has been successfully added.');
			}
			if ($this->session->get('ssInvoice_Items'))
			{
				url::redirect(
						'invoice_items/add/' . $invoice->id .
						'/' . count($this->session->get('ssInvoice_Items'))
				);
			}
			else
			{
				url::redirect('invoices/show_all');
			}
		}

		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text('Add new invoice');

		$view = new View('main');
		$view->title = __('Add new invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = __('Add new invoice item');
		$view->content->form = $this->form->html();
		$view->render(TRUE);
	}

	/**
	 * Edits invoice
	 * 
	 * @param integer $invoice_id	id of invoice to edit
	 */
	public function edit($invoice_id = NULL)
	{
		// access rights
		if (!$this->acl_check_edit('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		$invoice = new Invoice_Model($invoice_id);

		if (!$invoice_id || !$invoice->id)
			url::redirect('invoices/show_all');
		
		$arr_members = ORM::factory('member')->select_list();

		// creates form
		$this->form = new Forge('invoices/edit/' . $invoice->id);

		$this->form->group('Basic information');
		
		$this->form->dropdown('supplier_id')
				->label(__('Supplier') . ':')
				->options($arr_members)
				->rules('required')
				->selected($invoice->supplier_id);
		
		$this->form->input('invoice_nr')
				->label(__('Invoice number') . ':')
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice->invoice_nr);
		
		$this->form->input('var_sym')
				->label(__('Variable symbol') . ':')
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice->var_sym);
		
		$this->form->input('con_sym')
				->label(__('Constant symbol') . ':')
				->rules('required|length[1,40]|valid_numeric')
				->value($invoice->con_sym);
		
		$this->form->date('date_inv')
				->label(__('Date of issue') . ':')
				->years(date('Y') - 100, date('Y'))
				->rules('required')
				->value(strtotime($invoice->date_inv));
		
		$this->form->date('date_due')
				->label(__('Due date') . ':')
				->years(date('Y') - 100, date('Y'))
				->rules('required')
				->value(strtotime($invoice->date_due));
		
		$this->form->date('date_vat')
				->label(__('Date vat') . ':')
				->years(date('Y') - 100, date('Y'))
				->rules('required')
				->value(strtotime($invoice->date_vat));
		
		$this->form->radio('vat')
				->label(__('VAT') . ':')
				->options(arr::bool())
				->default($invoice->vat);
		
		$this->form->input('order_nr')
				->label(__('Order number') . ':')
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice->order_nr);
		
		$this->form->input('currency')
				->label(__('Currency') . ':')
				->rules('required|length[3,3]')
				->value($invoice->currency);

		$this->form->submit('Edit');

		// if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			$invoice = new Invoice_Model($invoice_id);

			$invoice->supplier_id = $form_data['supplier_id'];
			$invoice->invoice_nr = $form_data['invoice_nr'];
			$invoice->var_sym = $form_data['var_sym'];
			$invoice->con_sym = $form_data['con_sym'];
			$invoice->date_inv = date("Y-m-d", $form_data['date_inv']);
			$invoice->date_due = date("Y-m-d", $form_data['date_due']);
			$invoice->date_vat = date("Y-m-d", $form_data['date_vat']);
			$invoice->vat = $form_data['vat'];
			$invoice->order_nr = $form_data['order_nr'];
			$invoice->currency = $form_data['currency'];

			// success
			if ($invoice->save())
			{
				status::success('Invoice has been successfully updated.');
			}
			url::redirect('invoices/show_all');
		}

		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->link('invoices/show/' . $invoice_id,
						__('Invoice') . ' (' . $invoice->id . ')',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text('Edit invoice');

		$view = new View('main');
		$view->title = __('Edit invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = __('Edit invoice');
		$view->content->form = $this->form->html();
		$view->render(TRUE);
	}

	/**
	 * Deletes invoice
	 * 
	 * @param integer $invoice_id id of invoice to delete
	 */
	public function delete($invoice_id = NULL)
	{
		// access rights
		if (!$this->acl_check_delete('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);
		
		$invoice = new Invoice_Model($invoice_id);

		if (!$invoice_id || !$invoice->id)
			url::redirect('invoices');

		// success
		if ($invoice->delete())
		{
			status::success('Invoice has been successfully deleted.');
		}
		
		url::redirect('invoices/show_all');
	}

}
