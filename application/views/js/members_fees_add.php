<?php
/**
 * Members fees add javascript view.
 * Sets up times.
 * 
 * @author Michal Kliment
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var fee_intervals = new Array();
	fee_intervals[0] = new Array();
	fee_intervals[0]["from"] = "0000-00-00";
	fee_intervals[0]["to"] = "0000-00-00";

	<?php foreach ($fees as $fee): ?>
	
	fee_intervals[<?php echo $fee->id ?>] = new Array();
	fee_intervals[<?php echo $fee->id ?>]['from'] = '<?php echo $fee->from ?>';
	fee_intervals[<?php echo $fee->id ?>]['to'] = '<?php echo $fee->to ?>';
	
	<?php endforeach ?>
	
	$("#fee_type_id").change(function()
	{
		$("#fee_id").html('<option value=""><?php echo __('Loading data, please wait') ?>...</option>');
		
		if ($(this).val() != '')
			$("#fee_id_add_button").parent().attr("href","<?php echo url_lang::base() ?>fees/add/"+$(this).val()+"?popup=1");
		else
			$("#fee_id_add_button").parent().attr("href","<?php echo url_lang::base() ?>fees/add?popup=1");
		
		$.getJSON("<?php echo url_lang::base() ?>json/get_fees_by_type?id=" + $(this).val(), function(data)
		{
			var options = '';
			$.each(data, function(key, val)
			{
				options += '<option value="' + key + '">' + val + '</option>';
			});
			$("#fee_id").html(options);
		})
	})
	
	$(function()
	{
		var dates = $( "#from, #to" ).datepicker({
			dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true,
			onSelect: function( selectedDate )
			{
				var option = this.id == "from" ? "minDate" : "maxDate",
				instance = $( this ).data( "datepicker" );
				date = $.datepicker.parseDate(
				instance.settings.dateFormat ||
					$.datepicker._defaults.dateFormat,
				selectedDate, instance.settings );
				dates.not( this ).datepicker( "option", option, date );
			}
		});
		$.datepicker.regional['<?php echo Config::get('lang') ?>'];
	});

	$("#fee_id").change(function()
	{
		if ($("#from").val()=="")
		{
			$("#from").val(fee_intervals[$(this).val()]["from"]);
		}

		var from_date = new Date($("#from").val());
		var fee_from_date = new Date(fee_intervals[$(this).val()]["from"]);
		var to_date = new Date($("#to").val());
		var fee_to_date = new Date(fee_intervals[$(this).val()]["to"]);

		if (from_date.getTime() < fee_from_date.getTime() ||
			from_date.getTime() > fee_to_date.getTime())
		{
			$("#from").val(fee_intervals[$(this).val()]["from"]);
		}

		if ($("#to").val()=="")
		{
			$("#to").val(fee_intervals[$(this).val()]["to"]);
		}

		if (to_date.getTime() > fee_to_date.getTime() ||
			to_date.getTime() < fee_from_date.getTime())
		{
			$("#to").val(fee_intervals[$(this).val()]["to"]);
		}

		$("#from").datepicker("option", "minDate", new Date($("#from").val()));
		$("#from").datepicker("option", "maxDate", new Date($("#to").val()));
		$("#to").datepicker("option", "minDate", new Date($("#from").val()));
	});
