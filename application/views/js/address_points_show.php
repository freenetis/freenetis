<?php
/**
 * Map in address point detail, coordinate loaded from data attributes.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	$(document).ready(function ()
	{
		function mapycz_addr(divId, gpsx, gpsy)
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

		var $map = $('#ap_gmap');
		mapycz_addr('ap_gmap', $map.data('gpsx'), $map.data('gpsy'));
	});
	