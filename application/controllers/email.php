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
 * Email controller. Enables writing and sending emails from system.
 * 
 * @author  Sevcik Roman
 * @package Controller
 */
class Email_Controller extends Controller
{
	/**
	 * Constructor, only test if email is enabled
	 * 
	 * @author Michal Kliment
	 */
	public function __construct()
	{
		parent::__construct();
		
		if (!module::e('email'))
			Controller::error (ACCESS);
	}
	
	/**
	 * Shows email form
	 */
	public function index()
	{
		if ($this->input->post('address') == NULL ||
			$this->input->post('email_member_id') == NULL)
		{
			Controller::warning(PARAMETER);
		}

		$te = new TextEditor();
		$te->setWidth(656);
		$te->setHeight(480);
		$te->setFieldName('editor');
		$te->setContent(
				($this->input->post('editor') == NULL)
				? '' : $this->input->post('editor')
		);

		$view = new View('main');
		$view->title = __('Write email');
		$view->content = new View('email/write');
		$view->content->editorFieldName = $te->getFieldName();
		$view->content->email_from = Settings::get('email_default_email');
		$view->content->email_to = $this->input->post('address');
		$view->content->subject = ($this->input->post('subject') == NULL) ? '' : $this->input->post('subject');
		$view->content->editor = $te->getHtml();
		$view->content->email_member_id = $this->input->post('email_member_id');
		$view->render(TRUE);
	}

	/**
	 * Sends email
	 */
	public function send()
	{
		if ($this->input->post('email_from') == NULL ||
			$this->input->post('email_to') == NULL ||
			$this->input->post('email_member_id') == NULL)
		{
			Controller::warning(PARAMETER);
		}

		// Use connect() method to load Swiftmailer and connect
		// using the parameters set in the email config file
		$swift = email::connect();

		// From, subject and HTML message
		$from = $this->input->post('email_from');
		$subject = 'FreenetIS - ' . $this->input->post('subject');

		$message = '<html><body>' . $this->input->post('editor') . '</body></html>';
		// Build recipient lists
		$recipients = new Swift_RecipientList;
		$recipients->addTo($this->input->post('email_to'));

		// Build the HTML message
		$message = new Swift_Message($subject, $message, "text/html");

		if ($swift->send($message, $recipients, $from))
		{
			url::redirect('members/show/' . $this->input->post('email_member_id'));
		}
		else
		{
			$content = form::open(url_lang::base() . 'email');
			$content.= form::hidden('email_member_id', $this->input->post('email_member_id'));
			$content.= form::hidden('address', $this->input->post('email_to'));
			$content.= form::hidden('email_from', $this->input->post('email_from'));
			$content.= form::hidden('subject', $this->input->post('subject'));
			$content.= form::hidden('editor', $this->input->post('editor'));
			$content.= form::submit('submit', __('Back'), 'class="submit"');
			$content.= form::close();

			Controller::error(EMAIL, $content);
		}

		// Disconnect
		$swift->disconnect();
	}

	/**
	 * Send email to developers wia AJAX.
	 * If send was successful it prints: '1' else it prints '0'
	 * @see Email address of developers is in file /index.php in constant DEVELOPER_EMAIL_ADDRESS
	 * @author Ondřej Fibich
	 */
	public function send_email_to_developers()
	{
		// From, subject and HTML message
		$email = @$_POST['uemail'];
		$uname = @$_POST['uname'];
		$name = @$_POST['name'];
		$message = @$_POST['message'];
		$url = @$_POST['url'];
		$error = @$_POST['error'];
		$description = @$_POST['udescription'];
		$edescription = @$_POST['description'];
		$detail = @$_POST['detail'];
		$trace = @$_POST['trace'];
		$line = @$_POST['line'];
		$file = @$_POST['file'];
		$ename = @$_POST['ename'];
		
		if (!valid::email($email))
		{
			// Redirect
			status::error('Wrong email filled in!');
			url::redirect(url::base());
		}
		
		// Use connect() method to load Swiftmailer and connect using the 
		// parameters set in the email config file
		$swift = email::connect();

		$fn_version = '-';
		
		if (defined('FREENETIS_VERSION'))
		{
			$fn_version = FREENETIS_VERSION;
		}
		
		// Build content
		$subject = __('FreenetIS bug report: ' . (empty($ename) ? $url : $ename));
		$message_body = nl2br($description);
		$attachment = '<html><body>' .
				'<h1>' . __('Bug report from') . ": . $url (file: $file, line: $line)</h1>" .
				'<p>FreenetIS ' . __('version') . ': ' . $fn_version . '</p>' .
				'<p>PHP ' . __('version') . ': ' . phpversion() . '</p>' .
				'<p>' . __('Reported by') . ': ' . $uname . '</p>' .
				'<p>' . __('Description') . ': ' . nl2br($description) . '</p>' .
				'<h2>' . $error . '</h2>' .
				'<p>' . htmlspecialchars_decode($edescription) . '</p>' .
				'<p class="message">' . htmlspecialchars_decode($message) . '</p>' .
				'<p class="detail">' . htmlspecialchars_decode($detail) . '</p>' .
				'<div>' . htmlspecialchars_decode($trace) . '</div>' .
				'</body></html>';
		$attachment = text::cs_utf2ascii($attachment);
		
		// Build recipient lists
		$recipients = new Swift_RecipientList;
		$recipients->addTo(DEVELOPER_EMAIL_ADDRESS);

		// Build the HTML message
		$message = new Swift_Message($subject);
		$message->attach(new Swift_Message_Part($message_body));
		$message->attach(new Swift_Message_Attachment($attachment, 'log.html', 'text/html'));

		// Send
		$swift->send($message, $recipients, $email);
		$swift->disconnect();

		// Redirect
		status::success('Thank you for your error report');
		url::redirect(url::base());
	}
	
	/**
	 * This function show message in database
	 * 
	 * @author Roman Sevcik, David Raska
	 * @param integer $email_id
	 */
	public function show($email_id = null)
	{
		// access
		if (!$this->acl_check_view('Email_queues_Controller', 'email_queue'))
		{
			Controller::error(ACCESS);
		}
		
	    if (!isset($email_id))
		{
			Controller::warning(PARAMETER);
		}

		$email = new Email_queue_Model($email_id);

	    if (!$email || !$email->id)
		{
			Controller::error(RECORD);
		}

		$email_info = $email->from . ' &rarr; ' . $email->to . ' (' . $email->access_time . ')';
		
		$breadcrumbs = breadcrumbs::add()
				->link('email_queues', 'E-mails')
				->disable_translation()
				->text($email_info);
	    
	    $view = new View('main');
	    $view->title = __('Show e-mail message');
	    $view->content = new View('email/show');
		$view->breadcrumbs = $breadcrumbs->html();
	    $view->content->headline = __('e-mail message');
	    $view->content->email = $email;
	    $view->render(true);
	}
	
	/**
	 * This function show email without FreenetIS GUI
	 * 
	 * @author David Raska
	 */
	public function preview()
	{
		$email_hash = $this->input->get('id');
		if (!isset($email_hash))
		{
			Controller::warning(PARAMETER);
		}

		$eq_model = new Email_queue_Model();
		$email = $eq_model->where('hash', $email_hash)->find_all();
		
		if (!count($email))
		{
			Controller::error(ACCESS);
		}
		
		$email = $email->current();
		
		echo $email->body;
		
		$email->state = Email_queue_Model::STATE_READ;
		$email->save();
	}
	
	/**
	 * Return image and marks e-mail as read
	 * 
	 * @author David Raška
	 */
	public function displayed()
	{
		$im = imagecreatetruecolor(1, 1);
		$color = imagecolorallocate($im, 255,255,255);
		imagefill($im, 0, 0, $color);
		header('Content-Type: image/png');
		imagepng($im);
		imagedestroy($im);
		
		$email_hash = $this->input->get('id');
		if (!isset($email_hash))
		{
			die;
		}

		$eq_model = new Email_queue_Model();
		$email = $eq_model->where('hash', $email_hash)->find_all();
		
		if (!count($email))
		{
			die;
		}
		
		$email = $email->current();
		
		$email->state = Email_queue_Model::STATE_READ;
		$email->save();
	}
	
	/**
	 * Callback for state of SMS message
	 *
	 * @param object $item
	 * @param string $name 
	 */
	protected static function state($item, $name)
	{
		if ($item->state == Email_queue_Model::STATE_OK)
		{
			echo '<div style="color:green;">' . __('Sent') . '</div>';
		}
		elseif ($item->state == Email_queue_Model::STATE_READ)
		{
			echo '<div style="color:green;">' . __('Read') . '</div>';
		}
		elseif ($item->state == Email_queue_Model::STATE_NEW)
		{
			echo '<div style="color:grey;">' . __('Unsent') . '</div>';
		}
		elseif ($item->state == Email_queue_Model::STATE_FAIL)
		{
			echo '<b style="color:red;">' . __('Failed') . '</b>';
		}
	}
}
