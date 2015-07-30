<?php
/**
 * Variable symbols - adding.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	// on enable of VS generation, disable VS field
	$('#variable_symbol_generate', context).change(function ()
	{
		if ($(this).is(':checked'))
			$('#variable_symbol', context).attr('disabled', true);
		else
			$('#variable_symbol', context).removeAttr('disabled');
	}).trigger('change');
	