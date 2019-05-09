<h2><?php echo __('Address point detail') . ' ' . $address_point->name ?></h2><br />

<?php
if ($gps == '' && $this->acl_check_edit('Address_points_Controller', 'address_point'))
	echo html::anchor('address_points/edit/' . $address_point->id, __('Fill in GPS'));
else
	echo html::anchor('address_points/edit/' . $address_point->id, __('Edit'), array('title' => __('Edit'), 'class' => 'popup_link'));

?>
<br /><br />

<div style="display: grid; grid-column-gap: 50px; grid-template-columns: 1fr 2fr; margin-bottom: 2em">

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $address_point->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Country') ?></th>
		<td><?php echo $address_point->country->country_name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Town') ?></th>
		<td><?php echo html::anchor('towns/show/' . $address_point->town->id, $address_point->town->town) ?></td>
	</tr>
	<?php if ($address_point->town->quarter != ''): ?>
		<tr>
			<th><?php echo __('Quarter') ?></th>
			<td><?php echo html::anchor('towns/show/' . $address_point->town->id, $address_point->town->quarter) ?></td>
		</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('ZIP code') ?></th>
		<td><?php echo html::anchor('towns/show/' . $address_point->town->id, $address_point->town->zip_code) ?></td>
	</tr>
	<?php if (!empty($address_point->street->id)): ?>
		<tr>
			<th><?php echo __('Street') ?></th>
			<td><?php echo html::anchor('streets/show/' . $address_point->street->id, $address_point->street->street) ?></td>
		</tr>
	<?php endif; ?>
	<?php if (!empty($address_point->street_number)): ?>
		<tr>
			<th><?php echo __('Street number') ?></th>
			<td><?php echo $address_point->street_number ?></td>
		</tr>
	<?php endif; ?>
	<?php if (!empty($gps)): ?>
		<tr>
			<th><?php echo __('GPS') ?></th>
			<td><?php echo $gps ?></td>
		</tr>
	<?php endif ?>
</table>

<?php if (!empty($gps)): ?>
	<div id="ap_gmap" style="min-height: 400px" data-gpsx="<?php echo $gpsx ?>" data-gpsy="<?php echo $gpsy ?>"></div>
<?php endif; ?>

</div>

<h3><?php echo __('Members on this address') ?></h3>
<?php echo $members_grid ?>

<br /><br />

<h3><?php echo __('Devices on this address') ?></h3>
<?php echo $devices_grid ?>

<div class="clear"></div>