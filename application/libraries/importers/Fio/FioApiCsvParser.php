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
 * Auxiliary class for parsing CSV API bank account listings from czech bank
 * "FIO banka". Listing may be obtain from from the ebanking web application
 * of FIO bank.
 * 
 * The CSV format looks like this:
 * ﻿"accountId";"4561093331"
 * "bankId";"2010"
 * "currency";"CZK"
 * "iban";"CZ4920100000004561093331"
 * "bic";"FIOBCZPPXXX"
 * "openingBalance";"18 204,70"
 * "closingBalance";"30 154,48"
 * "dateStart";"01.10.2024"
 * "dateEnd";"12.11.2024"
 * "idFrom";"26714150488"
 * "idTo";"26778821144"
 * 
 * "ID operace";"Datum";"Objem";"Měna";"Protiúčet";"Název protiúčtu";
 *      "Kód banky";"Název banky";"KS";"VS";"SS";"Poznámka";
 *      "Zpráva pro příjemce";"Typ";"Provedl";"Upřesnění";"Poznámka";"BIC";
 *      "ID pokynu"
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.1.27
 * @todo i18n of error messages
 */
class FioApiCsvParser
{
    /**
     * CSV column separator.
     */
    const CSV_COL_DELIM = ';';
    
    /**
     * CSV column value wrapper.
     */
    const CSV_COL_WRAPPER = '"';

    /**
     * Default CSV file encoding.
     */
    const DEFAULT_CHARSET = 'UTF-8';
    
    /**
     * Mandatory header fields.
     *
     * @var array[string]
     */
    private static $header_fields = array
    (
        'accountId',
        'bankId',
        'currency',
        'iban',
        'bic',
        'openingBalance',
        'closingBalance',
        'dateStart',
        'dateEnd',
        'idFrom',
        'idTo',
    );

    /**
     * All fields available must be used and sorted alphabetically
     *
     * @var array[string]
     */
    private static $fields = array
    (
        'id_pohybu' => 'ID operace',
        'datum' => 'Datum',
        'castka' => 'Objem',
        'mena' => 'Měna',
        'protiucet' => 'Protiúčet',
        'nazev_protiuctu' => 'Název protiúčtu',
        'kod_banky' => 'Kód banky',
        'nazev_banky' => 'Název banky',
        'ks' => 'KS',
        'vs' => 'VS',
        'ss' => 'SS',
        'identifikace' => 'Poznámka',
        'zprava' => 'Zpráva pro příjemce',
        'typ' => 'Typ',
        'provedl' => 'Provedl',
        'upresneni' => 'Upřesnění',
        'identifikace2' => 'Poznámka',
        'bic' => 'BIC',
        'id_pokynu' => 'ID pokynu',
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
     * @return array[array]	Integer-indexed array of associative arrays.
     *						Each associative array represents one line of the CSV
     * @throws Exception on parse error
     */
    public function parse($csv, $charset = self::DEFAULT_CHARSET)
    {
        $total_sum = -1;
        $sum = 0;
        $keys = array_keys(self::$fields);
        $lines = self::transformFileToLineArray($csv, $charset);
        $result = array();
        
        $header = $this->parseHeader($lines);
        $i = $header['read_index'];
    
        // check each line of CSV
        while ($i < count($lines))
        {
            $line = trim($lines[$i++]);
            if (empty($line)) // empty last line?
            {
                break;
            }
            // data lines
            $cols = $this->parseLine($line, $keys);
            // add data row
            $sum += $cols['castka'];
            $result[] = $cols;
        }
        
        $this->checkIntegrity($header['header'], $sum);
        
        return array
        (
            'header' => $header['header'],
            'items' => $result,
        );
    }
    
    /**
     * Check whether parser accept given CSV file.
     * 
     * @param string $csv string containing the original csv file.
     * @param string $charset optional charset name of file, default is UTF-8
     * @return boolean
     */
    public function accept_file($csv, $charset = self::DEFAULT_CHARSET)
    {
        $lines = self::transformFileToLineArray($csv, $charset);
        try
        {
            $this->parseHeader($lines);
            return TRUE;
        }
        catch (Exception $ex)
        {
            return FALSE;
        }
    }
    
    /**
     * Parse bank statement header.
     *
     * @param array[string] $lines lines of statement
     * @return array
     * @throws Exception
     */
    private function parseHeader($lines)
    {
        $header = array();
        $i = 0;
        
        if (count(self::$header_fields) + 2 >= count($lines))
        {
            throw new Exception('Výpis neobsahuje hlavičku.');
        }
        
        foreach (self::$header_fields as $header_field)
        {
            $line = trim($lines[$i++]);
            // first column has issue with some UTF-8 characters
            if ($i == 1)
            {
                $line = preg_replace('/^[\x00-\x1F\x80-\xFF]+/', '', $line);
            }
            
            $cols = str_getcsv($line, self::CSV_COL_DELIM, self::CSV_COL_WRAPPER);
            
            if (count($cols) != 2)
            {
                throw new Exception('Hlavička neobsahuje dva sloupce.');
            }
            if ($cols[0] != $header_field)
            {
                throw new Exception('Hlavička obsahuje neočekávaný sloupec.');
            }
            
            $header[$header_field] = $cols[1];
        }
        
        if (trim($lines[$i++]) != '')
        {
            throw new Exception('Chybí oddělovač hlaviček.');
        }
        
        $this->checkHeaders($lines[$i++]);
        
        return array
        (
            'read_index' => $i,
            'header' => self::convertToHeaderData($header),
        );
    }
    
    private static function convertToHeaderData($header)
    {
    		$header['openingBalance'] = self::parseAmount($header['openingBalance']);
    		$header['closingBalance'] = self::parseAmount($header['closingBalance']);
    		$header['dateStart'] = self::parseDate($header['dateStart']);
    		$header['dateEnd'] = self::parseDate($header['dateEnd']);
        return $header;
    }
    
    /**
     * Checks headers that start data part of statement.
     *
     * @param integer $header_line header line
     * @throws Exception
     */
    private function checkHeaders($header_line)
    {
        $expected_header_cols = array_values(self::$fields);
        // extract header
        $header_cols = str_getcsv($header_line, self::CSV_COL_DELIM,
                self::CSV_COL_WRAPPER);
        // check if extracted
        if (empty($header_cols))
        {
            throw new Exception('Hlavička výpisu je prázdná.');
        }
        // check if count match
        if (count($header_cols) != count($expected_header_cols))
        {
            throw new Exception('Počet položek hlavičky výpisu neodpovídá.');
        }
        // check each column
        for ($i = count($header_cols) - 1; $i >= 0; $i--)
        {
            if ($header_cols[$i] != $expected_header_cols[$i])
            {
                throw new Exception('Hlavičky výpisu neopovídají.');
            }
        }
    }

    /**
     * Parse line of dump
     *
     * @param string $line
     * @param array $keys
     * @param boolean $parse_date
     * @return array
     * @throws Exception
     */
    private function parseLine($line, $keys, $parse_date = TRUE)
    {
        $cols = str_getcsv($line, self::CSV_COL_DELIM, self::CSV_COL_WRAPPER);

        if (count($cols) != count($keys))
        {
            throw new Exception('Chybný počet políček v položce výpisu.');
        }

        // Convert to associative array
        $assoc_cols = array_combine($keys, $cols);

        // Convert date
        if ($parse_date)
        {
            $assoc_cols['datum'] = self::parseDate($assoc_cols['datum']);
        }

        // Amount has to be converted
        $assoc_cols['castka'] = self::parseAmount($assoc_cols['castka']);

        // Trim leading zeros from VS
        $assoc_cols['vs'] = ltrim($assoc_cols['vs'], '0');
        
        // column prevod N/A
        $assoc_cols['prevod'] = NULL;

        return $assoc_cols;
    }

    /**
     * Checks parsed money amount agains data from header.
     *
     * @param array[string] $header
     * @param integer $calculated_sum total counted sum
     * @throws Exception on integrity error
     */
    private function checkIntegrity($header, $calculated_sum)
    {
        $sum = self::parseAmount($header['closingBalance']) 
            - self::parseAmount($header['openingBalance']);
      
        if (abs($sum - $calculated_sum) > 0.0001)
        {
            throw new Exception("Chybný kontrolní součet částky "
                    . "('$calculated_sum' != '$sum').");
        }
    }
    
    /**
     * Normalize string amount to double value.
     *
     * @example "   1000 278,40  " -> "1000278.40"
     * @param string $amount
     * @return double
     * @throws InvalidArgumentException on invalid passed amount
     */
    private static function parseAmount($amount)
    {
        $amount_no_ws = preg_replace('/\s+/u', '', $amount);
        $norm_amount = str_replace(',', '.', $amount_no_ws);
        if (!is_numeric($norm_amount))
        {
            $m = __('Invalid amount format') . ': ' . $amount;
            throw new InvalidArgumentException($m);
        }
        return doubleval($norm_amount);
    }

    /**
     * Parse date from format DD.MM.YYYY into YYYY-MM-DD.
     *
     * @param string $date in format DD.MM.YYYY
     * @return string date in format YYYY-MM-DD
     * @throws InvalidArgumentException on invalid date format
     */
    private static function parseDate($date)
    {
        $matches = NULL;
        if (!preg_match("/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/", $date, $matches))
        {
            $m = __('Invalid date format') . ': ' . $date;
            throw new InvalidArgumentException($m);
        }
        $timestamp = mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]);
        return date('Y-m-d', $timestamp);
    }

    /**
     * Transforms file content in passed charset into array of its lines encoded
     * in UTF-8 encoding. This function must handle differences of end of line
     * separators on all platforms.
     *
     * @param string $file_content file countent to be transformed
     * @param string $charset charset of file content
     * @return array array of lines in UTF-8 charset
     */
    public static function transformFileToLineArray($file_content, $charset)
    {
        $internal_charset = 'UTF-8';
        $fc_utf8 = NULL;
        // transform to UTF-8
        if (strtolower($charset) != strtolower($internal_charset))
        {
            $fc_utf8 = iconv($charset, $internal_charset, $file_content);
        }
        else
        {
            $fc_utf8 = $file_content;
        }
        // eplode lines
        return preg_split("/\r\n|\n|\r/", $fc_utf8);
    }

}
