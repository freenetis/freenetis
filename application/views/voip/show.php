<h2>
	<?php echo __('VoIP account') . ' ' . $voip->name ?>
</h2>
<br />
<?php echo $links ?>

<br />
<br />
<table class="extended" style="margin-right:10px; float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo __('Login data').'&nbsp;'.help::hint("voip_login_data") ?></th>
	</tr>
    <tr>
		<th><?php echo __('username') ?></th>
		<td><?php echo $voip->name ?></td>
	</tr>
    <tr>
		<th><?php echo __('password') ?></th>
		<td><?php echo html::anchor(url_lang::base() . 'voip/change_password/' . $voip->user_id, __('Change')); ?></td>
	</tr>
    <tr>
		<th><?php echo __('CLIP data') ?></th>
		<td><?php echo $voip->callerid ?></td>
	</tr>
    <tr>
		<th><?php echo __('Proxy server') ?></th>
		<td><?php echo $sip_server ?></td>
	</tr>
</table>

<table class="extended" cellspacing="0" style="width:360px;">
    <tr>
		<th colspan="2"><?php echo __('Phone registration status').'&nbsp;'.help::hint('voip_status') ?></th>
    </tr>
    <tr>
		<th><?php echo __('Status') ?></th>
		<td><?php echo $link_status ? '<b style="color:green;">' . __('Registered') . '</b>' : '<b style="color:red;">' . __('Not registered') . '</b>'; ?></td>

    </tr>
    <tr>
		<th><?php echo __('IP address') ?></th>
		<td><?php echo $ipaddr ?></td>
    </tr>
    <tr>
		<th><?php echo __('Port') ?></th>
		<td><?php echo $port ?></td>
    </tr>
    <tr>
		<th><?php echo __('Expiry time of registration') ?></th>
		<td><?php echo $regseconds . ' s' ?></td>
    </tr>
</table>
<br />
<table class="extended" style="margin-right:10px; float:left; width:360px;">
    <tr>
		<th colspan="2"><?php echo __('Voicemail data') ?></th>
    </tr>
    <tr>
		<th><?php echo __('Status') ?></th>
		<td><?php echo ($voice_status == 1) ? '<b style="color:green;">' . __('Active') . '</b>' : '<b style="color:red;">' . __('Inactive') . '</b>'; ?></td>
    </tr>
    <tr>
		<th><?php echo __('password') ?></th>
		<td><?php echo html::anchor(url_lang::base() . 'voip/change_voicemail_password/' . $voip->user_id, __('Change')); ?></td>
    </tr>
    <tr>
		<th><?php echo __('email') ?></th>
		<td><?php echo $voice_email ?></td>
    </tr>
</table>
<?php if ($void_account_enabled): ?>
<table class="extended" cellspacing="0" style="width:360px;">
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
<?php endif; ?>