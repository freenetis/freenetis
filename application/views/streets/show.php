<h2><?php echo  __('Street detail') ?></h2><br />
<?php
if ($this->acl_check_edit('Address_points_Controller', 'street'))
	echo html::anchor('streets/edit/'.$street->id,__('Edit'));
?>
<br /><br />
<table class="extended" style="float:left" cellspacing="0">
	<tr>
		<th><?php echo  __('ID') ?></th>
		<td><?php echo  $street->id ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Street') ?></th>
		<td><?php echo  $street->street ?></td>
	</tr>
	<?php if ($street->town_id): ?>
	<tr>
		<th><?php echo  __('Town') ?></th>
		<td><?php echo  html::anchor('towns/show/' . $street->town_id, $street->town) ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo  __('Count of address points') ?></th>
		<td><?php echo  $count_address_points ?></td>
	</tr>
</table>