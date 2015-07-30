<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo  $title ?> | <?php echo $this->settings->get('title') ?></title>
<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
<?php echo html::stylesheet('media/css/installation.css') ?>
<?php echo html::stylesheet('media/css/jquery-ui.css') ?>
<?php echo html::stylesheet('media/css/jquery.autocomplete.css') ?>
<?php echo html::stylesheet('media/css/jquery.validate.password.css') ?>
<?php echo html::script('media/js/jquery.min', FALSE) ?>
<?php echo html::script('media/js/jquery-ui.min', FALSE) ?>
<?php echo html::script('media/js/jquery.min', FALSE) ?>
<?php echo html::script('media/js/jquery-ui.min', FALSE) ?>
<?php echo html::script('media/js/jquery.autocomplete.min', FALSE) ?>
<?php echo html::script('media/js/jquery.validate.min', FALSE) ?>
<script type="text/javascript"><!--
	// settings for jquery.validate.password
	var security_password_level = <?php echo Settings::get('security_password_level') ?>;
	var security_password_length = <?php echo Settings::get('security_password_length') ?>;
//--></script>
<?php echo html::script('media/js/jquery.validate.password', FALSE) ?>
<?php echo html::script('media/js/messages_cs', FALSE) ?>
<?php echo html::script('media/js/php.min', FALSE) ?>
</head>

<body>

<div id="main">
	<h1><span><?php echo $this->settings->get('title') ?></span><i><?php echo Version::get_version() ?></i></h1>
	<div id="loading-overlay" style="display: none"></div>
	<div class="flags"><?php echo special::create_language_flags(array('cs' => 'Česky', 'en' => 'English')) ?></div>
	<div id="main-padd">
		<div id="content"><?php echo $content ?></div>
		<div class="clear"></div>
	</div>
</div><br />

</body>
</html>
