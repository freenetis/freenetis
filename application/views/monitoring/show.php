<h2><?php echo __('Monitoring detail of device').' '.$monitor_host->device->name; ?></h2>
<br />

<table class="extended" style="float:left; width:330px;">
	<tr>
		<th><?php echo __('Current state') ?></th>
		<td><span style="color: <?php echo Monitor_host_Model::get_color($monitor_host->state) ?>"><?php echo Monitor_host_Model::get_label($monitor_host->state) ?></span></td>
	</tr>
	<tr>
		<th><?php echo __('Current latency') ?></th>
		<td><?php echo text::not_null($monitor_host->latency_current, TRUE, '???', __('ms')) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Minimal latency') ?></th>
		<td><?php echo text::not_null($monitor_host->latency_min, TRUE, '???', __('ms')) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Maximal latency') ?></th>
		<td><?php echo text::not_null($monitor_host->latency_max, TRUE, '???', __('ms')) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Average latency') ?></th>
		<td><?php echo text::not_null($monitor_host->latency_avg, TRUE, '???', __('ms')) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Total polls') ?></th>
		<td><?php echo $monitor_host->polls_total ?></td>
	</tr>
	<tr>
		<th><?php echo __('Failed polls') ?></th>
		<td><?php echo $monitor_host->polls_failed ?></td>
	</tr>
	<tr>
		<th><?php echo __('Availability') ?></th>
		<td><?php echo $monitor_host->availability ?>%</td>
	</tr>
</table>