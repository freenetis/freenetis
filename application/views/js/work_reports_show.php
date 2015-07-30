<?php
/**
 * Work reports show javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$('#mark_all_abstain').click(function ()
	{
		$('select[name^="vote"]').val('0');
		
		return false;
	});
	
	$('#mark_all_disagree').click(function ()
	{
		$('select[name^="vote"]').val('-1');
		
		return false;
	});
	
	$('#mark_all_agree').click(function ()
	{
		$('select[name^="vote"]').val('1');
		
		return false;
	});
	
	// current state of description
	var work_report__show_descr__state = true;
	
	$('#work_report__show_descr').click(function ()
	{
		var $tr = $('#work_reports__show_grid tbody tr');
		
		$tr.find('td:nth-child(2) .help, td:nth-child(3) .help').each(function (i, e)
		{
			var shortcut = $(e).text();
			var title = $(e).attr('title');
			
			$(e).text(title).attr('title', shortcut);
		});
		
		work_report__show_descr__state = !work_report__show_descr__state;
		
		if (work_report__show_descr__state)
		{
			$(this).text('<?php echo __('Show whole descriptions'); ?>');
		}
		else
		{
			$(this).text('<?php echo __('Hide whole descriptions'); ?>');
		}
			
		return false;
	});
	