<?php
/**
 * Invoice contact information javascript view.
 * Hides/Shows contact information in invoice form.
 * 
 * @author Jan Dubina
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	$('#member_id').change(function (){
		var val = $(this).val();

		if (val != 0) {
			$('th.partner_company').parent().hide();
			$('th.partner_name').parent().hide();
			$('th.partner_street').parent().hide();
			$('th.partner_street_number').parent().hide();
			$('th.partner_town').parent().hide();
			$('th.partner_zip_code').parent().hide();
			$('th.partner_country').parent().hide();
			$('th.organization_identifier').parent().hide();
			$('th.vat_organization_identifier').parent().hide();
			$('th.phone_number').parent().hide();
			$('th.email').parent().hide();
		} else {
			$('th.partner_company').parent().show();
			$('th.partner_name').parent().show();
			$('th.partner_street').parent().show();
			$('th.partner_street_number').parent().show();
			$('th.partner_town').parent().show();
			$('th.partner_zip_code').parent().show();
			$('th.partner_country').parent().show();
			$('th.organization_identifier').parent().show();
			$('th.vat_organization_identifier').parent().show();
			$('th.phone_number').parent().show();
			$('th.email').parent().show();
		}
	}).change();