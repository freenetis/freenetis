<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo __('Installation done') ?> | <?php echo $this->settings->get('title') ?></title>
<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
<?php echo html::stylesheet('media/css/installation.css') ?>
</head>

<body>

<div id="main">
	<h1><span><?php echo $this->settings->get('title') ?></span></h1>
	<div class="flags">
			<?php echo  special::create_language_flags(array('cs' => 'Česky', 'en' => 'English')) ?>
		</div>
	<div id="main-padd">
		<div id="content">
		    <h2><?php echo __('Installation done') ?></h2><br />
			<p><?php echo __('Installation has been successfully finished') . '<br>' .
				__('Now you can login using account name and password you entered during installation.') ?></p>
		    <?php echo form::open(url_lang::base().'login') ?>
		    <?php echo form::submit('submit', __('Login')) ?>
		    <?php echo form::close() ?>
		</div>
		<div class="clear"></div>
	</div>
</div><br />


</body>
</html>
