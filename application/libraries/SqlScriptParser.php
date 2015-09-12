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
 * SQL script file parser for parsing of SQL queries passed as single string
 * delimited by semicolons. Comment are started using "--".
 *
 * @author Ondřej Fibich <fibich@freenetis.org>
 * @since 1.2
 */
class SqlScriptParser
{
    // states for finite automata that is used for parsing

    /** In query state */
    const STATE_QUERY = 0;
    /** In query string state */
    const STATE_STRING = 2;
    /** In query string escaped character state */
    const STATE_STRING_ESCAPE = 3;
    /** In maybe comment state */
    const STATE_COMMENT_START = 4;
    /** In comment state */
    const STATE_COMMENT = 5;

    /**
     * Parse SQL queries that must be ended by semicolons.
     *
     * @param string $script_content SQL script file content
     * @return array parsed queries, each query is trimed for white spaces
     * @throws InvalidArgumentException on invalid script content
     */
    public function parse_queries($script_content)
    {
        if (!is_string($script_content) || empty($script_content))
        {
            return array();
        }

        $s = self::STATE_QUERY; // FA status
        $queries = array();     // parsed queries (result)
        $buffer = array();      // help letter buffer
        $string_term = NULL;    // last string termination character (" or ')
        $script_content_char_array = str_split($script_content);
        $script_length = count($script_content_char_array);

        for ($i = 0; $i < $script_length; $i++)
        {
            $letter = $script_content_char_array[$i];

            switch ($s)
            {
                case self::STATE_QUERY:
                case self::STATE_COMMENT_START:
                    $s = $this->fa_in_query($s, $letter, $buffer, $queries,
                            $string_term);
                    break;
                case self::STATE_STRING:
                    $s = $this->fa_in_string($letter, $buffer, $string_term);
                    break;
                case self::STATE_STRING_ESCAPE:
                    $buffer[] = $letter;
                    $s = self::STATE_STRING; // only one letter after \
                    break;
                case self::STATE_COMMENT:
                    $s = $this->fa_in_comment($letter);
                    break;
                default:
                    throw new Exception('Unhandled FA status: ' . $s);
            }
        }

        return $queries;
    }

    private function fa_in_query($status, $letter, array &$buffer,
            array &$queries, &$string_term)
    {
        switch ($letter)
        {
            case '-':
                if ($status === self::STATE_COMMENT_START)
                {
                    array_pop($buffer); // remove -
                    return self::STATE_COMMENT;
                }
                $buffer[] = $letter;
                return self::STATE_COMMENT_START;
            case '"':
            case '\'':
                $buffer[] = $letter;
                $string_term = $letter;
                return self::STATE_STRING;
            case ';': // query end
                if (empty($buffer))
                {
                    throw new InvalidArgumentException('empty SQL query parsed');
                }
                $queries[] = trim(implode('', $buffer));
                $buffer = array();
                return self::STATE_QUERY;
            default:
                $buffer[] = $letter;
                return $status;
        }
    }

    private function fa_in_string($letter, array &$buffer, &$string_term)
    {
        $buffer[] = $letter;

        switch ($letter)
        {
            case '\\':
                return self::STATE_STRING_ESCAPE;
            case $string_term:
                $string_term = NULL;
                return self::STATE_QUERY;
            default:
                return self::STATE_STRING;
        }
    }

    private function fa_in_comment($letter)
    {
        return ($letter === "\n") ? self::STATE_QUERY : self::STATE_COMMENT;
    }

}
