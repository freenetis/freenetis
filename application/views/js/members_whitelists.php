<?php
/**
 * Whitelist form show javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	// disable since and until form fields if whitelist is permanent
	$('#permanent').change(function ()
	{
		var checked = $(this).is(':checked');
		
		if (checked)
		{
			$('select[name^="since["], select[name^="until["]').attr('disabled', true);
			$('th.since, th.until').parent('tr').hide();
		}
		else
		{
			$('select[name^="since["], select[name^="until["]').removeAttr('disabled');
			$('th.since, th.until').parent('tr').show();
		}
	}).trigger('change');
	