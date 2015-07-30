<?php
/**
 * JavaScript funcionality for adding notification message
 * 
 * @author David Raška
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$('select[name^="message_id"]').live('change', function()
	{
		var value = $(this).val();
		
		$(this).parent().find('a.message_show').remove();
		
		if (!parseInt(value))
		{
			return;
		}
		
		// add link for showing of message
		$(this).parent().append($('<a>', {
			href:	'<?php echo url_lang::base() ?>messages/show/' + value,
			title:	'<?php echo __('Show selected message') ?>'
		}).addClass('message_show_button popup_link message_show').html('<?php echo html::image(array('src' => '/media/images/icons/grid_action/show.png')) ?>'));
	});