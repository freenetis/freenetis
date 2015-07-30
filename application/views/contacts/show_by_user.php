<h2><?php echo __('User contacts'); ?></h2><br />
<p><?php echo __('Another contact information'); ?></p><br />
<?php if ($can_add)
	echo html::anchor('contacts/add/' . $user_id, __('Add contact'), array('title' => __('Add contact'), 'class' => 'popup_link')) . "<br /><br />" ?>
<?php echo $grid_contacts; ?>

<br /><br />
<h2><?php echo __('Private user contacts'); ?></h2><br />
<p><?php echo __('Private phone contacts of user, which are used in telephone invoices'); ?>.</p>
<p><?php echo __('Contacts can be added in telephone invoices.'); ?>.</p><br />

<?php echo html::anchor('private_phone_contacts/import/' . $user_id, __('Import contact from server Funanbol')) ?>

<?php if (!empty($grid_private_contacts)): ?>
	<br /><br />
	<?php echo $grid_private_contacts; ?>
<?php endif; ?>