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

// This hook sets the locale.language and locale.lang config values
// based on the language found in the first segment of the URL.

Event::add('system.routing', 'site_lang');

function site_lang()
{
	// Array of allowed languages
	$locales = Config::get('allowed_locales');
	
	// Extract language from URL
	$segments = explode('/', url::current());
	$lang = strtolower($segments[0]);

	// Invalid language is given in the URL
	if (!array_key_exists($lang, $locales))
	{
		// Look for default alternatives and store them in order
		// of importance in the $new_langs array:
		//  1. cookie
		//  2. http_accept_language header
		//  3. default lang
		// Look for cookie
		$new_langs[] = (string) cookie::get('lang');

		// Look for HTTP_ACCEPT_LANGUAGE
		if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
		{
			foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $part)
			{
				$new_langs[] = substr($part, 0, 2);
			}
		}

		// Lowest priority goes to default language
		// changed to Czech Language as default by Ondřej Fibich
		$new_langs[] = Config::get('lang'); // prev. value 'en'
		// Now loop through the new languages and pick out the first valid one
		foreach (array_unique($new_langs) as $new_lang)
		{
			$new_lang = strtolower($new_lang);

			if (array_key_exists($new_lang, $locales))
			{
				$lang = $new_lang;
				break;
			}
		}
		
		// Redirect to URL with valid language
		$index_page = (Settings::get('index_page')) ? 'index.php/' : '';
		url::redirect(url::base() . $index_page . $lang . url_lang::current());
	}

	// Store locale config values
	Config::set('lang', $lang);
	Config::set('language', $locales[$lang]);

	// Overwrite setlocale which has already been set before in Kohana::setup()
	if (setlocale(LC_ALL, Config::get('language') . '.UTF-8') === FALSE)
	{
		throw new ErrorException(
				'Cannot setlocale to: ' . Config::get('language') . '.UTF-8. ' .
				'Please generate locale in your system.'
		);
	}
	// Decimal number are always written with .
	if (setlocale(LC_NUMERIC, 'en_US.UTF-8') === FALSE)
	{
		throw new ErrorException(
				'Cannot setlocale to: en_US.UTF-8. ' .
				'Please generate locale in your system.'
		);
	}

	// Finally set a language cookie for 6 months
	cookie::set('lang', $lang, 15768000, '/');
}
