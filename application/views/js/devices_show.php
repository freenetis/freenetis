<?php
/**
 * Devices show javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("#device_ifaces_link, #device_vlan_ifaces_link, #device_ports_link, #device_ip_addresses_link, #device_engineers_link, #device_admins_link").click(function ()
	{
		var name = substr(this.id,0,strrpos(this.id,"link")-1);
		if ($("#"+name).hasClass("dispNone"))
		{
			$("#"+name+"_button").attr("src", "<?php echo url::base() ?>media/images/icons/ico_minus.gif");
			$("#"+name+"_link").attr("title", "<?php echo __('Hide this table') ?>");
			$("#"+name).removeClass("dispNone");
		}
		else
		{
			$("#"+name+"_button").attr("src", "<?php echo url::base() ?>media/images/icons/ico_add.gif");
			$("#"+name+"_link").attr("title", "<?php echo __('Show this table') ?>");
			$("#"+name).addClass("dispNone");
		}
	});
	