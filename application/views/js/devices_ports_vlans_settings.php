<?php

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif;
?>
	var vlan_id = <?php echo ($vlan_id) ? $vlan_id : 'null' ?>;
	
	var changed = false;
	
	$("#vlan-form button").hide();
	
	$("#vlan-form #vlan_id").change(function (){
		if (changed)
		{
			if (confirm('<?php echo __("Are you sure")?>? <?php echo __("Your changes will be lost") ?>'))
				$("#vlan-form").submit();
		}
		else
		{
			$("#vlan-form").submit();
		}
	});
	
	$("#ports-vlans-form select").change(function (){
		changed = true;
	});