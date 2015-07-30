<h2><?php echo $cloud->name ?></h2>
<?php

if ($this->acl_check_edit('Clouds_Controller', 'clouds'))
{
	echo html::anchor('clouds/edit/' . $cloud->id, __('Edit cloud')) . ' | ';
	echo html::anchor('notifications/cloud/' . $cloud->id, __('Notifications'));
}
?>

<br /><br />

<h3><?php echo __('Subnets') ?></h3>
<?php echo $subnets ?>

<br />

<h3><?php echo __('Admins') ?></h3>
<?php echo $admins ?>
