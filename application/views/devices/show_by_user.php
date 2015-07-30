<h2><?php echo $headline ?></h2><br />

<?php
if ($this->acl_check_new('Devices_Controller', 'devices', $member_id))
	echo html::anchor('devices/add/' . $user_id, __('Add new device'))
?><br />

<div id="tabs">
	<ul class="tabs">
		<li class="ui-corner-all"><a href="#user-devices-base-grid"><?php echo __('Base mode') ?></a></li>
		<li class="ui-corner-all"><a href="#user-devices-advanced-grid"><?php echo __('Advanced mode') ?></a></li>
	</ul>

	<br />
	
	<div id="user-devices-base-grid">
		<?php echo $base_grid ?>
	</div>

	<div id="user-devices-advanced-grid" class="clear">
		<?php echo $order_form ?>
		<br />
		<?php echo __('Total items') ?>: <?php echo $total_devices ?> | <?php echo __('All devices') ?>:
		<img src="<?php echo url::base() ?>media/images/icons/ico_minus.gif" id="device_button_minus"> /
		<img src="<?php echo url::base() ?>media/images/icons/ico_add.gif" id="device_button_plus">
		<br /><br />

		<?php foreach ($devices as $id => $device): ?>
			<h3>
				<a name="device_<?php echo $id ?>_link" href="<?php echo url_lang::base() ?>devices/show/<?php echo $id ?>" title="<?php echo __('Show device') ?>"><?php echo __('device') ?> <?php echo $device['name'] ?> (<?php echo $device['type'] ?>)</a>
				<img src="<?php echo url::base() ?>media/images/icons/ico_minus.gif" id="device_<?php echo $id ?>_button" class="device_button">
				<?php echo html::anchor('devices/edit/' . $id, html::image(array('src' => 'media/images/icons/gtk_edit.png')), array('title' => __('Edit device'))) ?>
				<?php echo html::anchor('devices/delete/' . $id, html::image(array('src' => 'media/images/icons/delete.png')), array('title' => __('Delete device'), 'class' => 'delete_link')) ?>
			</h3>

			<div id="device_<?php echo $id ?>" class="device">
				<?php foreach ($device['grids'] as $grid): ?>
					<div class="tabs">
						<ul class="tabs" style="font-size: 12px;">
							<?php if ($grid['ip_addresses'] != ''): ?>
								<li class="ui-corner-all"><a href="#ip_addresses"><?php echo __('IP addresses') ?></a></li>
							<?php endif ?>
							<li class="ui-corner-all"><a href="#interfaces"><?php echo __('Interfaces') ?></a></li>
						</ul>
						
					<?php if ($this->acl_check_view(get_class($this), 'ip_address', $member_id)) { ?>
						<div id="ip_addresses">
						<?php echo $grid['ip_addresses'] ?>
						</div>
					<?php } ?>

					<?php if ($this->acl_check_view(get_class($this), 'iface', $member_id)) { ?>
						<div id="interfaces">
						<?php echo $grid['ifaces'] ?>
						</div>
					<?php } ?>

					</div>
				<?php endforeach ?>
				<br /><br />
			</div>
		<?php endforeach ?>

		<?php echo $this->pagination ?>
		<?php echo $this->selector ?>
	</div>
</div>
