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

require_once APPPATH."libraries/dbase/Dbase_Table.php";
require_once APPPATH."libraries/dbase/Dbase_Column.php";

/**
 * Controller performs actions over invoices.
 *
 * @author Michal Kliment
 * @package Controller
 */
class Invoices_Controller extends Controller
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
		if (is_numeric($this->input->post('record_per_page')))
			$limit_results = (int) $this->input->post('record_per_page');

		// parameters control
		$allowed_order_type = array
		(
			'id', 'partner', 'invoice_nr', 'invoice_type', 'date_inv', 'date_due',
			'comments_count', 'price', 'price_vat'
		);
		
		if (!in_array(strtolower($order_by), $allowed_order_type))
			$order_by = 'id';
		
		if (strtolower($order_by_direction) != 'desc')
			$order_by_direction = 'asc';
	
		$filter_form = Invoices_Controller::create_filter_form();
		
		$invoice_model = new Invoice_Model();

		$total_invoices = $invoice_model->count_all_invoices($filter_form->as_sql());

		if (($sql_offset = ($page - 1) * $limit_results) > $total_invoices)
			$sql_offset = 0;

		$invoices = $invoice_model->get_all_invoices(
				$sql_offset, $limit_results, $order_by, 
				$order_by_direction, $filter_form->as_sql()
		);
		
		// path to form
		$path = Config::get('lang') . '/invoices/show_all/' . $limit_results . '/'
				. $order_by . '/' . $order_by_direction.'/'
				. $page_word. '/' . $page;
		
		// create grid
		$grid = new Grid('invoices', '', array
		(
				'use_paginator'				=> true,
				'use_selector'				=> true,
				'current'					=> $limit_results,
				'selector_increace'			=> 200,
				'selector_min'				=> 200,
				'selector_max_multiplier'	=> 10,
				'base_url'					=> $path,
				'uri_segment'				=> 'page',
				'total_items'				=> $total_invoices,
				'items_per_page'			=> $limit_results,
				'style'						=> 'classic',
				'order_by'					=> $order_by,
				'order_by_direction'		=> $order_by_direction,
				'limit_results'				=> $limit_results,
				'filter'					=> $filter_form
		));

		// access control
		if ($this->acl_check_new('Accounts_Controller', 'invoices'))
		{
			$grid->add_new_button('invoices/add', __('Add new invoice'));
			$grid->add_new_button('invoices/import', __('Import new invoice'));
			$grid->add_new_button('invoices/export_filter/' . server::query_string(), __('Export'));
		}
		
		$grid->order_field('id');
		
		$grid->order_callback_field('partner')
				->callback('callback::partner_field');
		
		$grid->order_field('invoice_nr')
				->label('Invoice number');
		
		$grid->order_callback_field('invoice_type')
				->label('Invoice type')
				->callback('callback::invoice_type_field');
		
		$grid->order_field('date_inv')
				->label('Date of issue');
		
		$grid->order_field('date_due')
				->label('Due date');
		
		$grid->order_callback_field('comments_count')
				->label('items count')
				->callback('callback::comments_field');
		
		$grid->order_callback_field('price')
				->callback('callback::money');
		
		$grid->order_callback_field('price_vat')
				->label('price vat')
				->callback('callback::money');
		
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

		$partner = NULL;
		if ($invoice->member_id)
			$partner = new Member_Model($invoice->member_id);

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
		
		$grid->order_callback_field('price')
				->callback('callback::round');
		
		$grid->order_callback_field('vat')
				->label(__('Tax rate'))
				->callback('callback::percent2');
		
		$grid->order_callback_field('price')
			->label('price vat')
			->callback('callback::price_vat_field');
		
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
		$view->content->partner = $partner;
		$view->content->grid = $grid;
		$view->render(TRUE);
	}

	/**
	 * Function to import invoice
	 * 
	 * @author Michal Kliment, Jan Dubina
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

			// file format is set up
			switch ($invoice_template->type) {
				case Invoice_template_Model::TYPE_EFORM:
					$type = 'htm,html,xml';
					break;
				case Invoice_template_Model::TYPE_XML:
				case Invoice_template_Model::TYPE_ED_INV:
					$type = 'xml';
					break;
				case Invoice_template_Model::TYPE_ISDOC:
					$type = 'isdoc';
					break;
				case Invoice_template_Model::TYPE_DBASE:
					$type = 'dbf';
					break;
				default:
					status::error('Error - bad template');
					url::redirect('invoices/import/' . $invoice_template->id);
					break;
			}

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
					
					//if file format is xml or html
					if ($invoice_template->type != Invoice_template_Model::TYPE_DBASE)
					{
						$doc = new DOMDocument;

						// xml and isdoc file will be load directly
						if ($invoice_template->type == Invoice_template_Model::TYPE_EFORM)
						{
							// open html file
							$file = @fopen($filename, 'r');

							if (!$file) 
							{
								status::error('File not specified');
								url::redirect('invoices/import/' . $invoice_template->id);
							}

							$text = fread($file, filesize($filename));

							// charset must be utf-8
							if ($invoice_template->charset != 'utf-8')
								$text = iconv($invoice_template->charset, 'UTF-8', $text);
						
							$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
							
							// parse xml from html
							if ($ext == 'html' || $ext = 'htm')
							{
								// get only part from begin tag to end tag
								if(preg_match(
										'/(' . $invoice_template->begin_tag . '.+' .
										$invoice_template->end_tag . ')/sim',
										$text, $match
								) === false)
								{
									throw new Exception('');
								}

								//get only part between root tags
								if(preg_match(
										'/(<eform.+<\/eform>)/sim',
										$text, $match
								) === false)
								{
									throw new Exception('');
								}

								// load xml string
								$text = $match[1];
							}
							
							$doc->loadXML($text);
							fclose($file);
						}
						else
							$doc->load($filename);

						$xpath = new DOMXPath($doc);
						
						//add namespaces
						$namespace = json_decode($invoice_template->namespace, true);

						if (count($namespace) != 0)
							foreach ($namespace as $key => $value)
								$xpath->registerNamespace($key, $value);

						//check if invoice belongs to association
						$association = new Member_Model(1);	
						$association_id = $association->organization_identifier;
						$org_id = @$xpath->evaluate($invoice_template->org_id);

						if (!empty($org_id))
							if ($org_id != $association_id)
							{
								unlink($filename);
								status::error('Error - invoice does not belong to this association');
								url::redirect('invoices/import/' . $invoice_template->id);
							}

						$member_model = new Member_Model();

						//loop through invoices
						foreach ($xpath->evaluate($invoice_template->invoices) as $xpath_invoice)
						{
							$invoice = new Invoice_Model();
							$invoice_type = @$xpath->evaluate($invoice_template->invoice_type, 
												$xpath_invoice);

							if ($invoice_type !== Invoice_Model::TYPE_ISSUED &&
									$invoice_type !== Invoice_Model::TYPE_RECEIVED &&
									$invoice_type !== false)
								if ($invoice_type == $invoice_template->invoice_type_issued)
									$invoice_type = Invoice_Model::TYPE_ISSUED;
								else
									$invoice_type = Invoice_Model::TYPE_RECEIVED;

							$supplier_id = null;
							$customer_id = null;
							$partner_id = null;

							// get partner by organization id
							$partner_org_id = @$xpath->evaluate($invoice_template->sup_organization_identifier, 
												$xpath_invoice);
							
							if (!empty($partner_org_id))
							{
								$partner = $member_model
										->where('organization_identifier', $partner_org_id)
										->find();
								$partner_id = $partner->id;
							}

							//get supplier and customer
							if ($invoice_type === false)
							{
								// get customer by organization id
								$customer_org_id = @$xpath->evaluate($invoice_template->cus_organization_identifier, 
													$xpath_invoice);
								if (!empty($customer_org_id))
								{
									$customer = $member_model
											->where('organization_identifier', $customer_org_id)
											->find();
									$customer_id = $customer->id;
								}

								$supplier_id = $partner_id;
								
								//check if invoice belongs to association
								if ($supplier_id != 1 && $customer_id != 1)
								{
									unlink($filename);
									status::error('Error - invoice does not belong to this association');
									url::redirect('invoices/import/' . $invoice_template->id);
								}

								$invoice_type = $supplier_id == 1 ? Invoice_Model::TYPE_ISSUED 
																	: Invoice_Model::TYPE_RECEIVED;
							}
							else 
							{
								if ($invoice_type == Invoice_Model::TYPE_ISSUED)
								{
									$supplier_id = 1;
									$customer_id = $partner_id;
								}
								else
								{
									$supplier_id = $partner_id;
									$customer_id = 1;
								}
							}

							$invoice->invoice_type = $invoice_type;
							$invoice->invoice_nr = @$xpath->evaluate($invoice_template->invoice_nr, $xpath_invoice);
							$invoice->var_sym = @$xpath->evaluate($invoice_template->var_sym, $xpath_invoice);
							$invoice->con_sym = @$xpath->evaluate($invoice_template->con_sym, $xpath_invoice);
							$invoice->date_inv = @$xpath->evaluate($invoice_template->date_inv, $xpath_invoice);
							$invoice->date_due = @$xpath->evaluate($invoice_template->date_due, $xpath_invoice);
							$invoice->date_vat = @$xpath->evaluate($invoice_template->date_vat, $xpath_invoice);
							$invoice->order_nr = @$xpath->evaluate($invoice_template->order_nr, $xpath_invoice);
							$invoice->note = @$xpath->evaluate($invoice_template->note, $xpath_invoice);
							$invoice->vat = @$xpath->evaluate($invoice_template->vat, $xpath_invoice);

							$account_nr = @$xpath->evaluate($invoice_template->account_nr, $xpath_invoice);
							$invoice->account_nr = valid::bank_account($account_nr) ? $account_nr : '';

							$currency = @$xpath->evaluate($invoice_template->currency, $xpath_invoice);
							if (!empty($currency))
								$invoice->currency = $currency;
							else
								$invoice->currency = Settings::get ('currency');

							if ($invoice_type == Invoice_Model::TYPE_ISSUED)
							{
								if (!empty($customer_id))
									$invoice->member_id = $customer_id;
								else
								{
									$invoice->partner_company = @$xpath->evaluate($invoice_template->cus_company, $xpath_invoice);
									$invoice->partner_name = @$xpath->evaluate($invoice_template->cus_name, $xpath_invoice);
									$invoice->partner_town = @$xpath->evaluate($invoice_template->cus_town, $xpath_invoice);
									$invoice->partner_zip_code = @$xpath->evaluate($invoice_template->cus_zip_code, $xpath_invoice);
									$invoice->organization_identifier = @$xpath->evaluate($invoice_template->cus_organization_identifier, $xpath_invoice);
									$invoice->vat_organization_identifier = @$xpath->evaluate($invoice_template->cus_vat_organization_identifier, $xpath_invoice);
									$invoice->phone_number = @$xpath->evaluate($invoice_template->cus_phone_number, $xpath_invoice);
									$invoice->email = @$xpath->evaluate($invoice_template->cus_email, $xpath_invoice);

									$street = @$xpath->evaluate($invoice_template->cus_street, $xpath_invoice);
									$street_number = @$xpath->evaluate($invoice_template->cus_street_number, $xpath_invoice);

									if (!empty($street) && empty($street_number))
									{
										$street_arr = address::street_split($street);
										$street = $street_arr['street'];
										$street_number = $street_arr['street_number'];
									}

									$invoice->partner_street = $street;
									$invoice->partner_street_number = $street_number;

									$country = @$xpath->evaluate($invoice_template->cus_country, $xpath_invoice);
									if (empty($country))
									{
										$def_country = new Country_Model(Settings::get('default_country'));
										$invoice->partner_country = $def_country->country_name;
									}
									else
										$invoice->partner_country = $country;
								}
							}
							else 
							{
								if (!empty($supplier_id))
									$invoice->member_id = $supplier_id;
								else
								{
									$invoice->partner_company = @$xpath->evaluate($invoice_template->sup_company, $xpath_invoice);
									$invoice->partner_name = @$xpath->evaluate($invoice_template->sup_name, $xpath_invoice);
									$invoice->partner_town = @$xpath->evaluate($invoice_template->sup_town, $xpath_invoice);
									$invoice->partner_zip_code = @$xpath->evaluate($invoice_template->sup_zip_code, $xpath_invoice);
									$invoice->organization_identifier = @$xpath->evaluate($invoice_template->sup_organization_identifier, $xpath_invoice);
									$invoice->vat_organization_identifier = @$xpath->evaluate($invoice_template->sup_vat_organization_identifier, $xpath_invoice);
									$invoice->phone_number = @$xpath->evaluate($invoice_template->sup_phone_number, $xpath_invoice);
									$invoice->email = @$xpath->evaluate($invoice_template->sup_email, $xpath_invoice);

									$street = @$xpath->evaluate($invoice_template->sup_street, $xpath_invoice);
									$street_number = @$xpath->evaluate($invoice_template->sup_street_number, $xpath_invoice);

									if (!empty($street) && empty($street_number))
									{
										$street_arr = address::street_split($street);
										$street = $street_arr['street'];
										$street_number = $street_arr['street_number'];
									}

									$invoice->partner_street = $street;
									$invoice->partner_street_number = $street_number;

									$country = @$xpath->evaluate($invoice_template->sup_country, $xpath_invoice);
									if (empty($country))
									{
										$def_country = new Country_Model(Settings::get('default_country'));
										$invoice->partner_country = $def_country->country_name;
									}
									else
										$invoice->partner_country = $country;
								}
							}

							$invoice->save();

							$items = $xpath->evaluate($invoice_template->items, $xpath_invoice);
							$vat_var = json_decode($invoice_template->vat_variables, true);

							//import invoice items
							if ($items->length != 0)
								foreach ($items as $item)
								{
									$invoice_item = new Invoice_item_Model();
									$invoice_item->invoice_id = $invoice->id;
									$invoice_item->name = @$xpath->evaluate($invoice_template->item_name, $item);
									$invoice_item->code = @$xpath->evaluate($invoice_template->item_code, $item);
									$invoice_item->quantity = @$xpath->evaluate($invoice_template->item_quantity, $item);
									$invoice_item->price = @$xpath->evaluate($invoice_template->item_price, $item);
									$vat = @$xpath->evaluate($invoice_template->item_vat, $item);

									if (is_numeric($vat))
										if (valid::range($vat))
											$invoice_item->vat = $vat / 100;
									else
									{
										$vat = strtolower($vat);
										$invoice_item->vat = array_key_exists($vat, $vat_var['import']) ? 
																			$vat_var[$vat] / 1000 : 0;
									}

									$invoice_item->save();
								}
							else
							{
								$price = @$xpath->evaluate($invoice_template->price, $xpath_invoice);
								$price_vat = @$xpath->evaluate($invoice_template->price_vat, $xpath_invoice);

								if ($price === false || $price_vat === false)
								{
									unlink($filename);
									status::error('Error - bad file');
									url::redirect('invoices/import/' . $invoice_template->id);
								}

								$invoice_item = new Invoice_item_Model();
								$invoice_item->invoice_id = $invoice->id;
								$invoice_item->name = '';
								$invoice_item->code = '';
								$invoice_item->quantity = 1;
								$invoice_item->price = $price;
								$invoice_item->vat = round($price_vat / $price - 1, 3);
								$invoice_item->save();
							}

						}
					}
					else
					{
						//import dbase file
						$table = new Dbase_Table();
						$records = $table->read_table($filename);

						//get an array of field names
						$fields = Invoice_template_Model::$fields;
						
						$member_model = new Member_Model();
						
						foreach ($records as $record) 
						{
							$invoice = new Invoice_Model();
							
							//link partner to existing member
							if (array_key_exists($fields['organization_identifier'], $record))
								if ($record[$fields['organization_identifier']])
								{
									$partner = $member_model
											->where('organization_identifier', 
													$record[$fields['organization_identifier']])
											->find();
									$partner_id = $partner->id;
								}
							
							if (!empty($partner_id))
								$invoice->member_id = $partner_id;
							else
							{
								$invoice->partner_company = array_key_exists($fields['company'], $record) ?
																		$record[$fields['company']] : '';
								$invoice->partner_name = array_key_exists($fields['name'], $record) ? 
																		$record[$fields['name']] : '';
								
								if (array_key_exists($fields['street'], $record))
								{
									$street = address::street_split($record[$fields['street']]);
									$invoice->partner_street = $street['street'];
									$invoice->partner_street_number = $street['street_number'];
								}
								
								$invoice->partner_town = array_key_exists($fields['town'], $record) ?
																			$record[$fields['town']] : '';
								$invoice->partner_zip_code = array_key_exists($fields['zip_code'], $record) ?
																				$record[$fields['zip_code']] : '';
								$country = new Country_Model(Settings::get('default_country'));
								$invoice->partner_country = $country->country_name;
								$invoice->organization_identifier = array_key_exists($fields['organization_identifier'], $record) ?
																						$record[$fields['organization_identifier']] : '';
								$invoice->vat_organization_identifier = array_key_exists($fields['vat_organization_identifier'], $record) ?
																						$record[$fields['vat_organization_identifier']] : '';
								$invoice->phone_number = array_key_exists($fields['phone'], $record) ?
																			$record[$fields['phone']] : '';
								$invoice->email = array_key_exists($fields['email'], $record) ?
																	$record[$fields['email']] : '';
								
								if (array_key_exists($fields['account_nr'], $record) &&
										array_key_exists($fields['bank_code'], $record))
								{
									$account_nr = $record[$fields['account_nr']] .
													'/' . $record[$fields['bank_code']];
									$invoice->account_nr = valid::bank_account($account_nr) ?
																				$account_nr : '';
								}
							}
							
							$invoice->invoice_nr = array_key_exists($fields['invoice_nr'], $record) ?
																	$record[$fields['invoice_nr']] : 0;
							$invoice->invoice_type = $invoice_template->invoice_type;
							$invoice->order_nr = array_key_exists($fields['order_nr'], $record) ?
																	$record[$fields['order_nr']] : 0;
							$invoice->var_sym = array_key_exists($fields['var_sym'], $record) ?
																	$record[$fields['var_sym']] : 0;
							$invoice->con_sym = array_key_exists($fields['con_sym'], $record) ?
																	$record[$fields['con_sym']] : 0;
							$invoice->date_inv = array_key_exists($fields['date_inv'], $record) ?
																	$record[$fields['date_inv']] : '';
							$invoice->date_due = array_key_exists($fields['date_due'], $record) ?
																	$record[$fields['date_due']] : '';
							$invoice->date_vat = array_key_exists($fields['date_vat'], $record) ?
																	$record[$fields['date_vat']] : '';
							$invoice->vat = 1;
							$invoice->note = array_key_exists($fields['note'], $record) ?
																$record[$fields['note']] : '';

							if (array_key_exists($fields['currency'], $record) && 
									$record[$fields['currency']] != '')
								$invoice->currency = $record[$fields['currency']];
							else
								$invoice->currency = Settings::get('currency');
						
							$invoice->save();
							
							$vat_var = json_decode($invoice_template->vat_variables, true);
							
							$price_none = array_key_exists($fields['price_none'], $record) ?
																$record[$fields['price_none']] : 0;
							$price_low = array_key_exists($fields['price_low'], $record) ?
																$record[$fields['price_low']] : 0;
							$price_high = array_key_exists($fields['price_high'], $record) ?
																$record[$fields['price_high']] : 0;
							$price_sum = array_key_exists($fields['price_sum'], $record) ?
																$record[$fields['price_sum']] : 0;
							
							if (!empty($price_none)) {
								$invoice_item = new Invoice_item_Model();
								$invoice_item->invoice_id = $invoice->id;
								$invoice_item->quantity = 1;
								$invoice_item->price = $price_none;
								$invoice_item->vat = $vat_var['import']['none'] / 1000;
								$invoice_item->save();
							}
							
							if (!empty($price_low)) {
								$invoice_item = new Invoice_item_Model();
								$invoice_item->invoice_id = $invoice->id;
								$invoice_item->quantity = 1;
								$invoice_item->price = $price_low;
								$invoice_item->vat = $vat_var['import']['low'] / 1000;
								$invoice_item->save();
							}
							
							if (!empty($price_high)) {
								$invoice_item = new Invoice_item_Model();
								$invoice_item->invoice_id = $invoice->id;
								$invoice_item->quantity = 1;
								$invoice_item->price = $price_high;
								$invoice_item->vat = $vat_var['import']['high'] / 1000;
								$invoice_item->save();
							}
							
							if (empty($price_none) && empty($price_low) && empty($price_high))
							{
								$invoice_item = new Invoice_item_Model();
								$invoice_item->invoice_id = $invoice->id;
								$invoice_item->quantity = 1;
								$invoice_item->price = $price_sum;
								$invoice_item->vat = 0;
								$invoice_item->save();
							}
						}
					}

					unlink($filename);
					url::redirect('invoices/show/' . $invoice->id);

				}
				else
				{
					//validation error
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
	 * Function to export invoice
	 * 
	 * @author Jan Dubina
	 * @param integer $invoice_id  id of invoice
	 * @param integer $invoice_template_id	id of type of invoice template (type)
	 */
	public function export_single($invoice_id = NULL, $invoice_template_id = NULL)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text('Export invoice');
		
		if (!$invoice_id)
			url::redirect('invoices');
		
		$invoice_template_model = new Invoice_template_Model();
		$invoice_templates = $invoice_template_model->get_all_invoice_templates();

		$types = array();

		foreach ($invoice_templates as $invoice_template)
			if ($invoice_template->type != Invoice_template_Model::TYPE_ED_INV)
				$types[$invoice_template->id] = $invoice_template->name;

		$form = new Forge();

		$form->group('Select type');

		$form->dropdown('type')
				->options($types)->rules('required');

		$form->submit('Do export');

		if ($form->validate())
		{
			$form_data = $form->as_array();

			$invoice_template = new Invoice_template_Model($form_data['type']);

			if (!$invoice_template)
			{
				status::error('Error - bad template');
				url::redirect('invoices/show_all/');
			}

			switch ($invoice_template->type) {
				case Invoice_template_Model::TYPE_EFORM:
					url::redirect('export/eform_invoices/' . $invoice_id . '/'. $form_data['type']);
					break;
				case Invoice_template_Model::TYPE_ISDOC:
					url::redirect('export/isdoc_invoices/' . $invoice_id . '/'. $form_data['type']);
					break;
				case Invoice_template_Model::TYPE_XML:
					url::redirect('export/xml_invoices/' . $form_data['type'] . '/'. $invoice_id);
					break;
				case Invoice_template_Model::TYPE_DBASE:
					url::redirect('export/dbf_invoices/' . $form_data['type'] . '/'. $invoice_id);
					break;
				default:
					status::error('Error - bad template');
					url::redirect('invoices/show_all/');
					break;
			}
		}

		$view = new View('main');
		$view->title = __('Export invoice');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = __('Export invoice');
		$view->content->link_back = '';
		$view->content->form = $form->html();
		$view->render(TRUE);
	}
	
	/**
	 * Function to export invoices
	 * 
	 * @author Jan Dubina
	 * @param integer $invoice_template_id	id of type of invoice template (type)
	 */
	public function export_filter($invoice_template_id = NULL)
	{
	// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);
		
		// breadcrumbs
		$breadcrumbs = breadcrumbs::add()
				->link('invoices/show_all', 'Invoices',
						$this->acl_check_view('Accounts_Controller', 'invoices'))
				->text('Export invoice');

		$invoice_template_model = new Invoice_template_Model();
		$invoice_templates = $invoice_template_model->get_all_invoice_templates();

		$types = array();

		foreach ($invoice_templates as $invoice_template)
			if ($invoice_template->type != Invoice_template_Model::TYPE_ED_INV &&
					$invoice_template->type != Invoice_template_Model::TYPE_ISDOC &&
					$invoice_template->type != Invoice_template_Model::TYPE_EFORM)
				$types[$invoice_template->id] = $invoice_template->name;

		$form = new Forge(url::base().url::current(TRUE));

		$form->group('Select type');

		$form->dropdown('type')
				->options($types)->rules('required');

		$form->submit('Do export');

		if ($form->validate())
		{
			$form_data = $form->as_array();

			$invoice_template = new Invoice_template_Model($form_data['type']);

			if (!$invoice_template)
			{
				status::error('Error - bad template');
				url::redirect('invoices/show_all/');
			}
			
			switch ($invoice_template->type) {
				case Invoice_template_Model::TYPE_XML:
					url::redirect(url_lang::base().'export/xml_invoices/'.$form_data['type'].'//'.server::query_string());
					break;
				case Invoice_template_Model::TYPE_DBASE:
					url::redirect(url_lang::base().'export/dbf_invoices/'.$form_data['type'].'//'.server::query_string());
					break;
				default:
					status::error('Error - bad template');
					url::redirect('invoices/show_all/');
					break;
			}
		}

		$view = new View('main');
		$view->title = __('Export invoices');
		$view->breadcrumbs = $breadcrumbs->html();
		$view->content = new View('form');
		$view->content->headline = __('Export invoices');
		$view->content->link_back = '';
		$view->content->form = $form->html();
		$view->render(TRUE);	
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

		$arr_members = array
		(
			0 => '--- ' . __('Non-member') . ' ---'
		) + ORM::factory('member')->select_list('id', 'name');

		// creates form
		
		$this->form = new Forge('invoices/add');

		$this->form->group('')
				->label(__('Basic information'));
		
		$this->form->dropdown('invoice_type')
				->label('Invoice type')
				->options(Invoice_Model::types());
		
		$this->form->input('invoice_nr')
				->label('Invoice number')
				->rules('required|length[3,40]|valid_numeric');
		
		$this->form->input('account_nr')
				->label('Account number')
				->rules('valid_bank_account');
		
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
				->years(date('Y') - 100)
				->rules('required');
		
		$this->form->date('date_vat')
				->label('Date vat')
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
		
		$this->form->input('note')
				->rules('length[3,240]');
		
		$this->form->group('')
				->label(__('Contact information'));
		
		$this->form->dropdown('member_id')
				->label('Member')
				->options($arr_members)
				->style('width:200px');
		
		$this->form->input('partner_company')
				->label('Company')
				->rules('length[3,100]');
		
		$this->form->input('partner_name')
				->label('Name')
				->rules('length[3,100]')
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_street')
				->label('Street')
				->rules('length[3,30]');
		
		$this->form->input('partner_street_number')
				->label('Street number')
				->rules('length[1,50]')
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_town')
				->label('Town')
				->rules('length[3,50]')
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_zip_code')
				->label('Zip code')
				->rules('length[3,10]')
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_country')
				->label('Country')
				->rules('length[3,100]')
				->callback(array($this, 'partner_field'));
		
		$this->form->input('organization_identifier')
				->rules('length[3,20]');
		
		$this->form->input('vat_organization_identifier')
				->rules('length[3,30]');
		
		$this->form->input('phone_number')
				->rules('valid_phone');
		
		$this->form->input('email')
				->rules('valid_email');
		
		$this->form->submit('Add');

		//if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			// creates new Invoice
			$invoice = new Invoice_Model();
			
			$invoice->invoice_type = $form_data['invoice_type'];
			$invoice->invoice_nr = $form_data['invoice_nr'];
			$invoice->account_nr = $form_data['account_nr'];
			$invoice->var_sym = $form_data['var_sym'];
			$invoice->con_sym = $form_data['con_sym'];
			$invoice->date_inv = date("Y-m-d", $form_data['date_inv']);
			$invoice->date_due = date("Y-m-d", $form_data['date_due']);
			$invoice->date_vat = date("Y-m-d", $form_data['date_vat']);
			$invoice->vat = $form_data['vat'];
			$invoice->order_nr = $form_data['order_nr'];
			$invoice->currency = $form_data['currency'];
			$invoice->note = $form_data['note'];
			
			if (!empty($form_data['member_id']))
				$invoice->member_id = $form_data['member_id'];
			else	
			{
				$invoice->partner_company = $form_data['partner_company'];
				$invoice->partner_name = $form_data['partner_name'];
				$invoice->partner_street = $form_data['partner_street'];
				$invoice->partner_street_number = $form_data['partner_street_number'];
				$invoice->partner_town = $form_data['partner_town'];
				$invoice->partner_zip_code = $form_data['partner_zip_code'];
				$invoice->partner_country = $form_data['partner_country'];
				$invoice->organization_identifier = $form_data['organization_identifier'];
				$invoice->vat_organization_identifier = $form_data['vat_organization_identifier'];
				$invoice->phone_number = $form_data['phone_number'];
				$invoice->email = $form_data['email'];
			}

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
		
		$arr_members = array
		(
			0 => '--- ' . __('Non-member') . ' ---'
		) + ORM::factory('member')->select_list('id', 'name');

		// creates form
		$this->form = new Forge('invoices/edit/' . $invoice->id);

		$this->form->group('Basic information');
		
		$this->form->dropdown('invoice_type')
				->label('Invoice type')
				->options(Invoice_Model::types())
				->selected($invoice->invoice_type);

		$this->form->input('invoice_nr')
				->label(__('Invoice number') . ':')
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice->invoice_nr);
		
		$this->form->input('account_nr')
				->label(__('Account number') . ':')
				->rules('valid_bank_account')
				->value($invoice->account_nr);
		
		$this->form->input('var_sym')
				->label(__('Variable symbol') . ':')
				->rules('required|length[3,40]|valid_numeric')
				->value($invoice->var_sym);
		
		$this->form->input('con_sym')
				->label(__('Constant symbol') . ':')
				->rules('length[1,40]|valid_numeric')
				->value($invoice->con_sym);
		
		$this->form->date('date_inv')
				->label(__('Date of issue') . ':')
				->years(date('Y') - 100, date('Y'))
				->rules('required')
				->value(strtotime($invoice->date_inv));
		
		$this->form->date('date_due')
				->label(__('Due date') . ':')
				->years(date('Y') - 100)
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
		
		$this->form->input('note')
				->rules('length[3,240]')
				->value($invoice->note);

		$this->form->group('Contact information');
		
		$this->form->dropdown('member_id')
				->label(__('Member') . ':')
				->options($arr_members)
				->selected($invoice->member_id);
		
		$this->form->input('partner_company')
				->label('Company')
				->rules('length[3,100]')
				->value($invoice->partner_company);
		
		$this->form->input('partner_name')
				->label('Name')
				->rules('length[3,100]')
				->value($invoice->partner_name)
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_street')
				->label('Street')
				->rules('length[3,30]')
				->value($invoice->partner_street);
		
		$this->form->input('partner_street_number')
				->label('Street number')
				->rules('length[1,50]')
				->value($invoice->partner_street_number)
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_town')
				->label('Town')
				->rules('length[3,50]')
				->value($invoice->partner_town)
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_zip_code')
				->label('Zip code')
				->rules('length[3,10]')
				->value($invoice->partner_zip_code)
				->callback(array($this, 'partner_field'));
		
		$this->form->input('partner_country')
				->label('Country')
				->rules('length[3,100]')
				->value($invoice->partner_country)
				->callback(array($this, 'partner_field'));
		
		$this->form->input('organization_identifier')
				->rules('length[3,20]')
				->value($invoice->organization_identifier);
		
		$this->form->input('vat_organization_identifier')
				->rules('length[3,30]')
				->value($invoice->vat_organization_identifier);
		
		$this->form->input('phone_number')
				->rules('valid_phone')
				->value($invoice->phone_number);
		
		$this->form->input('email')
				->rules('valid_email')
				->value($invoice->email);
		
		$this->form->submit('Edit');

		// if form is validated
		if ($this->form->validate())
		{
			$form_data = $this->form->as_array();

			$invoice = new Invoice_Model($invoice_id);
			
			if (!empty($form_data['member_id']))
				$invoice->member_id = $form_data['member_id'];
			else
				$invoice->member_id = null;
			
			$invoice->invoice_type = $form_data['invoice_type'];
			$invoice->invoice_nr = $form_data['invoice_nr'];
			$invoice->account_nr = $form_data['account_nr'];
			$invoice->var_sym = $form_data['var_sym'];
			$invoice->con_sym = $form_data['con_sym'];
			$invoice->date_inv = date("Y-m-d", $form_data['date_inv']);
			$invoice->date_due = date("Y-m-d", $form_data['date_due']);
			$invoice->date_vat = date("Y-m-d", $form_data['date_vat']);
			$invoice->vat = $form_data['vat'];
			$invoice->order_nr = $form_data['order_nr'];
			$invoice->currency = $form_data['currency'];
			$invoice->note = $form_data['note'];
			
			if (!empty($form_data['member_id']))
				$invoice->member_id = $form_data['member_id'];
			else	
			{
				$invoice->partner_company = $form_data['partner_company'];
				$invoice->partner_name = $form_data['partner_name'];
				$invoice->partner_street = $form_data['partner_street'];
				$invoice->partner_street_number = $form_data['partner_street_number'];
				$invoice->partner_town = $form_data['partner_town'];
				$invoice->partner_zip_code = $form_data['partner_zip_code'];
				$invoice->partner_country = $form_data['partner_country'];
				$invoice->organization_identifier = $form_data['organization_identifier'];
				$invoice->vat_organization_identifier = $form_data['vat_organization_identifier'];
				$invoice->phone_number = $form_data['phone_number'];
				$invoice->email = $form_data['email'];
			}

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

	/**
	 * Static function for creating filter form
	 * due to this filter is used in multiple controllers
	 * 
	 * @return \Filter_form
	 */
	public static function create_filter_form()
	{	
		// filter form
		$filter_form = new Filter_form('iv');
		
		$filter_form->add('invoice_nr')
				->label('Invoice number')
				->type('number');

		$filter_form->add('id')
				->type('number');

		$filter_form->add('invoice_type')
				->type('select')
				->values(Invoice_Model::types());
		
		$filter_form->add('account_nr')
				->label('Account number')
				->callback('json/invoice_account_nr');

		$filter_form->add('var_sym')
				->label('Variable symbol')
				->type('number');
		
		$filter_form->add('con_sym')
				->label('Constant symbol')
				->type('number');
		
		$filter_form->add('date_inv')
				->label('Date of issue')
				->type('date');
		
		$filter_form->add('date_due')
				->label('Due date')
				->type('date');
		
		$filter_form->add('date_vat')
				->label('Date vat')
				->type('date');
		
		$filter_form->add('vat')
				->type('select')
				->values(arr::bool());
		
		$filter_form->add('order_nr')
				->label('Order number')
				->type('number');
		
		$filter_form->add('currency');
		
		$filter_form->add('price')
				->type('number');
		
		$filter_form->add('price_vat')
				->type('number');
		
		$filter_form->add('company')
				->callback('json/invoice_company');
		
		$filter_form->add('partner')
				->callback('json/invoice_name');
		
		$filter_form->add('street')
				->callback('json/invoice_street');
		
		$filter_form->add('street_number')
				->callback('json/invoice_street_number');
		
		$filter_form->add('town')
				->callback('json/invoice_town');
		
		$filter_form->add('zip_code')
				->callback('json/invoice_zip_code');
		
		$filter_form->add('country')
				->callback('json/invoice_country');
		
		$filter_form->add('organization_identifier')
				->callback('json/invoice_organization_id');
		
		$filter_form->add('vat_organization_identifier')
				->callback('json/invoice_vat_organization_id');
		
		$filter_form->add('email')
				->callback('json/invoice_email');
		
		$filter_form->add('phone')
				->callback('json/invoice_phone_nr');
		
		$filter_form->add('note');
		
		return $filter_form;
	}
	
	public function partner_field ($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

		if ($this->input->post('member_id') == 0 && $input->value == '')
		{
			$input->add_error('required', __('This information is required.'));
		}
	}
}
