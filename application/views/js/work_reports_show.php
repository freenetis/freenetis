<?php
/**
 * Work reports show javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	// user can vote
	if ($('select[name^="vote"]').length > 0)
	{
		$('#work_report__show_descr').remove();
		$('<a href="#" id="work_report__show_descr"><?php echo __('Show whole descriptions') ?></a>').insertBefore("#work_reports__show_grid-wrapper");
		
		vote_options = $('select[name^="vote"]').first().find('option');
		if (vote_options.length > 0)
		{
			$('<spa> | </span>').insertBefore("#work_reports__show_grid-wrapper");
		}
		
		prev = false;
		
		// shows only selectable vote options
		vote_options.each(function()
		{
			if (prev === true)
			{
				$('<spa> | </span>').insertBefore("#work_reports__show_grid-wrapper");
			}
			
			switch ($(this).val())
			{
				case '0':
					$('<a href="#" id="mark_all_abstain"><?php echo __('Set votes to') . ' ' . __('Abstain', '', 1) ?></a>').insertBefore("#work_reports__show_grid-wrapper");
					prev = true;
					break;
				case '-1':
					$('<a href="#" id="mark_all_disagree"><?php echo __('Set votes to') . ' ' . __('Disagree', '', 1) ?></a>').insertBefore("#work_reports__show_grid-wrapper");
					prev = true;
					break;
				case '1':
					$('<a href="#" id="mark_all_agree"><?php echo __('Set votes to') . ' ' . __('Agree', '', 1) ?></a>').insertBefore("#work_reports__show_grid-wrapper");
					prev = true;
					break;
				default:
					prev = false;
			}
		});
	}
	
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
	