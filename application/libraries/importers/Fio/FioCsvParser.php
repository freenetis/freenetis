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

require_once __DIR__ . '/FioCsvParserUtil.php';
require_once __DIR__ . '/FioCsvStatement.php';

/**
 * Auxiliary class for parsing CSV bank account listings from czech bank
 * "FIO banka". Listing may be obtain from from the ebanking web application
 * of FIO bank.
 * The CSV format looks like this:
 *
 * @author Petr Hruska, Lukas Turek, Tomas Dulik, Jiri Svitak
 * @todo i18n of error messages
 */
class FioCsvParser
{
    /**
     * CSV colum separator.
     */
    const CSV_COL_DELIM = ";";

    /**
     * Bank account number regex.
     */
    const ACCOUNT_NUMBER_RE = "/^(\d+)\/(\d+)$/";

    /**
     * Last line date string that is used for end of statement detection.
     */
    const LAST_LINE_DATE_VALUE = 'Suma';

    /**
     * Default CSV file encoding.
     */
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * All fields available must be used and sorted alphabetically
     *
     * @var array[string]
     */
    private static $fields = array
    (
        'datum' => 'Datum',
        'id_pohybu' => 'ID pohybu',
        'id_pokynu' => 'ID pokynu',
        'kod_banky' => 'Kód banky',
        'ks' => 'KS',
        'mena' => 'Měna',
        'nazev_banky' => 'Název banky',
        'nazev_protiuctu' => 'Název protiúčtu',
        'castka' => 'Objem',
        'protiucet' => 'Protiúčet',
        'provedl' => 'Provedl',
        'prevod' => 'Převod',
        'ss' => 'SS',
        'typ' => 'Typ',
        'upresneni' => 'Upřesnění',
        'identifikace' => 'Uživatelská identifikace',
        'vs' => 'VS',
        'zprava' => 'Zpráva pro příjemce',
    );

    /**
     * FIO statement columns fields names.
     *
     * @return array
     */
    public static function get_fields()
    {
        return self::$fields;
    }

    /**
     * Parse bank statement in CSV format that is passed as string.
     *
     * @param string $csv string containing the original csv file.
     * @param string $charset optional charset name of file, default is UTF-8
     * @return FioCsvStatement result
     * @throws Exception on parse error
     */
    public function parse($csv, $charset = self::DEFAULT_CHARSET)
    {
        $result = new FioCsvStatement();
        $sum = 0;
        $keys = array_keys(self::$fields);
        $lines = FioCsvParserUtil::transformFileToLineArray($csv, $charset);
        // check each line of CSV
        for ($i = 0; $i < count($lines); $i++)
        {
            $line = trim($lines[$i]);
            // header
            if ($i < 10)
            {
                $this->parseHeaderLine($result, $line, $i);
                continue;
            }
            // data lines
            $cols = $this->parseLine($line, $keys);
            // check last sum line, if last line encountered, then we are done
            if ($cols['datum'] == self::LAST_LINE_DATE_VALUE)
            {
                $this->checkIntegrity($cols, $sum);
                return $result;
            }
            // add data row
            $sum += $cols['castka'];
            $result->items[] = $cols;
        }
        throw new Exception('CSV soubor není kompletní.');
    }

    /**
     * Parse given line of statement header.
     *
     * @param FioCsvStatement $result result for attaching of header values
     * @param string $line_str raw CSV line
     * @param integer $number number line from zero
     */
    private function parseHeaderLine(FioCsvStatement &$result, $line_str, $number)
    {
        // first line of file - get the account number
        if ($number == 0)
        {
            $account_number = $this->parseBankAccountNumberLine($line_str);
            $result->account_nr = $account_number['account_nr'];
            $result->bank_nr = $account_number['bank_nr'];
        }
        // 4th line, account date from and date to
        else if ($number == 3)
        {
            $period = $this->parseBankStatementPeriodLine($line_str);
            $result->from = $period[0];
            $result->to = $period[1];
        }
        // 5th line, opening balance
        else if ($number == 4)
        {
            $result->opening_balance = $this->parseOpenningBalance($line_str);
        }
        // 6th line, closing balance
        else if ($number == 5)
        {
            $result->closing_balance = $this->parseClosingBalance($line_str);
        }
        // 10th line, checking column header names and count
        else if ($number == 9)
        {
            $this->checkHeaders($line_str);
        }
    }

    /**
     * Parse bank account number on provided line.
     *
     * @param string $line_str raw CSV line
     * @return array account bank number (key "account_nr") and bank number
     *      (key "bank_nr")
     * @throws InvalidArgumentException on invalid line data
     */
    private function parseBankAccountNumberLine($line_str)
    {
        $line_arr = explode('"', $line_str);
        if (count($line_arr) < 2)
        {
            throw new InvalidArgumentException("Nemohu najít číslo účtu na "
                    . "prvním řádku.");
        }
        $account_number = $line_arr[1];
        $matches = NULL;
        if (!preg_match(self::ACCOUNT_NUMBER_RE, $account_number, $matches))
        {
            throw new InvalidArgumentException("Nemohu rozlišit číslo účtu a "
                    . "kód banky na prvním řádku.");
        }
        return array
        (
            'account_nr' => $matches[1],
            'bank_nr' => $matches[2]
        );
    }

    /**
     * Parse line with statement period information balance and set it.
     *
     * @param string $line_str raw CSV line
     * @return array period, first value is from and second is to
     * @throws InvalidArgumentException on invalid line data
     */
    private function parseBankStatementPeriodLine($line_str)
    {
        $line_arr = explode(":", $line_str);
        if (count($line_arr) < 2)
        {
            throw new InvalidArgumentException("Nemohu najít datum od a do na "
                    . "čtvrtém řádku.");
        }
        $period_parts = array_map('trim', explode('-', $line_arr[1]));
        if (count($period_parts) != 2)
        {
            throw new InvalidArgumentException("Nemohu najít datum od a do na "
                    . "čtvrtém řádku.");
        }
        return array
        (
            FioCsvParserUtil::parseDate($period_parts[0]),
            FioCsvParserUtil::parseDate($period_parts[1])
        );
    }

    /**
     * Parse line with openning balance.
     *
     * @param string $line_str raw CSV line
     * @return double openning balance
     * @throws InvalidArgumentException on invalid line data
     */
    private function parseOpenningBalance($line_str)
    {
        $line_arr = explode(":", $line_str);
        if (count($line_arr) < 2)
        {
            throw new Exception("Nemohu najít počáteční zůstatek.");
        }
        $amount = str_replace(" CZK", "", $line_arr[1]);
        return FioCsvParserUtil::parseAmount($amount);
    }

    /**
     * Parse line with closing balance.
     *
     * @param string $line_str raw CSV line
     * @return double closing balance
     * @throws InvalidArgumentException on invalid line data
     */
    private function parseClosingBalance($line_str)
    {
        $line_arr = explode(":", $line_str);
        if (count($line_arr) < 2)
        {
            throw new Exception("Nemohu najít konečný zůstatek.");
        }
        $amount = str_replace(" CZK", "", $line_arr[1]);
        return FioCsvParserUtil::parseAmount($amount);
    }

    /**
     * Checks headers that start data part of statement.
     *
     * @param integer $header_line header line
     * @throws Exception
     */
    private function checkHeaders($header_line)
    {
        // reconstruct valid header
        $expected = implode(self::CSV_COL_DELIM, self::$fields)
                . self::CSV_COL_DELIM;
        // compare with passed
        if ($header_line != $expected)
        {
            throw new Exception(__("Nelze parsovat hlavičku Fio výpisu. "
                    . "Ujistěte se, že jste zvolili všech " . count(self::$fields)
                    . " sloupců k importu v internetovém bankovnictví."));
        }
    }

    /**
     * Parse line of dump
     *
     * @param string $line
     * @param array $keys
     * @return array
     * @throws Exception
     */
    private function parseLine($line, $keys)
    {
        $cols = explode(self::CSV_COL_DELIM, $line);

        if (count($cols) != count(self::$fields) + 1)
        {
            throw new Exception('Chybný počet políček v položce výpisu.');
        }

        array_pop($cols);

        // Convert to associative array
        $assoc_cols = array_combine($keys, $cols);

        // Convert date
        if ($assoc_cols['datum'] != self::LAST_LINE_DATE_VALUE)
        {
            $assoc_cols['datum'] = FioCsvParserUtil::parseDate($assoc_cols['datum']);
        }

        // Amount has to be converted
        $assoc_cols['castka'] = FioCsvParserUtil::parseAmount($assoc_cols['castka']);

        // Trim leading zeros from VS
        $assoc_cols['vs'] = ltrim($assoc_cols['vs'], '0');

        return $assoc_cols;
    }

    /**
     * Checks parsed money amount agains data from integrity line.
     *
     * @param array $integrity_cols
     * @param integer $calculated_sum total counted sum
     * @throws Exception on integrity error
     */
    private function checkIntegrity($integrity_cols, $calculated_sum)
    {
        $amount = $integrity_cols['castka'];

        if (abs($calculated_sum - $amount) > 0.0001)
        {
            throw new Exception("Chybný kontrolní součet částky "
                    . "('$calculated_sum' != '$amount').");
        }
    }

}
