<h2><?php echo __('Show SMS message') ?></h2>

<br />
<table class="extended" style="margin-right:10px; float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo __('About SMS') ?></th>
	</tr>
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $sms->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Author') ?></th>
		<td><?php echo html::anchor('users/show/' . $user->id, $user->get_full_name()); ?></td>
	</tr>
	<tr>
		<th><?php echo __('Create date') ?></th>
		<td><?php echo $sms->stamp ?></td>
	</tr>
	<tr>
		<th><?php echo __('Send date') ?></th>
		<td><?php echo $sms->send_date ?></td>
	</tr>
	<tr>
		<th><?php echo __('Number of the sender') ?></th>
		<td><?php echo $sms->sender ?></td>
	</tr>
	<tr>
		<th><?php echo __('Number of the recipient') ?></th>
		<td><?php echo $receiver ?></td>
	</tr>
</table>

<table class="extended" cellspacing="0" style="width:360px;">
	<tr>
		<th colspan="2"><?php echo __('Login data') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td><b><?php echo ($sms->type == Sms_message_Model::RECEIVED) ? __('Received message') : __('Sent message'); ?></b></td>
	</tr>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><?php $this->state($sms, null) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Gateway') ?></th>
		<td><?php echo Sms::get_driver_name($sms->driver) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Information') ?></th>
		<td><?php echo ($sms->state == Sms_message_Model::SENT_FAILED) ? __($sms->message) : $sms->message ?></td>
	</tr>
	<tr>
		<th><?php echo __('Answer') ?></th>
		<td><?php echo $answer ?></td>
	</tr>
</table>

<br />
<br />

<table class="extended" style="margin-right:10px; float:left; width:730px;">
	<tr>
		<th colspan="2"><?php echo __('Text') ?></th>
	</tr>
	<tr>
		<td><?php echo $sms->text ?></td>
	</tr>
</table>

