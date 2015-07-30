<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo __('Setup config') ?> - FreenetIS</title>
		<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
		<?php echo html::stylesheet('media/css/installation.css') ?>
	</head>

	<body>

		<div id="main">
			<h1><span>FreenetIS</span><i><?php echo Version::get_version() ?></i></h1>
			<div class="flags">
				<?php echo special::create_language_flags(array('cs' => 'Česky', 'en' => 'English')) ?>
			</div>
			<div id="main-padd">

				<div id="content">
					<?php echo $content ?>
				</div>
				<div class="clear"></div>
			</div>
		</div><br />

	</body>
</html>
