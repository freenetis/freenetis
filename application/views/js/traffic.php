<?php
/**
 * Traffics show javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("#type").parent().parent().parent().parent().parent().children("button").hide();
		
	$("#type").change(function()
	{
		$("#type").parent().parent().parent().parent().parent().submit();
	});