<?php
/**
 * Segment edit javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	bitrates = new Array();
	<?php foreach (Link_Model::get_wireless_max_bitrates() as $norm => $bitrate): ?>
	bitrates[<?php echo $norm ?>] = <?php echo $bitrate ?>;
	<?php endforeach ?>
		
	old_norm = null;
	
	function update_roaming()
	{
		var medium_id = $("#medium option:selected").val();
		$("#bitrate").parent().parent().toggleClass("dispNone", medium_id == '<?php echo Link_Model::MEDIUM_ROAMING ?>');
		$("#bitrate").toggleClass("required", medium_id != '<?php echo Link_Model::MEDIUM_ROAMING ?>');
		$("#duplex").parent().parent().toggleClass("dispNone", medium_id == '<?php echo Link_Model::MEDIUM_ROAMING ?>');
	}
	
	function update_wireless()
	{
		var medium_id = $("#medium option:selected").val();
		
		if (medium_id == <?php echo Link_Model::MEDIUM_AIR ?>)
		{
			$("#wireless_ssid").parent().parent().prev().show();
			$("#wireless_ssid").parent().parent().show();
			$("#wireless_norm").parent().parent().show();
			$("#wireless_frequency").parent().parent().show();
			$("#wireless_channel").parent().parent().show();
			$("#wireless_channel_width").parent().parent().show();
			$("#wireless_polarization").parent().parent().show();
		}
		else
		{
			$("#wireless_ssid").parent().parent().prev().hide();
			$("#wireless_ssid").parent().parent().hide();
			$("#wireless_norm").parent().parent().hide();
			$("#wireless_frequency").parent().parent().hide();
			$("#wireless_channel").parent().parent().hide();
			$("#wireless_channel_width").parent().parent().hide();
			$("#wireless_polarization").parent().parent().hide();
		}
	}
	
	function update_norm()
	{
		var old_bitrate = (old_norm != null) ? bitrates[old_norm] : 0;
		
		if ($("#bitrate").val() == '' || old_bitrate == $("#bitrate").val())
		{
			$("#bitrate").val(bitrates[$("#wireless_norm").val()]);
			$("#bit_unit").val(1048576);
		}
		
		old_norm = $("#wireless_norm").val();
	}
	
	function update_form()
	{
		update_roaming();
		update_wireless();
	}

	update_form();
	update_norm();

	$("#medium").change(update_form);
	$("#wireless_norm").change(update_norm);