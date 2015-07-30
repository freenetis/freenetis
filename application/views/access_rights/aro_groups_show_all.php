<?php if (isset($submenu)): ?>
	<?php echo $submenu; ?><br /><br />
<?php endif ?>

<h2><?php echo $headline ?></h2>
<br />
<?php echo __('Total items') ?>: <?php echo count($rows) ?>
<br /><br />
<?php
// check access
if ($this->acl_check_edit('Settings_Controller', 'access_rights'))
	echo html::anchor('aro_groups/add', __('Add new group')).'<br /><br />';
?>
<div>
	<table class="extended" style="float:left">
		<?php
		foreach ($rows as $row)
			echo $row;
		?>
	</table>
</div>