<?php
/**
 * Ulog time members traffics javascript view.
 * 
 * @author Michal Kliment
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$(document).ready(function()
	{
		$(function()
		{
			var dates = $( "#date_from, #date_to" ).datepicker({
				dateFormat: "yy-mm-dd",
				changeMonth: true,
				changeYear: true,
				onSelect: function( selectedDate )
				{
					var option = this.id == "date_from" ? "minDate" : "maxDate";
					instance = $( this ).data( "datepicker" );
					date = $.datepicker.parseDate(
					instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
					selectedDate, instance.settings );
					dates.not( this ).datepicker( "option", option, date );
				}
			});
			$.datepicker.regional["<?php echo Config::get('lang') ?>"];
		})
	});
	