<?php defined('SYSPATH') or die('No direct script access.');
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
 * Generates variable keys with chacksum.
 * 
 * Generator sums identificator with seed saved in database and
 * appends its checksum.
 * 
 * @author David Raska
 */
class Checksum_Variable_Key_Generator extends Variable_Key_Generator
{	
	const PARITY_SEED = 'variable_symbol_checksum_generator_seed';

	/**
	 * Generated variable key from given member ID.
	 *
	 * @param mixed $identificator Indentificator for generate from
	 * @return integer	Variable key
	 */
	public function generate($identificator)
	{
		// get seed from database
		$seed = Settings::get(self::PARITY_SEED);
		
		// create new seed if not exist
		if (!$seed)
		{
			$seed = rand(1000, 10000);
			Settings::set(self::PARITY_SEED, $seed);
		}
		
		// prepare variable symbol
		$key = strrev(sprintf("%06d", $identificator));
		$key = sprintf("%06d", (intval($key) + $seed) % 999999);		
		
		$sum = $this->countVariableSymbolChecksum($key);
		
		// concatenate symbol and parity
		$var_key = $key . strval(sprintf("%04d", $sum % 9999));
		
		return $var_key;
	}
	
	/*
	 * @override
	 */
	public function errorCheckAvailable()
	{
		return TRUE;
	}
	
	/*
	 * @override
	 */
	public function errorCheck($var_key)
	{
		$diff = $this->countVariableSymbolChecksumDiff($var_key);
			
		return $diff == 0;
	}
	
	/*
	 * @override
	 */
	public function errorCorrectionAvailable()
	{
		return TRUE;
	}

	/*
	 * @override
	 */
	public function errorCorrection($var_key)
	{
		$diff = $this->countVariableSymbolChecksumDiff($var_key);	
		
		if ($diff == 0)
		{
			return array
			(
				'status' => TRUE,
				'corrected_variable_key' => $var_key
			);
		}
		else
		{		
			// number weights
			$weight = array(11,13,17,19,23,29);
			
			for ($i = 0; $i < 6; $i++)
			{
				if (($diff % $weight[$i]) == 0)
				{
					$sign = $diff / $weight[$i];
					
					// error is bigger than 9 => unreal
					if (abs($sign) > 9)
					{
						$i = 6;
						break;
					}
					
					$var_key[$i] = $var_key[$i] + $sign;
					
					// stop after fixing first number
					break;
				}
			}
			
			if ($i == 6)	// try to fix error by recounting checksum
			{
				$vs = substr($var_key, 0, 6);
				$parity = substr($var_key, 6);

				$new_parity = sprintf("%04d", intval($parity) - $diff);

				return array
				(
					'status' => TRUE,
					'corrected_variable_key' => $vs . $new_parity
				);
			}
			else			// fixed error
			{
				return array
				(
					'status' => TRUE,
					'corrected_variable_key' => $var_key
				);
			}
		}
	}


	/**
	 * Function counts parity for given variable symbol
	 * 
	 * @param string $vs Variable symbol
	 * @return integer Variable symbol parity
	 */
	private function countVariableSymbolChecksum($vs)
	{
		// number weights
		$weight = array(11,13,17,19,23,29);
			
		// init
		$check_sum = 0;

		// count weighted sum
		for ($i = 0; $i < strlen($vs); $i++)
		{
			$check_sum += intval($vs[$i]) * $weight[$i];
		}
		
		return $check_sum;
	}
	
	/**
	 * Function counts difference between counted parity and parity in
	 * variable symbol
	 * 
	 * @param string $var_key Variable symbol
	 * @return int Variable symbol parity diff
	 */
	private function countVariableSymbolChecksumDiff($var_key)
	{
		$vs = substr($var_key, 0, 6);
		$parity = substr($var_key, 6);
		
		// get seed from database
		$seed = Settings::get(self::PARITY_SEED);
		
		if (!$seed)
		{
			return 0;
		}
		
		$check_sum = $this->countVariableSymbolChecksum($vs);
		
		return intval($parity) - ($check_sum % 9999);
	}
}
