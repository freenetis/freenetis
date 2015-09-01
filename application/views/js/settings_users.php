<?php
/**
 * Settings users javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	$('#users_birthday_empty_enabled').live('change', function()
	{
		if ($(this).is(':checked'))
		{
			$('#members_age_min_limit').parent().parent().show();
		}
		else
		{
			$('#members_age_min_limit').parent().parent().hide();
		}
	}).change();
	