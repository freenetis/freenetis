<h2><?php echo __('Current prices for VoIP calls') ?></h2><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Destination') ?></th>
		<th><?php echo __('Price') ?></th>
	</tr>
	<?php if (isset($fixed_price) && is_array($fixed_price)): ?>
	<tr>
		<td><?php echo __('Fixed line') ?></td>
		<td><?php echo number_format($fixed_price['price'], 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php endif; ?>
	<?php if (isset($cellphone_price) && is_array($cellphone_price)): ?>
	<tr>
		<td><?php echo __('Cellphone operator') ?></td>
		<td><?php echo number_format($cellphone_price['price'], 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php endif; ?>
	<?php if (isset($voip_price) && is_array($voip_price)): ?>
	<tr>
		<td><?php echo __('VoIP') ?></td>
		<td><?php echo number_format($voip_price['price'], 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<td>
			<table>
				<tr>
					<td style="border: none; padding: 0;">
						<input type="text" name="number" id="number" value="<?php echo __('Phone number') ?>" style="width: 110px; color: #ccc" />
					</td>
					<td style="border: none; padding: 0 0 0 2px;">
						<a id="a_calculate" style="text-decoration: none;" title="<?php echo __('Calculate') ?>">
							<img src="<?php echo url::base() ?>media/images/icons/calculate.png" width="16" height="16" alt="<?php echo __('Calculate') ?>" />
						</a>
					</td>
				</tr>
			</table>
		</td>
		<td id="number_price"><?php echo __('Calulate price for call to any number') ?></td>
	</tr>
</table>

<br /><br />

<?php echo $grid; ?>