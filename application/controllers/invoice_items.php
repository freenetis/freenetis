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
 * Controller performs actions over invoice items which belongs to invoice.
 *
 * @author Michal Kliment
 * @package Controller
 */
class Invoice_items_Controller extends Controller
{
	/**
	 * Constructor, only test if finance is enabled
	 */
	public function __construct()
	{		
		parent::__construct();
		
		if (!Settings::get('finance_enabled'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Index redirects to invoices show all
	 */
	public function index()
	{
		url::redirect('invoices/show_all');
	}

	/**
	 * Shows invoice item
	 * 
	 * @author Michal Kliment
	 * @param integer $invoice_item_id	id of invoice item do show
	 */
	public function show($invoice_item_id = NULL)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);
		
		$invoice_item = new Invoice_item_Model($invoice_item_id);

		if (!$invoice_item_id || !$invoice_item->id)
			url::redirect('invoices');

		$this->session->set('ssInvoice_item_id', $invoice_item_id);
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->link('invoices/show/' . $invoice_item->id,
						__('Invoice') . ' (' . $invoice_item->id . ')',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text($invoice_item->name . ' (' . $invoice_item->id . ')')
				->text('Show invoice item');

		$view = new View('main');
		$view->title = __('Show invoice item');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('invoices/items_show');
		$view->content->headline = __('Show invoice item');
		$view->content->invoice_item = $invoice_item;
		$view->render(TRUE);
	}

	/**
	 * Adds new invoice items (more than one)
	 * 
	 * @author Michal Kliment
	 * @param integer $invoice_id id of invoice which belongs to added items
	 */
	public function add($invoice_id = NULL)
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		if (!$invoice_id)
		{
			$invoice_model = new Invoice_Model();
			$arr_invoices = $invoice_model->select_list('id', 'invoice_nr');
		} 
		else 
		{
			if (!is_numeric($invoice_id))
				Controller::warning (PARAMETER);
			
			$invoice = new Invoice_Model($invoice_id);
			
			if (!$invoice->id)
				Controller::error (RECORD);
			
			$arr_invoices = array($invoice->id => $invoice->invoice_nr);
		}

		// creates form
		$this->form = new Forge();

		$this->form->group('Basic information');
		
		$this->form->dropdown('invoice_id')
				->label(__('Invoice number') . ':')
				->options($arr_invoices)
				->rules('required')
				->selected($invoice_id);
		
		$this->form->input('name')
				->rules('required|length[3,40]');
		
		$this->form->input('code')
				->rules('required|length[3,40]');
		
		$this->form->input('quantity')
				->rules('required|length[1,40]|valid_numeric');
		
		$this->form->input('author_fee')
				->rules('length[1,40]|valid_numeric');
		
		$this->form->input('contractual_increase')
				->rules('length[1,40]|valid_numeric');
		
		$this->form->radio('service')
				->options(arr::bool())
				->default(0);
		
		$this->form->input('price')
				->rules('required|length[1,40]|valid_numeric');
		
		$this->form->input('vat')
				->label('tax rate')
				->rules('length[1,5]|valid_range|valid_numeric')
				->value(0);

		$this->form->submit('Add');
		

		//if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			// creates new invoice item
			$invoice_item = new Invoice_item_Model();
			$invoice_item->invoice_id = $form_data['invoice_id'];
			$invoice_item->name = $form_data['name'];
			$invoice_item->code = $form_data['code'];
			$invoice_item->quantity = (double) $form_data['quantity'];
			$invoice_item->author_fee = (double) $form_data['author_fee'];
			$invoice_item->contractual_increase = (double) $form_data['contractual_increase'];
			$invoice_item->service = $form_data['service'];
			$invoice_item->price = (double) $form_data['price'];
			$invoice_item->vat = (double) !empty($form_data['vat']) ? $form_data['vat'] / 100 : 0;
			
			// success
			if ($invoice_item->save())
			{
				status::success('Invoice item has been successfully added');
			}
			
			$this->redirect('invoices/show/' . $invoice_id);
		} else {
			// breadcrumbs
			$breadcrumbs = breadcrumbs::add()
					->link('invoices/show_all', 'Invoices',
							$this->acl_check_view('Accounts_Controller', 'invoices'))
					->link('invoices/show/' . $invoice_id,
							__('Invoice') . ' (' . $invoice_id . ')',
							$this->acl_check_view('Accounts_Controller', 'invoices'))
					->text('Add new invoice item');

			$view = new View('main');
			$view->title = __('Add new invoice item');
			$view->breadcrumbs = $breadcrumbs->html();
			$view->content = new View('form');
			$view->content->headline = __('Add new invoice item');
			$view->content->link_back = '';
			$view->content->form = $this->form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Edits invoice item
	 * 
	 * @author Michal Kliment
	 * @param integer $invoice_item_id	id of invoice item to edit
	 */
	public function edit($invoice_item_id = NULL)
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'invoices'))
			Controller::Error(1);

		$invoice_item = new Invoice_item_Model($invoice_item_id);

		if (!$invoice_item_id || !$invoice_item->id)
			url::redirect('invoices');

		$invoice_model = new Invoice_Model();
		$arr_invoices = $invoice_model->select_list('id', 'invoice_nr');

		// creates form

		$this->form = new Forge();

		$this->form->group('Basic information');
		
		$this->form->dropdown('invoice_id')
				->label(__('Invoice number') . ':')
				->rules('required')
				->options($arr_invoices)
				->selected($invoice_item->invoice_id);
		
		$this->form->input('name')
				->rules('required|length[3,40]')
				->value($invoice_item->name);
		
		$this->form->input('code')
				->rules('required|length[3,40]')
				->value($invoice_item->code);
		
		$this->form->input('quantity')
				->label(__('Quantity') . ':')
				->rules('required|length[1,40]|valid_numeric')
				->value($invoice_item->quantity);
		
		$this->form->input('author_fee')
				->rules('length[1,40]|valid_numeric')
				->value($invoice_item->author_fee);
		
		$this->form->input('contractual_increase')
				->rules('length[1,40]|valid_numeric')
				->value($invoice_item->contractual_increase);
		
		$this->form->radio('service')
				->options(arr::bool())
				->default($invoice_item->service);
		
		$this->form->input('price')
				->rules('required|length[1,40]|valid_numeric')
				->value($invoice_item->price);
		
		$this->form->input('vat')
				->label(__('Tax rate') . ':')
				->rules('length[1,5]|valid_range|valid_numeric')
				->value($invoice_item->vat * 100);

		$this->form->submit('Edit');

		//if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			// creates new Invoice
			$invoice_item = new Invoice_item_Model($invoice_item_id);

			$invoice_item->invoice_id = $form_data['invoice_id'];
			$invoice_item->name = $form_data['name'];
			$invoice_item->code = $form_data['code'];
			$invoice_item->quantity = (double) $form_data['quantity'];
			$invoice_item->author_fee = (double) $form_data['author_fee'];
			$invoice_item->contractual_increase = (double) $form_data['contractual_increase'];
			$invoice_item->service = $form_data['service'];
			$invoice_item->price = (double) $form_data['price'];
			$invoice_item->vat = (double) !empty($form_data['vat']) ? $form_data['vat'] / 100 : 0;

			// succes
			if ($invoice_item->save())
			{
				status::success('Invoice item has been successfully updated.');
			}
			
			url::redirect('invoices/show/' . $invoice_item->invoice_id);
		}

		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->link('invoices/show/' . $invoice_item->id,
						__('Invoice') . ' (' . $invoice_item->id . ')',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->link('invoice_items/show/' . $invoice_item_id,
						$invoice_item->name . ' (' . $invoice_item->id . ')',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text('Edit invoice item');

		$view = new View('main');
		$view->title = __('Edit invoice item');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = __('Edit invoice item');
		$view->content->form = $this->form->html();
		$view->render(TRUE);
	}

	/**
	 * Deletes invoice item
	 * 
	 * @author Michal Kliment
	 * @param integer $invoice_item_id	id off invoice item to delete
	 */
	public function delete($invoice_item_id = NULL)
	{
		// access rights
		if (!$this->acl_check_delete('Accounts_Controller', 'invoices'))
			Controller::Error(1);
		
		$invoice_item = new Invoice_item_Model($invoice_item_id);

		if (!$invoice_item_id || !$invoice_item->id)
			url::redirect('invoices');

		$invoice_id = $invoice_item->invoice_id;

		// success
		if ($invoice_item->delete())
		{
			status::success('Invoice item has been successfully deleted.');
		}
		url::redirect('invoices/show/' . $invoice_id);
	}

	/**
	 * Validator callback
	 *
	 * @param object $input 
	 */
	public function valid_name($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $input->method;
		$ignore_name = str_replace('name_', 'ignore_', $input->name);

		if (!$this->input->$method($ignore_name) && !$input->value)
		{
			$input->add_error('required', vsprintf(
					url_lang::lang('validation.required.'),
					utf8::strtolower(__('name'))
			));
		}
	}

	/**
	 * Validator callback
	 *
	 * @param object $input 
	 */
	public function valid_code($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $input->method;
		$ignore_name = str_replace('code_', 'ignore_', $input->name);

		if (!$this->input->$method($ignore_name) && !$input->value)
		{
			$input->add_error('required', vsprintf(
					url_lang::lang('validation.required.'),
					utf8::strtolower(__('code'))
			));
		}
	}

	/**
	 * Validator callback
	 *
	 * @param object $input 
	 */
	public function valid_quantity($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $input->method;
		$ignore_name = str_replace('quantity_', 'ignore_', $input->name);

		if (!$this->input->$method($ignore_name) && !$input->value)
		{
			$input->add_error('required', vsprintf(
					url_lang::lang('validation.required.'),
					utf8::strtolower(__('quantity'))
			));
		}
	}

}
