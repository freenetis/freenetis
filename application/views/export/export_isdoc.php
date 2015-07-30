<?php
echo '<?xml version="' . $const['version'] . '" encoding="' . $const['encoding'] . '"?>';
echo '<Invoice xmlns="' . $const['namespace'] . '" version="' . $const['isdoc_version'] . '">';
?>

<DocumentType>1</DocumentType>
<ID><?php echo $invoice->invoice_nr ?></ID>
<UUID><?php echo $const['guid'] ?></UUID>
<IssuingSystem><?php echo Settings::get('title') ?></IssuingSystem>
<IssueDate><?php echo $invoice->date_inv ?></IssueDate>
<TaxPointDate><?php echo $invoice->date_vat ?></TaxPointDate>
<VATApplicable><?php echo $invoice->vat ? 'true' : 'false' ?></VATApplicable>
<Note><?php echo $invoice->note; ?></Note>
<LocalCurrencyCode><?php echo $invoice->currency ?></LocalCurrencyCode>
<CurrRate>1</CurrRate>
<RefCurrRate>1</RefCurrRate>
<AccountingSupplierParty>
	<Party>
<?php	if (!empty($supplier)) { ?>
	<PartyIdentification>	
		<ID><?php echo $supplier->organization_identifier ?></ID>
	</PartyIdentification>
	<PartyName>
		<Name><?php echo $supplier->name ?></Name>
	</PartyName>
	<PostalAddress>
		<StreetName><?php if (!empty($supplier->address_point->street_id))
					echo $supplier->address_point->street->street; ?></StreetName>	
		<BuildingNumber><?php if (!empty($supplier->address_point->street_number))
					echo $supplier->address_point->street_number; ?></BuildingNumber>
		<CityName><?php echo $supplier->address_point->town->town ?></CityName>
		<PostalZone><?php echo $supplier->address_point->town->zip_code ?></PostalZone>
		<Country>
		<IdentificationCode><?php echo $supplier->address_point->country
							->country_iso ?></IdentificationCode>
		<Name><?php echo $supplier->address_point->country->country_name ?></Name>
		</Country>
	</PostalAddress>
<?php	if ($invoice->vat) { ?>
	<PartyTaxScheme>
		<CompanyID></CompanyID>
		<TaxScheme>VAT</TaxScheme>
	</PartyTaxScheme>
<?php	} ?>
	<Contact>
<?php	$phone = null;
		$email = null;
		$contact_model = new Contact_Model();
		$contacts = $contact_model->find_all_users_contacts($supplier->id);
		
		foreach ($contacts as $contact) {
			if ( $contact->type == Contact_Model::TYPE_PHONE )
				$phone = $contact->value;
			if ( $contact->type == Contact_Model::TYPE_EMAIL )
				$email = $contact->value;
		}
?>
		<Telephone><?php echo $phone ?></Telephone>
		<ElectronicMail><?php echo $email ?></ElectronicMail>
	</Contact>
<?php	} else { ?>
	<PartyIdentification>	
		<ID><?php echo $invoice->organization_identifier; ?></ID>
	</PartyIdentification>
	<PartyName>
		<Name><?php echo !empty($invoice->partner_company) ? 
								$invoice->partner_company : 
								$invoice->partner_name ?></Name>
	</PartyName>
	<PostalAddress>
		<StreetName><?php echo $invoice->partner_street ?></StreetName>	
		<BuildingNumber><?php echo $invoice->partner_street_number ?></BuildingNumber>
		<CityName><?php echo $invoice->partner_town ?></CityName>
		<PostalZone><?php echo $invoice->partner_zip_code ?></PostalZone>
		<Country>
		<IdentificationCode></IdentificationCode>
		<Name><?php echo $invoice->partner_country ?></Name>
		</Country>
	</PostalAddress>
<?php	if ($invoice->vat) { ?>
	<PartyTaxScheme>
		<CompanyID></CompanyID>
		<TaxScheme>VAT</TaxScheme>
	</PartyTaxScheme>
<?php	} ?>
	<Contact>
<?php	if (!empty($invoice->partner_company)) {?>
		<Name><?php echo $invoice->partner_name ?></Name>
<?php	} ?>
		<Telephone><?php echo $invoice->phone_number ?></Telephone>
		<ElectronicMail><?php echo $invoice->email ?></ElectronicMail>
	</Contact>
<?php	} ?>
	</Party>
</AccountingSupplierParty>
<AccountingCustomerParty>
	<Party>
<?php	if (!empty($customer)) {?>
	<PartyIdentification>	
		<ID><?php echo $customer->organization_identifier; ?></ID>
	</PartyIdentification>
	<PartyName>
		<Name><?php echo $customer->name ?></Name>
	</PartyName>
	<PostalAddress>
		<StreetName><?php if (!empty($customer->address_point->street_id))
					echo $customer->address_point->street->street; ?></StreetName>	
		<BuildingNumber><?php if (!empty($customer->address_point->street_number))
					echo $customer->address_point->street_number; ?></BuildingNumber>
		<CityName><?php echo $customer->address_point->town->town ?></CityName>
		<PostalZone><?php echo $customer->address_point->town->zip_code ?></PostalZone>
		<Country>
		<IdentificationCode><?php echo $customer->address_point->country
							->country_iso ?></IdentificationCode>
		<Name><?php echo $customer->address_point->country->country_name ?></Name>
		</Country>
	</PostalAddress>
<?php	if ($invoice->vat) {?>
	<PartyTaxScheme>
		<CompanyID></CompanyID>
		<TaxScheme>VAT</TaxScheme>
	</PartyTaxScheme>
<?php	} ?>
	<Contact>
<?php	$phone = null;
		$email = null;
		$contact_model = new Contact_Model();
		$contacts = $contact_model->find_all_users_contacts($customer->id);
		
		foreach ($contacts as $contact) {
			if ( $contact->type == Contact_Model::TYPE_PHONE )
				$phone = $contact->value;
			if ( $contact->type == Contact_Model::TYPE_EMAIL )
				$email = $contact->value;
		}
?>
		<Telephone><?php echo $phone ?></Telephone>
		<ElectronicMail><?php echo $email ?></ElectronicMail>
	</Contact>
<?php	} else { ?>
	<PartyIdentification>	
		<ID><?php echo $invoice->organization_identifier; ?></ID>
	</PartyIdentification>
	<PartyName>
		<Name><?php echo !empty($invoice->partner_company) ? 
								$invoice->partner_company : 
								$invoice->partner_name ?></Name>
	</PartyName>
	<PostalAddress>
		<StreetName><?php echo $invoice->partner_street ?></StreetName>	
		<BuildingNumber><?php echo $invoice->partner_street_number ?></BuildingNumber>
		<CityName><?php echo $invoice->partner_town ?></CityName>
		<PostalZone><?php echo $invoice->partner_zip_code ?></PostalZone>
		<Country>
		<IdentificationCode></IdentificationCode>
		<Name><?php echo $invoice->partner_country ?></Name>
		</Country>
	</PostalAddress>
<?php	if ($invoice->vat) {?>
	<PartyTaxScheme>
		<CompanyID></CompanyID>
		<TaxScheme>VAT</TaxScheme>
	</PartyTaxScheme>
<?php	} ?>
	<Contact>
<?php	if (!empty($invoice->partner_company)) {?>
		<Name><?php echo $invoice->partner_name ?></Name>
<?php	} ?>
		<Telephone><?php echo $invoice->phone_number ?></Telephone>
		<ElectronicMail><?php echo $invoice->email ?></ElectronicMail>
	</Contact>
<?php } ?>
	</Party>
</AccountingCustomerParty>
<InvoiceLines>
<?php
$vat_cat = array();
$price = array();
$price_vat = array();

foreach ($invoice->invoice_items as $item) { 
	$item_price_vat = ($item->vat + 1) * $item->price;
	if (!in_array($item->vat, $vat_cat)) {
		$vat_cat[] = $item->vat;
		$price[] = 0;
		$price_vat[] = 0;
	}
	?>
	<InvoiceLine>
	<ID><?php echo $item->code ?></ID>
	<InvoicedQuantity><?php echo $item->quantity ?></InvoicedQuantity>
	<LineExtensionAmount><?php echo round($item->quantity * $item->price, 2) ?></LineExtensionAmount>
	<LineExtensionAmountTaxInclusive><?php echo round($item->quantity * $item_price_vat, 2) ?></LineExtensionAmountTaxInclusive>
	<LineExtensionTaxAmount><?php echo round($item->quantity * ($item_price_vat - $item->price), 2) ?></LineExtensionTaxAmount>
	<UnitPrice><?php echo round($item->price, 2) ?></UnitPrice>
	<UnitPriceTaxInclusive><?php echo round($item_price_vat, 2) ?></UnitPriceTaxInclusive>
	<ClassifiedTaxCategory>
		<Percent><?php echo $item->vat * 100 ?></Percent>
		<VATCalculationMethod><?php echo $const['vat_method'] ?></VATCalculationMethod>
	</ClassifiedTaxCategory>
	<Item>
		<Description><?php echo $item->name ?></Description>
	</Item>
	</InvoiceLine>
<?php 
	$index = array_search($item->vat, $vat_cat);
	$price[$index] += $item->quantity * $item->price;
	$price_vat[$index] += $item->quantity * $item_price_vat;
} ?>
</InvoiceLines>
<TaxTotal>
<?php array_multisort($vat_cat, $price, $price_vat);
	for ($i = 0; $i < count($vat_cat); $i++) { 
		$price_r = round($price[$i], 2);
		$price_vat_r = round($price_vat[$i], 2);
?>
	<TaxSubTotal>
	<TaxableAmount><?php echo $price_vat_r ?></TaxableAmount>
	<TaxInclusiveAmount><?php echo $price_vat_r ?></TaxInclusiveAmount>
	<TaxAmount><?php echo $price_vat_r - $price_r ?></TaxAmount>
	<AlreadyClaimedTaxableAmount>0</AlreadyClaimedTaxableAmount>
	<AlreadyClaimedTaxAmount>0</AlreadyClaimedTaxAmount>
	<AlreadyClaimedTaxInclusiveAmount>0</AlreadyClaimedTaxInclusiveAmount>
	<DifferenceTaxableAmount><?php echo $price_r ?></DifferenceTaxableAmount>
	<DifferenceTaxAmount><?php echo $price_vat_r - $price_r ?></DifferenceTaxAmount>
	<DifferenceTaxInclusiveAmount><?php echo $price_vat_r ?></DifferenceTaxInclusiveAmount>
	<TaxCategory>
		<Percent><?php echo $vat_cat[$i] * 100 ?></Percent>
	</TaxCategory>
	</TaxSubTotal>
<?php } 
	$total_price = round(array_sum($price), 2);
	$total_price_vat = round(array_sum($price_vat), 2); ?>
	<TaxAmount><?php echo $total_price_vat - $total_price ?></TaxAmount>
</TaxTotal>

<LegalMonetaryTotal>
	<TaxExclusiveAmount><?php echo $total_price ?></TaxExclusiveAmount>
	<TaxInclusiveAmount><?php echo $total_price_vat ?></TaxInclusiveAmount>
	<AlreadyClaimedTaxExclusiveAmount>0</AlreadyClaimedTaxExclusiveAmount>
	<AlreadyClaimedTaxInclusiveAmount>0</AlreadyClaimedTaxInclusiveAmount>
	<DifferenceTaxExclusiveAmount><?php echo $total_price ?></DifferenceTaxExclusiveAmount>
	<DifferenceTaxInclusiveAmount><?php echo $total_price_vat ?></DifferenceTaxInclusiveAmount>
	<PayableRoundingAmount><?php echo round(ceil($total_price_vat) - $total_price_vat, 2) ?></PayableRoundingAmount>
	<PaidDepositsAmount>0</PaidDepositsAmount>
	<PayableAmount><?php echo ceil($total_price_vat) ?></PayableAmount>
</LegalMonetaryTotal>

<PaymentMeans>
	<Payment>
	<PaidAmount><?php echo ceil($total_price_vat) ?></PaidAmount>
	<PaymentMeansCode>42</PaymentMeansCode>
	<Details>
		<PaymentDueDate><?php echo $invoice->date_due ?></PaymentDueDate>
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
		<ID><?php echo $account_nr ?></ID>
		<BankCode><?php echo $bank_code ?></BankCode>
		<Name/>
		<IBAN/>
		<BIC/>
		<VariableSymbol><?php if(!empty($invoice->var_sym))echo $invoice->var_sym ?></VariableSymbol>
		<ConstantSymbol><?php if(!empty($invoice->con_sym)) echo $invoice->con_sym; ?></ConstantSymbol>
	</Details>
	</Payment>
</PaymentMeans>
</Invoice>