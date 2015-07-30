<?php echo form::open(url_lang::base() . 'email/send') ?>
<?php echo form::hidden('email_member_id', $email_member_id) ?>
<?php echo form::hidden('email_to', $email_to) ?>
<?php echo form::hidden('email_from', $email_from) ?>

<table class="extended" cellspacing="0" style="float:left">
	<tr>
        <th><?php echo __('From') ?></th>
		<td><b><?php echo $email_from; ?></b></td>
	</tr>
    <tr>
        <th><?php echo __('To') ?></th>
		<td><b><?php echo $email_to; ?></b></td>
	</tr>
    <tr>
        <th><?php echo __('Subject') ?></th>
		<td><b><?php echo form::input(array('name' => 'subject', 'style' => 'width:651px;'), $subject) ?></b></td>
	</tr>
    <tr >
        <th style="vertical-align: top"><?php echo __('Message') ?></th>
		<td><?php echo $editor ?></td>
	</tr>
    <tr>
        <th>&nbsp;</th>
		<td style="text-align: right"><?php echo form::submit('submit', __('Send'), 'class="submit"'); ?></td>
	</tr>
</table>

<?php echo form::close() ?>