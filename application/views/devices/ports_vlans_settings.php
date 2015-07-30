<h2><?php echo __('Ports and vlans settings') ?></h2><br>
<?php echo $form ?>
<?php if (isset($ports)): ?>
<br><br>
<h3><?php echo $vlan->name ?></h3>
<br>
<?php echo form::open('', array('id' => 'ports-vlans-form')) ?>
<table cellspacing="0" class="form" style="width: 100%">
<tr>
	<th class="group"><?php echo __('Number') ?></th>
	<th class="group"><?php echo __('Mode') ?></th>
	<th class="group"><?php echo __('Port VLAN') ?></th>
	<th class="group"><?php echo __('Tagged') ?></th>
	<th class="group"><?php echo __('Untagged') ?></th>
	<th class="group"><?php echo __('None') ?></th>
</tr>
<?php foreach ($ports as $number => $port): ?>
<tr>
	<th>
		<?php echo $number ?>
		<?php echo form::hidden(array('id['.$number.']' => $port['id'])) ?>
	</th>
	<td>
		<?php echo form::dropdown('mode['.$number.']', Iface_Model::get_port_modes(), $port['mode'], " class='mode'") ?>
	</td>
	<td>
		<?php echo form::dropdown('pvid['.$number.']', $port['vlans'], $port['pvid'], " class='pvid'") ?>
	</td>
	<td class="center">
		<?php echo form::radio('type['.$number.']', Iface_Model::PORT_VLAN_TAGGED, ($port['type'] == Iface_Model::PORT_VLAN_TAGGED), " class='type'") ?>
		</td>
	<td class="center">
		<?php echo form::radio('type['.$number.']', Iface_Model::PORT_VLAN_UNTAGGED, ($port['type'] == Iface_Model::PORT_VLAN_UNTAGGED), " class='type'") ?>
	</td>
	<td class="center">
		<?php echo form::radio('type['.$number.']', NULL, (!$port['type']), " class='type'") ?>
	</td>
</tr>
<?php endforeach ?>
</table>
<?php echo form::submit('submit', __('Submit')) ?>
<?php echo form::close() ?>
<?php endif ?>