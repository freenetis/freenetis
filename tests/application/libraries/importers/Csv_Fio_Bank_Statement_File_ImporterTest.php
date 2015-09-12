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
 * Integration tests for Csv_Fio_Bank_Statement_File_Importer library class
 * that uses FreenetIS instalation with data located in importers.sql file.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class Csv_Fio_Bank_Statement_File_ImporterTest extends AbstractItCase
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
     * Test local CSV listing file import to FIO bank account of association.
     * This integration test is able to test following features:
     *
     * - pairing of payments by their VS
     * - double-entry transfers that are made from imported bank payments
     * - double-entry transactional transfers
     * - e-mail notification about accepted payment
     * - assigning of unknown bank account to memeber according to payment
     *   received from that account with VS that match a member
     * - duplicity transactions detection
     * - integration listing check
     * - CSV listing parsing
     */
    public function testImport()
    {
        // prepare global and bank double-entry accounts
        $suppliers = ORM::factory('account')->get_account_by_attribute(
                Account_attribute_Model::SUPPLIERS);
        $member_fees = ORM::factory('account')->get_account_by_attribute(
                Account_attribute_Model::MEMBER_FEES);
        $operating = ORM::factory('account')->get_account_by_attribute(
                Account_attribute_Model::OPERATING);
        $fio_ba = $this->fio_account->get_related_account_by_attribute_id(
                Account_attribute_Model::BANK);
        $fio_interests = $this->fio_account->get_related_account_by_attribute_id(
                Account_attribute_Model::BANK_INTERESTS);


        /* Invalid test - bank account not match */
        try
        {
            $csv_invalid = __DIR__ .'/fio.it.invalid_account_number.csv';
            Bank_Statement_File_Importer::import($this->fio_account,
                $csv_invalid, 'csv', 1, FALSE, FALSE);
            $this->fail('should fail on bank account match');
        }
        catch (Exception $ex)
        {}

        /* Invalid test - invalid sum */
        try
        {
            $csv_invalid = __DIR__ .'/fio.it.invalid_sum.csv';
            Bank_Statement_File_Importer::import($this->fio_account,
                $csv_invalid, 'csv', self::USER_ID, FALSE, FALSE);
            $this->fail('should fail on bank account match');
        }
        catch (Exception $ex)
        {}

        /* Valid test */
        $csv_valid = __DIR__ .'/fio.it.csv';
        $statement = Bank_Statement_File_Importer::import($this->fio_account,
                $csv_valid, 'csv', self::USER_ID, TRUE, FALSE);
        $this->assertNotEmpty($statement->id);
        $this->assertEquals($this->fio_account->id, $statement->bank_account_id);
        $this->assertEquals(12345.67, $statement->opening_balance, '', 0.0001);
        $this->assertEquals(16815.78, $statement->closing_balance, '', 0.0001);
        $this->assertEquals('2010-12-16', $statement->from);
        $this->assertEquals('2011-01-01', $statement->to);
        $this->assertNotEmpty($statement->type);
        $this->assertEquals(1, $statement->user_id);
        $this->assertNotEmpty($statement->bank_transfers);
        $trans = $statement->bank_transfers->as_array();
        $this->assertEquals(9, count($trans));

        //// 1 transaction: incoming with invalid VS from account not present
        //// in DB
        $bt1 = $trans[0];
        // bank transfer details
        $this->assertEquals('přichozí platba s neplatným VS', $bt1->comment);
        $this->assertEquals(1115992591, $bt1->transaction_code);
        $this->assertEquals(0, $bt1->number);
        $this->assertEquals('78945', $bt1->constant_symbol);
        $this->assertEquals('215', $bt1->variable_symbol);
        $this->assertEquals('7845', $bt1->specific_symbol);
        // origin account (new without assigned member)
        $this->assertEquals('int78', $bt1->origin->name);
        $this->assertEquals('670100-2202442842', $bt1->origin->account_nr);
		$this->assertEquals('6210', $bt1->origin->bank_nr);
		$this->assertNull($bt1->origin->member_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt1->destination_id);
        // transfer is unidentified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt1->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt1->transfer->destination->id);
        $this->assertNull($bt1->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt1->transfer->user_id);
        $this->assertNull($bt1->transfer->type);
        $this->assertEquals(strtotime('2010-12-16'),
                strtotime($bt1->transfer->datetime));
        $this->assertNotEmpty($bt1->transfer->creation_datetime);
        $this->assertEquals('přichozí platba s neplatným VS', $bt1->transfer->text);
        $this->assertEquals(2111.11, $bt1->transfer->amount, '', 0.0001);
        $this->assertNull($bt1->transfer->previous_transfer_id);
        // depend transfers
        $this->assertEmpty(0, $bt1->transfer->get_dependent_transfers()->count());

        //// 2 transaction: incoming valid VS and from bank account that already
        //// exists in DB
        $bt2 = $trans[1];
        // bank transfer details
        $this->assertEquals('přichozí platba s VS a existujicim uctem v DB '
                . '(clen Kucera)', $bt2->comment);
        $this->assertEquals(1115992592, $bt2->transaction_code);
        $this->assertEquals(1, $bt2->number);
        $this->assertEmpty($bt2->constant_symbol);
        $this->assertEquals('654123', $bt2->variable_symbol);
        $this->assertEmpty($bt2->specific_symbol);
        // origin account (existing with assigned member)
        $this->assertEquals(2, $bt2->origin->id);
        $this->assertEquals('2001345678', $bt2->origin->account_nr);
		$this->assertEquals('0500', $bt2->origin->bank_nr);
		$this->assertEquals(2, $bt2->origin->member_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt2->destination_id);
        // transfer is identified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt2->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt2->transfer->destination->id);
        $this->assertEquals(2, $bt2->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt2->transfer->user_id);
        $this->assertNull($bt2->transfer->type);
        $this->assertEquals(strtotime('2010-12-17'),
                strtotime($bt2->transfer->datetime));
        $this->assertNotEmpty($bt2->transfer->creation_datetime);
        $this->assertEquals('přichozí platba s VS a existujicim uctem v DB '
                . '(clen Kucera)', $bt2->transfer->text);
        $this->assertEquals(930, $bt2->transfer->amount, '', 0.0001);
        $this->assertNull($bt2->transfer->previous_transfer_id);
        // depend transfers
        $bt2_deps = $bt2->transfer->get_dependent_transfers()->as_array();
        $this->assertEquals(2, count($bt2_deps));
        $bt2_credit = $bt2->transfer->member->get_credit_account();
        $this->assertNotEmpty($bt2_credit->id);
        foreach ($bt2_deps as $dep)
        {
            // shared
            $this->assertEquals($bt2->transfer->member_id, $dep->member_id);
            $this->assertEquals($bt2->transfer->id, $dep->previous_transfer_id);
            $this->assertEquals(self::USER_ID, $dep->user_id);
            $this->assertEquals(strtotime('2010-12-17'), strtotime($dep->datetime));
            // different for each transaction
            if ($dep->amount == 30) // transact fee
            {
                $this->assertEquals($bt2_credit->id, $dep->origin_id);
                $this->assertEquals($operating->id, $dep->destination_id);
            }
            else // payment
            {
                $this->assertEquals($this->fio_account->id, $dep->origin_id);
                $this->assertEquals($bt2_credit->id, $dep->destination_id);
            }
        }

        //// 3 transaction: outbound payment
        $bt3 = $trans[2];
        // bank transfer details
        $this->assertEmpty($bt3->comment); // no comment for this type
        $this->assertEquals(1115992593, $bt3->transaction_code);
        $this->assertEquals(2, $bt3->number);
        $this->assertEmpty($bt3->constant_symbol);
        $this->assertEquals('878487', $bt3->variable_symbol);
        $this->assertEquals('789', $bt3->specific_symbol);
        // origin account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt3->origin_id);
        // destination account (new without assigned member) with name as
        // identification field because nazev_protiuctu was empty
        $this->assertEquals('identifikace', $bt3->destination->name);
        $this->assertEquals('25445451', $bt3->destination->account_nr);
		$this->assertEquals('4400', $bt3->destination->bank_nr);
		$this->assertNull($bt3->destination->member_id);
        // transfer is outbound (FIO -> SUPPLIERS)
        $this->assertEquals($fio_ba->id, $bt3->transfer->origin->id);
        $this->assertEquals($suppliers->id, $bt3->transfer->destination->id);
        $this->assertNull($bt3->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt3->transfer->user_id);
        $this->assertNull($bt3->transfer->type);
        $this->assertEquals(strtotime('2010-12-17'),
                strtotime($bt3->transfer->datetime));
        $this->assertNotEmpty($bt3->transfer->creation_datetime);
        $this->assertEquals('odchozí platba', $bt3->transfer->text);
        $this->assertEquals(1000.00, $bt3->transfer->amount, '', 0.0001);
        $this->assertNull($bt3->transfer->previous_transfer_id);
        // depend transfers
        $this->assertEmpty(0, $bt3->transfer->get_dependent_transfers()->count());

        //// 4 transaction: bank interest
        $bt4 = $trans[3];
        // bank transfer details
        $this->assertEmpty($bt4->comment); // no comment for this type
        $this->assertEquals(1115992594, $bt4->transaction_code);
        $this->assertEquals(3, $bt4->number);
        $this->assertEmpty($bt4->constant_symbol);
        $this->assertEmpty($bt4->variable_symbol);
        $this->assertEmpty($bt4->specific_symbol);
        // origin not exists (some internal bank account)
        $this->assertNull($bt4->origin_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt4->destination_id);
        // transfer is outbound (BANK INTERESTS -> FIO)
        $this->assertEquals($fio_interests->id, $bt4->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt4->transfer->destination->id);
        $this->assertNull($bt4->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt4->transfer->user_id);
        $this->assertNull($bt4->transfer->type);
        $this->assertEquals(strtotime('2010-12-31'),
                strtotime($bt4->transfer->datetime));
        $this->assertNotEmpty($bt4->transfer->creation_datetime);
        $this->assertEquals('Připsaný úrok', $bt4->transfer->text); // static text
        $this->assertEquals(4.00, $bt4->transfer->amount, '', 0.0001);
        $this->assertNull($bt4->transfer->previous_transfer_id);
        // depend transfers
        $this->assertEmpty(0, $bt4->transfer->get_dependent_transfers()->count());

        //// 5 transaction: incoming deposit without VS
        $bt5 = $trans[4];
        // bank transfer details
        $this->assertEquals('platba pokladnou bez VS', $bt5->comment);
        $this->assertEquals(1115992595, $bt5->transaction_code);
        $this->assertEquals(4, $bt5->number);
        $this->assertEmpty($bt5->constant_symbol);
        $this->assertEmpty($bt5->variable_symbol);
        $this->assertEquals('0558', $bt5->specific_symbol);
        // no origin account
        $this->assertNull($bt5->origin_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt5->destination_id);
        // transfer is unidentified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt5->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt5->transfer->destination->id);
        $this->assertNull($bt5->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt5->transfer->user_id);
        $this->assertNull($bt5->transfer->type);
        $this->assertEquals(strtotime('2010-12-31'),
                strtotime($bt5->transfer->datetime));
        $this->assertNotEmpty($bt5->transfer->creation_datetime);
        $this->assertEquals('platba pokladnou bez VS', $bt5->transfer->text);
        $this->assertEquals(1200.00, $bt5->transfer->amount, '', 0.0001);
        $this->assertNull($bt5->transfer->previous_transfer_id);
        // depend transfers
        $this->assertEmpty(0, $bt5->transfer->get_dependent_transfers()->count());

        //// 6 transaction: incoming deposit with valid VS
        $bt6 = $trans[5];
        // bank transfer details
        $this->assertEquals('platba pokladnou s validnim VS (clen Emanuel Bacigala)',
                $bt6->comment);
        $this->assertEquals(1115992596, $bt6->transaction_code);
        $this->assertEquals(5, $bt6->number);
        $this->assertEmpty($bt6->constant_symbol);
        $this->assertEquals('5462317894', $bt6->variable_symbol);
        $this->assertEquals('0558', $bt6->specific_symbol);
        // no origin account
        $this->assertNull($bt6->origin_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt6->destination_id);
        // transfer is unidentified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt6->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt6->transfer->destination->id);
        $this->assertEquals(3 /* bacigala */, $bt6->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt6->transfer->user_id);
        $this->assertNull($bt6->transfer->type);
        $this->assertEquals(strtotime('2010-12-31'),
                strtotime($bt6->transfer->datetime));
        $this->assertNotEmpty($bt6->transfer->creation_datetime);
        $this->assertEquals('platba pokladnou s validnim VS (clen Emanuel Bacigala)',
                $bt6->transfer->text);
        $this->assertEquals(1211.00, $bt6->transfer->amount, '', 0.0001);
        $this->assertNull($bt6->transfer->previous_transfer_id);
        // depend transfers
        $bt6_deps = $bt6->transfer->get_dependent_transfers()->as_array();
        $this->assertEquals(2, count($bt6_deps));
        $bt6_credit = $bt6->transfer->member->get_credit_account();
        $this->assertNotEmpty($bt6_credit->id);
        foreach ($bt6_deps as $dep)
        {
            // shared
            $this->assertEquals($bt6->transfer->member_id, $dep->member_id);
            $this->assertEquals($bt6->transfer->id, $dep->previous_transfer_id);
            $this->assertEquals(self::USER_ID, $dep->user_id);
            $this->assertEquals(strtotime('2010-12-31'), strtotime($dep->datetime));
            // different for each transaction
            if ($dep->amount == 30) // transact fee
            {
                $this->assertEquals($bt6_credit->id, $dep->origin_id);
                $this->assertEquals($operating->id, $dep->destination_id);
            }
            else // payment
            {
                $this->assertEquals($this->fio_account->id, $dep->origin_id);
                $this->assertEquals($bt6_credit->id, $dep->destination_id);
            }
        }

        //// 7 transaction: incoming deposit with invalid VS
        $bt7 = $trans[6];
        // bank transfer details
        $this->assertEquals('platba pokladnou s neplatnym VS', $bt7->comment);
        $this->assertEquals(1115992597, $bt7->transaction_code);
        $this->assertEquals(6, $bt7->number);
        $this->assertEmpty($bt7->constant_symbol);
        $this->assertEquals('5462317895', $bt7->variable_symbol);
        $this->assertEquals('0558', $bt7->specific_symbol);
        // no origin account
        $this->assertNull($bt7->origin_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt7->destination_id);
        // transfer is unidentified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt7->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt7->transfer->destination->id);
        $this->assertNull($bt7->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt7->transfer->user_id);
        $this->assertNull($bt7->transfer->type);
        $this->assertEquals(strtotime('2011-01-01'),
                strtotime($bt7->transfer->datetime));
        $this->assertNotEmpty($bt7->transfer->creation_datetime);
        $this->assertEquals('platba pokladnou s neplatnym VS', $bt7->transfer->text);
        $this->assertEquals(11.00, $bt7->transfer->amount, '', 0.0001);
        $this->assertNull($bt7->transfer->previous_transfer_id);
        // depend transfers
        $this->assertEmpty(0, $bt7->transfer->get_dependent_transfers()->count());

        //// 8 transaction: member payment from Emanuel Bacigala with valid VS
        //// but from bank account that is already in system without assigned
        //// member so after import this bank account should be assigned to
        //// Bacigala
        $bt8 = $trans[7];
        // bank transfer details
        $this->assertEquals('prevod s VS (clen E.B.), ale b.ucet uz existuje '
                . '(bez member_id)', $bt8->comment);
        $this->assertEquals(1115992598, $bt8->transaction_code);
        $this->assertEquals(7, $bt8->number);
        $this->assertEmpty($bt8->constant_symbol);
        $this->assertEquals('5462317894', $bt8->variable_symbol);
        $this->assertEmpty($bt8->specific_symbol);
        // origin account (existing with assigned member)
        $this->assertEquals(4, $bt8->origin->id); // from importers.sql
        $this->assertEquals('Neprirazeny ucet', $bt8->origin->name);
        $this->assertEquals('78974515545', $bt8->origin->account_nr);
		$this->assertEquals('2100', $bt8->origin->bank_nr);
		$this->assertEquals(3, $bt8->origin->member_id); // Bacigala
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt8->destination_id);
        // transfer is identified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt8->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt8->transfer->destination->id);
        $this->assertEquals(3, $bt8->transfer->member_id); // Bacigala
        $this->assertEquals(self::USER_ID, $bt8->transfer->user_id);
        $this->assertNull($bt8->transfer->type);
        $this->assertEquals(strtotime('2011-01-01'),
                strtotime($bt8->transfer->datetime));
        $this->assertNotEmpty($bt8->transfer->creation_datetime);
        $this->assertEquals('prevod s VS (clen E.B.), ale b.ucet uz existuje '
                . '(bez member_id)', $bt8->transfer->text);
        $this->assertEquals(1.00, $bt8->transfer->amount, '', 0.0001);
        $this->assertNull($bt8->transfer->previous_transfer_id);
        // depend transfers
        $bt8_deps = $bt8->transfer->get_dependent_transfers()->as_array();
        $this->assertEquals(2, count($bt8_deps));
        $bt8_credit = $bt8->transfer->member->get_credit_account();
        $this->assertNotEmpty($bt8_credit->id);
        foreach ($bt8_deps as $dep)
        {
            // shared
            $this->assertEquals($bt8->transfer->member_id, $dep->member_id);
            $this->assertEquals($bt8->transfer->id, $dep->previous_transfer_id);
            $this->assertEquals(self::USER_ID, $dep->user_id);
            $this->assertEquals(strtotime('2011-01-01'), strtotime($dep->datetime));
            // different for each transaction
            if ($dep->amount == 30) // transact fee
            {
                $this->assertEquals($bt8_credit->id, $dep->origin_id);
                $this->assertEquals($operating->id, $dep->destination_id);
            }
            else // payment
            {
                $this->assertEquals($this->fio_account->id, $dep->origin_id);
                $this->assertEquals($bt8_credit->id, $dep->destination_id);
            }
        }

        //// 9 transaction: member payment from Emanuel Bacigala with valid VS
        //// but from bank account of different member so after import this bank
        //// account should NOT be assigned to Bacigala
        $bt9 = $trans[8];
        // bank transfer details
        $this->assertEquals('prevod s VS (clen E.B.), ale b.ucet uz existuje',
                $bt9->comment);
        $this->assertEquals(1115992599, $bt9->transaction_code);
        $this->assertEquals(8, $bt9->number);
        $this->assertEmpty($bt9->constant_symbol);
        $this->assertEquals('5462317894', $bt9->variable_symbol);
        $this->assertEmpty($bt9->specific_symbol);
        // origin account (existing with assigned member)
        $this->assertEquals('Kučera Milan', $bt9->origin->name);
        $this->assertEquals('2000001245', $bt9->origin->account_nr);
		$this->assertEquals('2100', $bt9->origin->bank_nr);
		$this->assertEquals(2, $bt9->origin->member_id); // Kucera
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt9->destination_id);
        // transfer is identified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt9->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt9->transfer->destination->id);
        $this->assertEquals(3, $bt9->transfer->member_id); // Bacigala
        $this->assertEquals(self::USER_ID, $bt9->transfer->user_id);
        $this->assertNull($bt9->transfer->type);
        $this->assertEquals(strtotime('2011-01-01'),
                strtotime($bt9->transfer->datetime));
        $this->assertNotEmpty($bt9->transfer->creation_datetime);
        $this->assertEquals('prevod s VS (clen E.B.), ale b.ucet uz existuje',
                $bt9->transfer->text);
        $this->assertEquals(2.00, $bt9->transfer->amount, '', 0.0001);
        $this->assertNull($bt9->transfer->previous_transfer_id);
        // depend transfers
        $bt9_deps = $bt9->transfer->get_dependent_transfers()->as_array();
        $this->assertEquals(2, count($bt9_deps));
        $bt9_credit = $bt9->transfer->member->get_credit_account();
        $this->assertNotEmpty($bt9_credit->id);
        foreach ($bt9_deps as $dep)
        {
            // shared
            $this->assertEquals($bt9->transfer->member_id, $dep->member_id);
            $this->assertEquals($bt9->transfer->id, $dep->previous_transfer_id);
            $this->assertEquals(self::USER_ID, $dep->user_id);
            $this->assertEquals(strtotime('2011-01-01'), strtotime($dep->datetime));
            // different for each transaction
            if ($dep->amount == 30) // transact fee
            {
                $this->assertEquals($bt9_credit->id, $dep->origin_id);
                $this->assertEquals($operating->id, $dep->destination_id);
            }
            else // payment
            {
                $this->assertEquals($this->fio_account->id, $dep->origin_id);
                $this->assertEquals($bt9_credit->id, $dep->destination_id);
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
                . 'Your current balance is: 900,-', $emails[0]->body);

        /* Invalid test - duplicates from previous import */
        try
        {
            $csv_invalid = __DIR__ .'/fio.it.invalid_duplications.csv';
            Bank_Statement_File_Importer::import($this->fio_account,
                $csv_invalid, 'csv', self::USER_ID, FALSE, FALSE);
            $this->fail('should fail on duplicates');
        }
        catch (Exception $ex)
        {}

        /* Valid test2 with new parser */
        $csv_valid_new = __DIR__ .'/fio_new.it.csv';
        $statement_new = Bank_Statement_File_Importer::import($this->fio_account,
                $csv_valid_new, 'csv', self::USER_ID, FALSE, FALSE);
        $this->assertNotEmpty($statement_new->id);
        $this->assertEquals($this->fio_account->id, $statement_new->bank_account_id);
        $this->assertEmpty($statement_new->opening_balance);
        $this->assertEmpty($statement_new->closing_balance);
        $this->assertEmpty($statement_new->from);
        $this->assertEmpty($statement_new->to);
        $this->assertNotEmpty($statement_new->type);
        $this->assertEquals(1, $statement_new->user_id);
        $this->assertNotEmpty($statement_new->bank_transfers);
        $trans_new = $statement_new->bank_transfers->as_array();
        $this->assertEquals(1, count($trans_new));

        //// 1 transaction: incoming with invalid VS from account not present
        //// in DB
        $bt1 = $trans_new[0];
        // bank transfer details
        $this->assertEquals('Ing. Jiří Novák - roční členské příspěvky',
                $bt1->comment);
        $this->assertEquals(7150326538, $bt1->transaction_code);
        $this->assertEquals(0, $bt1->number);
        $this->assertEmpty($bt1->constant_symbol);
        $this->assertEquals('774074794', $bt1->variable_symbol);
        $this->assertEmpty($bt1->specific_symbol);
        // origin account (new without assigned member)
        $this->assertEquals('NOVÁK JIŘÍ', $bt1->origin->name);
        $this->assertEquals('7894561678', $bt1->origin->account_nr);
		$this->assertEquals('600', $bt1->origin->bank_nr);
		$this->assertNull($bt1->origin->member_id);
        // destination account is FIO
        $this->assertEquals(self::FIO_ACCOUNT_ID, $bt1->destination_id);
        // transfer is unidentified (MEMBER_FEES -> BANK)
        $this->assertEquals($member_fees->id, $bt1->transfer->origin->id);
        $this->assertEquals($fio_ba->id, $bt1->transfer->destination->id);
        $this->assertNull($bt1->transfer->member_id);
        $this->assertEquals(self::USER_ID, $bt1->transfer->user_id);
        $this->assertNull($bt1->transfer->type);
        $this->assertEquals(strtotime('2015-03-02'),
                strtotime($bt1->transfer->datetime));
        $this->assertNotEmpty($bt1->transfer->creation_datetime);
        $this->assertEquals('Ing. Jiří Novák - roční členské příspěvky',
                $bt1->transfer->text);
        $this->assertEquals(1800.4, $bt1->transfer->amount, '', 0.0001);
        $this->assertNull($bt1->transfer->previous_transfer_id);
        // depend transfers
        $this->assertEmpty(0, $bt1->transfer->get_dependent_transfers()->count());
    }

}
