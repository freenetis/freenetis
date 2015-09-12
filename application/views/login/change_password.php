<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Freenetis" />
<meta name="description" content="Freenetis" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<title><?php echo  $title ?> | <?php echo $this->settings->get('title') ?></title>
<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
<?php echo html::stylesheet('media/css/installation.css', 'screen') ?>
<?php echo html::stylesheet('media/css/m.installation.css', 'handheld, screen and (max-device-width: 640px)') ?>
<?php echo html::stylesheet('media/css/jquery.validate.password.css') ?>
<?php echo html::script('media/js/jquery.min', FALSE) ?>
<?php echo html::script('media/js/jquery.validate.min', FALSE) ?>
<script type="text/javascript"><!--
	// settings for jquery.validate.password
	var security_password_level = <?php echo Settings::get('security_password_level') ?>;
	var security_password_length = <?php echo Settings::get('security_password_length') ?>;
	//--></script>
<?php echo html::script('media/js/jquery.validate.password', FALSE) ?>
<?php echo html::script('media/js/messages_cs', FALSE) ?>
<script type="text/javascript"><!--
	$(document).ready(function()
	{
		$.validator.passwordRating.messages = {
			"too-short": "<?php echo __('Too short') ?>",
			"very-weak": "<?php echo __('Very weak') ?>",
			"weak": "<?php echo __('Weak') ?>",
			"good": "<?php echo __('Good') ?>",
			"strong": "<?php echo __('Strong') ?>"
		}

		$('form').validate();
	});

	//--></script>
<style type="text/css"><!--
	table.form th, table.form td {
		border-bottom-width: 0px;
	}
--></style>
</head>

<body onload="document.getElementById('password').focus();">

<div id="main" style="width: 390px;">
	<h1><span>Free<em>net</em>IS</span><i><?php echo Version::get_version() ?></i></h1>
	<div class="flags">
			<?php echo  special::create_language_flags(array('cs' => 'Česky', 'en' => 'English')) ?>
	</div>
	<div id="main-padd">
	    <div id="content" style="width: auto;">
		<div id="login_div">
		    <h2 style="margin-bottom: 0.8em"><?php echo  __('new password') ?></h2>
			<p style="text-align: justify;"><?php echo __('You have logged in using onetime password').' '.__('You have to change your password before you can use this system.') ?></p>
			<?php echo form::open(url_lang::base().'login/change_password', array('class' => 'login_form')) ?>
				<?php if (!empty($error)): ?><div id="error"><strong><?php echo $error ?></strong></div><?php endif; ?>
				<?php if (!empty($success)): ?><div id="success"><strong><?php echo $success ?></strong></div><?php endif; ?>
				<table cellspacing="0" cellpadding="0" class="form">
					<tr>
						<th class="label_required"><?php echo form::label('password', __('New password').':') ?></th>
						<td>
							<?php echo form::password('password', '', ' maxlength="50" class="required main_password"') ?>
							<div class="password-meter" style="float:none;margin:0px;
							">
								<div class="password-meter-message">&nbsp;</div>
								<div class="password-meter-bg">
									<div class="password-meter-bar"></div>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<th class="label_required"><?php echo form::label('confirm_password', __('Confirm new password').':') ?></th>
						<td><?php echo form::password('confirm_password', '', ' maxlength="50" class="required"') ?></td>
					</tr>
				</table>
				<?php echo  form::submit('submit', __('Change password'), ' class="submit" style="float: left; margin: 15px 0px 0px 0px;"') ?>
				<p class="forgotten_password_link"><?php echo html::anchor('login/logout', __('Logout')) ?></p>
			<?php echo  form::close() ?>
		</div>
		<div class="clear"></div>
	    </div>
		<div class="clear"></div>
	</div>
</div>

</body>
</html>
