<?php
/**
 * Members add javascript view.
 *
 * @author David Raska
 */

// IDE complementing
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	$("#autogen_pass").change(function ()
	{
		if ($(this).is(':checked'))
		{
			$('th.password').removeClass('label_required');
			$('th.confirm_password').removeClass('label_required');

			$('.password .error').remove();
			$('.confirm_password .error').remove();

			$('#password').removeClass('required error main_password');
			$('#confirm_password').removeClass('required error');
		}
		else
		{
			$('th.password').addClass('label_required');
			$('th.confirm_password').addClass('label_required');

			$('#password').addClass('required main_password');
			$('#confirm_password').addClass('required');
		}
	});

	$("#autogen_pass").change();
	