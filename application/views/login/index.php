<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Freenetis" />
<meta name="description" content="Freenetis" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?php echo  $title ?> | <?php echo $this->settings->get('title') ?></title>
<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
<?php echo  html::stylesheet('media/css/installation.css', 'screen') ?>
<style type="text/css"><!--
	table.form th, table.form td {
		border-bottom-width: 0px;
	}
--></style>
</head>

<body onload="document.getElementById('username').focus();">

<div id="main" style="width: 579px;">
	<h1><span>Free<em>net</em>IS</span></h1>
	<div class="flags">
			<?php echo  special::create_language_flags(array('cs' => 'Česky', 'en' => 'English')) ?>
	</div>
	<div id="main-padd">
	    <div id="lock">&nbsp;</div>
	    <div id="content" style="width: auto;">
		<div id="login_div">
			<h2 style="margin-bottom: 0.8em"><?php echo  __('login to') ?></h2>
		    <?php echo form::open(url_lang::base().'login', array('class' => 'login_form')) ?>
				<table cellspacing="0" cellpadding="0" class="form" style="width: auto;">
					<tr>
						<th><?php echo form::label('username', __('Login name').':') ?></th>
						<td><?php echo form::input('username',@$_POST['username'],' maxlength="50"') ?></td>
					</tr>
					<tr>
						<th><?php echo form::label('password', __('Password').':') ?></th>
						<td><?php echo form::password('password', '', ' maxlength="50"') ?></td>
					</tr>
				</table>
				<?php echo  form::submit('submit', __('login'), ' class="submit" style="float: left; margin: 15px 0px 0px 0px;"') ?>
				<?php if (Settings::get('self_registration')) echo '<p class="registration">'.html::anchor('registration', __('Applicant for membership')).'</p>' ?>
				<?php if (Settings::get('forgotten_password')) echo '<p class="registration">'.html::anchor('forgotten_password', __('Forgotten password')).'</p>' ?>
			<?php echo  form::close() ?>
		</div>
		<div class="clear"></div>
	    </div>
		<div class="clear"></div>
	</div>
	<?php if ($err_message = $error): ?> <div id="error"><strong><?php echo  $err_message ?></strong></div><?php endif; ?>
</div>

</body>
</html>
