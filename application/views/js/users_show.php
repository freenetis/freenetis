<?php
/**
 * Users show javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$(".switch-link").click(function ()
	{
		$(".switch-box").hide();
		$("#" + this.id + "-box").show();
		return false;
	});

	$("#admin-devices").trigger("click");
	