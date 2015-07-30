<h2><?php echo __('Edit contact'); ?></h2><br />

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
</table>

<?php echo $form; ?>
