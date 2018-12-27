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
 * Setup config controller.
 * Configure database connection and .htaccess file before installation.
 *
 * @author  Michal Kliment
 * @package	Controller
 */
class Setup_config_Controller extends Controller
{
	/**
	 * Introductory page of setup config, only some information about setup config
	 * 
	 * @author Michal Kliment
	 */
	public function index()
	{		
		$f_state = $this->get_file_statuses(array('config.php', '.htaccess'));
		
		// check if the database is empty
		if ($f_state['file_exist']['config.php'] && 
			$f_state['file_exist']['.htaccess'] &&
			Settings::get('db_schema_version'))
		{
			url::redirect('members/show/'.$this->session->get('member_id'));
		}
		
		$d_state = $this->get_dir_statuses(array('upload', 'logs'));
		
		$writeable = $f_state['state'] & $d_state['state'];
		
		$html_statuses = $f_state['html'] . $d_state['html'];
		
		if (!server::is_mod_rewrite_enabled())
		{
			$html_statuses .= html::image(array('src' => 'media/images/icons/status/error.png', 'class' => 'status_icon'));
			$html_statuses .= '<span style="color: red">'.__('Mod_rewrite is not enabled').'</span><br>';
			$writeable = false;
		}
		else
		{
			$html_statuses .= html::image(array('src' => 'media/images/icons/status/success.png', 'class' => 'status_icon'));
			$html_statuses .= __('Mod_rewrite is enabled').'<br>';
		}
		
		if ($writeable)
		{	
			$form = new Forge('setup_config/htaccess');
			$form->submit(__('Next step') . ' >>>');
		}
		else
		{
			// new form
			$form = new Forge();
			$form->submit('Check again');
		}

		$view = new View('setup_config/main');
		$view->content = new View('setup_config/index');
		$view->content->form = $form->html();
		$view->content->file_statuses = $html_statuses;
		$view->content->writeable = $writeable;
		$view->content->config_exist = $f_state['file_exist']['config.php'];
		$view->render(TRUE);
	}
	
	/*
	 * This method creates new .htaccess file
	 * 
	 * @author David Raška
	 */
	public function htaccess()
	{
		// set subdirectory
		$suffix = substr(server::script_name(),0,-9);
		$errors = array();
		
		if (!file_exists('.htaccess'))
		{
			// load .htaccess sample file
			$htaccessFile = @file('.htaccess-sample');
			
			if (!$htaccessFile)
			{
				$errors['fopen'] = TRUE;
			}
			else
			{
				foreach ($htaccessFile as $line_num => $line)
				{
					// find line with RewriteBase
					if (preg_match("/^RewriteBase (.+)/", $line))
					{
						// and set there our suffix (subdirectory)
						$htaccessFile[$line_num] = preg_replace(
								"/^(RewriteBase )(.+)/", 
								'${1}'.$suffix, $line
						);
					}
					// update language after redirection (#460)
					else if (preg_match("/ (en)\//", $line))
					{
						$htaccessFile[$line_num] = str_replace(
								' en/', ' ' . Config::get('lang') . '/',
								$htaccessFile[$line_num]
						);
					}
				}
				// root directory is writable, create .htacess
				$handle = @fopen('.htaccess', 'w');

				if (!$handle)
				{
					$errors['fopen'] = TRUE;
				}
				else
				{
					foreach($htaccessFile as $line )
					{
						@fwrite($handle, $line);
					}
					
					@fclose($handle);

					if (!@chmod('.htaccess', 0666))
					{
						$errors['chmod'] = TRUE;
					}
				}
			}
		}
		
		if (count($errors))
		{
			$view = new View('setup_config/main');
			
			$view->content = '<h2>'.__('Error - Cannot create %s file', '.htaccess').'</h2>';
			
			$view->render(TRUE);
		}
		else
		{
			$this->redirect('setup_config/setup');
		}
		
	}

	/**
	 * This method create config file (if root directory is writable)
	 * or generate code to create it by user
	 * 
	 * @author Michal Kliment
	 */
	public function setup()
	{
		// check if the database is empty
		if ($this->settings->get('db_schema_version'))
		{
			url::redirect('members/show/'.$this->session->get('member_id'));
		}
		
		// new form
		$form = new Forge('setup_config/setup');
		
		$form->group('<h2>'.__('Database information').'</h2>');
		
		$form->input('db_name')
				->label('Database name')
				->value('freenetis')
				->rules('required')
				->callback(array($this, 'valid_db_name'))
				->help('The name of the database you want to run FreenetIS in.');
		
		$form->input('db_user')
				->label('User name')
				->value('freenetis')
				->rules('required')
				->help('Your MySQL username');
		
		$form->input('db_password')
				->label('Password')
				->value('password')
				->rules('required')
				->help('Your MySQL password');
		
		$form->input('db_host')
				->label('Database host')
				->value('localhost')
				->rules('required')
				->help('99&#37 chance you won\'t need to change this value.');
		
		$form->submit(__('Next step').' >>>');

		// form is valid
		if ($form->validate())
		{
			// convert object to array
			$form_data = $form->as_array(FALSE);

			// test connection to database
			$database = new Database(array
			(
				'type'     => (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 60000) ? 'mysqli' : 'mysql',
				'host'     => $form_data['db_host'],
				'user'     => $form_data['db_user'],
				'pass'     => $form_data['db_password'],
				'database' => $form_data['db_name'],
				'port'     => FALSE,
				'socket'   => FALSE
			));

			try
			{
				$database->connect();
				$con = TRUE;
			}
			catch (Exception $ex)
			{
				$con = FALSE;
				$error_cause = $ex->getMessage();
			}

			$view = new View('setup_config/main');
			$view->content = new View('setup_config/setup');

			// cannot connect to database => form data are bad
			if (!$con)
			{
				$view->content->error_cause = $error_cause;
				$view->content->error = TRUE;
			}
			// successfully connect to database, we can create config file
			else
			{
				// load config-sample
				$config_file = file('config-sample' . EXT);
				
				foreach ($config_file as $line_num => $line)
				{
					// find only config lines (no comments or blank lines)
					if (preg_match("/^\\\$config\['(.+)'\]/", $line, $matches))
					{
						// this config line is one from database config
						if (isset($form_data[$matches[1]]))
						{
							// set value from form
							$value = $form_data[$matches[1]];
							$config_file[$line_num] = preg_replace(
									"/^(\\\$config\[')(.+)('\] = ')(.+)(';)/",
									'${1}${2}${3}' . $value . '${5}', $line
							);
						}
					}
				}
				
				// root directory is writable, create config
				$handle = fopen('config.php', 'w');

				foreach ($config_file as $line)
				{
					fwrite($handle, $line);
				}

				fclose($handle);
				chmod('config.php', 0666);
			}
			$view->render(TRUE);
		}
		else
		{
			$view = new View('setup_config/main');
			$view->content = $form->html();
			$view->render(TRUE);
		}
	}
	
	/**
	 * Returns states (exist, writeable) of files in html format and boolean
	 * 
	 * @author David Raška
	 * @param array $files
	 * @return array
	 */
	private function get_file_statuses($files)
	{
		$ok = true;
		$html = '';
		$file_exist = array();
		
		foreach ($files AS $file)
		{
			if (file_exists($file))
			{
				$html .= html::image(array('src' => 'media/images/icons/status/success.png', 'class' => 'status_icon'));
				$html .= __('File %s exist.',$file).'<br>';
				$file_exist[$file] = true;
			}
			else if (!is_writeable('.'))
			{
				$html .= html::image(array('src' => 'media/images/icons/status/error.png', 'class' => 'status_icon'));
				$html .= '<span style="color: red">'.__('i can\'t write the %s file',$file).'</span><br>'.
						'<span style="margin-left: 3em">'.__('Change root FreenetIS directory permissions:').' <code>chmod ugo+w '.server::base_dir().'</code></span><br>';
				$ok = false;
				$file_exist[$file] = false;
			}
			else
			{
				$html .= html::image(array('src' => 'media/images/icons/status/info.png', 'class' => 'status_icon'));
				$html .= __('File %s will be created.',$file).'<br>';
				$file_exist[$file] = false;
			}
		}
		
		return array('state' => $ok, 'html' => $html, 'file_exist' => $file_exist);
	}
	
	/**
	 * Returns states (exist & writeable) of directories in html format and boolean
	 * 
	 * @param array $dirs
	 * @return array
	 */
	private function get_dir_statuses($dirs)
	{
		$ok = true;
		$html = '';
		
		foreach ($dirs AS $dir)
		{
			if (file_exists($dir) && is_writeable($dir))
			{
				$html .= html::image(array('src' => 'media/images/icons/status/success.png', 'class' => 'status_icon'));
				$html .= __('Directory %s exist and is writeable.',$dir).'<br>';
			}
			else
			{
				$html .= html::image(array('src' => 'media/images/icons/status/error.png', 'class' => 'status_icon'));
				$html .= '<span style="color: red">'.__('i can\'t write to dir %s',$dir).'</span><br>'.
						'<span style="margin-left: 3em">'.__('Change directory permissions:').' <code>chmod ugo+w '.server::base_dir().'/'.$dir.'</code></span><br>';
				$ok = false;
			}
		}
		
		return array('state' => $ok, 'html' => $html);
	}

    /**
	 * Callback function validator for DB name.
	 *
	 * @param object $input
	 */
	public function valid_db_name($input = NULL)
	{
		// validators cannot be accessed
		if (empty($input) || !is_object($input))
		{
			self::error(PAGE);
		}

		$value = $input->value;

		if (!empty($value) && !preg_match('/^[a-z0-9_-]+$/i', $value))
		{
			$input->add_error('required', __(
					'Only alpha numeric characters, \'-\' and \'_\' allowed.'
			));
		}
	}

}
