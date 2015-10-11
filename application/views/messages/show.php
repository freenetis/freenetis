<h2><?php echo $headline ?></h2>

<br />
<table class="extended">
	<tr>
		<th colspan="2"><?php echo __('About notification message') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Message name') ?></th>
		<td><?php echo $message->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('User-cancelable') ?></th>
		<td><?php echo callback::boolean($message, 'self_cancel') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Ignore whitelist') ?></th>
		<td><?php echo callback::boolean($message, 'ignore_whitelist') ?></td>
	</tr>
</table>
<br>

<table class="extended">
	<?php if (module::e('redirection') && $message->text): ?>
	<tr>
		<th><?php echo __('Content of the message for redirection') ?></th>
		<td class="notification_message_content"><?php echo $message->text ?></td>
	</tr>
	<?php endif ?>
	<?php if (module::e('email') && $message->email_text): ?>
	<tr>
		<th><?php echo __('Content of the message for E-mail') ?></th>
		<td class="notification_message_content"><?php echo $message->email_text ?></td>
	</tr>
	<?php endif ?>
	<?php if (module::e('sms') && $message->sms_text): ?>
	<tr>
		<th><?php echo __('Content of the message for SMS') ?></th>
        <td class="notification_message_content"><?php echo html::specialchars($message->sms_text) ?></td>
	</tr>
	<?php endif ?>
</table>
