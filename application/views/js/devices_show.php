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

	window.mapycz_dev = function (divId, gpsx, gpsy)
	{
		var center = SMap.Coords.fromWGS84(gpsy, gpsx);
		var m = new SMap(JAK.gel(divId), center, 17);
		m.addDefaultLayer(SMap.DEF_OPHOTO);
		m.addDefaultLayer(SMap.DEF_BASE).enable();

		var layerSwitch = new SMap.Control.Layer();
		layerSwitch.addDefaultLayer(SMap.DEF_BASE);
		layerSwitch.addDefaultLayer(SMap.DEF_OPHOTO);
		m.addControl(layerSwitch, {left: "8px", top: "9px"});
		m.addControl(new SMap.Control.Sync());
		m.addDefaultControls();

		var markerLayer = new SMap.Layer.Marker();
		markerLayer.addMarker(new SMap.Marker(center, "myMarker", {}));
		m.addLayer(markerLayer);
		markerLayer.enable();
	};
	