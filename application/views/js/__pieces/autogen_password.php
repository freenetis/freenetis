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
		var parent = $(this).parent().parent().parent().parent().parent();

		if ($(this).is(':checked'))
		{
			parent.find('th.password').removeClass('label_required');
			parent.find('th.confirm_password').removeClass('label_required');

			parent.find('.password label.error').remove();
			parent.find('.confirm_password label.error').remove();

			parent.find('#password').removeClass('required error main_password').attr('disabled', 'disabled');
			parent.find('#confirm_password').removeClass('required error').attr('disabled', 'disabled');
		}
		else
		{
			parent.find('th.password').addClass('label_required');
			parent.find('th.confirm_password').addClass('label_required');

			parent.find('#password').addClass('required main_password');
			parent.find('#confirm_password').addClass('required');
		}
	});

	$("#autogen_pass").change();
	