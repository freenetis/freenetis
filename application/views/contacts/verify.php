<h2><?php echo __('Verify contact'); ?></h2><br />

<table cellspacing="0" class="form" id="form_table">
    <tr>
		<th><?php echo __('Type') ?>:</th>
		<td><b><?php echo $contact_type; ?></b></td>
    </tr>
    <?php if (!empty($country_code)): ?>
    <tr>
		<th><?php echo __('Country code') ?>:</th>
		<td><b><?php echo $country_code; ?></b></td>
    </tr>
	<?php endif; ?>
    <tr>
		<th><?php echo __('Value') ?>:</th>
		<td><b><?php echo $value; ?></b></td>
    </tr>
	<tr>
		<th><?php echo __('Verified') ?>: <?php echo help::hint('verified_contact'); ?></th>
		<td><b><?php echo ($verified ? __('Yes') : __('No')) ?></b></td>
	</tr>
</table>

<div>
	<div class="submit_hint"><?php echo $form; ?></div>
	<div class="submit_hint"><?php echo help::hint('verify_contact'); ?></div>
</div>
<div>
	<div class="submit_hint"><?php echo $form2; ?></div>
	<div class="submit_hint"><?php echo help::hint('send_verification_message'); ?></div>
</div>