<?php
/**
 * This file acts as the "front controller" to your application. You can
 * configure your application and system directories here, as well as error
 * reporting and error display.
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */

/**
 * Kohana website application directory. This directory should contain your
 * application configuration, controllers, models, views, and other resources.
 *
 * This path can be absolute or relative to this file.
 */
$kohana_application = 'application';

/**
 * Kohana package files. This directory should contain the core/ directory, and
 * the resources you included in your download of Kohana.
 *
 * This path can be absolute or relative to this file.
 */
$kohana_system = 'system';

/**
 * Set the error reporting level. Unless you have a special need, E_ALL is a
 * good level for error reporting.
 */
error_reporting(E_ALL & ~E_STRICT);

/**
 * Turning off display_errors will effectively disable Kohana error display
 * and logging. You can turn off Kohana errors in application/config/config.php
 */
ini_set('display_errors', TRUE);

/**
 * If you rename all of your .php files to a different extension, set the new
 * extension here. This option can left to .php, even if this file is has a
 * different extension.
 */
define('EXT', '.php');

/**
 * Test to make sure that FreenetIS is running on PHP 5.3.1 or newer.
 */
(PHP_VERSION_ID < 50301) and exit('FreenetIS requires PHP 5.3.1 or newer.');

//
// DO NOT EDIT BELOW THIS LINE, UNLESS YOU FULLY UNDERSTAND THE IMPLICATIONS.
// ----------------------------------------------------------------------------
// $Id: index.php 1631 2007-12-28 00:11:38Z Shadowhand $
//

// Tests if system is running in unit testing mode
define('UNITTEST', empty($_SERVER['SERVER_NAME']) &&
        strpos(@$_SERVER['SCRIPT_NAME'], 'phpunit') !== FALSE);

// If unit testing change relative address of system and application folders
if (UNITTEST)
{
	$kohana_application = __DIR__ . '/' . $kohana_application;
	$kohana_system = __DIR__ . '/' . $kohana_system;

	// Define the front controller name and docroot
	define('DOCROOT', __DIR__ . '/');
	define('KOHANA',  substr(__FILE__, strlen(DOCROOT)));
}
else
{
	// Define the front controller name and docroot
	define('DOCROOT', getcwd().DIRECTORY_SEPARATOR);
	define('KOHANA',  substr(__FILE__, strlen(DOCROOT)));
}

// Define application and system paths
define('APPPATH', str_replace('\\', '/', realpath($kohana_application)).'/');
define('SYSPATH', str_replace('\\', '/', realpath($kohana_system)).'/');

// Clean up
unset($kohana_application, $kohana_system);

(is_dir(APPPATH)) or die
(
	'Your <code>$kohana_application</code> directory does not exist. '.
	'Set a valid <code>$kohana_application</code> in <tt>'.KOHANA.'</tt>.'
);

(is_dir(SYSPATH) AND file_exists(SYSPATH.'/core/'.'Bootstrap'.EXT)) or die
(
	'Your <code>$kohana_system</code> directory does not exist. '.
	'Set a valid <code>$kohana_system</code> in <tt>'.KOHANA.'</tt>.'
);

// Mail to developers
define('DEVELOPER_EMAIL_ADDRESS', 'bugs@freenetis.org');

// URL to AXO documentation web application
define('AXODOC_URL', 'http://axo.doc.freenetis.org/');

// If unit testing just initilize do not execute
if (UNITTEST)
{
    require 'tests/BootstrapPHPUnit'.EXT;
}
else
{
    require SYSPATH.'core/Bootstrap'.EXT;
}
