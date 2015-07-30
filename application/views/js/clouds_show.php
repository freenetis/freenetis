<?php
/**
 * Clouds show javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("#admins_mark_all").change(function ()
	{
		var checked = $(this).is(':checked');
		
		if (checked)
		{
			$(':checkbox[name^="uid"]').attr('checked', 'checked');
		}
		else
		{
			$(':checkbox[name^="uid"]').removeAttr('checked');
		}
	});
	
	$("#subnets_mark_all").change(function ()
	{
		var checked = $(this).is(':checked');
		
		if (checked)
		{
			$(':checkbox[name^="sid"]').attr('checked', 'checked');
		}
		else
		{
			$(':checkbox[name^="sid"]').removeAttr('checked');
		}
	});
	