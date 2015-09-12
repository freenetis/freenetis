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
 * Controller performs installation of FreenetIS
 * 
 * @package Controller
 */
class Installation_Controller extends Controller
{
	/**
	 * Array of countries, index corresponds to id of country in table countries
	 * @var array
	 */
	private static $COUNTRIES = array
	(
	    '1' => 'Afghanistan',
	    '2' => 'Albania',
	    '3' => 'Algeria',
	    '4' => 'American Samoa',
	    '5' => 'Andorra',
	    '6' => 'Angola',
	    '7' => 'Anguilla',
	    '8' => 'Antarctica',
	    '9' => 'Antigua and Barbuda',
	    '10' => 'Argentina',
	    '11' => 'Armenia',
	    '12' => 'Aruba',
	    '13' => 'Australia',
	    '14' => 'Austria',
	    '15' => 'Azerbaijan',
	    '16' => 'Bahamas',
	    '17' => 'Bahrain',
	    '18' => 'Bangladesh',
	    '19' => 'Barbados',
	    '20' => 'Belarus',
	    '21' => 'Belgium',
	    '22' => 'Belize',
	    '23' => 'Benin',
	    '24' => 'Bermuda',
	    '25' => 'Bhutan',
	    '26' => 'Bolivia',
	    '27' => 'Bosnia and Herzegovina',
	    '28' => 'Botswana',
	    '29' => 'Brazil',
	    '30' => 'British Indian Ocean Territory',
	    '31' => 'British Virgin Islands',
	    '32' => 'Brunei',
	    '33' => 'Bulgaria',
	    '34' => 'Burkina Faso',
	    '35' => 'Burma (Myanmar)',
	    '36' => 'Burundi',
	    '37' => 'Cambodia',
	    '38' => 'Cameroon',
	    '39' => 'Canada',
	    '40' => 'Cape Verde',
	    '41' => 'Cayman Islands',
	    '42' => 'Central African Republic',
	    '43' => 'Chad',
	    '44' => 'Chile',
	    '45' => 'China',
	    '46' => 'Christmas Island',
	    '47' => 'Cocos (Keeling) Islands',
	    '48' => 'Colombia',
	    '49' => 'Comoros',
	    '50' => 'Cook Islands',
	    '51' => 'Costa Rica',
	    '52' => 'Croatia',
	    '53' => 'Cuba',
	    '54' => 'Cyprus',
	    '55' => 'Czech Republic',
	    '56' => 'Democratic Republic of the Congo',
	    '57' => 'Denmark',
	    '58' => 'Djibouti',
	    '59' => 'Dominica',
	    '60' => 'Dominican Republic',
	    '61' => 'Ecuador',
	    '62' => 'Egypt',
	    '63' => 'El Salvador',
	    '64' => 'Equatorial Guinea',
	    '65' => 'Eritrea',
	    '66' => 'Estonia',
	    '67' => 'Ethiopia',
	    '68' => 'Falkland Islands',
	    '69' => 'Faroe Islands',
	    '70' => 'Fiji',
	    '71' => 'Finland',
	    '72' => 'France',
	    '73' => 'French Polynesia',
	    '74' => 'Gabon',
	    '75' => 'Gambia',
	    '76' => 'Georgia',
	    '77' => 'Germany',
	    '78' => 'Ghana',
	    '79' => 'Gibraltar',
	    '80' => 'Greece',
	    '81' => 'Greenland',
	    '82' => 'Grenada',
	    '83' => 'Guam',
	    '84' => 'Guatemala',
	    '85' => 'Guinea',
	    '86' => 'Guinea-Bissau',
	    '87' => 'Guyana',
	    '88' => 'Haiti',
	    '89' => 'Holy See (Vatican City)',
	    '90' => 'Honduras',
	    '91' => 'Hong Kong',
	    '92' => 'Hungary',
	    '93' => 'Iceland',
	    '94' => 'India',
	    '95' => 'Indonesia',
	    '96' => 'Iran',
	    '97' => 'Iraq',
	    '98' => 'Ireland',
	    '99' => 'Isle of Man',
	    '100' => 'Israel',
	    '101' => 'Italy',
	    '102' => 'Ivory Coast',
	    '103' => 'Jamaica',
	    '104' => 'Japan',
	    '105' => 'Jersey',
	    '106' => 'Jordan',
	    '107' => 'Kazakhstan',
	    '108' => 'Kenya',
	    '109' => 'Kiribati',
	    '110' => 'Kuwait',
	    '111' => 'Kyrgyzstan',
	    '112' => 'Laos',
	    '113' => 'Latvia',
	    '114' => 'Lebanon',
	    '115' => 'Lesotho',
	    '116' => 'Liberia',
	    '117' => 'Libya',
	    '118' => 'Liechtenstein',
	    '119' => 'Lithuania',
	    '120' => 'Luxembourg',
	    '121' => 'Macau',
	    '122' => 'Macedonia',
	    '123' => 'Madagascar',
	    '124' => 'Malawi',
	    '125' => 'Malaysia ',
	    '126' => 'Maldives',
	    '127' => 'Mali',
	    '128' => 'Malta',
	    '129' => 'Marshall Islands',
	    '130' => 'Mauritania',
	    '131' => 'Mauritius',
	    '132' => 'Mayotte',
	    '133' => 'Mexico',
	    '134' => 'Micronesia',
	    '135' => 'Moldova',
	    '136' => 'Monaco',
	    '137' => 'Mongolia ',
	    '138' => 'Montenegro ',
	    '139' => 'Montserrat',
	    '140' => 'Morocco',
	    '141' => 'Mozambique',
	    '142' => 'Namibia ',
	    '143' => 'Nauru',
	    '144' => 'Nepal',
	    '145' => 'Netherlands',
	    '146' => 'Netherlands Antilles',
	    '147' => 'New Caledonia',
	    '148' => 'New Zealand ',
	    '149' => 'Nicaragua',
	    '150' => 'Niger',
	    '151' => 'Nigeria',
	    '152' => 'Niue',
	    '153' => 'Norfolk Island',
	    '154' => 'North Korea',
	    '155' => 'Northern Mariana Islands',
	    '156' => 'Norway',
	    '157' => 'Oman',
	    '158' => 'Pakistan',
	    '159' => 'Palau',
	    '160' => 'Panama',
	    '161' => 'Papua New Guinea',
	    '162' => 'Paraguay',
	    '163' => 'Peru ',
	    '164' => 'Philippines',
	    '165' => 'Pitcairn Islands',
	    '166' => 'Poland',
	    '167' => 'Portugal',
	    '168' => 'Puerto Rico',
	    '169' => 'Qatar',
	    '170' => 'Republic of the Congo',
	    '171' => 'Romania',
	    '172' => 'Russia',
	    '173' => 'Rwanda',
	    '174' => 'Saint Barthelemy',
	    '175' => 'Saint Helena',
	    '176' => 'Saint Kitts and Nevis',
	    '177' => 'Saint Lucia',
	    '178' => 'Saint Martin',
	    '179' => 'Saint Pierre and Miquelon',
	    '180' => 'Saint Vincent and the Grenadines',
	    '181' => 'Samoa',
	    '182' => 'San Marino',
	    '183' => 'Sao Tome and Principe',
	    '184' => 'Saudi Arabia',
	    '185' => 'Senegal',
	    '186' => 'Serbia',
	    '187' => 'Seychelles',
	    '188' => 'Sierra Leone',
	    '189' => 'Singapore',
	    '190' => 'Slovakia',
	    '191' => 'Slovenia',
	    '192' => 'Solomon Islands',
	    '193' => 'Somalia',
	    '194' => 'South Africa',
	    '195' => 'South Korea',
	    '196' => 'Spain',
	    '197' => 'Sri Lanka',
	    '198' => 'Sudan',
	    '199' => 'Suriname',
	    '200' => 'Svalbard',
	    '201' => 'Swaziland',
	    '202' => 'Sweden',
	    '203' => 'Switzerland',
	    '204' => 'Syria',
	    '205' => 'Taiwan',
	    '206' => 'Tajikistan',
	    '207' => 'Tanzania',
	    '208' => 'Thailand',
	    '209' => 'Timor-Leste',
	    '210' => 'Togo',
	    '211' => 'Tokelau',
	    '212' => 'Tonga',
	    '213' => 'Trinidad and Tobago',
	    '214' => 'Tunisia',
	    '215' => 'Turkey',
	    '216' => 'Turkmenistan',
	    '217' => 'Turks and Caicos Islands',
	    '218' => 'Tuvalu',
	    '219' => 'Uganda',
	    '220' => 'Ukraine',
	    '221' => 'United Arab Emirates',
	    '222' => 'United Kingdom',
	    '223' => 'United States',
	    '224' => 'Uruguay',
	    '225' => 'US Virgin Islands',
	    '226' => 'Uzbekistan',
	    '227' => 'Vanuatu',
	    '228' => 'Venezuela',
	    '229' => 'Vietnam',
	    '230' => 'Wallis and Futuna',
	    '231' => 'Western Sahara',
	    '232' => 'Yemen',
	    '233' => 'Zambia',
	    '234' => 'Zimbabwe'
	);

	/**
	 * Function shows installation dialog. To show it must exist empty database freenetis and user with the
	 * same name. After form validation it creates member representing association, his primary user,
	 * first bank account and many double-entry accounts required for accounting.
	 */
	public function index()
	{
		// check if the database is empty
		if (Settings::get('db_schema_version'))
			url::redirect('members/show/'.$this->session->get('member_id'));

		/**
		 * finds default currency and country from lang
		 * @todo do it better, maybe from some special table
		 */
		switch (Config::get('lang'))
		{
			// language is czech
			case 'cs':
				$currency = 'CZK';
				$country_id = 55;
				break;
			default:
				$currency = 'USD';
				$country_id = 223;
				break;
		}

		// form	
		$form = new Forge('installation');
		
		// login
		
		$form->group('Login data');
		
		$form->input('login')
				->label('Username')
				->rules('required|length[3,50]')
				->value('admin')
				->help(help::hint('login_name'))
				->callback(array($this, 'valid_username'));
		
		$pass_min_len = Settings::get('security_password_length');
		
		$form->password('password')
				->rules('required|length['.$pass_min_len.',50]')
				->class('main_password')
				->title(url_lang::lang('help.password'));
		
		$form->password('confirm_password')
				->rules('required|length['.$pass_min_len.',50]')
				->matches($form->password);
		
		// association
		
		$form->group('Association information');
		
		$form->input('name')
				->label('Name of the association')
				->rules('required|length[3,30]');
		
		$form->date('foundation')
				->label('Date of foundation')
				->years(date('Y')-100, date('Y'))
				->rules('required');
		
		$form->input('street')
				->rules('required|length[1,200]');
		
		$form->input('street_number')
				->rules('length[1,50]');
		
		$form->input('town')
				->rules('required|length[1,200]');
		
		$form->input('quarter')
				->rules('length[1,50]');
		
		$form->input('zip_code')
				->rules('required|length[5,10]');
		
		$form->input('phone')
				->rules('required|length[9,9]|valid_phone');
		
		$form->input('email')
				->rules('required|valid_email|length[3,50]');
		
		// bank account
		
		$form->group(
				__('The first bank account information') . ' ' .
				help::hint('bank_accounts_of_association')
		);
		
		$form->input('account_name')
				->rules('required|length[3,50]');
		
		$form->input('account_nr')
				->label('Account number')
				->rules('required|length[3,50]|valid_numeric');
		
		$form->input('bank_nr')
				->label('Bank code')
				->rules('required|length[3,10]|valid_numeric');
		
		$form->input('IBAN');
		
		$form->input('SWIFT');
		
		// fees
		
		$form->group('Fees');
		
		$form->input('deduct_day')
				->rules('valid_numeric')
				->help(help::hint('deduct_day'))
				->rules('required')
				->value(15);
		
		$form->input('entrance_fee')
				->rules('valid_numeric')
				->help(help::hint('entrance_fee'));
		
		$form->input('regular_member_fee')
				->label('Monthly member fee')
				->rules('valid_numeric');
		
		$form->input('transfer_fee')
				->rules('valid_numeric');
		
		$form->input('penalty')
				->rules('valid_numeric');
		
		// system
		
		$form->group('System');
		
		$form->input('title')
				->label('Page title')
				->value('Freenetis')
				->rules('required');
		
		$form->dropdown('default_country')
				->label('Country')
				->rules('required')
				->options(self::$COUNTRIES)
				->selected($country_id)
				->style('width:200px');
		
		$form->input('currency')
				->rules('required')
				->value($currency);
		
		// submit button
		
		$form->submit('Install');
		
		// valid post?
		if ($form->validate())
		{
			$form_data = $form->as_array();
			// try to open mutex file
			if (($f = @fopen(server::base_dir().'/upload/mutex', 'w')) === FALSE)
			{
				// directory is not writeable
				self::error(WRITABLE, server::base_dir().'/upload/');
			}

			// acquire an exclusive access to file
			// wait while database is being updated
			if (flock($f, LOCK_EX))
			{
				// first access - update db
				// other access - skip
				if (!Version::is_db_up_to_date())
				{
					// this executes all queries that upgrade database structure
					try
					{
						Version::make_db_up_to_date();
					}
					catch (Exception $e)
					{
						throw new Exception(
								__('Database upgrade failed') . ': ' .
								$e->getMessage(), 0, $e
						);
					}

					// data
					$town_model = new Town_Model();
					$street_model = new Street_Model();

					try
					{
						$town_model->transaction_start();
						
						// set deduct day
						Settings::set('deduct_day', max(1, min(31, $form_data['deduct_day'])));

						// first member is special, it represents association
						$member = new Member_Model();
						$member->registration  = NULL;
						$member->name		   = $form_data['name'];

						// address
						$town = $town_model->get_town(
								$form_data['zip_code'],
								$form_data['town'],
								$form_data['quarter']
						);

						$street = $street_model->get_street($form_data['street'], $town->id);

						$address_point_model = new Address_point_Model();

						$address_point = $address_point_model->get_address_point(
								$form_data['default_country'],
								$town->id, $street->id,
								$form_data['street_number']
						);

						// address point doesn't exist exist, create it
						if (!$address_point->id)
						{
							$address_point->save_throwable();
						}

						$member->address_point_id = $address_point->id;

						$enum_type = new Enum_type_Model();
						$member->type			= $enum_type->get_type_id('Honorary member');
						$member->entrance_date	= date('Y-m-d', $form_data['foundation']);
						$member->save_throwable();

						// every member has its one primary user of type "member"
						$user = new User_Model();
						$user->member_id = $member->id;
						$user->name = $form_data['login'];
						$user->surname = $form_data['login'];
						$user->birthday = date('Y-m-d', $form_data['foundation']);
						$user->login = $form_data['login'];
						$user->password = sha1($form_data['password']);
						$user->type = User_Model::MAIN_USER;
						$user->application_password = security::generate_password();
						$user->save_throwable();

						// add user to access list as admin
						$groups_aro_map = new Groups_aro_map_Model();
						$groups_aro_map->aro_id = $user->id;
						$groups_aro_map->group_id = Aro_group_Model::ADMINS;
						$groups_aro_map->save_throwable();

						// users telephone
						$contact = new Contact_Model();
						$contact->type = Contact_Model::TYPE_PHONE;
						$contact->value = $form_data['phone'];
						$contact->save_throwable();

						$contact->add($user);
						$contact->save_throwable();

						$phone_country = new Country_Model($form_data['default_country']);
						$contact->add($phone_country);
						$contact->save_throwable();
						$contact->clear();

						// users email
						if (! empty($form_data['email']))
						{
							$contact->type = Contact_Model::TYPE_EMAIL;
							$contact->value = $form_data['email'];
							$contact->save_throwable();
							$contact->add($user);
							$contact->save_throwable();
						}

						// association has at least one real bank account
						$bank_account = new Bank_account_Model();
						$bank_account->name = $form_data['account_name'];
						$bank_account->member_id = $member->id;
						$bank_account->account_nr = $form_data['account_nr'];
						$bank_account->bank_nr = $form_data['bank_nr'];
						$bank_account->IBAN = $form_data['IBAN'];
						$bank_account->SWIFT = $form_data['SWIFT'];
						$bank_account->save_throwable();	

						// these three double-entry accounts are related to one bank account through relation table
						// double-entry bank account
						$doubleentry_bank_account = new Account_Model();
						$doubleentry_bank_account->member_id = $member->id;
						$doubleentry_bank_account->name = $form_data['account_name'];
						$doubleentry_bank_account->account_attribute_id = Account_attribute_Model::BANK;
						$doubleentry_bank_account->comment = __('Bank accounts');
						$doubleentry_bank_account->add($bank_account);
						$doubleentry_bank_account->save_throwable();

						// double-entry account of bank fees
						$bank_fees_account = new Account_Model();
						$bank_fees_account->member_id = $member->id;
						$bank_fees_account->name = $form_data['account_name'].' - '.__('Bank fees');
						$bank_fees_account->account_attribute_id = Account_attribute_Model::BANK_FEES;
						$bank_fees_account->comment = __('Bank fees');
						$bank_fees_account->add($bank_account);
						$bank_fees_account->save_throwable();

						// double-entry account of bank interests
						$bank_interests_account = new Account_Model();
						$bank_interests_account->member_id = $member->id;
						$bank_interests_account->name = $form_data['account_name'].' - '.__('Bank interests');
						$bank_interests_account->account_attribute_id = Account_attribute_Model::BANK_INTERESTS;
						$bank_interests_account->comment = __('Bank interests');
						$bank_interests_account->add($bank_account);
						$bank_interests_account->save_throwable();

						// other double entry accounts independent of bank account
						// double-entry cash account
						$cash_account = new Account_Model();
						$cash_account->member_id = $member->id;
						$cash_account->name = __('Cash');
						$cash_account->account_attribute_id = Account_attribute_Model::CASH;
						$cash_account->comment = __('Cash');
						$cash_account->save_throwable();

						// double-entry operating account
						$operating_account = new Account_Model();
						$operating_account->member_id = $member->id;
						$operating_account->name = __('Operating account');
						$operating_account->account_attribute_id = Account_attribute_Model::OPERATING;
						$operating_account->comment = __('Operating account');
						$operating_account->save_throwable();

						// double-entry infrastructure account
						$infrastructure_account = new Account_Model();
						$infrastructure_account->member_id = $member->id;
						$infrastructure_account->name = __('Infrastructure account');
						$infrastructure_account->account_attribute_id = Account_attribute_Model::INFRASTRUCTURE;
						$infrastructure_account->comment = __('Infrastructure account');
						$infrastructure_account->save_throwable();

						// double-entry account of purchasers
						$purchasers_account = new Account_Model();
						$purchasers_account->member_id = $member->id;
						$purchasers_account->name = __('Purchasers account');
						$purchasers_account->account_attribute_id = Account_attribute_Model::PURCHASERS;
						$purchasers_account->comment = __('Purchasers account');
						$purchasers_account->save_throwable();

						// double-entry account of suppliers
						$suppliers_account = new Account_Model();
						$suppliers_account->member_id = $member->id;
						$suppliers_account->name = __('Suppliers account');
						$suppliers_account->account_attribute_id = Account_attribute_Model::SUPPLIERS;
						$suppliers_account->comment = __('Suppliers account');
						$suppliers_account->save_throwable();

						// double-entry account of received member fees
						$fees_account = new Account_Model();
						$fees_account->member_id = $member->id;
						$fees_account->name = __('Received member fees');
						$fees_account->account_attribute_id = Account_attribute_Model::MEMBER_FEES;
						$fees_account->comment = __('Received member fees');
						$fees_account->save_throwable();

						// interval of fee availability
						$from = date('Y-m-d', $form_data['foundation']);
						$to = '9999-12-31 23:59:59';

						// entrance fee
						$entrance_fee = new Fee_Model();
						$entrance_fee->fee = $form_data['entrance_fee'];
						$entrance_fee->from = $from;
						$entrance_fee->to = $to;
						$entrance_fee->type_id = $enum_type->get_type_id('entrance fee');
						$entrance_fee->save_throwable();

						// default entrance fee
						$default_entrance_fee = new Members_fee_Model();
						$default_entrance_fee->fee_id = $entrance_fee->id;
						$default_entrance_fee->member_id = $member->id;
						$default_entrance_fee->activation_date = $from;
						$default_entrance_fee->deactivation_date = $to;
						$default_entrance_fee->priority = 1;
						$default_entrance_fee->save_throwable();

						// regular member fee
						$regular_member_fee = new Fee_Model();
						$regular_member_fee->fee = $form_data['regular_member_fee'];
						$regular_member_fee->from = $from;
						$regular_member_fee->to = $to;
						$regular_member_fee->type_id = $enum_type->get_type_id('regular member fee');
						$regular_member_fee->save_throwable();

						// default regular member fee
						$default_regular_member_fee = new Members_fee_Model();
						$default_regular_member_fee->fee_id = $regular_member_fee->id;
						$default_regular_member_fee->member_id = $member->id;
						$default_regular_member_fee->activation_date = $from;
						$default_regular_member_fee->deactivation_date = $to;
						$default_regular_member_fee->priority = 1;
						$default_regular_member_fee->save_throwable();

						// transfer fee
						$transfer_fee = new Fee_Model();
						$transfer_fee->fee = $form_data['transfer_fee'];
						$transfer_fee->from = $from;
						$transfer_fee->to = $to;
						$transfer_fee->type_id = $enum_type->get_type_id('transfer fee');
						$transfer_fee->save_throwable();

						// default transfer fee
						$default_transfer_fee = new Members_fee_Model();
						$default_transfer_fee->fee_id = $transfer_fee->id;
						$default_transfer_fee->member_id = $member->id;
						$default_transfer_fee->activation_date = $from;
						$default_transfer_fee->deactivation_date = $to;
						$default_transfer_fee->priority = 1;
						$default_transfer_fee->save_throwable();

						// penalty
						$penalty = new Fee_Model();
						$penalty->fee = $form_data['penalty'];
						$penalty->from = $from;
						$penalty->to = $to;
						$penalty->type_id = $enum_type->get_type_id('penalty');
						$penalty->save_throwable();

						// default transfer fee
						$default_penalty = new Members_fee_Model();
						$default_penalty->fee_id = $penalty->id;
						$default_penalty->member_id = $member->id;
						$default_penalty->activation_date = $from;
						$default_penalty->deactivation_date = $to;
						$default_penalty->priority = 1;
						$default_penalty->save_throwable();
						
						// permament whitelist
						$members_whitelist = new Members_whitelist_Model();
						$members_whitelist->member_id = $member->id;
						$members_whitelist->permanent = 1;
						$members_whitelist->since = date('Y-m-d');
						$members_whitelist->until = '9999-12-31';
						$members_whitelist->save_throwable();

						// system settings
						Settings::set('title', $form_data['title']);
						Settings::set('currency', $form_data['currency']);
						Settings::set('default_country', $form_data['default_country']);

						// saves special (read-only) types of fee
						$fee_model = new Fee_Model();
						$special_types = array
						(
							1 => 'Membership interrupt',
							2 => 'Fee-free regular member',
							3 => 'Non-member',
							4 => 'Honorary member'
						);

						foreach ($special_types as $id => $name)
						{
							$fee_model->clear();
							$fee_model->readonly = 1;
							$fee_model->fee = 0;
							$fee_model->from = $from;
							$fee_model->to = $to;
							$fee_model->type_id = $enum_type->get_type_id('regular member fee');
							$fee_model->name = $name;
							$fee_model->special_type_id = $id;
							$fee_model->save_throwable();
						}

						// commit changes
						$town_model->transaction_commit();
					}
					catch (Exception $e)
					{
						$town_model->transaction_rollback();
						Log::add_exception($e);
						throw new Exception(__('Installation has failed') . ': ' . $e);
					}

					// array for store error
					$errors = array();

					// set base domain
					Settings::set('domain', server::http_host());

					// set subdirectory
					$suffix = substr(server::script_name(),0,-9);
					Settings::set('suffix', $suffix);

					$view = new View('installation/done');

					// remove index.php from urls
					Settings::set('index_page', 0);

					$view->errors = $errors;
					$view->render(TRUE);
				}

				// unlock mutex file
				flock($f, LOCK_UN);
			}
			// close mutex file
			fclose($f);
		}
		else
		{
			$view = new View('installation/index');
			$view->title = __('Installation');
			$view->form = $form->html();
			$view->render(TRUE);
		}
	}

	/**
	 * Function checks validity of username.
	 * 
	 * @param object $input
	 */
	public static function valid_username($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}
		
		$username_regex = Settings::get('username_regex');
		
		if (preg_match($username_regex, $input->value) == 0)
		{
			$input->add_error(
					'required', __(
							'Login must contains only a-z and 0-9 and ' .
							'starts with literal.'
					)
			);
		}
	}
	
}
