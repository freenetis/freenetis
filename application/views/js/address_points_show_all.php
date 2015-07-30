<?php
/**
 * Additional user contact javascript view.
 * Auto send member filter.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$(document).ready(function()
	{
		$("#member_id").parent().parent().parent().parent().parent().children("button").hide();
		$("#member_id").change(function()
		{
			$(this).parent().parent().parent().parent().parent().submit();
		});
	});
	