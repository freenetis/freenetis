<?php
/**
 * Applicant approval - autoload of calculated additional payment,
 * after changing of entrance day.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	$('#connection_payment_amount', context).after('<span class="recalculating" style="margin: 5px; display: none"><?php echo html::image('/media/images/icons/animations/ajax-loader.gif') . ' ' .  __('Recalculating') ?>...</span>');
	$('#connection_payment_amount', context).parent().find('.loading');
	
	// recalculate additional payment on change of entrance date
	$('select[name^="entrance_date["]', context).change(function ()
	{
		var day = $('select[name="entrance_date[day]"]', context).val();
		var month = $('select[name="entrance_date[month]"]', context).val();
		var year = $('select[name="entrance_date[year]"]', context).val();
		var entrance_date = year + '-' + month + '-' + day;
		
		$('#connection_payment_amount', context).parent().find('span.recalculating').show();
			
		$.getJSON('<?php echo url_lang::base() ?>json/calculate_additional_payment_of_applicant?entrance_date=' + entrance_date + '&connected_from=<?php echo $applicant_connected_from ?>', function (data)
		{
			if (data.amount != undefined)
			{
				$('#connection_payment_amount', context).val(data.amount);
				$('#connection_payment_amount', context).parent().find('span.recalculating').hide();
			}
		});
	});
	
	// on disable of payment hide disable amount field
	$('#allow_additional_payment', context).change(function ()
	{
		if ($(this).is(':checked'))
			$('#connection_payment_amount', context).removeAttr('disabled');
		else
			$('#connection_payment_amount', context).attr('disabled', true);
	}).trigger('change');
	