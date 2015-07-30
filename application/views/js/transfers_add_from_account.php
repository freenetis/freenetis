<?php
/**
 * Add transfer from account javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("#account_type").change(function()
	{
		$("#aname").html('<option value=""><?php echo __('Loading data, please wait') ?>...</option>');
		$.getJSON("<?php echo url_lang::base() ?>json/get_accounts_by_type<?php echo (isset($origin_account_id) ? "/$origin_account_id" : '') ?>?id=" + urlencode($(this).val()), function(data)
		{
			var options = [];
			$.each(data, function(key, val)
			{
				options.push('<optgroup label="');
				options.push(key);
				options.push('">');
				
				$.each(val, function(key, val)
				{
					options.push('<option value="');
					options.push(val);
					options.push('">');
					options.push(key);
					options.push('</option>');
				});
				
				options.push('</optgroup>');
			});
			$("#aname").html(options.join(''));
		});
	});
	