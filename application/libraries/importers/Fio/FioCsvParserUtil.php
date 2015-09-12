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
 * Utility methods for FIO CSV parsers.
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
final class FioCsvParserUtil
{
    /**
     * Utility pattern - instance cannot be created.
     */
    private function __construct()
    {
    }
    
    /**
     * Normalize string amount to double value.
     *
     * @example "   1000 278,40  " -> "1000278.40"
     * @param string $amount
     * @return double
     * @throws InvalidArgumentException on invalid passed amount
     */
    public static function parseAmount($amount)
    {
        $norm_amount = str_replace(array(' ', ','), array('', '.'), $amount);
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
    public static function parseDate($date)
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
        // transform to uTF-8
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
