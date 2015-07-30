<h2><?php echo __('Topology of device').' '.$device->name ?></h2>

<ul class="topology" id="topology-<?php echo $device->id ?>">
<?php foreach ($device_topology->ifaces as $iface): ?>
	<li>
		<?php echo html::anchor(
			'ifaces/show/'.$iface->id,
			$iface->name,
			array
			(
				'title'	=> __('Display detail of interface'),
				'class' => 'popup_link'
			)
		) ?>:
		<?php if ($iface->connected_devices): ?>
			<?php if (count($iface->connected_devices) > 0): ?>
				<?php if (count($iface->connected_devices) > 1): ?>
					<?php echo count($iface->connected_devices)?> <?php echo __('devices') ?>
					<ul>
				<?php endif ?>
				<?php foreach ($iface->connected_devices as $connected_device): ?>
				<?php echo count($iface->connected_devices) > 1 ? '<li>' : '' ?>
				<h3><?php if ($this->acl_check_view('Devices_Controller', 'topology', $connected_device->member_id)): ?>
				<?php echo html::anchor(
						'devices/topology/'.$connected_device->id,
						$connected_device->name,
						array
						(
							'title'	=> __('Display topology of device').' '.$connected_device->name,
							'class' => 'topology-link',
							'id' => 'topology-link-'.$connected_device->id
						)
					);
				?>
				<?php else: ?>
				<?php echo $connected_device->name ?>
				<?php endif?>
				<?php echo html::anchor(
					'devices/show/'.$connected_device->id,
					html::image(array('src' => 'media/images/icons/grid_action/show.png')),
					array
					(
						'title'	=> __('Display detail of device'),
						'class' => 'popup_link'
					)
				) ?></h3>
				<?php echo count($iface->connected_devices) > 1 ? '</li>' : '' ?>
				<?php endforeach ?>
				<?php if (count($iface->connected_devices) > 1): ?>
					</ul>
				<?php endif ?>
				<?php endif ?>
				<?php else: ?>
				<span style="color: green"><?php echo __('Not connected') ?></span>
				<?php endif ?>
	</li>
<?php endforeach ?>
</ul>