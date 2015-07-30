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

require_once APPPATH."libraries/dbase/Dbase_Table.php";
require_once APPPATH."libraries/dbase/Dbase_Column.php";

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

				// export emails
				case 'email_queue_sent':
					
					if (!$this->acl_check_view('Email_queues_Controller', 'email_queue'))
					{
						Controller::error(ACCESS);
					}

					$filter_form = new Filter_form();
					$filter_form->autoload();
					
					$email_queue = new Email_queue_Model();

					try
					{
						$items = $email_queue->get_all_sent_emails_for_export($filter_form->as_sql());
					}
					catch (Exception $e)
					{
						$items = array();
					}

					$filename = __('E-mails') . '.csv';

					break;

				// export for items of subnet
				case 'subnets':

					$subnet_model = new Subnet_Model($id);

					if ($subnet_model->id == 0)
					{
						Controller::error(RECORD);
					}

					if (!$this->acl_check_view('Subnets_Controller', 'subnet'))
					{
						Controller::error(ACCESS);
					}

					$items = $subnet_model->get_items_of_subnet($id);
					$filename = $subnet_model->name . '.csv';

					break;

				// auto export for all tables
				default:

					if (!$this->acl_check_view('Export_Controller', 'all_tables'))
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
						$model = ORM::factory($content);
						$all = $model->find_all();
						$items = array();
						
						// header
						$items[0] = array_keys($model->list_fields());
						
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
				
				$is_utf = $encoding == 'utf-8';

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
					if (!$is_utf)
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
						if (!$is_utf)
						{
							$value = iconv('utf-8', $encoding, $value);
						}
						// emails body
						if ($content == 'email_queue_sent' && $key == 'message')
						{
							$value = str_replace('"', '\'', strip_tags($value));
						}
						// output
						echo '"';
						echo $value;
						echo '";';
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
    
    /**
	 * Function exports contacts to vCard file
	 * 
	 * @author David Raška
	 */
	public function vcard($export = NULL)
	{
		if (!$this->acl_check_view('Members_Controller', 'members'))
		{
			Controller::error(ACCESS);
		}

		$form = new Forge();

		$form->set_attr('class', 'form nopopup');
		
		$form->dropdown('format')
				->options( array
				(
					'vcard21' => 'vCard 2.1',
					'vcard40' => 'vCard 4.0',
				))
				->selected('vcard40');

		if ($export != 'users')
		{
			$form->checkbox('main_only')
				->label('Export only main users')
				->checked(TRUE);
		}
		
		$form->hidden('export')
				->value($export);
		
		$form->submit('Submit');
		
		if ($form->validate())
		{
			$form_data = $form->as_array();
			
			$to_export = $form_data['export'];
			
			$main_only = isset($form_data['main_only']) && $form_data['main_only'] == '1';
					
			$filter_form = new Filter_form();
			$filter_form->autoload();

			$user_model = new User_Model();
			
			$items = array();
			
			try
			{
				if ($to_export === 'users')
				{
					// export all users
					$count = $user_model->count_all_users($filter_form->as_sql());
					
					$items = $user_model->get_all_users(
						0, $count, 'id', 'ASC', $filter_form->as_sql()
					);
				}
				else if ($to_export === 'members')
				{
					// export main users
					$member_model = new Member_Model();
					
					$count = $member_model->count_all_members($filter_form->as_sql());
					
					$members = $member_model->get_all_members(
						0, $count, 'id', 'ASC', $filter_form->as_sql()
					);
					
					$member_ids = array();
					foreach ($members as $m)
					{
						$member_ids[] = $m->id;
					}
					
					if (!empty($member_ids))
					{
						$count = $user_model->count_all_users_of_members($member_ids);
						
						$items = $user_model->get_all_users_of_members($member_ids, 0, $count);
					}
				}
				else if (is_numeric($export))
				{
					// export members users
					$count = $user_model->count_all_users_by_member($export);
					
					$items = $user_model->get_all_users_of_member($export, 0, $count);
				}
				
			}
			catch (Exception $e)
			{
				$items = array();
				die;
				
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
				header('Content-type: text/vcard; charset=utf-8');
				header('Content-Disposition: attachment; filename="'.__('Contacts').'.vcf"');

				switch ($form_data['format'])
				{
					case 'vcard21':
						echo self::vcard21($items, $main_only);
						break;
					case 'vcard40':
					default:
						echo self::vcard40($items, $main_only);
				}

				// do not display view
				die();
			}
		}
		
		$title = __('Export contacts');

		$view = new View('main');
		$view->title = $title;
		$view->content = new View('form');
		$view->content->headline = $title;
		$view->content->form = $form;
		$view->render(TRUE);
	}
	
	/**
	 * Function Generates vCard in 4.0 format
	 * 
	 * @param array $users		Array of all users
	 * @param bool $main_only	Export only main users	
	 */
	private function vcard40($users = NULL, $main_only = false)
	{
		$vCard = '';

		/* Generate vCard for each user */
		foreach ($users AS $user)
		{
			/* Skip all users except main users */
			if ($main_only && $user->type != User_Model::MAIN_USER)
			{
				continue;
			}

			$pre_title = empty($user->pre_title) ? '' : "$user->pre_title "; 
			$name = empty($user->name) ? '' : "$user->name ";
			$middle_name = empty($user->middle_name) ? '' : "$user->middle_name ";
			$post_title = empty($user->post_title) ? '' : " $user->post_title"; 

			/* Begining of vCard */
			$vCard .=
				"BEGIN:VCARD\n".
				"VERSION:4.0\n".
				"N:$user->surname;$user->name;$user->middle_name;$user->pre_title;$user->post_title\n".
				"FN:$pre_title$name$middle_name$user->surname$post_title\n";

			$cm = new Contact_Model();
			$contacts = $cm->find_all_users_contacts($user->id);
			$mm = new Member_Model($user->member_id);

			/* Add all contacts of user */
			foreach ($contacts AS $c)
			{
				$unknown = false;

				switch ($c->type)
				{
					case Contact_Model::TYPE_ICQ:
						$vCard .= "X-ICQ:";
						break;
					case Contact_Model::TYPE_JABBER:
						$vCard .= "X-JABBER:";
						break;
					case Contact_Model::TYPE_EMAIL:
						$vCard .= "EMAIL;TYPE=PREF,INTERNET:";
						break;
					case Contact_Model::TYPE_PHONE:
						$vCard .= "TEL;TYPE=HOME,VOICE:+";
						break;
					case Contact_Model::TYPE_SKYPE:
						$vCard .= "X-SKYPE:";
						break;
					case Contact_Model::TYPE_MSN:
						$vCard .= "X-MSN:";
						break;
					case Contact_Model::TYPE_WEB:
						$vCard .= "URL:";
						break;
					default:
						$vCard .= "";
						$unknown = true;
						break;
				}

				if (!$unknown)
				{
					$vCard .= "$c->value\n";
				}
			}
			
			$ap = $mm->address_point;

			$street = ($ap->street != NULL ? $ap->street->street." " : "");
			$street .= $ap->street_number;
			$town = $ap->town->town;
			$zip = $ap->town->zip_code;
			$country = $ap->country->country_name;
			
			$vCard .= "ADR;TYPE=HOME:;;$street;$town;;$zip;$country\n";
			$vCard .= "END:VCARD\n\n";
		}
		
		return $vCard;
	}

	/**
	 * Function Generates vCard in 2.1 format
	 * 
	 * @param array $users		Array of all users
	 * @param bool $main_only	Export only main users	
	 */
	private function vcard21($users = NULL, $main_only = false)
	{
		$vCard = '';

		/* Generate vCard for each user */
		foreach ($users AS $user)
		{
			/* Skip all users except main users */
			if ($main_only && $user->type != User_Model::MAIN_USER)
			{
				continue;
			}

			$name = empty($user->name) ? '' : "$user->name ";

			/* Begining of vCard */
			$vCard .=
				"BEGIN:VCARD\n".
				"VERSION:2.1\n".
				"N:$user->surname;$user->name\n".
				"FN:$name$user->surname\n";

			$cm = new Contact_Model();
			$contacts = $cm->find_all_users_contacts($user->id);
			$mm = new Member_Model($user->member_id);

			/* Add all contacts of user */
			foreach ($contacts AS $c)
			{
				$unknown = false;

				switch ($c->type)
				{
					case Contact_Model::TYPE_ICQ:
						$vCard .= "X-ICQ:";
						break;
					case Contact_Model::TYPE_JABBER:
						$vCard .= "X-JABBER:";
						break;
					case Contact_Model::TYPE_EMAIL:
						$vCard .= "EMAIL;PREF;INTERNET:";
						break;
					case Contact_Model::TYPE_PHONE:
						$vCard .= "TEL;HOME;VOICE:+";
						break;
					case Contact_Model::TYPE_SKYPE:
						$vCard .= "X-SKYPE:";
						break;
					case Contact_Model::TYPE_MSN:
						$vCard .= "X-MSN:";
						break;
					case Contact_Model::TYPE_WEB:
						$vCard .= "URL:";
						break;
					default:
						$vCard .= "";
						$unknown = true;
						break;
				}

				if (!$unknown)
				{
					$vCard .= "$c->value\n";
				}
			}
			
			$ap = $mm->address_point;

			$street = ($ap->street != NULL ? $ap->street->street." " : "");
			$street .= $ap->street_number;
			$town = $ap->town->town;
			$zip = $ap->town->zip_code;
			$country = $ap->country->country_name;
			
			$vCard .= "ADR;HOME:;;$street;$town;;$zip;$country\n";
			$vCard .= "END:VCARD\n\n";
		}
		
		return $vCard;
	}
	
	/**
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
	
	/**
	 * Function to export invoice to isdoc format
	 * 
	 * @author Jan Dubina
	 * @param integer $invoice_id	id of invoice to export
	 */
	public function isdoc_invoices($invoice_id = null)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		$invoice = new Invoice_Model($invoice_id);

		if (!$invoice_id || !$invoice->id)
			url::redirect('invoices/show_all');
		
		//cant export invoice with no items
		if ($invoice->invoice_items->count() == 0) {
			status::error('Error - cannot export invoice with no items');
			url::redirect('invoices/show/' . $invoice_id);
		}
	
		//create an array of constants
		$const = array(
			'version'		=> '1.0',
			'encoding'		=> 'utf-8',
			'namespace'		=> 'http://isdoc.cz/namespace/invoice',
			'isdoc_version' => '5.3.1',
			'vat_method'	=> 0,
			'guid'			=> guid::getGUID()
		);
		
		//get association and partner models
		$association = new Member_Model(1);
		$partner = $invoice->member_id ? new Member_Model($invoice->member_id) : null;
		
		if ($invoice->invoice_type == Invoice_Model::TYPE_ISSUED) {
			$supplier = $association;
			$customer = $partner;
		} else {
			$customer = $association;
			$supplier = $partner;
		}
		
		//set header
		header('Content-type: "text/xml"; charset="utf8"');
		header('Content-disposition: attachment; filename=inv_isdoc_' 
				. $invoice->invoice_nr . '.isdoc');
		
		$view = new View('export/export_isdoc');
		$view->const = $const;
		$view->invoice = $invoice;
		$view->supplier = $supplier;
		$view->customer = $customer;
		$view->render(TRUE);
	}
	
	/**
	 * Function exports list of invoices to XML
	 * 
	 * @author Jan Dubina
	 * @param integer $invoice_template_id	id of template of invoice to export
	 * @param integer $invoice_id	id of invoice to export
	 */
	public function xml_invoices($invoice_template_id = null, $invoice_id = null)
	{
		//access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
		{
			Controller::error(ACCESS);
		}
		
		$invoice_template = new Invoice_template_Model($invoice_template_id);
		
		if(!$invoice_template_id || !$invoice_template->id)
			url::redirect ('invoices/show_all/');
		
		//export one invoice or filtered selection
		if(!empty($invoice_id))
			$filter = 'iv.id = ' . $invoice_id;
		else
		{
			$filter_form = new Filter_form('iv');
			$filter_form->autoload();
			$filter = $filter_form->as_sql();
		}

		$invoice_model = new Invoice_Model();

		try
		{
			$invoices = $invoice_model->get_all_invoices_export($filter);
		}
		catch (Exception $e)
		{
			$invoices = array();		
		}

		//no invoices selected
		if ($invoices->count() == 0) {
			status::error('Invalid data - no data available');
			url::redirect('invoices/show_all/');
		}
		
		//cant export invoice with no items
		foreach ($invoices as $invoice)
			if ($invoice->comments_count == 0) {
				status::error('Error - cannot export invoice with no items');
				url::redirect('invoices/show/' . $invoice->id);
			}
		
		//organization identifier must be set
		$association = new Member_Model(Member_Model::ASSOCIATION);
		
		if (!$association->organization_identifier) {
			status::error('Error - organization identifier must be set');
			url::redirect('invoices/show_all/');
		}
 
		//create an array of constants
		$const = array(
			'version'		=> '1.0',
			'encoding'		=> 'utf-8',
			'id'			=> 'inv_' . date("Y-m-d_H-i-s"),
			'org_id'		=> $association->organization_identifier,
			'application'	=> Settings::get('title'),
			'currency'		=> Settings::get('currency'),
			'invoice_ver'	=> '2.0',
			'data_ns'		=> 'http://www.stormware.cz/schema/version_2/data.xsd',
			'invoice_ns'	=> 'http://www.stormware.cz/schema/version_2/invoice.xsd',
			'type_ns'		=> 'http://www.stormware.cz/schema/version_2/type.xsd'
		);
		
		$vat_var = json_decode($invoice_template->vat_variables, true);

		//set header
		header('Content-type: "text/xml"; charset="utf8"');
		header('Content-disposition: attachment; filename=inv_xml_' 
				. $const['id'] . '.xml');
		
		$view = new View('export/export_xml');
		$view->const = $const;
		$view->invoices = $invoices;
		$view->vat_var = $vat_var;
		$view->render(TRUE);
	}
	
	/**
	 * Function to export invoice(s) to dbase format
	 * 
	 * @author Jan Dubina
	 * @param integer $invoice_template_id	id of template of invoice to export
	 * @param integer $invoice_id	id of invoice to export
	 */
	public function dbf_invoices($invoice_template_id = null, $invoice_id = null)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		$invoice_template = new Invoice_template_Model($invoice_template_id);
		
		//create an array of constants
		$const = array(
						'type' => 'Faktura',
						'form' => 'příkazem',
		);

		//get an array of field names
		$fields = Invoice_template_Model::$fields;
		
		if(!$invoice_template_id || !$invoice_template->id)
			url::redirect ('invoices/show_all/');
		
		if (!empty($invoice_id))
			$filter = 'iv.id = ' . $invoice_id;
		else
		{
			$filter_form = new Filter_form('iv');
			$filter_form->autoload();
			$filter = $filter_form->as_sql();
		}

		$invoice_model = new Invoice_Model();
		
		try
		{
			$invoices = $invoice_model->get_all_invoices_export($filter);
		}
		catch (Exception $e)
		{
			$invoices = array();		
		}

		//no invoices selected
		if ($invoices->count() == 0) {
			status::error('Invalid data - no data available');
			url::redirect('invoices/show_all/');
		}
		
		//cant export invoice with no items
		foreach ($invoices as $invoice)
			if ($invoice->comments_count == 0) {
				status::error('Error - cannot export invoice with no items');
				url::redirect('invoices/show/' . $invoice->id);
			}
			
		$vat_var = json_decode($invoice_template->vat_variables, true);
			
		//create an array of columns
		$columns = array(
			array($fields['type'], Dbase_Column::DBFFIELD_TYPE_CHAR, 254),
			array($fields['form'], Dbase_Column::DBFFIELD_TYPE_CHAR, 254),
			array($fields['invoice_nr'], Dbase_Column::DBFFIELD_TYPE_CHAR, 11),
			array($fields['var_sym'], Dbase_Column::DBFFIELD_TYPE_CHAR, 20),
			array($fields['con_sym'], Dbase_Column::DBFFIELD_TYPE_CHAR, 4),
			array($fields['date_inv'], Dbase_Column::DBFFIELD_TYPE_DATE),
			array($fields['date_due'], Dbase_Column::DBFFIELD_TYPE_DATE),
			array($fields['date_vat'],Dbase_Column::DBFFIELD_TYPE_DATE),
			array($fields['order_nr'], Dbase_Column::DBFFIELD_TYPE_CHAR, 16),
			array($fields['price_none'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['price_low'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['price_low_vat'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['price_high'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['price_high_vat'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['price_sum'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['rounding_amount'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['price_liq'], Dbase_Column::DBFFIELD_TYPE_NUMERIC, 20, 5),
			array($fields['company'], Dbase_Column::DBFFIELD_TYPE_CHAR, 96),
			array($fields['name'], Dbase_Column::DBFFIELD_TYPE_CHAR, 32),
			array($fields['street'], Dbase_Column::DBFFIELD_TYPE_CHAR, 64),
			array($fields['zip_code'], Dbase_Column::DBFFIELD_TYPE_CHAR, 15),
			array($fields['town'], Dbase_Column::DBFFIELD_TYPE_CHAR, 45),
			array($fields['organization_identifier'], Dbase_Column::DBFFIELD_TYPE_CHAR, 15),
			array($fields['email'], Dbase_Column::DBFFIELD_TYPE_CHAR, 98),
			array($fields['phone'], Dbase_Column::DBFFIELD_TYPE_CHAR, 40),
			array($fields['account_nr'], Dbase_Column::DBFFIELD_TYPE_CHAR, 34),
			array($fields['bank_code'], Dbase_Column::DBFFIELD_TYPE_CHAR, 11),
			array($fields['currency'], Dbase_Column::DBFFIELD_TYPE_CHAR, 254),
			array($fields['note'], Dbase_Column::DBFFIELD_TYPE_CHAR, 250)
		);
		
		$records = array();
		
		//create an array of records
		foreach ($invoices as $invoice) {
			$record = array();
			
			$record[$fields['type']] = $const['type'];
			$record[$fields['form']] = $const['form'];
			$record[$fields['note']] = $invoice->note;
			$record[$fields['invoice_nr']] = $invoice->invoice_nr;
			$record[$fields['date_inv']] = $invoice->date_inv;
			$record[$fields['date_due']] = $invoice->date_due;
			$record[$fields['date_vat']] = $invoice->date_vat;
			
			if (!empty($invoice->order_nr))
			$record[$fields['order_nr']] = $invoice->order_nr;
			
			if (!empty($invoice->con_sym))
				$record[$fields['con_sym']] = $invoice->con_sym;
			
			if (!empty($invoice->var_sym))
				$record[$fields['var_sym']] = $invoice->var_sym;
			
			//count price of invoice items
			$price_none = 0;
			$price_low = 0;
			$price_low_vat = 0;
			$price_high = 0;
			$price_high_vat = 0;
			
			$invoice_item_model = new Invoice_item_Model();
			$invoice_items = $invoice_item_model->get_items_of_invoice($invoice->id);
			
			foreach ($invoice_items as $item) 	
			{
				$item_price = $item->price * $item->quantity;
				$item_price_vat = $item->price * $item->quantity * (1 + $item->vat);
				$vat_value = intval($item->vat * 1000);
				
				if (array_key_exists($vat_value, $vat_var['export'])) {
					$vat_rate = $vat_var['export'][$vat_value];
					switch ($vat_rate) {
						case 'low':
							$price_low += $item_price;
							$price_low_vat += $item_price_vat;
							break;
						case 'high':
							$price_high += $item_price;
							$price_high_vat += $item_price_vat;
							break;
						default:
							$price_none += $item_price;
							break;
					}
				}
				else
					$price_none += $item_price;
			}

			$record[$fields['price_none']] = round($price_none, 2);
			$record[$fields['price_low']] = round($price_low, 2);
			$record[$fields['price_low_vat']] = round($price_low_vat - $price_low, 2);
			$record[$fields['price_high']] = round($price_high, 2);
			$record[$fields['price_high_vat']] = round($price_high_vat - $price_high, 2);

			$record[$fields['price_sum']] = ceil($price_none + $price_low_vat + $price_high_vat);
			$record[$fields['price_liq']] = ceil($price_none + $price_low_vat + $price_high_vat);
			$record[$fields['rounding_amount']] =  ceil($price_none + $price_low_vat + $price_high_vat) -
														 ($price_none + $price_low_vat + $price_high_vat);

			$record[$fields['company']] = $invoice->company;
			$record[$fields['name']] = $invoice->partner;
			$record[$fields['street']] = address::street_join($invoice->street, $invoice->street_number);	
			$record[$fields['zip_code']] = $invoice->zip_code;
			$record[$fields['town']] = $invoice->town;
			$record[$fields['organization_identifier']] = $invoice->organization_identifier;
			$record[$fields['phone']] = $invoice->phone;
			$record[$fields['email']] = $invoice->email;

			if (!empty($invoice->account_nr)) {
				@list($account_nr, $bank_code) = explode('/', $invoice->account_nr);
				$record[$fields['account_nr']] = $account_nr;
				$record[$fields['bank_code']] = $bank_code;
			}
			
			if ($invoice->currency != Settings::get('currency'))
				$record[$fields['currency']] = $invoice->currency;
			
			$records[] = $record;
		}
		
		//create dbase table
		$dbf_table = new Dbase_Table();
		$dbf_contents = $dbf_table->create_table($columns, $records);
		
		//set header
		header('Content-Type: application/dbf');
		header('Content-Disposition: attachment; filename=idbf' . date("dm") . '.dbf');
		header('Content-Description: PHP Generated Data');
		echo $dbf_contents;
	}
	
		/**
	 * Function to export invoice to eForm format
	 * 
	 * @author Jan Dubina
	 * @param integer $invoice_id	id of invoice to export
	 * @param integer $invoice_template_id	id of template of invoice to export
	 */
	public function eform_invoices($invoice_id = null, $invoice_template_id = null)
	{
		// access rights
		if (!$this->acl_check_view('Accounts_Controller', 'invoices'))
			Controller::Error(ACCESS);

		$invoice = new Invoice_Model($invoice_id);
		$invoice_template = new Invoice_template_Model($invoice_template_id);

		if (!$invoice_id || !$invoice->id || 
				!$invoice_template_id || !$invoice_template->id)
			url::redirect('invoices/show_all');
		
		//cant export invoice with no items
		if ($invoice->invoice_items->count() == 0) {
			status::error('Error - cannot export invoice with no items');
			url::redirect('invoices/show/' . $invoice_id);
		}
	
		//create an array of constants
		$const = array(
			'version'			=> '1.0',
			'encoding'		=> 'Windows-1250',
			'eform_version'		=> '1.3',
			'invoice_version'	=> '1.5',
		);
		
		//create an array of encodings
		$enc = array(
			'in'	=> 'UTF-8',
			'out'	=> 'CP1250'
		);
		
		//get association and partner models
		$association = new Member_Model(1);
		$partner = $invoice->member_id ? new Member_Model($invoice->member_id) : null;
		
		if ($invoice->invoice_type == Invoice_Model::TYPE_ISSUED) {
			$supplier = $association;
			$customer = $partner;
		} else {
			$customer = $association;
			$supplier = $partner;
		}
		
		$vat_var = json_decode($invoice_template->vat_variables, true);

		//set header
		header('Content-type: "text/xml"; charset="windows-1250"');
		header('Content-disposition: attachment; filename=inv_eform_' 
											. $invoice->invoice_nr . '.xml');
		
		$view = new View('export/export_eform');
		$view->const = $const;
		$view->invoice = $invoice;
		$view->supplier = $supplier;
		$view->customer = $customer;
		$view->vat_var = $vat_var;
		$view->enc = $enc;
		$view->render(TRUE);
	}
}
