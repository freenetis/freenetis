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
		$.getJSON("<?php echo url_lang::base() ?>json/get_accounts_by_type?id=" + urlencode($(this).val()), function(data)
		{
			var options = '';
			$.each(data, function(key, val)
			{
				options += '<option value="' + key + '">' + val + '</option>';
			});
			$("#aname").html(options);
		})
	})
	