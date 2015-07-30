<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo __('Registration has been sended') ?> | <?php echo $this->settings->get('title') ?></title>
		<?php echo html::stylesheet('media/css/installation.css') ?>
	</head>

	<body>

		<div id="main">
			<h1><a href="<?php echo url_lang::base() ?>"><span>FreenetIS</span></a></h1>
			<div class="flags">
				<?php echo special::create_language_flags(array('cs' => 'Česky', 'en' => 'English')) ?>
			</div>
			<div id="main-padd">
				<div id="content">
					<h2><?php echo __('Thank you for your registration') ?></h2>
					<p><b><?php echo __('Your registration has been sended and waiting for admin approval') ?>.</b></p>
					<p><?php echo __('You will be inform by your email address about admin decision') ?>.</p>
					<p><?php echo html::anchor(url_lang::base() . 'login', '&laquo; ' . __('back to login')) ?></p>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		
		<br />

	</body>
	
</html>
