<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model class.
 *
 * $Id: Model.php 1911 2008-02-04 16:13:16Z PugFish $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Model
{

	/**
	 *
	 * @var Database
	 */
	protected $db;

	/**
	 * Loads database to $this->db.
	 */
	public function __construct()
	{
		$this->db = Database::instance();
	}

} // End Model class