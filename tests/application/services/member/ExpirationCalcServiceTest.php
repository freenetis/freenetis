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

namespace freenetis\service\member;

use AbstractItCase;
use Settings;

require_once APPPATH . '/services/member/ExpirationCalcService' . EXT;

/**
 * Test case for ExpirationCalcService class.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class ExpirationCalcServiceTest extends AbstractItCase
{

    /**
     * @var ExpirationCalcService
     */
    private $object;

	/**
	 * Hold deduct day during test for recover.
	 *
	 * @var int day number
	 */
	private $old_deduct_day;

	protected function setUp()
    {
        $this->object = new ConfigurableTestExpirationCalcService(self::$services);
		$this->old_deduct_day = Settings::get('deduct_day');
    }

	protected function tearDown()
	{
		Settings::set('deduct_day', $this->old_deduct_day);
	}

	/**
	 * @see https://dev.freenetis.org/issues/1076
	 */
	public function test_get_expiration_info__issue1076()
	{
		Settings::set('deduct_day', 28);

		$this->object->set_fee_model(new TestStaticFeeModel(2, 12.65));
		$this->object->set_transfer_model(new TestStaticTransferModel(11, '2017-02-28'));

		$account = new \stdClass();
		$account->id = 11;
		$account->balance = -37.95;
		$account->member_id = 2;
		$account->member = new \stdClass();
		$account->member->entrance_date = '2016-06-01';
		$account->member->entrance_fee = 9.9;
		$account->member->debt_payment_rate = 0;

		$res = $this->object->get_expiration_info($account);
		$this->assertFalse($res->shortened);
		$this->assertEquals($res->expiration_date, '2016-11-30', $res->expiration_date);
	}

	public function test_get_expiration_info__no_entrance_fee()
	{
		Settings::set('deduct_day', 15);

		$this->object->set_fee_model(new TestStaticFeeModel(3, 150));
		$this->object->set_transfer_model(new TestStaticTransferModel(12, '2017-10-15'));

		$account = new \stdClass();
		$account->id = 12;
		$account->balance = 1500.00;
		$account->member_id = 3;
		$account->member = new \stdClass();
		$account->member->entrance_date = '2017-09-01';
		$account->member->entrance_fee = 0;
		$account->member->debt_payment_rate = 0;

		$res = $this->object->get_expiration_info($account);
		$this->assertFalse($res->shortened);
		$this->assertEquals('2018-08-31', $res->expiration_date);

		// Shortened test
		$res_shortened = $this->object->get_expiration_info($account, 2017);
		$this->assertTrue($res_shortened->shortened);
		$this->assertEquals('2017-12-31', $res_shortened->expiration_date);
	}

	public function test_get_expiration_info__debt_month()
	{
		Settings::set('deduct_day', 15);

		$this->object->set_fee_model(new TestStaticFeeModel(4, 150));
		$this->object->set_transfer_model(new TestStaticTransferModel(13, '2017-10-15'));

		$account = new \stdClass();
		$account->id = 13;
		$account->balance = -150.00;
		$account->member_id = 4;
		$account->member = new \stdClass();
		$account->member->entrance_date = '2017-09-01';
		$account->member->entrance_fee = 0;
		$account->member->debt_payment_rate = 0;

		$res = $this->object->get_expiration_info($account);
		$this->assertFalse($res->shortened);
		$this->assertEquals('2017-09-30', $res->expiration_date);
	}

	public function test_get_expiration_info__entrance_fee()
	{
		Settings::set('deduct_day', 1);

		$this->object->set_fee_model(new TestStaticFeeModel(10, 100));
		$this->object->set_transfer_model(new TestStaticTransferModel(20, '2017-10-01'));

		$account = new \stdClass();
		$account->id = 20;
		$account->balance = 600.00;
		$account->member_id = 10;
		$account->member = new \stdClass();
		$account->member->entrance_date = '2017-09-01';
		$account->member->entrance_fee = 1000;
		$account->member->debt_payment_rate = 500;

		$res = $this->object->get_expiration_info($account);
		$this->assertFalse($res->shortened);
		$this->assertEquals('2017-11-30', $res->expiration_date);
	}

}

/**
 * Special ExpirationCalcService that allows to replace models for testing
 * purposes.
 */
class ConfigurableTestExpirationCalcService extends ExpirationCalcService
{
	public function set_transfer_model($transfer_model)
	{
		$this->transfer_model = $transfer_model;
	}

	public function set_fee_model($fee_model)
	{
		$this->fee_model = $fee_model;
	}

	public function set_device_model($device_model)
	{
		$this->device_model = $device_model;
	}
}

class TestStaticTransferModel
{
	private $account_id;
	private $last_transfer_date;

	function __construct($account_id, $last_transfer_date)
	{
		$this->account_id = $account_id;
		$this->last_transfer_date = $last_transfer_date;
	}

	public function get_last_transfer_datetime_of_account($account_id)
	{
		return $this->account_id == $account_id ? $this->last_transfer_date : NULL;
	}
}

class TestStaticFeeModel
{
	private $member_id;
	private $fee;

	function __construct($member_id, $fee)
	{
		$this->member_id = $member_id;
		$this->fee = $fee;
	}

	public function get_min_fromdate_fee_by_type($fee_name)
	{
		return '0000-00-00';
	}

	public function get_max_todate_fee_by_type($fee_name)
	{
		return '9999-12-31';
	}

	public function get_regular_member_fee_by_member_date($member_id, $date)
	{
		return ($this->member_id == $member_id) ? $this->fee : 0;
	}
}
