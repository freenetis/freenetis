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
	
	$(document).ready(function()
	{
		$("#gpsx, #gpsy").keyup(function ()
		{
			if ($("#gpsx").val() == '' || $("#gpsy").val() == '')
			{
				$.get("<?php echo url_lang::base() ?>address_points/get_gps_by_address",
				{
					"street":		$("#street_id option:selected").text(),
					"street_number":	$("#street_number").val(),
					"town":			$("#town_id option:selected").text(),
					"country":		$("#country_id option:selected").text()
				}, function(data)
				{
					var s = data.split(" ");
					$("#gpsx").val(s[0]);
					$("#gpsy").val(s[1]);
				});
			}
		});
		
		$("#gpsx, #gpsy").trigger("keyup");
		
	});