<h2><?php echo __('Show e-mail message') ?></h2>

<br />
<table class="extended" style="margin-right:10px; width:360px;">
	<tr>
		<th colspan="2"><?php echo __('About e-mail message') ?></th>
	</tr>
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $email->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Receiver') ?></th>
		<td><?php echo $email->to ?></td>
	</tr>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><?php echo $this->state($email, null) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date and time') ?></th>
		<td><?php echo $email->access_time ?></td>
	</tr>
	<tr>
		<th><?php echo __('Subject') ?></th>
		<td><?php echo $email->subject ?></td>
	</tr>
	<tr>
		<th><?php echo __('Sender') ?></th>
		<td><?php echo $email->from ?></td>
	</tr>
</table>

<br />
<br />

<table class="extended" style="margin-right:10px; width:730px;">
	<tr>
		<th colspan="2"><?php echo __('Text') ?></th>
	</tr>
	<tr>
		<td><div style='padding:1em'><?php echo $email->body ?></div></td>
	</tr>
</table>

