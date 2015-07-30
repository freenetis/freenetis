<?php
/**
 * Settings system javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

var modules = {
<?php foreach ($modules as $module => $dependencies): ?>
		
	'<?php echo $module ?>': [<?php echo "'".implode("', '", $dependencies)."'" ?>],
			
<?php endforeach ?>
}

$("input[type=radio]").live('change', function (){
	
	var name = $(this).attr('name');
	
	var value = parseInt($(this).val());
	
	for (i in modules[name])
	{
		if (value)
		{
			$("input[name='"+modules[name][i]+"']").removeAttr('disabled');
			$("input[name='"+modules[name][i]+"']:checked").parent().find('.additional-info').hide();
		}
		else
		{
			$("input[name='"+modules[name][i]+"']").attr('disabled', 'disabled');
			$("input[name='"+modules[name][i]+"']:checked").parent().find('.additional-info').show();
		}
	}		
});

$("input[type=radio]:checked").trigger('change');