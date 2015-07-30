<?php
/**
 * Show all logs javascript view.
 * Set date picker for filter.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	// logs are persisted for 30 days => set date picker for this interval
	$('#date_from, #date_to').datepicker({
		dateFormat:		'yy-mm-dd',
		changeMonth:	false,
		changeYear:		false,
		minDate:		-30,
		maxDate:		0
	});
	
	// set lang
	$.datepicker.regional['<?php echo Config::get('lang') ?>'];
	