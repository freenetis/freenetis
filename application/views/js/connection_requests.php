<?php
/**	
 * JavaScript funcionality for request for connection.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

?>
	
	// confirm before rejecting of a request
	$('.confirm_reject').unbind('click').click(function ()
	{
		return window.confirm('<?php echo __('Do you really want to reject this request') ?>?');
	});
	
	// on change type update form to proper functionality
	$('#device_type_id').change(function ()
	{
		var value = $(this).val();
		var tem_val = $('#device_template_id').val();
		var is_edit = $('#crform_is_edit').length > 0;
		
		// reload devices templates
		$('#device_template_id').html('<option><?php echo __('Loading data, please wait') ?>...</option>');
		
		$.getJSON('<?php echo url_lang::base() ?>json/get_device_templates_by_type?type='+value, function(data)
		{
			var options = [];
			
			options.push('<option value="">--- <?php echo __('Select template') ?> ---</option>');
			
			$.each(data, function(key, val)
			{
				options.push('<option value="' + val.id + '"');
				
				if ((is_edit && tem_val == val.id) || val.isDefault)
				{
					options.push(' selected="selected"');
				}
				
				options.push('>' + val.name + '</option>');
			});
			
			$('#device_template_id').html(options.join(''));
		});
	}).trigger('change');
	