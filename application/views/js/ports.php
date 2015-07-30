<?php
/**
 * Action for ports
 * 
 * @author Michal Kliment
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	function update_options(id)
	{
		values[id] = new Array();
				
		$("#"+id).children().each(function (){
			values[id].push({'key': $(this).attr('value'), 'value': $(this).html()});
		});
	}
	
	$("#port_vlan_id, #tagged_vlan_id, #untagged_vlan_id").live('addOption', function (e, new_option_id){

		switch ($(this).attr('id'))
		{
			case 'port_vlan_id':
				
				$(this).val(new_option_id);
				
				multiple_select_add_option('tagged_vlan_id', new_option_id);
				
				update_options('tagged_vlan_id_options');
				
				multiple_select_add_option('untagged_vlan_id', new_option_id);
				
				update_options('untagged_vlan_id_options');
				
				break;
				
			case 'tagged_vlan_id':
				
				multiple_select_add_option('untagged_vlan_id', new_option_id);
				
				update_options('untagged_vlan_id_options');
				
				var port_vlan_id = $("#port_vlan_id").val();
				
				reload_element("#port_vlan_id", "<?php echo url_lang::base().url_lang::current(0,1) ?>");
				
				$("#port_vlan_id").val(port_vlan_id);
				
				update_options('tagged_vlan_id');
				
				break;
				
			case 'untagged_vlan_id':
				
				multiple_select_add_option('tagged_vlan_id', new_option_id);
				
				update_options('tagged_vlan_id_options');
				
				reload_element("#port_vlan_id", "<?php echo url_lang::base().url_lang::current(0,1) ?>");
				
				var port_vlan_id = $("#port_vlan_id").val();
				
				reload_element("#port_vlan_id", "<?php echo url_lang::base().url_lang::current(0,1) ?>");
				
				$("#port_vlan_id").val(port_vlan_id);
				
				update_options('untagged_vlan_id');
				
				break;
		}
		
	});
	
	$("#mode").live('change', function (){
		
		switch ($(this).val())
		{
			case '<?php echo Port_Model::PORT_MODE_ACCESS ?>':
					
					$("#tagged_vlan_id").parent().parent().parent().parent().parent().parent().addClass('dispNone');
					$("#untagged_vlan_id").parent().parent().parent().parent().parent().parent().removeClass('dispNone');
					
				break;
			
			case '<?php echo Port_Model::PORT_MODE_TRUNK ?>':
					
					$("#tagged_vlan_id").parent().parent().parent().parent().parent().parent().removeClass('dispNone');
					$("#untagged_vlan_id").parent().parent().parent().parent().parent().parent().addClass('dispNone');
					
				break;
			
			case '<?php echo Port_Model::PORT_MODE_HYBRID ?>':
					
					$("#tagged_vlan_id").parent().parent().parent().parent().parent().parent().removeClass('dispNone');
					$("#untagged_vlan_id").parent().parent().parent().parent().parent().parent().removeClass('dispNone');
					
				break;
		}
		
	});
	
	$("#mode").trigger('change');
	
	$(".form").live('submit', function (){
		
		if ($("#tagged_vlan_id").parent().parent().parent().parent().parent().parent().hasClass('dispNone'))
		{
			$("#tagged_vlan_id").remove();
		}
		
		if ($("#untagged_vlan_id").parent().parent().parent().parent().parent().parent().hasClass('dispNone'))
		{
			$("#untagged_vlan_id").remove();
		}
		
		//return false;
	});
	
	