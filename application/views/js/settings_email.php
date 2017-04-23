<?php
/**
 * Settings email javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var driver = $("#email_driver option:selected").val();
	if (driver != "smtp")
	{
		$("#email_hostname,#email_port,#email_encryption,#email_username,#email_password").attr("disabled", true);
	}
	
	$("#email_driver").live("change", function()
	{
		var driver = $("#email_driver option:selected").val();
		
		if (driver != "smtp")
			$("#email_hostname,#email_port,#email_encryption,#email_username,#email_password").attr("disabled", true);
		else
			$("#email_hostname,#email_port,#email_encryption,#email_username,#email_password").removeAttr("disabled");
	});
	