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

/**
 * CSV parser result.
 * 
 * @author Ondřej Fibich <fibich@freenetis.org>
 */
class FioCsvStatement
{
    /**
     * Account number
     *
     * @var integer
     */
    public $account_nr = 0;

    /**
     * Bank number
     *
     * @var integer
     */
    public $bank_nr;

    /**
     * Statement period from date in format YYYY-MM-DD
     *
     * @var string
     */
    public $from;

    /**
     * Statement period to date in format YYYY-MM-DD
     *
     * @var string
     */
    public $to;

    /**
     * Openning balance
     *
     * @var double
     */
    public $opening_balance;

    /**
     * Closing balance
     *
     * @var double
     */
    public $closing_balance;

    /**
     * Items of statement that contain array of associative array where
     * each item contains fields defined by FioCsvParser::$field array keys.
     *
     * @var array
     */
    public $items;

    /**
     * Returns account number as array
     *
     * @return associative array('account_nr'=>'XXXXXXX', 'bank_nr' => 'YYYY'
     */
    public function getAccountNumberAsArray()
    {
        return array
        (
            'account_nr' => $this->account_nr,
            'bank_nr' => $this->bank_nr
        );
    }

    /**
     * Returns associative array containing important listing header information.
     * Must be called after parsing.
     *
     * @author Jiri Svitak
     * @return header information
     */
    public function getListingHeader()
    {
        // account number
        $header = $this->getAccountNumberAsArray();
        // date from to
        $header['from'] = $this->from;
        $header['to'] = $this->to;
        // opening and closing balance
        $header['opening_balance'] = $this->opening_balance;
        $header['closing_balance'] = $this->closing_balance;

        return $header;
    }

}
