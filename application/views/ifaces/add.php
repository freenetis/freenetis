<h2><?php echo $headline ?></h2>

<?php if (isset($additional_info) && !empty($additional_info)): ?>
<br /><div class="status_message_warning"><?php echo $additional_info ?></div>
<?php endif; ?>

<br /><?php echo $form ?><br />


<div id="dialog_link_detail" style="display: none">
	<form class="form">
		<table class="form" cellspacing="0">
			<tr>
				<th><label><?php echo __('Name') ?>:</label></th>
				<td><?php echo form::input('link_name_input', '', 'class="textbox"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Comment') ?>:</label></th>
				<td><?php echo form::textarea('link_comment_input', '', 'class="textbox" rows="5" cols="20"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Medium') ?>:</label></th>
				<td><?php echo form::dropdown('eth_medium_input', $eth_mediums, '', 'style="width:200px"') ?>
				</td>
			</tr>
			<tr>
				<th><label><?php echo __('Medium') ?>:</label></th>
				<td><?php echo form::dropdown('wl_medium_input', $wl_mediums, '', 'style="width:200px"') ?>
				</td>
			</tr>
			<tr>
				<th><label><?php echo __('Medium') ?>:</label></th>
				<td><?php echo form::dropdown('port_medium_input', $port_mediums, '', 'style="width:200px"') ?>
				</td>
			</tr>
			<tr>
				<th><label><?php echo __('Norm') ?>:</label></th>
				<td><?php echo form::dropdown('norm_input', $norms, '', 'style="width:200px"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Bitrate') ?>:</label></th>
				<td><?php echo form::input('bitrate_input', '', 'class="number textbox" style="width:100px; margin-right:5px;"') ?>
					<?php echo form::dropdown('bitrate_unit_input', $bit_units) ?>
				</td>
			</tr>
			<tr>
				<th><label><?php echo __('Duplex') ?>:</label></th>
				<td><?php echo form::dropdown('duplex_input', arr::bool()) ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('SSID') ?>:</label></th>
				<td><?php echo form::input('ssid_input', '', 'class="textbox"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Frequency') ?>:</label></th>
				<td><?php echo form::input('frequency_input', '', 'class="number textbox"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Channel') ?>:</label></th>
				<td><?php echo form::input('channel_input','','class="number textbox"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Channel width') ?>:</label></th>
				<td><?php echo form::input('channel_width_input', '', 'class="number textbox"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Polarization') ?>:</label></th>
				<td><?php echo form::dropdown('polarization_input', $polarizations, '', 'style="width:200px"') ?></td>
			</tr>
		</table>
		<button class="submit" type="button"><?php echo __('Save') ?></button>
	</form>
</div>

<div id="dialog_filter_devices" class="dispNone"><?php echo $filter ?></div>
