<h2><?php echo __('Address point detail') . ' ' . $address_point->name ?></h2><br />

<?php
if ($gps == '' && $this->acl_check_edit(get_class($this), 'address_point'))
	echo html::anchor('address_points/edit/' . $address_point->id, __('Fill in GPS'));
else
	echo html::anchor('address_points/edit/' . $address_point->id, __('Edit'), array('title' => __('Edit'), 'class' => 'popup_link'));

?>
<br /><br />
<table class="extended" style="float:left; margin-bottom: 20px;" cellspacing="0">
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
	<a href="http://maps.google.com/maps?f=q&hl=<?php echo $lang ?>&geocode=&q=<?php echo $gpsx ?>,<?php echo $gpsy ?>&z=18&t=h&ie=UTF8"  target="_blank">
		<img alt="<?php echo __('Address point detail') ?>" src="http://maps.google.com/maps/api/staticmap?center=<?php echo $gpsx ?>,<?php echo $gpsy ?>&zoom=16&maptype=hybrid&size=400x300&markers=color:red%7C<?php echo $gpsx ?>,<?php echo $gpsy ?>&language<?php echo $lang ?>&sensor=false" style="float: right; margin-right: 10px;" />
	</a>
	<div style="margin-bottom: 10px; float:left"></div>
<?php endif; ?>

<div style="clear: both"></div>
<br /><br />

<h3><?php echo __('Members on this address') ?></h3>
<?php echo $members_grid ?>

<br /><br />

<h3><?php echo __('Devices on this address') ?></h3>
<?php echo $devices_grid ?>

<div class="clear"></div>