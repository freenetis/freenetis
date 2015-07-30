<h2><?php echo __('Phone invoices of user') ?></h2><br />

<?php echo $grid ?>
<br />
<h3><?php echo __('Total price') ?></h3>

<table class="extended" cellspacing="0">
    <tr>
		<th><?php echo __('Company') ?>:</th>
		<td>
			<?php echo number_format($total_prices->price_company, 2, ',', ' ') ?>
			<?php echo __(Settings::get('currency')) ?>
		</td>
    </tr>
    <tr>
		<th><?php echo __('Private') ?>:</th>
		<td>
			<b><?php echo number_format($total_prices->price_private, 2, ',', ' ') ?></b>
			<?php echo __(Settings::get('currency')) ?>
		</td>
    </tr>
</table>
