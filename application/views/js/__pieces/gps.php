<?php
/**
 * GPS javascript view.
 * During adding of address point, try to add GPS for selected country,
 * street, street number and town and set it to GPS fields.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	var gps_get = null;
	// try to find GPS for address point and set it (esed ad devices and members)
	$("#street_id, #street_number, #town_id, #country_id").change(function ()
	{
		get_gps_by_address($("#street_id").val(), $("#street_number").val(),
			$("#town_id").val(), $("#country_id").val(), function(data)
			{
				if (data != '')
				{
					var s = data.split(" ");
					$("#gpsx").val(gps_dms_coord(s[0]));
					$("#gpsy").val(gps_dms_coord(s[1]));
				}
				else
				{
					$("#gpsx").val("");
					$("#gpsy").val("");
				}
				gps_get = null;
			});
	});

	// try to find GPS for domicile address point and set it (used at members)
	$("#domicile_street_id, #domicile_street_number, #domicile_town_id, #domicile_country_id").change(function ()
	{
		get_gps_by_address($("#domicile_street_id").val(),
			$("#domicile_street_number").val(), $("#domicile_town_id").val(),
			$("#domicile_country_id").val(), function(data)
			{
				var s = data.split(" ");
				$("#domicile_gpsx").val(gps_dms_coord(s[0]));
				$("#domicile_gpsy").val(gps_dms_coord(s[1]));
			});
	});

	function get_gps_by_address(street_id, street_number, town_id, country_id, clb)
	{
		if (gps_get != null)
		{
			gps_get.abort();
			gps_get = null;
		}
		if (!street_number ||!town_id || !country_id)
		{
			return false;
		}
		gps_get = $.get("<?php echo url_lang::base() ?>address_points/get_gps_by_address/",
		{
			"street_id":		street_id,
			"street_number":	street_number,
			"town_id":			town_id,
			"country_id":		country_id
		}, function(data)
		{
			clb(data);
			gps_get = null;
		});
		return true;
	}
