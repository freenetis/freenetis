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
	 * Page with installation dialog if not initialized database is configured
     * or not properly setup. After form validation it creates member
     * representing association, his primary user, first bank account and
     * default double-entry accounts required for accounting.
	 */
	public function index()
	{
		// check if the database is empty
		if (Settings::get('db_schema_version') && Settings::get('domain'))
        {
            url::redirect('members/show/' . $this->session->get('member_id'));
        }

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
            unset($form_data['confirm_password']);

            $lck_file = server::base_dir() . '/upload/mutex';

            try
            {
                // database init
                $this->services->injectCoreDatabaseInit()->make($lck_file);
                // setup association data
                $setup_service = $this->services->injectCoreSetup();
                $data = new freenetis\service\core\AssociationSetupData();
                $data->set_values($form_data);
                $data->domain = server::http_host();
                $data->suffix = substr(server::script_name(), 0, -9);
                $data->index_page = 0;
                $setup_service->association($data);
                // done
                $view = new View('installation/done');
                $view->render(TRUE);
                exit();
            }
            catch (Exception $ex)
            {
                Log::add_exception($ex);
                status::error('Installation has failed', $ex);
            }
		}

        $view = new View('installation/index');
        $view->title = __('Installation');
        $view->form = $form->html();
        $view->render(TRUE);
	}

    /**
     * Installation done page.
     */
    public function done()
    {
        $view = new View('installation/done');
        $view->render(TRUE);
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
