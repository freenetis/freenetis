<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>

<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<title><?php echo $error ?></title>

<style type="text/css">
/* <![CDATA[ */
* {padding:0;margin:0;border:0;}
body {background:#eee;font-family:sans-serif;font-size:85%;}
h1,h2,h3,h4 {margin-bottom:0.5em;padding:0.2em 0;border-bottom:solid 1px #ccc;color:#911;}
h1 {font-size:2em;}
h2 {font-size:1.5em;}
p,pre {margin-bottom:0.5em;}
strong {color:#700;}
#wrap {width:600px;margin:2em auto;padding:0.5em 1em;background:#fff;border:solid 1px #ddd;border-bottom:solid 2px #aaa;}
#stats {margin:0;padding-top: 0.5em;border-top:solid 1px #ccc;font-size:0.8em;text-align:center;color:#555;}
.message {margin:1em;padding:0.5em;background:#dfdfdf;border:solid 1px #999;}
.detail {text-align:center;}
.backtrace {margin:0 2em 1em;}
.backtrace pre {background:#eee;}
.error {font-size: 10px; color: red}
/* ]]> */
</style>
<?php echo html::stylesheet('media/css/forms.css', 'screen') ?>
<!--[if IE]>
<?php echo html::stylesheet('media/css/forms.css', 'screen') ?>
<![endif]-->
<!--
 This is a little <script> does two things:
   1. Prevents a strange bug that can happen in IE when using the <style> tag
   2. Accounts for PHP's relative anchors in errors
-->
<script type="text/javascript">document.write('<base href="http://php.net/" />')</script>
<?php echo html::script('media/js/jquery.min', FALSE) ?>
<?php echo html::script('media/js/jquery.validate.min', FALSE) ?>
<script type="text/javascript"><!--

	$(document).ready(function ()
	{
		// toogle trace path
		$('#link_stack_trace').click(function ()
		{
			$(this).hide();
			$('#stack_trace_message').show();
			return false;
		});

		// on click on tetx area remove text and delete this trigger
		$('textarea').focus(function ()
		{
			$(this).text('');
			$(this).focus(function () {});
			return false;
		});

		// form
		$('form').validate();
	});

//--></script>
</head>
<body>
	<div id="wrap">
		<h1><?php echo Kohana::lang('core.Server error') ?></h1>
		<p><?php echo Kohana::lang('core.Please report this error as bug using form below') ?>:</p>
		<form action="<?php echo url_lang::base() ?>email/send_email_to_developers" class="form" method="post" style="margin-bottom: 20px">
			<table cellspacing="4" class="form" style="background: #F1F1F1">
				<tr>
					<td><label for="uname"><?php echo url_lang::lang('texts.Your name') ?>:</label></td>
					<td><input type="text" name="uname" class="required" /></td>
				</tr>
				<tr>
					<td><label for="uemail"><?php echo url_lang::lang('texts.Your email') ?>:</label></td>
					<td><input type="text" name="uemail" class="required email" /></td>
				</tr>
				<tr>
					<td><label for="ename"><?php echo url_lang::lang('texts.Name of error') ?>:</label></td>
					<td><input type="text" name="ename" class="required" /></td>
				</tr>
				<tr>
					<td><label for="udescription"><?php echo url_lang::lang('texts.Description') ?>:</label></td>
					<td><textarea cols="80" rows="20"name="udescription"  class="required" style="width: 380px; height: 100px;"><?php echo url_lang::lang('texts.Describe what you have been doing, when the error came out') ?>...</textarea></td>
				</tr>
				<tr>
					<td></td>
					<td><input type="submit" value="<?php echo Kohana::lang('core.Send error report') ?>" /></td>
				</tr>
				<input type="hidden" name="line" value="<?php echo ($line ? $line : -1); ?>" />
				<input type="hidden" name="file" value="<?php echo ($file ? $line : -1); ?>" />
				<input type="hidden" name="url" value="<?php echo url_lang::current() ?>" />
				<input type="hidden" name="error" value="<?php echo htmlspecialchars($error) ?>" />
				<input type="hidden" name="description" value="<?php echo htmlspecialchars($description) ?>" />
				<input type="hidden" name="detail" value="<?php echo htmlspecialchars(Kohana::lang('core.error_message', $line, $file)) ?>" />
				<input type="hidden" name="trace" value="<?php if (isset($trace)) echo htmlspecialchars($trace); ?>" />
				<input type="hidden" name="message" value="<?php echo (isset($message)) ? $message : '' ?>" />
			</table>
		</form>
		<h2><?php echo $error ?></h2>
		<p><?php echo $description ?></p>
		<p class="message"><?php echo $message ?></p>
		<?php if ($line != FALSE AND $file != FALSE): ?>
			<p class="detail"><?php echo Kohana::lang('core.error_message', $line, $file) ?></p>
		<?php endif; ?>
		<?php if (isset($trace)): ?>
			<h3><?php echo Kohana::lang('core.stack_trace') ?></h3>
			<?php echo $trace ?>
		<?php endif; ?>
		<p id="stats"><?php echo Kohana::lang('core.stats_footer') ?></p>
	</div>
</body>
</html>