<?php
/**
 * Additional user contact add javascript view.
 * Change form during to type of contact.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	var country_code_dropdown = $('.country_code');
	var mail_redirection_checkbox = $('.mail_redirection');

	$('#type_dropdown').live('change', function () {
	   var type = parseInt($(this).val(), 10);
	   
		$("#value").removeClass('number email');
		$("#value").removeAttr('minlength');

		switch (type)
		{
			case <?php echo Contact_Model::TYPE_PHONE ?>:
				country_code_dropdown.show();
				mail_redirection_checkbox.hide();
				$("#value").addClass('number');
				$("#value").attr('minlength',9);
				break;
			case <?php echo Contact_Model::TYPE_EMAIL ?>:
				country_code_dropdown.hide();
				mail_redirection_checkbox.show();
				$("#value").addClass('email');
				break;
			default:
				country_code_dropdown.hide();
				mail_redirection_checkbox.hide();
		}
	});
	
	$('#type_dropdown').trigger('change');
