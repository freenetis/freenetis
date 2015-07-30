<?php 
echo '<?xml version="' . $const['version'] . 
		'" encoding="' . $const['encoding'] . '"?>';
echo '<eform version="' . $const['eform_version'] . '">';
echo '<invoice version="' . $const['invoice_version'] . '">';
$con_sym = !empty($invoice->con_sym) ? $invoice->con_sym : '';
$var_sym = !empty($invoice->var_sym) ? $invoice->var_sym : '';
$order_nr = !empty($invoice->order_nr) ? $invoice->order_nr : '';
echo '<documenttax number="' . $invoice->invoice_nr . 
		'" date="' . $invoice->date_inv . 
		'" datetax="' . $invoice->date_vat . 
		'" datedue="' . $invoice->date_due . 
		'" symvar="' . $var_sym . 
		'" symconst="' . $con_sym . 
		'" numberorder="' . $order_nr .
		'" >' . iconv($enc['in'], $enc['out'], $invoice->note) . '</documenttax>';

$price_none = 0;
$price_low = 0;
$price_low_vat = 0;
$price_high = 0;
$price_high_vat = 0;
$vat_rate = 0;

foreach ($invoice->invoice_items as $item) {
	$item_price = $item->price * $item->quantity;
	$item_price_vat = $item->price * $item->quantity * (1 + $item->vat);
	$vat_value = intval($item->vat * 1000);
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
		}
	}
	else
		$price_none += $item_price;
	
	$vat = $invoice->vat ? 'yes' : 'no';
	
	echo '<invoiceitem code="' . iconv($enc['in'], $enc['out'], $item->code) . 
			'" quantity="' . iconv($enc['in'], $enc['out'], $item->quantity) . 
			'" price="' . round($item->price, 2) . 
			'" payvat="' . $vat . 
			'" ratevat="' . iconv($enc['in'], $enc['out'], $vat_rate) .
			'" pricesum="' . round($item_price, 2) . 
			'" pricesumvat="' . round($item_price_vat, 2) . 
			'">' . iconv($enc['in'], $enc['out'], $item->name) . '</invoiceitem>';
}
echo '<pricestax pricehigh="' . round($price_high, 2) .
		'" pricehighvat="' . round($price_high_vat, 2) .
		'" pricelow="' . round($price_low, 2) . 
		'" pricelowvat="' . round($price_low_vat, 2) . 
		'" pricenone="' . round($price_none, 2) . 
		'" priceround="' . round(ceil($price_none + $price_low_vat + $price_high_vat) - 
								($price_none + $price_low_vat + $price_high_vat), 2) .
		'" roundingdocument="up2one"' . 
		' priceRoundSum="' . ceil($price_none + $price_low + $price_high) . 
		'" priceRoundSumVAT="' . ceil($price_none + $price_low_vat + $price_high_vat) .
		'">';
?>
<supplier>
<?php if (!empty($supplier)) { ?>
	<company></company>
	<name><?php echo iconv($enc['in'], $enc['out'], $supplier->name) ?></name>
	<street><?php 
		$street = address::street_join(!empty($supplier->address_point->street_id) ? 
									$supplier->address_point->street->street : '', 
									$supplier->address_point->street_number);
		echo iconv($enc['in'], $enc['out'], $street); ?></street>
	<city><?php echo iconv($enc['in'], $enc['out'], $supplier->address_point->town->town) ?></city>
	<psc><?php echo $supplier->address_point->town->zip_code ?></psc>
	<ico><?php echo $supplier->organization_identifier ?></ico>
	<dic></dic>
	<?php	
		$phone = null;
		$email = null;
		$contact_model = new Contact_Model();
		$contacts = $contact_model->find_all_users_contacts($supplier->id);
		
		foreach ($contacts as $contact) {
			if ( $contact->type == Contact_Model::TYPE_PHONE )
				$phone = $contact->value;
			if ( $contact->type == Contact_Model::TYPE_EMAIL )
				$email = $contact->value;
		} ?>
	<tel><?php echo $phone ?></tel>
	<fax></fax>
	<email><?php echo $email ?></email>
	<remark></remark>
<?php } else { ?>
	<company><?php echo iconv($enc['in'], $enc['out'], $invoice->partner_company) ?></company>
	<name><?php echo iconv($enc['in'], $enc['out'], $invoice->partner_name) ?></name>
	<street><?php echo iconv($enc['in'], $enc['out'], 
							address::street_join($invoice->partner_street, $invoice->partner_street_number)) ?></street>
	<city><?php echo iconv($enc['in'], $enc['out'], $invoice->partner_town) ?></city>
	<psc><?php echo $invoice->partner_zip_code ?></psc>
	<ico><?php echo $invoice->organization_identifier ?></ico>
	<dic></dic>
	<tel><?php echo $invoice->phone_number ?></tel>
	<fax></fax>
	<email><?php echo $invoice->email ?></email>
	<remark></remark>
<?php } ?>
</supplier>
<customer>
<?php if (!empty($customer)) { ?>
	<company></company>
	<name><?php echo iconv($enc['in'], $enc['out'], $customer->name) ?></name>
	<street><?php 
		$street = address::street_join(!empty($customer->address_point->street_id) ? 
									$customer->address_point->street->street : '', 
									$customer->address_point->street_number);
		echo iconv($enc['in'], $enc['out'], $street); ?></street>
	<city><?php echo iconv($enc['in'], $enc['out'], $customer->address_point->town->town) ?></city>
	<psc><?php echo $customer->address_point->town->zip_code ?></psc>
	<ico><?php echo $customer->organization_identifier ?></ico>
	<dic></dic>
	<?php	
		$phone = null;
		$email = null;
		$contact_model = new Contact_Model();
		$contacts = $contact_model->find_all_users_contacts($customer->id);
		
		foreach ($contacts as $contact) {
			if ( $contact->type == Contact_Model::TYPE_PHONE )
				$phone = $contact->value;
			if ( $contact->type == Contact_Model::TYPE_EMAIL )
				$email = $contact->value;
		} ?>
	<tel><?php echo $phone ?></tel>
	<fax></fax>
	<email><?php echo $email ?></email>
	<remark></remark>
<?php } else { ?>
	<company><?php echo iconv($enc['in'], $enc['out'], $invoice->partner_company) ?></company>
	<name><?php echo iconv($enc['in'], $enc['out'], $invoice->partner_name) ?></name>
	<street><?php echo iconv($enc['in'], $enc['out'], 
						address::street_join($invoice->partner_street, $invoice->partner_street_number)) ?></street>
	<city><?php echo iconv($enc['in'], $enc['out'], $invoice->partner_town) ?></city>
	<psc><?php echo $invoice->partner_zip_code ?></psc>
	<ico><?php echo $invoice->organization_identifier ?></ico>
	<dic></dic>
	<tel><?php echo $invoice->phone_number ?></tel>
	<fax></fax>
	<email><?php echo $invoice->email ?></email>
	<remark></remark>
<?php } ?>
</customer>
<?php	
	$account_nr = null;
	$bank_code = null;
	if (!empty($supplier)) {
		if ($supplier->bank_accounts->count() != 0 ) {
			$bank_accounts = $supplier->bank_accounts->as_array();
			$account_nr = $bank_accounts[0]->account_nr;
			$bank_code = $bank_accounts[0]->bank_nr;
		} 
	} elseif (!empty($invoice->account_nr))
		@list($account_nr, $bank_code) = explode('/', $invoice->account_nr);
?>
<?php
$pay_vat = $invoice->vat ? 'yes' : 'no';

echo '<payment paytype="draft"' .
		' payvat="' . $pay_vat . 
		'" accountno="' . $account_nr . 
		'" bankcode="' . $bank_code . 
		'"></payment>';
?>
</pricestax>
</invoice>
</eform>