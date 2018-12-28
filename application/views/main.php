<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="format-detection" content="telephone=no">
<title><?php echo strip_tags($title) ?> | <?php echo $this->settings->get('title') ?></title>
<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
<?php echo html::stylesheet('media/css/style.css', 'screen') ?>
<?php echo html::stylesheet('media/css/m.style.css', 'handheld, screen and (max-device-width: 640px)') ?>
<?php echo html::stylesheet('media/css/tables.css', 'screen') ?>
<?php echo html::stylesheet('media/css/forms.css', 'screen') ?>
<?php echo html::stylesheet('media/css/print.css', 'print') ?>
<?php echo html::stylesheet('media/css/jquery.validate.password.css') ?>
<?php echo html::stylesheet('media/css/jquery.jstree.css') ?>
<?php echo html::stylesheet('media/css/jquery-ui.css') ?>
<?php echo html::script('media/js/jquery.min', FALSE) ?>
<?php echo html::script('media/js/jquery-ui.min', FALSE) ?>
<?php echo html::script('media/js/jquery.ui.datepicker-cs', FALSE) ?>
<?php echo html::script('media/js/jquery.validate.min', FALSE) ?>
<?php echo html::script('media/js/jquery.cookie.min', FALSE) ?>
<script type="text/javascript"><!--
	// settings for jquery.validate.password
	var security_password_level = <?php echo Settings::get('security_password_level') ?>;
	var security_password_length = <?php echo Settings::get('security_password_length') ?>;
//--></script>
<?php echo html::script('media/js/jquery.validate.password', FALSE) ?>
<?php echo html::script('media/js/jquery.metadata', FALSE) ?>
<?php echo html::script('media/js/jquery.tablesorter', FALSE) ?>
<?php echo html::script('media/js/jquery.form.min', FALSE) ?>
<?php echo html::script('media/js/jquery.timer', FALSE) ?>
<?php echo html::script('media/js/jquery.autoresize', FALSE) ?>
<?php echo html::script('media/js/jquery.jstree.js', FALSE) ?>
<?php echo html::script('media/js/messages_cs', FALSE) ?>
<?php echo html::script('media/js/php.min', FALSE) ?>
<?php if (isset($google_jsapi_enabled)): ?><script type="text/javascript" src="https://www.google.com/jsapi"></script><?php endif ?>
<?php if (isset($mapycz_enabled)): ?><script type="text/javascript" src="https://api.mapy.cz/loader.js"></script><script type="text/javascript">Loader.load()</script><?php endif ?>
<?php if (TextEditor::$instance_counter): ?>
<?php echo html::script('media/js/tinymce/tiny_mce', FALSE) ?>
<script type="text/javascript"><!--
	// set up of tinyMCE only if pop up is not on
	/* advanced editor */
	var advancedTinyMCE = {
		// General options
		language :                          "<?php echo Config::get('lang') ?>",
		entity_encoding :                   "raw",
		mode :                              "textareas",
		theme :                             "advanced",
		editor_selector :                   "wysiwyg",
		editor_deselector :                 "textarea",
		plugins :                           "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
		elements :                          "registration_license",
		theme_advanced_toolbar_location :   "top",
		theme_advanced_buttons1 :           "preview,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
		theme_advanced_buttons2 :           "pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,|,forecolor,backcolor",
		theme_advanced_buttons3 :           "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
		theme_advanced_buttons4 :           "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
		theme_advanced_toolbar_align :      "left",
		theme_advanced_statusbar_location : "bottom",
		theme_advanced_resizing :           false,
		height :                            "480px",
		width :                             "600px",
		extended_valid_elements :			"iframe[src|width|height|name|align]",
		convert_urls :						0,
		remove_script_host :				0
	}
	tinyMCE.init(advancedTinyMCE);
	
	/* simple editor */
	tinyMCE.init({
		// General options
		language :                          "<?php echo Config::get('lang') ?>",
		entity_encoding :                   "raw",
		mode :                              "textareas",
		theme :                             "advanced",
		editor_selector :                   "wysiwyg_simple",
		editor_deselector :                 "textarea",
		theme_advanced_buttons1 :           "bold,italic,underline,strikethrough,|,link,unlink,|,undo,redo",
		theme_advanced_buttons2 :           "",
		theme_advanced_buttons3 :           "",
		theme_advanced_buttons4 :           "",
		theme_advanced_toolbar_location :   "top",
		theme_advanced_toolbar_align :      "left",
		theme_advanced_resizing :           false,
		height :                            "170px",
		width :                             "500px",
		convert_urls :						0,
		remove_script_host :				0
	});
//--></script>
<?php endif; ?>
<?php echo html::script('media/js/detect_mobile_browser', FALSE) ?>
<script type="text/javascript" src="<?php echo url_lang::base() .'js/' . url_lang::current() .  server::query_string() ?>"></script>
</head>
<body>
<?php if (!$this->popup): ?>	 
<div id="main">
	<?php if (!isset($loading_hide)): ?>
	<div id="loading-overlay"></div>
	<?php endif; ?>
	<div id="header">
		<div id="cellphone_show_menu"></div>
		<div id="cellphone_menu_tooltip"><?php echo __('Click on logo to open menu') ?></div>
		<a href="<?php echo url::base() ?>"><h1 id="logo"><span>Free<em>net</em>IS</span></h1></a>
		<div class="separator1"></div>
		<div class="status">
			<span class="logged_user"><?php echo $this->user_id ? __('Logged user') : __('Unlogged user') ?></span>&nbsp;
			<a href="<?php echo url_lang::base() ?>languages/change" title="<?php echo __('Change language') ?>" class="popup_link action_field_icon"><img src="<?php echo url::base() ?>media/images/icons/flags/<?php echo Config::get('lang') ?>.png" alt="<?php echo __('Change language') ?>" class="change_language_link"/></a>
			
			<?php
			
			if ($this->user_id)
			{
				
				$caption = __('Mail inbox');
				$src = 'media/images/layout/inbox.png';
				
				if ($this->unread_user_mails)
				{
					$caption .= ' ('.$this->unread_user_mails.')';
					$src = 'media/images/layout/inbox_warning.png';
				}
				
				echo html::anchor('mail/inbox', html::image(array
					(
						'src'	=> $src,
						'alt'	=> $caption,
						'title'	=> $caption
					)), array
					(
						'class' => 'action_field_icon'
					)
				);
			}

			?>
			<span class="cellphone_hide"><a class="action_field_icon" href="javascript: window.print();"><img src="<?php echo url::base() ?>media/images/layout/print.png" alt="print icon" /></a></span>
			
			<?php
			
			if (isset($this->is_favourite) && $this->user_id)
			{
				$caption = __('Add to favourites');
				$src = 'media/images/layout/fav_empty.png';
				
				if ($this->is_favourite)
				{
					$caption = __('Edit favourites');
					$src = 'media/images/layout/fav.png';
				}
				
				// add to favourites page canot be added to favourites
				if (url_lang::current() != 'user_favourite_pages/toggle')
				{
					echo html::anchor('user_favourite_pages/toggle?title=' . urlencode(strip_tags($title)) . '&page=' . urlencode(url_lang::current()) , html::image(array
						(
							'src'	=> $src,
							'alt'	=> $caption,
							'title'	=> $caption
						)), array
						(
							'class' => 'action_field_icon'
						)
					);
				}
			}
			?>
			
			<?php
			if ($this->axo_doc_access)
			{
				echo '<a href="'.AXODOC_URL.'?controller='.urlencode(url::slice(url::current(), 1, 2)).'" class="action_field_icon" target="_blank">' . html::image(array
				(
					'src'	=> 'media/images/layout/axodoc.png',
					'alt'	=> __('Show AXO documentation (required access rights) for this page'),
					'title'	=> __('Show AXO documentation (required access rights) for this page'),
					'class' => 'action_field_icon'
				))."</a>";
			}
			
			?>
			
			<table>
				<tr>
					<td class="orange cellphone_hide"><?php echo $this->user_id ? __('Name').':' : '' ?></td>
					<td class="bold">&nbsp;<?php echo $this->session->get('user_full_name') ?>&nbsp;(<?php echo $this->session->get('member_login') ?>)</td>
				</tr>
				<tr>
					<td class="orange cellphone_hide"><?php echo __('IP address').':' ?></td>
					<td class="bold"><div id="user_ip_address">&nbsp;<?php echo $this->ip_address_span ?></div></td>
				</tr>
			</table>
		</div>

		<div class="logout">
			<div><?php echo $this->user_id ? html::anchor('login/logout/', __('Logout')) : html::anchor('login', __('Login')) ?></div>
		</div>
		
	</div>

	<div id="middle">
		<div id="menu">
			<div id="cellphone_hide_menu"></div>
			<div id="menu-padd">
				<?php
				if ($this->user_id):
					echo form::open(url_lang::base().'search', array('method' => 'get', 'autocomplete' => 'off', 'class' => 'search'));
					echo form::input('keyword',(isset($keyword) ? $keyword : ''));
					echo form::imagebutton('search_submit', url::base().'media/images/layout/search.gif');
					echo form::close();
				?>
				<div id="whisper"></div>
				<?php
					try
					{
						echo $this->build_menu();
					}
					catch (Exception $e) 
					{
						Log::add_exception($e);
					}
				endif
				?>
			</div>
			<div class="clear"></div>
		</div>

		<div id="content">
			<div id="content-padd">
				<?php echo status::render() ?>
				<?php if (isset($breadcrumbs)): ?>
				<span class="breadcrumbs"><?php echo $breadcrumbs ?></span>
				<?php endif ?>
				<?php if (isset($action_logs)): ?>
				<span class="action_logs"><?php echo $action_logs ?></span>
				<?php endif ?>
				<?php if(isset($breadcrumbs) || isset($action_logs)): ?>
				<br /><br />
				<?php endif ?>
				<?php echo $content ?>
			</div>
		</div>

		<div class="clear"></div>
	</div>

	<div id="footer" class="noprint">
		<div id="footer-padd">
			<p style="float:left; margin-left:10px;">Powered by <?php echo html::anchor('http://www.freenetis.org/', 'FreenetIS') ?></p>
			<p style="float:right; margin-right:10px;"><?php echo Kohana::lang('core.stats_footer').' '.__('Version').': '.Version::get_version() ?></p>
			<div class="clear"></div>
		</div>
	</div>
</div>
<?php /* @TODO missing export/grid controller
<div id="export-div" class="dispNone">
	<form id="export-form" method="post">
		<?php echo __('Format') ?>:
		<select name="format">
			<option value="xls">XLS</option>
			<option value="html">HTML</option>
			<option value="pdf">PDF</option>
		</select><br /><br />
		<?php echo __('Name') ?>:
		<input type="text" id="export-form-filename" name="filename"><br /><br />
		<input type="hidden" id="export-form-html" name="html">
		<input type="submit" value="<?php echo __('Do export') ?>">
	</form>
</div>
 */ ?>
	
<?php else: ?>
    <?php if (!$this->dialog): ?>
	<div id="content" class="popup">
		<div id="content-padd">
			<?php echo $content ?>
		</div>
	</div>
    <?php else: ?>
	<?php echo $content ?>
    <?php endif ?>
<?php endif ?>
	
</body>
</html>
