<?php
/**
 * SMS send javascript view.
 * 
 * @author Roman Sevcik, Ondrej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var maxpartsize = 156 - 4;
	var maxparts = 5;
	var totalmaxlen = maxparts * maxpartsize;

	function count_char(form)
	{
		form.counter.disabled=true;
		var message = form.text;
		var text_length = message.value.length;
		var text_left = totalmaxlen - message.value.length;
		var parts = Math.ceil(text_length/maxpartsize).toString();
		
		if (text_left < 0)
		{
			text_left = 0;
			text_length = totalmaxlen;
			parts = maxparts;
			alert('<?php echo __('Message is too long') ?>!');
		}
		
		form.counter.value =
			'<?php echo __('Written is', '', 2) ?> ' + text_length +
			' <?php echo __('and', '', 1) ?> <?php echo __('have left', '', 1) ?> ' + text_left + 
			' <?php echo __('chars', '', 1) ?>. ' +
			'<?php echo __('Message will be splited to', '', 2) ?> ' + 
			parts + ' SMS.';
	}
	
	count_char(document.sms_form);
	
	$('#text').keyup(function ()
	{
		count_char(document.sms_form)
	});
	
	