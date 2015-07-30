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
	 * @param integer $item_count count of items to addd
	 */
	public function add($invoice_id = NULL, $item_count = 1)
	{
		// access rights
		if (!$this->acl_check_new('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		$invoice_model = new Invoice_Model();
		$invoices = $invoice_model->where('id', $invoice_id)->find_all();

		// if invoice doesn't exist redirects to list of all invoices
		if (!count($invoices))
			url::redirect('invoices/show_all');

		// transforms array of objects to classic array
		$arr_invoices = arr::from_objects($invoices, 'invoice_nr');

		$invoice_items = $this->session->get('ssInvoice_items');

		// creates form

		$this->form = new Forge(
				'invoice_items/add/' . $invoice_id . '/' . $item_count
		);

		$this->form->group('Basic information');
		
		$this->form->dropdown('invoice_id')
				->label(__('Invoice number') . ':')
				->options($arr_invoices)
				->rules('required')
				->selected($invoice_id);

		// creates first, only one required, 
		$this->form->group('')
				->label(__('Item number') . ' 1');
		
		$this->form->input('name_0')
				->label(__('Name') . ':')
				->rules('required|length[3,40]');
		
		$this->form->input('code_0')
				->label(__('Code') . ':')
				->rules('required|length[3,40]');
		
		$this->form->input('quantity_0')
				->label(__('Quantity') . ':')
				->rules('required|length[1,40]|valid_numeric');
		
		$this->form->input('author_fee_0')
				->label(__('Author fee') . ':')
				->rules('length[1,40]|valid_numeric');
		
		$this->form->input('contractual_increase_0')
				->label(__('Contractual increase') . ':')
				->rules('length[1,40]|valid_numeric');
		
		$this->form->radio('service_0')
				->label(__('Service') . ':')
				->options(arr::bool())
				->default(0);
		
		$this->form->input('price_0')
				->label(__('Price') . ':')
				->rules('length[1,40]|valid_numeric');
		
		$this->form->input('price_vat_0')
				->label(__('Price vat') . ':')
				->rules('length[1,40]|valid_numeric')
				->callback(array($this, 'valid_prices'));

		// next, only optional, with javascript func
		for ($i = 1; $i < $item_count; $i++)
		{
			$this->form->group('')
					->label(__('Item number') . ' ' . 
							($i + 1) . ' - ' . __('optional'));
			
			$this->form->checkbox('ignore_' . $i . '')
					->label(__('Ignore'))
					->value('1')
					->checked(FALSE)
					->onclick('ignore = document.getElementById(\'ignore_' . $i . 
							'\').checked; document.getElementById(\'name_' . $i . 
							'\').disabled = ignore; document.getElementById(\'code_' . $i . 
							'\').disabled = ignore; document.getElementById(\'quantity_' . $i . 
							'\').disabled = ignore; document.getElementById(\'author_fee_' . $i . 
							'\').disabled = ignore; document.getElementById(\'contractual_increase_' . $i . 
							'\').disabled = ignore; document.getElementById(\'price_' . $i . 
							'\').disabled = ignore; document.getElementById(\'price_vat_' . $i . 
							'\').disabled = ignore;'
					);
			
			$this->form->input('name_' . $i . '')
					->label(__('Name') . ':')
					->rules('length[3,40]')
					->callback(array($this, 'valid_name'))
					->onKeyUp('document.getElementById(\'ignore_' . $i .
							'\').checked = false;'
					);
			
			$this->form->input('code_' . $i . '')
					->label(__('Code') . ':')
					->rules('length[3,40]')
					->callback(array($this, 'valid_code'))
					->onKeyUp('document.getElementById(\'ignore_' . $i . 
							'\').checked = false;'
					);
			
			$this->form->input('quantity_' . $i . '')
					->label(__('Quantity') . ':')
					->rules('length[1,40]|valid_numeric')
					->callback(array($this, 'valid_quantity'))
					->onKeyUp('document.getElementById(\'ignore_' . $i . 
							'\').checked = false;'
					);
			
			$this->form->input('author_fee_' . $i . '')
					->label(__('Author fee') . ':')
					->rules('length[1,40]|valid_numeric')
					->onKeyUp('document.getElementById(\'ignore_' . $i . 
							'\').checked = false;'
					);
			
			$this->form->input('contractual_increase_' . $i . '')
					->label(__('Contractual increase') . ':')
					->rules('length[1,40]|valid_numeric')
					->onKeyUp('document.getElementById(\'ignore_' . $i . 
							'\').checked = false;'
					);
			
			$this->form->radio('service_' . $i . '')
					->label(__('Service') . ':')
					->options(arr::bool())
					->default(0)
					->onKeyUp('document.getElementById(\'ignore_' . $i .
							'\').checked = false;'
					);
			
			$this->form->input('price_' . $i . '')
					->label(__('Price') . ':')
					->rules('length[1,40]|valid_numeric')
					->onKeyUp('document.getElementById(\'ignore_' . $i . 
							'\').checked = false;'
					);
			
			$this->form->input('price_vat_' . $i . '')
					->label(__('Price vat') . ':')
					->rules('length[1,40]|valid_numeric')
					->callback(array($this, 'valid_prices'))
					->onKeyUp('document.getElementById(\'ignore_' . $i . 
							'\').checked = false;'
					);
		}

		$this->form->submit('Add');
		

		//if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();
			// counter of successfully saved items
			$count_saved = 0;

			for ($i = 0; $i < $item_count; $i++)
			{
				// if (ignore checkbox has been posted AND 
				// it has not been checked (optional items))
				// OR inore checkbox has not been posted (first, required item)
				if ((isset($form_data['ignore_' . $i]) &&
					!$form_data['ignore_' . $i]) ||
					!isset($form_data['ignore_' . $i]))
				{
					// creates new invoice item
					$ii = new Invoice_item_Model();
					$ii->invoice_id = $form_data['invoice_id'];
					$ii->name = $form_data['name_' . $i];
					$ii->code = $form_data['code_' . $i];
					$ii->quantity = (double) $form_data['quantity_' . $i];
					$ii->author_fee = (double) $form_data['author_fee_' . $i];
					$ii->contractual_increase = (double) $form_data['contractual_increase_' . $i];
					$ii->service = $form_data['service_' . $i];
					$ii->price = (double) $form_data['price_' . $i];
					$ii->price_vat = (double) $form_data['price_vat_' . $i];

					// if price/price_vat is null, counts it from price_vat/price
					if (!$ii->price || !$ii->price_vat)
					{
						$invoice = new Invoice_Model($invoice_id);
						$vat = ($invoice->vat / 100) + 1;
						if (!$ii->price_vat)
						{
							$ii->price_vat = round(
									$ii->price * $vat, 2
							);
						}
						else
						{
							$ii->price = round(
									$ii->price_vat / $vat, 2
							);
						}
					}
					// success
					if ($ii->save())
						$count_saved++;
				}
			}

			// succes
			if ($count_saved)
			{
				status::success('Item(s) have been successfully added.');
			}
			
			url::redirect('invoices/show/' . $invoice_id);
		}

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

		$invoice_model = new Invoice_Model($invoice_item->invoice_id);

		$arr_invoices[] = $invoice_model->invoice_nr;

		if ($this->session->get('ssInvoice_item_id'))
		{
			$link_back = html::anchor(
					'invoice_items/show/' . $this->session->get('ssInvoice_item_id'),
					__('Back to the invoice item')
			);
		}
		else
		{
			$link_back = html::anchor(
					'invoices/show/' . $invoice_item->invoice_id,
					__('Back to the invoice')
			);
		}

		// creates form

		$this->form = new Forge('invoice_items/edit/' . $invoice_item_id);

		$this->form->group('Basic informations');
		
		$this->form->dropdown('invoice_id')
				->label(__('Invoice number') . ':')
				->options($arr_invoices)
				->rules('required')
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
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice_item->price);
		
		$this->form->input('price_vat')
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice_item->price_vat);

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
			$invoice_item->price_vat = (double) $form_data['price_vat'];

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

	/**
	 * Validator callback
	 *
	 * @param object $input 
	 */
	public function valid_prices($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$method = $input->method;
		$ignore_name = str_replace('price_vat_', 'ignore_', $input->name);
		$price_name = str_replace('price_vat_', 'price_', $input->name);

		if (!$this->input->$method($ignore_name) &&
			!$this->input->$method($price_name) &&
			!$input->value)
		{
			$input->add_error('required', __('Fill in at least one from prices.'));
		}
	}

}
