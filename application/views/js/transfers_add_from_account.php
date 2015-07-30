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
			{ // val is ID and key is name of account
				options.push('<option value="');
				options.push(val);
				options.push('">');
				options.push(key);
				options.push('</option>');
			});
			$("#aname").html(options.join(''));
		});
	});
	