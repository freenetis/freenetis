<?php
/**
 * Members fees edit javascript view.
 * Sets up datepickers.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var dates = $( "#from, #to" ).datepicker({
		dateFormat: "yy-mm-dd",
		changeMonth: true,
		changeYear: true,
		onSelect: function( selectedDate )
		{
			var option = this.id == "from" ? "minDate" : "maxDate";
			instance = $(this).data("datepicker");
			date = $.datepicker.parseDate(
				instance.settings.dateFormat ||
				$.datepicker._defaults.dateFormat,
				selectedDate, instance.settings
			);
			dates.not( this ).datepicker("option", option, date);
		}
	});
	
	$.datepicker.regional['<?php echo Config::get('lang') ?>'];
	