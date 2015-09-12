<?php

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
 * Integration tests for Json_Fio_Bank_Statement_File_Importer library class
 * that uses FreenetIS instalation with data located in importers.sql file.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class Json_Fio_Bank_Statement_File_ImporterTest extends AbstractItCase
{
    /**
     * FIO bank account ID - we know it from SQL script.
     */
    const FIO_ACCOUNT_ID = 1;

    /**
     * User who made import.
     */
    const USER_ID = 1;

    /**
     * @var Bank_account_Model
     */
    private $fio_account;

    /**
     * Clears database and set init data located in SQL script file.
     */
    protected function setUp()
    {
        $it_db_script = __DIR__ . '/importers.sql';
        self::$services->injectCoreDatabase()
                ->run_file(self::$connection, $it_db_script);
        self::reset_url_settings_to_current();
        // load fio account
        $this->fio_account = ORM::factory('bank_account', self::FIO_ACCOUNT_ID);
        $this->assertEquals(self::FIO_ACCOUNT_ID, $this->fio_account->id);
    }

    /**
     * Test local JSON listing file import to FIO bank account of association.
     * This integration test is able to test following features:
     *
     * - pairing of payments by their VS
     * - double-entry transfers that are made from imported bank payments
     * - double-entry transactional transfers
     * - e-mail notification about accepted payment
     * - JSON listing parsing
     */
    public function testImport()
    {
        // prepare global and bank double-entry accounts
        $member_fees = ORM::factory('account')->get_account_by_attribute(
                Account_attribute_Model::MEMBER_FEES);
        $operating = ORM::factory('account')->get_account_by_attribute(
                Account_attribute_Model::OPERATING);
        $fio_ba = $this->fio_account->get_related_account_by_attribute_id(
                Account_attribute_Model::BANK);


        /* Invalid test - bank account not match */
        try
        {
            $json_invalid = __DIR__ .'/fio.it.invalid_account_number.json';
            Bank_Statement_File_Importer::import($this->fio_account,
                $json_invalid, 'json', 1, FALSE, FALSE);
            $this->fail('should fail on bank account match');
        }
        catch (Exception $ex)
        {}

        /* Valid test */
        $json_valid = __DIR__ .'/fio.it.json';
        $statement = Bank_Statement_File_Importer::import($this->fio_account,
                $json_valid, 'json', self::USER_ID, TRUE, FALSE);
        $this->assertNotEmpty($statement->id);
        $this->assertEquals($this->fio_account->id, $statement->bank_account_id);
        $this->assertEquals(195.00, $statement->opening_balance, '', 0.0001);
        $this->assertEquals(196.00, $statement->closing_balance, '', 0.0001);
        $this->assertEquals(strtotime('2012-07-27'), strtotime($statement->from));
        $this->assertEquals(strtotime('2012-07-29'), strtotime($statement->to));
        $this->assertNotEmpty($statement->type);
        $this->assertEquals(1, $statement->user_id);
        $this->assertNotEmpty($statement->bank_transfers);
        $trans = $statement->bank_transfers->as_array();
        $this->assertEquals(1, count($trans));

        //// 1 transaction: incoming valid VS and from bank account that already
        //// exists in DB
        $bt1 = $trans[0];
        // bank transfer details
        $this->assertEmpty($bt1->comment);
        $this->assertEquals(1148734530, $bt1->transaction_code);
        $this->assertEquals(0, $bt1->number);
        $this->assertEmpty($bt1->constant_symbol);
        $this->assertEquals('654123', $bt1->variable_symbol);
        $this->assertEmpty($bt1->specific_symbol);
        // origin account (existing with assigned member)
        $this->assertEquals(2, $bt1->origin->id);
        $this->assertEquals('2001345678', $bt1->origin->account_nr);
		$this->assertEquals('0500', $bt1->origin->bank_nr);
		$this->assertEquals(2, $bt1->origin->member_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt1->destination_id);
        // transfer is identified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt1->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt1->transfer->destination->id);
        $this->assertEquals(2, $bt1->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt1->transfer->user_id);
        $this->assertNull($bt1->transfer->type);
        $this->assertEquals(strtotime('2012-07-27'),
                strtotime($bt1->transfer->datetime));
        $this->assertNotEmpty($bt1->transfer->creation_datetime);
        $this->assertEmpty($bt1->transfer->text);
        $this->assertEquals(1.00, $bt1->transfer->amount, '', 0.0001);
        $this->assertNull($bt1->transfer->previous_transfer_id);
        // depend transfers
        $bt1_deps = $bt1->transfer->get_dependent_transfers()->as_array();
        $this->assertEquals(2, count($bt1_deps));
        $bt1_credit = $bt1->transfer->member->get_credit_account();
        $this->assertNotEmpty($bt1_credit->id);
        foreach ($bt1_deps as $dep)
        {
            // shared
            $this->assertEquals($bt1->transfer->member_id, $dep->member_id);
            $this->assertEquals($bt1->transfer->id, $dep->previous_transfer_id);
            $this->assertEquals(self::USER_ID, $dep->user_id);
            $this->assertEquals(strtotime('2012-07-27'), strtotime($dep->datetime));
            // different for each transaction
            if ($dep->amount == 30) // transact fee
            {
                $this->assertEquals($bt1_credit->id, $dep->origin_id);
                $this->assertEquals($operating->id, $dep->destination_id);
            }
            else // payment
            {
                $this->assertEquals($this->fio_account->id, $dep->origin_id);
                $this->assertEquals($bt1_credit->id, $dep->destination_id);
            }
        }

        //// e-mail notification, only one should be sended to kucera other
        /// because bacigala do not have e-mail
        $eq = new Email_queue_Model;
        $emails = $eq->find_all()->as_array();
        $this->assertEquals(1, count($emails));
        $this->assertEquals('milan@kucera.cz', $emails[0]->to);
        $this->assertEquals('Hello Milan Kučera,<br /><br />'
                . 'Your payment has been accepted into FreenetIS.<br/>'
                . 'Your current balance is: -29,-', $emails[0]->body);
    }

}
