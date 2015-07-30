<?php
echo '<?xml version="' . $const['version'] . '" encoding="' . $const['encoding'] . '"?>';
echo '<dat:dataPack id="' . $const['id'] . 
		'" ico="' . $const['org_id'] . 
		'" application="' . $const['application'] .
		'" version="' . $const['invoice_ver'] .
		'" note="Imported from ' . $const['application'] .
		'" xmlns:dat="' . $const['data_ns'] .
		'" xmlns:inv="' . $const['invoice_ns'] .
		'" xmlns:typ="' . $const['type_ns'] .
		'" >';
?>

<?php  
foreach ($invoices as $invoice) { ?>
<dat:dataPackItem <?php echo 'id="DP' . $invoice->invoice_nr . '" version="' . $const['invoice_ver'] . '" '?>>
	<inv:invoice <?php echo 'version="' . $const['invoice_ver'] . '" '?>>
		<inv:invoiceHeader>
			<inv:invoiceType><?php echo $invoice->invoice_type ?
							'receivedInvoice' :
							'issuedInvoice' ?></inv:invoiceType>
			<inv:number>
				<typ:numberRequested><?php echo $invoice->invoice_nr ?></typ:numberRequested>
			</inv:number>
			<inv:symVar><?php if(!empty($invoice->var_sym))echo $invoice->var_sym; ?></inv:symVar>
			<inv:date><?php echo $invoice->date_inv ?></inv:date>
			<inv:dateTax><?php echo $invoice->date_vat ?></inv:dateTax>
			<inv:dateDue><?php echo $invoice->date_due ?></inv:dateDue>
			<inv:partnerIdentity>
				<typ:address>
					<typ:company><?php echo $invoice->company ?></typ:company>
					<typ:name><?php echo $invoice->partner ?></typ:name>
					<typ:city><?php echo $invoice->town ?></typ:city>
					<typ:street><?php echo address::street_join($invoice->street, $invoice->street_number) ?></typ:street>
				<typ:zip><?php echo $invoice->zip_code ?></typ:zip>
				<typ:ico><?php echo $invoice->organization_identifier ?></typ:ico>
				<typ:country>
					<typ:ids><?php echo $invoice->country ?></typ:ids>
				</typ:country>
				<typ:phone><?php echo $invoice->phone ?></typ:phone>
				<typ:email><?php echo $invoice->email ?></typ:email>
				</typ:address>
			</inv:partnerIdentity>
			<inv:numberOrder><?php if(!empty($invoice->order_nr)) echo $invoice->order_nr; ?></inv:numberOrder>
			<inv:symConst><?php if(!empty($invoice->con_sym)) echo $invoice->con_sym; ?></inv:symConst>
		<?php 
			$account_nr = null;
			$bank_code = null;
			if (!empty($invoice->account_nr)) {
				@list($account_nr, $bank_code) = 
					explode('/', $invoice->account_nr);
		?>
			<inv:account>					
				<typ:accountNo><?php echo $account_nr ?></typ:accountNo>
				<typ:bankCode><?php echo $bank_code ?></typ:bankCode>
			</inv:account>
<?php } ?>
			<inv:note><?php echo $invoice->note ?></inv:note>
		</inv:invoiceHeader>
		<inv:invoiceDetail>
<?php 
		$price_none = 0;
		$price_low = 0;
		$price_low_vat = 0;
		$price_high = 0;
		$price_high_vat = 0;
		$foreign_curr = $invoice->currency != $const['currency'];
		$invoice_item_model = new Invoice_item_Model();
		$invoice_items = $invoice_item_model->get_items_of_invoice($invoice->id);
		$item_price = 0;
		$item_price_vat = 0;
		$vat_rate = 0;
		
		foreach ($invoice_items as $item) {
			$item_price = $item->quantity * $item->price;
			$item_price_vat = $item->quantity * $item->price * (1 + $item->vat); ?>
			<inv:invoiceItem>
				<inv:text><?php echo $item->name ?></inv:text>
				<inv:quantity><?php echo $item->quantity ?></inv:quantity>
	<?php	$vat_value = intval($item->vat * 1000);
			if (array_key_exists($vat_value, $vat_var['export'])) {
				$vat_rate = $vat_var['export'][$vat_value];
				switch ($vat_rate) {
					case 'low':
						$price_low += $item_price;
						$price_low_vat += $item_price_vat;
						break;
					case 'high':
						$price_high += $item_price;
						$price_high_vat += $item_price_vat;
						break;
					default:
						$price_none += $item_price;
						break;
				} ?>
				<inv:rateVAT><?php echo $vat_rate ?></inv:rateVAT>
	<?php } else {?>		
				<inv:percentVAT><?php echo $item->vat * 100 ?></inv:percentVAT>
	<?php	}
			if ($foreign_curr) {?>
				<inv:foreignCurrency>
	<?php	} else { ?>
				<inv:homeCurrency>
	<?php	} ?>
					<typ:unitPrice><?php echo round($item->price, 2) ?></typ:unitPrice>
					<typ:price><?php echo round($item_price, 2) ?></typ:price>
					<typ:priceVAT><?php echo round($item_price_vat - $item_price, 2) ?></typ:priceVAT>
					<typ:priceSum><?php echo round($item_price_vat, 2) ?></typ:priceSum>
	<?php	if ($foreign_curr) {?>
				</inv:foreignCurrency>
	<?php	} else { ?>
				</inv:homeCurrency>
	<?php	} ?>
				<inv:code><?php echo $item->code ?></inv:code>
			</inv:invoiceItem>	
	<?php } ?>
		</inv:invoiceDetail>
		<inv:invoiceSummary>
<?php	if ($foreign_curr) {?>
			<inv:roundingDocument>none</inv:roundingDocument>
			<inv:foreignCurrency>
				<typ:currency>
				<typ:ids><?php echo $invoice->currency ?></typ:ids>
				</typ:currency>
				<typ:priceSum><?php echo round($price_high_vat + $price_low_vat + $price_none, 2) ?></typ:priceSum>
			</inv:foreignCurrency>
<?php	} else { ?>
			<inv:roundingDocument>math2one</inv:roundingDocument>
			<inv:homeCurrency>
				<typ:priceNone><?php echo round($price_none, 2) ?></typ:priceNone>
				<typ:priceLow><?php echo round($price_low, 2) ?></typ:priceLow>
				<typ:priceLowVAT><?php echo round($price_low_vat - $price_low , 2) ?></typ:priceLowVAT>
				<typ:priceLowSum><?php echo round($price_low_vat, 2) ?></typ:priceLowSum>
				<typ:priceHigh><?php echo round($price_high, 2) ?></typ:priceHigh>
				<typ:priceHighVAT><?php echo round($price_high_vat - $price_high, 2) ?></typ:priceHighVAT>
				<typ:priceHighSum><?php echo round($price_high_vat, 2) ?></typ:priceHighSum>
				<typ:round>
				<typ:priceRound><?php echo round(ceil($price_none + $price_low_vat + $price_high_vat) - 
											($price_none + $price_low_vat + $price_high_vat), 2) ?></typ:priceRound>
				</typ:round>
			</inv:homeCurrency>
<?php	} ?>
		</inv:invoiceSummary>
	</inv:invoice>
</dat:dataPackItem>
<?php } ?>
</dat:dataPack>
