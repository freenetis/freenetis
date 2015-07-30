<h2><?php echo $headline ?></h2>
<br /><?php echo $form ?><br />

<div id="dialog_ip_address_detail" style="display: none">
	<form class="form">
		<table class="form" cellspacing="0">
			<tr>
				<th><label><?php echo __('Gateway') ?>:&nbsp;<?php echo help::hint('gateway') ?></label></th>
				<td><?php echo form::dropdown('gateway_input', $yes_no_option) ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Service') ?>:&nbsp;<?php echo help::hint('service') ?></label></th>
				<td><?php echo form::dropdown('service_input', $yes_no_option) ?></td>
			</tr>
		</table>
		<button class="submit" type="button"><?php echo __('Save') ?></button>
	</form>
</div>

<div id="dialog_iface_detail" style="display: none">
	<form class="form">
		<table class="form" cellspacing="0">
			<tr>
				<th><label><?php echo __('Interface name') ?>:</label></th>
				<td><?php echo form::input('iface_name_input','','class="textbox" minlength="3" maxlength="250"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Comment') ?>:</label></th>
				<td><?php echo form::textarea('comment_input','','class="textbox" rows="5" cols="20"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Number') ?>:</label></th>
				<td><?php echo form::input('port_number_input','','class="number textbox"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Port mode') ?>:</label></th>
				<td><?php echo form::dropdown('port_mode_input', $port_modes, array(), 'style="width:200px"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Wireless mode') ?>:</label></th>
				<td><?php echo form::dropdown('wireless_mode_input', $wireless_modes, array(), 'style="width:200px"') ?></td>
			</tr>
			<tr>
				<th><label><?php echo __('Wireless antenna') ?>:</label></th>
				<td><?php echo form::dropdown('wireless_antenna_input', $wireless_antennas, array(), 'style="width:200px"') ?></td>
			</tr>
		</table>
		<button class="submit" type="button"><?php echo __('Save') ?></button>
	</form>
</div>

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
				<td><?php echo form::dropdown('duplex_input', $yes_no_option) ?></td>
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
