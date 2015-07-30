<?php
/**
 * Domicile javascript view.
 * During adding/editing of member, toogle domicile fields.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	// toogle domicile fields on member editing/adding
	function update_domicile_fields()
	{
		if ($("#use_domicile:checked").val() != null)
		{
			$("[id^='domicile']").parent().parent().show();
			$("#toggle_addresses_button").show();
		}
		else
		{
			$("[id^='domicile']").parent().parent().hide();
			$("#toggle_addresses_button").hide();
		}
	}
	
	// toggle address with domicile
	$("#toggle_addresses_button").live('click', function (){
		var town_id = $("#town_id").val();
		var town = $("#town").val();
		var district = $("#district").val();
		var street_id = $("#street_id").val();
		var street = $("#street").val();
		var street_number = $("#street_number").val();
		var zip = $("#zip").val();
		var gpsx = $("#gpsx").val();
		var gpsy = $("#gpsy").val();
		var domicile_town_id = $("#domicile_town_id").val();
		var domicile_town = $("#domicile_town").val();
		var domicile_district = $("#domicile_district").val();
		var domicile_street_id = $("#domicile_street_id").val();
		var domicile_street = $("#domicile_street").val();
		var domicile_street_number = $("#domicile_street_number").val();
		var domicile_gpsx = $("#domicile_gpsx").val();
		var domicile_gpsy = $("#domicile_gpsy").val();
		var domicile_zip = $("#domicile_zip").val();
		
		$("#town_id").val(domicile_town_id);
		$("#town_id").trigger('change');
		$("#domicile_town_id").val(town_id);
		$("#domicile_town_id").trigger('change');
		
		$("#town").val(domicile_town);
		$("#domicile_town").val(town);
		
		$("#district").val(domicile_district);
		$("#domicile_district").val(district);
		
		$("#street_id").val(domicile_street_id);
		$("#street_id").trigger('change');
		$("#domicile_street_id").val(street_id);
		$("#domicile_street_id").trigger('change');
		
		$("#street_number").val(domicile_street_number);
		$("#street_number").trigger('change');
		$("#domicile_street_number").val(street_number);
		$("#domicile_street_number").trigger('change');
		
		$("#street").val(domicile_street);
		$("#street").trigger('change');
		$("#domicile_street").val(street);
		$("#domicile_street").trigger('change');
		
		$("#zip").val(domicile_zip);
		$("#domicile_zip").val(zip);
		
		$("#gpsx").val(domicile_gpsx);
		$("#gpsy").val(domicile_gpsy);
		
		$("#domicile_gpsx").val(gpsx);
		$("#domicile_gpsy").val(gpsy);
	});
	
	$("#use_domicile").parent().parent().append("<button id='toggle_addresses_button' type='button' class='submit' title='<?php echo __('Toggle address of connecting place with domicile') ?>'><img src='<?php echo url::base() ?>media/images/icons/reload.png'><?php echo __('Toggle addresses') ?></button>");
	
	// toogle domicile
	update_domicile_fields();
	// toogle domical on use_domicile change
	$("#use_domicile").change(update_domicile_fields);
