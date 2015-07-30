<?php
/**
 * Application password javascript view.
 * Hiding/showing user's/member's application password during show his account.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	// hide application password
	$("#application_password_span").addClass("dispNone");
	// show fake (**) application password
	$("#fake_application_password_span").removeClass("dispNone");
	// show link to display application password
	$("#show_application_password_link").removeClass("dispNone");
	// toogle show/hode of application password
	$("#show_application_password_link").click(function ()
	{
		$("#application_password_span").toggleClass("dispNone");
		$("#fake_application_password_span").toggleClass("dispNone");

		if ($("#application_password_span").hasClass("dispNone"))
		{
			$("#show_application_password_link").text("<?php echo __('Show') ?>");	
		}
		else
		{
			$("#show_application_password_link").text("<?php echo __('Hide') ?>");
		}

		return false;
	});
