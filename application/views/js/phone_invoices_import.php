<?php
/**
 * Phone invoices import javascript view.
 * 
 * @author David Raška
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var types = <?php echo $types ?>;
	var files = <?php echo $files ?>;
	
	// disable since and until form fields if whitelist is permanent
	$('#parser').change(function ()
	{
		var type = types[$(this).val()];
		var file_num = files[$(this).val()];
		
		var parse = $('#parse');
		var parse_row = parse.parent().parent();
		
		var file = $('.files');
		var file_row = file.parent().parent();
		
		var hint = $('#phone_invoice_import_hint');
		
		parse.removeClass('required error').parent().find('.error').remove();
		parse_row.hide();
		file.removeClass('required error').parent().find('.error').remove();
		file_row.hide();
		hint.hide();
		
		switch (type)
		{
			case <?php echo	Parser_Phone_Invoice::TYPE_UPLOAD ?>:
				var selected = $('.files:lt('+file_num+')');
				console.log(file_num);
				selected.each(function(){
					$(this).addClass('required');
					$(this).parent().parent().show();
				});

				break;
			case <?php echo	Parser_Phone_Invoice::TYPE_TEXTAREA ?>:
				parse.addClass('required');
				parse_row.show();
				hint.show();
				break;
		}
		
	}).trigger('change');
	