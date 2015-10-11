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

require_once __DIR__ . '/Fio_Bank_Statement_File_Importer.php';
require_once __DIR__ . '/Fio/FioCsvStatement.php';
require_once __DIR__ . '/Fio/FioCsvParser.php';
require_once __DIR__ . '/Fio/NewFioCsvParser.php';

/**
 * FIO importer for statements in CSV format that are obtained from the FIO
 * e-banking portal.
 *
 * It uses old version of driver for parsing.
 *
 * @author Ondrej Fibich
 * @since 1.1
 */
class Csv_Fio_Bank_Statement_File_Importer extends Fio_Bank_Statement_File_Importer
{
    /**
     * Required currency.
     *
     * @todo we want to handle more currency, maybe it will be good to have
     *       currency of each association bank account set by settings
     */
    const CURRENCY = 'CZK';

	/**
	 * Data reprezentation of import.
	 *
	 * @var FioCsvStatement
	 */
	private $data = NULL;
    
    /**
     * Indicates whether header is available or not.
     *
     * @var boolean
     */
    private $header_available = TRUE;

    /*
	 * @Override
	 */
	protected function check_file_data_format()
	{
		// reset
		$this->data = NULL;
        $this->header_available = TRUE;
		// parse (we have no function for checking)
		try
		{
            $file_data = $this->get_file_data();
            // new parser
            $parserNew = new NewFioCsvParser;
            if ($parserNew->accept_file($file_data))
            {
                $this->header_available = FALSE;
                $this->data = $parserNew->parse($file_data);
            }
            // old parser
            else
            {
                $parserOld = new FioCsvParser;
                $this->data = $parserOld->parse($file_data, 'cp1250');
            }
            // correct each data row
			for ($i = 0; $i < count($this->data->items); $i++)
			{
				$this->correct_listing_row($this->data->items[$i]);
			}
			// ok
			return TRUE;
		}
		catch (Exception $e)
		{
			$this->data = NULL;
			$this->add_exception_error($e, FALSE);
			return FALSE;
		}
	}

    /**
     * Correct passed listing row for application needs.
     *
     * @param array $row listing row passed by reference
     * @throws Exception on error in data
     */
    private function correct_listing_row(&$row)
    {
        if ($row['mena'] != self::CURRENCY)
        {
            throw new Exception(__('Unknown currency %s!', $row['mena']));
        }
        // only transfer from Fio to Fio have 'nazev_protiuctu'
        // for accounts in other banks we have to derive account name
        if (!$row['nazev_protiuctu'] && $row['identifikace'])
        {
            $row['nazev_protiuctu'] = $row['identifikace'];
        }
    }

    /*
	 * @Override
	 */
	protected function get_header_data()
	{
        if (!$this->header_available)
        {
            return NULL;
        }
        
		if (empty($this->data))
        {
            throw new InvalidArgumentException('Check CSV first');
        }

		$hd = new Header_Data($this->data->account_nr, $this->data->bank_nr);

		$hd->currency = self::CURRENCY;
		$hd->openingBalance = $this->data->opening_balance;
		$hd->closingBalance = $this->data->closing_balance;
		$hd->dateStart = $this->data->from;
		$hd->dateEnd = $this->data->to;

		return $hd;
	}

	/*
	 * @Override
	 */
	protected function parse_file_data()
	{
		// already parsed
		return !empty($this->data);
	}

	/*
	 * @Override
	 */
	protected function get_parsed_transactions()
	{
		return $this->data->items;
	}

}
