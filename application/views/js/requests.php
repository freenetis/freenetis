<?php
/**
 * Requests add/edit javascript view.
 * Hides or shows suggest amount according to the current type in form.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$('input[name="type"]').change(function ()
	{
		if ($(this).val() != '<?php echo Request_Model::TYPE_SUPPORT ?>' &&
			$(this).is(':checked'))
		{
			$('#suggest_amount').parents('tr').show();
			$('#approval_template_id').val(<?php echo Settings::get('default_request_approval_template') ?>);
		}
		else
		{
			$('#suggest_amount').parents('tr').hide();
			$('#approval_template_id').val(<?php echo Settings::get('default_request_support_approval_template') ?>);
		}
	}).trigger('change');
	