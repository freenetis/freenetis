<?php

/*
 *  This file is part of open source system FreenetIS
 *  and it is release under GPLv3 licence.
 *
 *  More info about licence can be found:
 *  http://www.gnu.org/licenses/gpl-3.0.html
 *
 *  More info about project can be found:
 *  http://www.freenetis.org/
 */

namespace freenetis\service\core;

use AbstractService;
use Settings;
use security;

/**
 * Service that handles FreenetIS database setup for organization such as
 * an association.
 *
 * @author Ondřej Fibich <ondrej.fibich@gmail.com>
 * @since 1.2
 */
class SetupService extends AbstractService
{

    /**
     * Creates service.
     *
     * @param \ServiceFactory $factory
     */
    public function __construct(\ServiceFactory $factory)
    {
        parent::__construct($factory);
    }

    /**
     * Setup database with provided data about association. After setup
     * admin user account, association bank account, double-entry account,
     * fees and basic settings are created or set and FreenetIS is ready
     * to be used.
     *
     * @param \freenetis\service\core\AssociationSetupData $d data
     * @throws Exception on any error
     */
    public function association(AssociationSetupData $d)
    {
        // data
        $town_model = new \Town_Model();
        $street_model = new \Street_Model();

        try
        {
            $town_model->transaction_start();

            // set deduct day
            Settings::set('deduct_day', max(1, min(31, $d->deduct_day)));

            // first member is special, it represents association
            $member = new \Member_Model();
            $member->id = \Member_Model::ASSOCIATION;
            $member->registration = NULL;
            $member->name = $d->name;

            // address
            $town = $town_model->get_town($d->zip_code, $d->town, $d->quarter);

            $street = $street_model->get_street($d->street, $town->id);

            $address_point_model = new \Address_point_Model();

            $address_point = $address_point_model->get_address_point(
                    $d->default_country, $town->id, $street->id,
                    $d->street_number
            );

            // address point doesn't exist exist, create it
            if (!$address_point->id)
            {
                $address_point->save_throwable();
            }

            $member->address_point_id = $address_point->id;

            $enum_type = new \Enum_type_Model();
            $member->type = $enum_type->get_type_id('Honorary member');
            $member->entrance_date = date('Y-m-d', $d->foundation);
            $member->save_throwable();

            // every member has its one primary user of type "member"
            $user = new \User_Model();
            $user->member_id = $member->id;
            $user->name = $d->login;
            $user->surname = $d->login;
            $user->birthday = date('Y-m-d', $d->foundation);
            $user->login = $d->login;
            $user->password = sha1($d->password);
            $user->type = \User_Model::MAIN_USER;
            $user->application_password = security::generate_password();
            $user->save_throwable();

            // add user to access list as admin
            $groups_aro_map = new \Groups_aro_map_Model();
            $groups_aro_map->aro_id = $user->id;
            $groups_aro_map->group_id = \Aro_group_Model::ADMINS;
            $groups_aro_map->save_throwable();

            // users telephone
            $contact = new \Contact_Model();
            $contact->type = \Contact_Model::TYPE_PHONE;
            $contact->value = $d->phone;
            $contact->save_throwable();

            $contact->add($user);
            $contact->save_throwable();

            $phone_country = new \Country_Model($d->default_country);
            $contact->add($phone_country);
            $contact->save_throwable();
            $contact->clear();

            // users email
            if (!empty($d->email))
            {
                $contact->type = \Contact_Model::TYPE_EMAIL;
                $contact->value = $d->email;
                $contact->save_throwable();
                $contact->add($user);
                $contact->save_throwable();
            }

            // association has at least one real bank account
            $bank_account = new \Bank_account_Model();
            $bank_account->name = $d->account_name;
            $bank_account->member_id = $member->id;
            $bank_account->account_nr = $d->account_nr;
            $bank_account->bank_nr = $d->bank_nr;
            $bank_account->IBAN = $d->IBAN;
            $bank_account->SWIFT = $d->SWIFT;
            $bank_account->save_throwable();

            // these three double-entry accounts are related to one bank account
            // through relation table double-entry bank account
            $doubleentry_bank_account = new \Account_Model();
            $doubleentry_bank_account->member_id = $member->id;
            $doubleentry_bank_account->name = $d->account_name;
            $doubleentry_bank_account->account_attribute_id = \Account_attribute_Model::BANK;
            $doubleentry_bank_account->comment = __('Bank accounts');
            $doubleentry_bank_account->add($bank_account);
            $doubleentry_bank_account->save_throwable();

            // double-entry account of bank fees
            $bank_fees_account = new \Account_Model();
            $bank_fees_account->member_id = $member->id;
            $bank_fees_account->name = $d->account_name . ' - ' . __('Bank fees');
            $bank_fees_account->account_attribute_id = \Account_attribute_Model::BANK_FEES;
            $bank_fees_account->comment = __('Bank fees');
            $bank_fees_account->add($bank_account);
            $bank_fees_account->save_throwable();

            // double-entry account of bank interests
            $bank_interests_account = new \Account_Model();
            $bank_interests_account->member_id = $member->id;
            $bank_interests_account->name = $d->account_name . ' - ' . __('Bank interests');
            $bank_interests_account->account_attribute_id = \Account_attribute_Model::BANK_INTERESTS;
            $bank_interests_account->comment = __('Bank interests');
            $bank_interests_account->add($bank_account);
            $bank_interests_account->save_throwable();

            // other double entry accounts independent of bank account
            // double-entry cash account
            $cash_account = new \Account_Model();
            $cash_account->member_id = $member->id;
            $cash_account->name = __('Cash');
            $cash_account->account_attribute_id = \Account_attribute_Model::CASH;
            $cash_account->comment = __('Cash');
            $cash_account->save_throwable();

            // double-entry operating account
            $operating_account = new \Account_Model();
            $operating_account->member_id = $member->id;
            $operating_account->name = __('Operating account');
            $operating_account->account_attribute_id = \Account_attribute_Model::OPERATING;
            $operating_account->comment = __('Operating account');
            $operating_account->save_throwable();

            // double-entry infrastructure account
            $infrastructure_account = new \Account_Model();
            $infrastructure_account->member_id = $member->id;
            $infrastructure_account->name = __('Infrastructure account');
            $infrastructure_account->account_attribute_id = \Account_attribute_Model::INFRASTRUCTURE;
            $infrastructure_account->comment = __('Infrastructure account');
            $infrastructure_account->save_throwable();

            // double-entry account of purchasers
            $purchasers_account = new \Account_Model();
            $purchasers_account->member_id = $member->id;
            $purchasers_account->name = __('Purchasers account');
            $purchasers_account->account_attribute_id = \Account_attribute_Model::PURCHASERS;
            $purchasers_account->comment = __('Purchasers account');
            $purchasers_account->save_throwable();

            // double-entry account of suppliers
            $suppliers_account = new \Account_Model();
            $suppliers_account->member_id = $member->id;
            $suppliers_account->name = __('Suppliers account');
            $suppliers_account->account_attribute_id = \Account_attribute_Model::SUPPLIERS;
            $suppliers_account->comment = __('Suppliers account');
            $suppliers_account->save_throwable();

            // double-entry account of received member fees
            $fees_account = new \Account_Model();
            $fees_account->member_id = $member->id;
            $fees_account->name = __('Received member fees');
            $fees_account->account_attribute_id = \Account_attribute_Model::MEMBER_FEES;
            $fees_account->comment = __('Received member fees');
            $fees_account->save_throwable();

            // interval of fee availability
            $from = date('Y-m-d', $d->foundation);
            $to = '9999-12-31 23:59:59';

            // entrance fee
            $entrance_fee = new \Fee_Model();
            $entrance_fee->fee = $d->entrance_fee;
            $entrance_fee->from = $from;
            $entrance_fee->to = $to;
            $entrance_fee->type_id = $enum_type->get_type_id('entrance fee');
            $entrance_fee->save_throwable();

            // default entrance fee
            $default_entrance_fee = new \Members_fee_Model();
            $default_entrance_fee->fee_id = $entrance_fee->id;
            $default_entrance_fee->member_id = $member->id;
            $default_entrance_fee->activation_date = $from;
            $default_entrance_fee->deactivation_date = $to;
            $default_entrance_fee->priority = 1;
            $default_entrance_fee->save_throwable();

            // regular member fee
            $regular_member_fee = new \Fee_Model();
            $regular_member_fee->fee = $d->regular_member_fee;
            $regular_member_fee->from = $from;
            $regular_member_fee->to = $to;
            $regular_member_fee->type_id = $enum_type->get_type_id('regular member fee');
            $regular_member_fee->save_throwable();

            // default regular member fee
            $default_regular_member_fee = new \Members_fee_Model();
            $default_regular_member_fee->fee_id = $regular_member_fee->id;
            $default_regular_member_fee->member_id = $member->id;
            $default_regular_member_fee->activation_date = $from;
            $default_regular_member_fee->deactivation_date = $to;
            $default_regular_member_fee->priority = 1;
            $default_regular_member_fee->save_throwable();

            // transfer fee
            $transfer_fee = new \Fee_Model();
            $transfer_fee->fee = $d->transfer_fee;
            $transfer_fee->from = $from;
            $transfer_fee->to = $to;
            $transfer_fee->type_id = $enum_type->get_type_id('transfer fee');
            $transfer_fee->save_throwable();

            // default transfer fee
            $default_transfer_fee = new \Members_fee_Model();
            $default_transfer_fee->fee_id = $transfer_fee->id;
            $default_transfer_fee->member_id = $member->id;
            $default_transfer_fee->activation_date = $from;
            $default_transfer_fee->deactivation_date = $to;
            $default_transfer_fee->priority = 1;
            $default_transfer_fee->save_throwable();

            // penalty
            $penalty = new \Fee_Model();
            $penalty->fee = $d->penalty;
            $penalty->from = $from;
            $penalty->to = $to;
            $penalty->type_id = $enum_type->get_type_id('penalty');
            $penalty->save_throwable();

            // default transfer fee
            $default_penalty = new \Members_fee_Model();
            $default_penalty->fee_id = $penalty->id;
            $default_penalty->member_id = $member->id;
            $default_penalty->activation_date = $from;
            $default_penalty->deactivation_date = $to;
            $default_penalty->priority = 1;
            $default_penalty->save_throwable();

            // permament whitelist
            $members_whitelist = new \Members_whitelist_Model();
            $members_whitelist->member_id = $member->id;
            $members_whitelist->permanent = 1;
            $members_whitelist->since = date('Y-m-d');
            $members_whitelist->until = '9999-12-31';
            $members_whitelist->save_throwable();

            // saves special (read-only) types of fee
            $fee_model = new \Fee_Model();

            foreach (\Fee_Model::$SPECIAL_TYPE_NAMES as $id => $name)
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

            // system settings
            Settings::set('title', $d->title);
            Settings::set('currency', $d->currency);
            Settings::set('default_country', $d->default_country);
            // set base domain
            Settings::set('domain', $d->domain);
            // set subdirectory
            Settings::set('suffix', $d->suffix);
            // remove index.php from urls
            Settings::set('index_page', $d->index_page);

            // commit changes
            $town_model->transaction_commit();
        }
        catch (Exception $ex)
        {
            $town_model->transaction_rollback();
            throw $ex;
        }
    }

}

/**
 * Data required for association setup.
 */
class AssociationSetupData {

    /**
     * Day in month of deduct (from 1 up to 31).
     *
     * @var int
     */
    public $deduct_day;

    /**
     * Organization name.
     *
     * @var string
     */
    public $name;

    /**
     * Date of foundation of organization.
     *
     * @var date
     */
    public $foundation;

    /**
     * Address - town name.
     *
     * @var string
     */
    public $town;

    /**
     * Address - town quarter name.
     *
     * @var string
     */
    public $quarter;

    /**
     * Address - street name.
     *
     * @var string
     */
    public $street;

    /**
     * Address - country number ID.
     *
     * @var int
     */
    public $default_country;

    /**
     * Address - street number.
     *
     * @var string
     */
    public $street_number;

    /**
     * Address - ZIP code.
     *
     * @var string
     */
    public $zip_code;

    /**
     * Admin account username.
     *
     * @var string
     */
    public $login;

    /**
     * Admin account password.
     *
     * @var string
     */
    public $password;

    /**
     * Organization phone.
     *
     * @var string
     */
    public $phone;

    /**
     * Organization e-mail.
     *
     * @var string
     */
    public $email;

    /**
     * Organization bank account name.
     *
     * @var string
     */
    public $account_name;

    /**
     * Organization bank account - account number.
     *
     * @var integer
     */
    public $account_nr;

    /**
     * Organization bank account - bank number.
     *
     * @var integer
     */
    public $bank_nr;

    /**
     * Organization bank account - IBAN.
     *
     * @var string
     */
    public $IBAN;

    /**
     * Organization bank account - SWIFT.
     *
     * @var string
     */
    public $SWIFT;

    /**
     * Entrance member fee amount - must be higher or equal zero.
     *
     * @var double
     */
    public $entrance_fee;

    /**
     * Regular month member fee amount - must be higher or equal zero.
     *
     * @var double
     */
    public $regular_member_fee;

    /**
     * Transfer fee amount - ust be higher or equal zero.
     * Transfer fee that is charged for each member transaction to association.
     *
     * @var double
     */
    public $transfer_fee;

    /**
     * Penalty fee amount - must be higher or equal zero. Penalty fee is charged
     * for invalid made bank transaction made by member.
     *
     * @var string
     */
    public $penalty;

    /**
     * System title.
     *
     * @var string
     */
    public $title;

    /**
     * System currency.
     *
     * @var string
     */
    public $currency;

    /**
     * System install domain name e.g. !freenetis.tlgh-free.cz".
     *
     * @var string
     */
    public $domain;

    /**
     * System install URL suffix e.g. "/freenetis-main/". Allows to tun multiple
     * FreenetIS installations on a single domain but commonly it is just "/".
     *
     * @var string
     */
    public $suffix;

    /**
     * Is index.php part of each URL (.httaccess server support required for
     * disabling this option).
     *
     * @var boolean
     */
    public $index_page;

    /**
     * Set values of abject from associative array.
     *
     * @param array $values associative properties array
     * @throws \InvalidArgumentException on invalid values or unknown property
     */
    public function set_values($values)
    {
        if (empty($values) || !is_array($values))
        {
            throw new \InvalidArgumentException('values must be assoc array');
        }
        foreach ($values as $key => $value)
        {
            if (!property_exists($this, $key))
            {
                throw new \InvalidArgumentException('unknown property ' . $key);
            }
            $this->{$key} = $value;
        }
    }

}
