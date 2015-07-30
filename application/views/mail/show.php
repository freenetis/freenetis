<br />
<?php echo html::anchor('mail/write_message/0/'.$message->id, __('Reply'), array('class' => 'button_link')) ?>
<?php echo html::anchor('mail/delete_message/'.$message->id, __('Delete'), array('class' => 'button_link delete_link')) ?>
<table class="extended" style="width: 720px; margin-top: 25px">
	<tr>
		<th><?php echo  __('Subject') ?></th>
		<td style="width: 610px;"><b><?php echo (mail_message::is_formated($message->subject) ? mail_message::printf($message->subject) : $message->subject) ?></b></td>
	</tr>

<?php if ($from_user): ?>
	<tr>
		<th><?php echo  __('From') ?></th>
		<td><?php echo html::anchor('users/show/'.$from_user->id, $from_user->name.' '.$from_user->surname, array('title' => __('Show user'))) ?></td>
	</tr>
<?php endif ?>

<?php if ($to_user): ?>
	<tr>
		<th><?php echo  __('To') ?></th>
		<td><?php echo html::anchor('users/show/'.$to_user->id, $to_user->name.' '.$to_user->surname, array('title' => __('Show user'))) ?></td>
	</tr>
<?php endif ?>
	
	<tr>
		<th><?php echo  __('Time') ?></th>
		<td style="width: 610px;"><?php echo date::mail_time($message->time) ?>
		<?php
		if (date::day_diff(date('Y-m-d H:i:s'), $message->time) == 1)
			echo '('.__('1 day ago').')';
		else if (date::day_diff(date('Y-m-d H:i:s'), $message->time) > 1 && date::day_diff(date('Y-m-d H:i:s'), $message->time) < 14)
			echo '('.__('%s days ago', date::day_diff(date('Y-m-d H:i:s'), $message->time)).')';
		else if (date::day_diff(date('Y-m-d H:i:s'), $message->time) == 0)
		{
			if (date::hour_diff(date('Y-m-d H:i:s'), $message->time) == 1)
				echo '('.__('1 hour ago').')';
			else if (date::hour_diff(date('Y-m-d H:i:s'), $message->time) > 1)
				echo '('.__('%s hours ago', date::hour_diff(date('Y-m-d H:i:s'), $message->time)).')';
			else if (date::hour_diff(date('Y-m-d H:i:s'), $message->time) == 0)
			{
				if (date::minute_diff(date('Y-m-d H:i:s'), $message->time) <= 1)
					echo '('.__('1 minute ago').')';
				else
					echo '('.__('%s minutes ago', date::minute_diff(date('Y-m-d H:i:s'), $message->time)).')';
			}
		}
		?></td>
	</tr>
</table>

<table class="extended" style="width: 720px; margin-top: 25px;">
	<tr>
	    <th colspan="2">
		Text:</th>
	</tr>

	<tr>
	    <td colspan="2" style="padding: 15px;">
		<?php echo (mail_message::is_formated($message->body) ? mail_message::printf($message->body) : $message->body) ?></td>
	</tr>

</table>