<?php
/**
 * Time activity rule add javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	var time_activity_rule_types = jQuery.parseJSON('<?php echo json_encode(Time_Activity_Rule::get_attribute_types()) ?>');
	
	$('#type').unbind('change').bind('change', function ()
	{
		var attr_type = time_activity_rule_types[$(this).val()];
		
		// hide attribute
		$('input[name^="attribute["]').val('').parents('tr').hide();
		
		// show required attributes
		if (attr_type !== undefined)
		{
			// all attrs
			for (var i = 0; i < attr_type.length; i++)
			{
				// not empty
				if (!attr_type[i]['type']) continue;
				// input
				var $input = $('input[name^="attribute["]').eq(i);
				// show attribute
				$input.parents('tr').show();
				// remove old help hint
				$input.parents('tr').find('.help_hint').remove();

				if (attr_type[i]['type'] === 'integer')
				{
					// messsage
					var mes = attr_type[i]['title']
							+ ' - <?php echo __('value in range since', array(), 1) ?> ' 
							+ attr_type[i]['range_from']
							+ ' <?php echo __('until', array(), 1) ?> '
							+ attr_type[i]['range_to'];
					// add new help
					$input.parents('tr').find('th > label').text('<?php echo __('Attribute') ?> (' + attr_type[i]['name'] + ') ').append($('<img>', {
						src		: '<?php echo url::base() ?>/media/images/icons/help_small.png',
						alt		: mes,
						title	: mes,
						class	: 'help_hint'
					}));
				}
			}
		}
		
	}).trigger('change');
	