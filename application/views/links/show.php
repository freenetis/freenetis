<h2><?php echo $headline ?></h2>
<?php if (isset($submenu)) echo '<div class="submenu">' . $submenu . '</div>'; ?>
<br /><?php echo $links ?><br /><br />

<table class="extended" cellspacing="0" style="float:left; width: 360px;">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $link->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Name') ?></th>
		<td><?php echo $link->name ?></td>
	</tr>
	<tr>
		<th><?php echo __('Medium') ?></th>
		<td><?php echo $medium ?></td>
	</tr>
	<?php if ($link->medium != Link_Model::MEDIUM_ROAMING): ?>
	<tr>
		<th><?php echo __('Bitrate') ?></th>
		<td><?php echo network::size($link->bitrate/1024) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Duplex') ?></th>
		<td><?php echo $duplex ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Comment') ?></th>
		<td><?php echo $link->comment ?></td>
	</tr>
</table>

<?php if ($link->medium == Link_Model::MEDIUM_AIR): ?>
	<table class="extended" style="float:left; margin-left:10px; width:360px;" cellspacing="0">
		<tr>
			<th colspan="2"><?php echo __('Wireless setting') ?></th>
		</tr>
		<?php if ($link->wireless_ssid != ''): ?>
		<tr>
			<th><?php echo __('SSID') ?></th>
			<td><b><?php echo $link->wireless_ssid ?></b></td>
		</tr>
		<?php endif ?>
		<?php if ($link->wireless_norm != ''): ?>
		<tr>
			<th><?php echo __('Norm') ?></th>
			<td><?php echo $link->get_wireless_norm() ?></td>
		</tr>
		<?php endif ?>
		<?php if ($link->wireless_channel != ''): ?>
		<tr>
			<th><?php echo __('Channel') ?></th>
			<td><?php echo $link->wireless_channel ?></td>
		</tr>
		<?php endif ?>
		<?php if ($link->wireless_channel_width != ''): ?>
		<tr>
			<th><?php echo __('Channel width') ?></th>
			<td><?php echo $link->wireless_channel_width ?></td>
		</tr>
		<?php endif ?>
		<?php if ($link->wireless_frequency != ''): ?>
		<tr>
			<th><?php echo __('Frequency') ?></th>
			<td><?php echo $link->wireless_frequency ?></td>
		</tr>
		<?php endif ?>
		<?php if ($link->wireless_polarization != ''): ?>
		<tr>
			<th><?php echo __('Polarization') ?></th>
			<td><?php echo $link->get_wireless_polarization() ?></td>
		</tr>
		<?php endif ?>
	</table>
<?php endif ?>
<br class="clear" />
<br />
<br />
<?php echo $grid ?>
				


