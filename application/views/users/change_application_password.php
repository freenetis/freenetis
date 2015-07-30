<h2><?php echo __('Change application password') ?></h2><br />
<br />

<?php if ($this->acl_check_view('Users_Controller', 'application_password', $member_id)): ?>
	<?php echo __('Current application password is') ?> <b><?php echo $password ?></b>
	<br /><br />
<?php endif ?>

<?php echo $form ?>

