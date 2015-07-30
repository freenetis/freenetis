<h2><?php echo __('Town detail') ?></h2><br />
<?php
if ($this->acl_check_edit('Address_points_Controller', 'town'))
	echo html::anchor('towns/edit/' . $town->id, __('Edit'));
?>
<br /><br />
<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $town->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Town') ?></th>
		<td><?php echo $town->town ?></td>
	</tr>
	<?php if ($town->quarter != ''): ?>
		<tr>
			<th><?php echo __('Quarter') ?></th>
			<td><?php echo $town->quarter ?></td>
		</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('ZIP code') ?></th>
		<td><?php echo $town->zip_code ?></td>
	</tr>
	<tr>
		<th><?php echo __('Count of address points') ?></th>
		<td><?php echo $count_address_points ?></td>
	</tr>
</table>

<br />
<br />

<h3><?php echo __('Streets') ?></h3>
<br />
<?php echo $grid_streets ?>