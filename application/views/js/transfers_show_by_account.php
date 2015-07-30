<?php
/**
 * Transfers by account javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$('#comments_grid').css('display','none');
	
	$('#transfers_link, #comments_link').click(function ()
	{
		if (this.id == 'transfers_link')
		{
			$('#comments_grid').hide('slow');
			$('#transfers_grid').show('slow');
		}
		else
		{
			$('#transfers_grid').hide('slow');
			$('#comments_grid').show('slow');
		}
		return false;
	});
	