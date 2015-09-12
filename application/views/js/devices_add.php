<?php
/**	
 * JavaScript funcionality for adding of device especially form building.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

?>
	
	// WARNING! be very careful during changing of form in controller,
	// this ID is dynamic, change of code in controller may broke it!
	var $eth_ifaces_group = $('#group-<?php echo 4 + Settings::get('finance_enabled') ?>');
	var $wlan_ifaces_group = $('#group-<?php echo 5 + Settings::get('finance_enabled') ?>');
	var $port_group = $('#group-<?php echo 6 + Settings::get('finance_enabled') ?>');
	var $internal_group = $('#group-<?php echo 7 + Settings::get('finance_enabled') ?>');
	
	// values for dropdowns
	var subnets_options = '<option value="">---- <?php echo __('Select subnet') ?> ----</option><?php foreach ($arr_subnets as $k => $v): ?><option value="<?php echo $k ?>"><?php echo $v ?></option><?php endforeach; ?>';
	var devices_options = '<option value="">---- <?php echo __('Select device') ?> ----</option><?php foreach ($arr_devices as $k_u => $v_u): ?><optgroup label="<?php echo $k_u ?>"><?php foreach ($v_u as $k => $v): ?><option value="<?php echo $k ?>"><?php echo $v ?></option><?php endforeach; ?></optgroup><?php endforeach; ?>';
	
	// subnets with gateway
	var gateway_subnets = [<?php echo implode(', ',array_keys($arr_gateway_subnets)) ?>];
	
	// port modes asociative array
	var port_modes = new Array();
	<?php foreach (Iface_Model::get_port_modes() as $k => $v): ?>
	port_modes[<?php echo $k ?>] = '<?php echo $v ?>';
	<?php endforeach; ?>
	
	// connecting suggestions
	var suggest_connected_to = new Array();
	
	/**
	 * Opens dialog for specifing of details of IP address of iface. Data
	 * are stored in hidden fields. 
	 */
	function add_detail_to_ip()
	{
		var $td = $(this).parent().parent();
		$('#dialog_ip_address_detail form').validate();
		
		// set form with current values
		$('#gateway_input').val($td.find('input[name^="gateway["]').val());
		$('#service_input').val($td.find('input[name^="service["]').val());
		
		// dialog button action submit
		$('#dialog_ip_address_detail form button').unbind('click').click(function ()
		{	
			if ($('#dialog_ip_address_detail form').valid())
			{
				// fill in hidden fields
				$td.find('input[name^="gateway["]').val($('#gateway_input').val())
				$td.find('input[name^="service["]').val($('#service_input').val())
				// close dialog
				$('#dialog_ip_address_detail').dialog('close');
			}
		});
		
		// open dialog
		$('#dialog_ip_address_detail').dialog({
			title: '<?php echo __('Add details to IP address') ?>',
			modal: true,
			position: ['center', 100]
		});
		
		return false;
	}
	
	/**
	 * Try find device and iface to which is device connected
	 * 
	 * @author Michal Kliment
	*/
	function get_connected_to_device_and_iface()
	{
		var $this = $(this);
		var $img = $this.find('img');
		var loader = '<?php echo url::base() ?>media/images/icons/animations/ajax-loader.gif';
		
		if ($img.attr('src') == loader)
			return false; // waiting
		
		var index = $this.parent().prev().prev().find('input[type="text"]').attr('name').substr('mac'.length);
		
		var mac_address = $('input[name="mac' + index + '"]').val();
		var subnet_id = $('select[name="subnet' + index + '"]').val();
		
		if (mac_address != '' && subnet_id)
		{
			var oldSrc = $this.find('img').attr('src');
			$img.attr('src', loader);
			
			$.getJSON('<?php echo url_lang::base() ?>/json/get_connected_to_device_and_iface/', {mac_address:mac_address,subnet_id:subnet_id}, function (data)
			{	
				if (data.state)
				{				
					$('select[name="connected' + index + '"] option[value="'+data.device_id+'"]').attr("selected", true);
					$('select[name="connected' + index + '"]').trigger('change');
					
					$('select[name="connected_iface' + index + '"] option[value="'+data.iface_id+'"]').attr("selected", true);
					$('select[name="connected_iface' + index + '"]').trigger('change');
				}
				else
				{
					alert (data.message);
				}
			});
			
			$img.attr('src', oldSrc);
		}
		
		return false;
	}
	
	/**
	 * Opens dialog for specifing of details of link of iface. Data are stored
	 * in hidden fields. 
	 */
	function add_detail_to_link()
	{
		var $td = $(this).parent().parent();
		$('#dialog_link_detail form').validate();
		
		// set form with current values
		$('#link_name_input').val($td.find('input[name^="link_name["]').val());
		$('#link_comment_input').val($td.find('input[name^="link_comment["]').val());
		$('#eth_medium_input').val($td.find('input[name^="medium["]').val());
		$('#wl_medium_input').val($td.find('input[name^="medium["]').val());
		$('#port_medium_input').val($td.find('input[name^="medium["]').val());
		
		var bitrate = $td.find('input[name^="bitrate["]').val();
		$('#bitrate_input').val(substr(bitrate, 0, bitrate.length-1));
		$('#bitrate_unit_input').val(substr(bitrate, -1));
		
		$('#duplex_input').val($td.find('input[name^="duplex["]').val());
		$('#ssid_input').val($td.find('input[name^="wireless_ssid["]').val());
		$('#norm_input').val($td.find('input[name^="wireless_norm["]').val());
		$('#frequency_input').val($td.find('input[name^="wireless_frequency["]').val());
		$('#channel_input').val($td.find('input[name^="wireless_channel["]').val());
		$('#channel_width_input').val($td.find('input[name^="wireless_channel_width["]').val());
		$('#polarization_input').val($td.find('input[name^="wireless_polarization["]').val());
		
		// dialog button action submit
		$('#dialog_link_detail form button').unbind('click').click(function ()
		{
			if ($('#dialog_link_detail form').valid())
			{
				// fill in hidden fields
				switch (parseInt($td.find('input[name^="type["]').val()))
				{
					case <?php echo Iface_Model::TYPE_WIRELESS ?>:
						$td.find('input[name^="medium["]').val($('#wl_medium_input').val());
						break;
					case <?php echo Iface_Model::TYPE_ETHERNET ?>:
						$td.find('input[name^="medium["]').val($('#eth_medium_input').val());
						break;
					case <?php echo Iface_Model::TYPE_PORT ?>:
						$td.find('input[name^="medium["]').val($('#port_medium_input').val());
						break;
				}
				
				$td.find('input[name^="link_autosave["]').val('1');
				$td.find('input[name^="link_name["]').val($('#link_name_input').val());
				$td.find('input[name^="link_comment["]').val($('#link_comment_input').val());
				$td.find('input[name^="bitrate["]').val($('#bitrate_input').val() + $('#bitrate_unit_input').val());
				$td.find('input[name^="duplex["]').val($('#duplex_input').val());
				$td.find('input[name^="wireless_ssid["]').val($('#ssid_input').val());
				$td.find('input[name^="wireless_norm["]').val($('#norm_input').val());
				$td.find('input[name^="wireless_frequency["]').val($('#frequency_input').val());
				$td.find('input[name^="wireless_channel["]').val($('#channel_input').val());
				$td.find('input[name^="wireless_channel_width["]').val($('#channel_width_input').val());
				$td.find('input[name^="wireless_polarization["]').val($('#polarization_input').val());
				
				// close dialog
				$('#dialog_link_detail').dialog('close');
			}
		});
		
		$('#eth_medium_input').parent().parent().show();
		$('#wl_medium_input').parent().parent().show();
		$('#port_medium_input').parent().parent().show();
		$('#ssid_input').parent().parent().show();
		$('#norm_input').parent().parent().show();
		$('#frequency_input').parent().parent().show();
		$('#channel_input').parent().parent().show();
		$('#channel_width_input').parent().parent().show();
		$('#polarization_input').parent().parent().show();
		
		switch (parseInt($td.find('input[name^="type["]').val()))
		{
			case <?php echo Iface_Model::TYPE_WIRELESS ?>:
				$('#eth_medium_input').parent().parent().hide();
				$('#port_medium_input').parent().parent().hide();
				break;
			case <?php echo Iface_Model::TYPE_ETHERNET ?>:
				$('#wl_medium_input').parent().parent().hide();
				$('#port_medium_input').parent().parent().hide();
				$('#ssid_input').parent().parent().hide();
				$('#norm_input').parent().parent().hide();
				$('#frequency_input').parent().parent().hide();
				$('#channel_input').parent().parent().hide();
				$('#channel_width_input').parent().parent().hide();
				$('#polarization_input').parent().parent().hide();
				break;
			case <?php echo Iface_Model::TYPE_PORT ?>:
				$('#wl_medium_input').parent().parent().hide();
				$('#eth_medium_input').parent().parent().hide();
				$('#ssid_input').parent().parent().hide();
				$('#norm_input').parent().parent().hide();
				$('#frequency_input').parent().parent().hide();
				$('#channel_input').parent().parent().hide();
				$('#channel_width_input').parent().parent().hide();
				$('#polarization_input').parent().parent().hide();
				break;
		};
		
		// open dialog
		$('#dialog_link_detail').dialog({
			title: '<?php echo __('Add details to link') ?>',
			modal: true,
			position: ['center', 100],
			width: 500
		});
		
		return false;
	}
	
	/**
	 * Opens dialog for specifing of details of iface. Data are stored in hidden
	 * fields. 
	 */
	function add_detail_to_iface()
	{
		var $td = $(this).parent().parent();
		$('#dialog_iface_detail form').validate();
		
		// set form with current values
		$('#iface_name_input').val($td.find('input[name^="name["]').val());
		$('#comment_input').val($td.find('input[name^="comment["]').val());
		$('#port_number_input').val($td.find('input[name^="number["]').val());
		$('#port_mode_input').val($td.find('input[name^="port_mode["]').val());
		$('#wireless_mode_input').val($td.find('input[name^="wireless_mode["]').val());
		$('#wireless_antenna_input').val($td.find('input[name^="wireless_antenna["]').val());
		
		// dialog button action submit
		$('#dialog_iface_detail form button').unbind('click').click(function ()
		{
			if ($('#dialog_iface_detail form').valid())
			{
				// fill in hidden fields
				$td.find('input[name^="name["]').val($('#iface_name_input').val());
				$td.find('input[name^="comment["]').val($('#comment_input').val());
				$td.find('input[name^="number["]').val($('#port_number_input').val());
				$td.find('input[name^="port_mode["]').val($('#port_mode_input').val());
				$td.find('input[name^="wireless_mode["]').val($('#wireless_mode_input').val());
				$td.find('input[name^="wireless_antenna["]').val($('#wireless_antenna_input').val());
				
				// update connected to device
				$td.find('select[name^="connected["]').trigger('change', $td.find('select[name^="connected_iface["]').val());
				
				//set texts
				if (parseInt($td.find('input[name^="type["]').val()) == <?php echo Iface_Model::TYPE_PORT ?>)
				{
					$td.find('.port_name').text('Port ' + $('#port_number_input').val() + ', <?php echo __('Mode') ?> ' + port_modes[$('#port_mode_input').val()]);
				}
				else
				{
					$td.find('.iface_name').text($('#iface_name_input').val());
				}
				// close dialog
				$('#dialog_iface_detail').dialog('close');
			}
			return false;
		});

		$('#port_number_input').parent().parent().show();
		$('#port_mode_input').parent().parent().show();
		$('#wireless_mode_input').parent().parent().show();
		$('#wireless_antenna_input').parent().parent().show();

		switch (parseInt($td.find('input[name^="type["]').val()))
		{
			case <?php echo Iface_Model::TYPE_WIRELESS ?>:
				$('#port_number_input').parent().parent().hide();
				$('#port_mode_input').parent().parent().hide();
				break;
			case <?php echo Iface_Model::TYPE_ETHERNET ?>:
				$('#port_number_input').parent().parent().hide();
				$('#port_mode_input').parent().parent().hide();
				$('#wireless_mode_input').parent().parent().hide();
				$('#wireless_antenna_input').parent().parent().hide();
				break;
			case <?php echo Iface_Model::TYPE_PORT ?>:
				$('#wireless_mode_input').parent().parent().hide();
				$('#wireless_antenna_input').parent().parent().hide();
				break;
			case <?php echo Iface_Model::TYPE_INTERNAL ?>:
				$('#port_number_input').parent().parent().hide();
				$('#port_mode_input').parent().parent().hide();
				$('#wireless_mode_input').parent().parent().hide();
				$('#wireless_antenna_input').parent().parent().hide();
				break;
		};
		
		// open dialog
		$('#dialog_iface_detail').dialog({
			title: '<?php echo __('Add details to interface') ?>',
			modal: true,
			position: ['center', 100],
			width: 500
		});
		
		return false;
	}
	
	/**
	 * Opens dialog with filter for connected to devices.
	 */
	function filter_devices()
	{
		var $tr = $(this).parent().parent();
		
		$('#loading-overlay').show();
		
		setTimeout(function()
		{
			// dialog button action submit
			$('#filter_form').unbind('submit').submit(function ()
			{
				var $i = $tr.find('input[name^="_device_filter["]');
				$i.val($(this).serialize()).trigger('change');
				$('#dialog_filter_devices').dialog('close');
				return false;
			});

			// filter field subnet
			var subnet_id = $tr.find('select[name^="subnet["]').val();
			$('.filter_field_subnet').val(subnet_id);
		
			// open dialog
			$('#dialog_filter_devices').dialog({
				title: '<?php echo __('Filter devices') ?>',
				modal: true,
				position: ['center', 100],
				width: 700
			});
			
			$('#loading-overlay').hide();
		});
		
		return false;
	}
	
	/**
 	 * On click to refresh button - reload connected ifaces
	 */
	function refresh_ifaces()
	{
		var $td = $(this).parent();
		var $siface = $td.find('select[name^="connected_iface["]');
		$siface.css('opacity', 0.5);
		$td.find('select[name^="connected["]').trigger('change', $siface.val());
		$siface.animate({'opacity': 1}, 100);
		return false;
	}
	
	/**
	 * On change of connected to device dropdown, select suitable ifaces
	 * 
	 * @param event			Change event
	 * @param iface_id		Iface Id for selecting loaded dependent fields
	 */
	function change_connected(event, iface_id)
	{
		$.ajaxSetup({
			async: false
		});
		
		var $eif = $(this).parent().find('select[name^="connected_iface["]');
		var $ety = $(this).parent().parent().find('input[name^="type["]');
		
		if (!$eif.length || !$ety.length)
		{
			return;
		}
		
		var value = $(this).val();
		
		$(this).parent().find('a.device_show').remove();
		
		var $popup_add_iface = $(this).parent().parent().find('.popup-add');
		var $refresh_button_ifaces = $(this).parent().parent().find('.refresh_ifaces');
		
		$popup_add_iface.hide();
		$refresh_button_ifaces.hide();
		
		if (!parseInt(value))
		{
			$eif.html('');
			$eif.removeClass('required');
			return;
		}
		
		if (!$eif.hasClass('required'))
		{
			$eif.addClass('required');
		}
		
		// add link for showing of device
		$(this).parent().find('.a_filter_devices').after($('<a>', {
			href:	'<?php echo url_lang::base() ?>devices/show/' + value,
			title:	'<?php echo __('Show selected device') ?>'
		}).addClass('device_add_detail_button popup_link device_show').html('<?php echo html::image(array('src' => '/media/images/icons/grid_action/show.png')) ?>'));
		
		// show add button
		var add_button_href = '<?php echo url_lang::base() ?>ifaces/add/';
		add_button_href += value + '/null/1/';
		add_button_href += $(this).parent().parent().find('input[name^="type["]').val();
		$popup_add_iface.attr('href', add_button_href).show();
		
		// show refresh button
		$refresh_button_ifaces.show();
		
		// change map button href for prefilled enum type in add form dialog
		var map_a = $(this).parent().find('.device_map');
		
		if (map_a.length)
		{
			var parts = map_a.attr('href').split('?');
			map_a.attr('href', rtrim(parts[0], '0123456789/') + '/' + value + '?' + parts[1]);
		}
		
		var wmode = $(this).parent().parent().find('input[name^="wireless_mode["]').val();
		
		$.getJSON('<?php echo url_lang::base() ?>json/get_ifaces?data='+value+'&itype='+
			$ety.val()+'&wmode='+wmode, function (data)
		{
			var options = ['<option value="">---- <?php echo __('Select interface') ?> ---</option>'];
			
			for (var i in data)
			{
				options.push('<option value="');
				options.push(data[i].id);
				options.push('"');
				
				if (iface_id == data[i].id || data.length == 1)
				{
					options.push(' selected="selected"');
				}
				
				options.push('>');
				options.push(data[i].name);
				options.push('</option>');
			}

			$eif.html(options.join('')).trigger('change');
		});
		
		$.ajaxSetup({
			async: true
		});
	}
	
	/**
	 * On change of connected iface to device dropdown, set link
	 */
	function change_connected_iface()
	{
		var iface_id = $(this).val();
		var $p = $(this).parent().parent();
		var made = false;
			
		if (parseInt(iface_id))
		{
			$.ajax({
				method:		'get',
				dataType:	'json',
				async:		false,
				url:		'<?php echo url_lang::base(); ?>json/get_link_by_iface?iface_id=' + iface_id,
				success:	function (v)
				{
					if (v && parseInt(v.id))
					{
						made = true;
						$p.find('input[name^="link_id["]').val(v.id);
						$p.find('input[name^="medium["]').val(v.medium);
						$p.find('input[name^="link_name["]').val(v.name);
						$p.find('input[name^="link_comment["]').val(v.comment);
						$p.find('input[name^="bitrate["]').val(v.bitrate);
						$p.find('input[name^="duplex["]').val(v.duplex);
						$p.find('input[name^="wireless_ssid["]').val(v.wireless_ssid);
						$p.find('input[name^="wireless_norm["]').val(v.wireless_norm);
						$p.find('input[name^="wireless_frequency["]').val(v.wireless_frequency);
						$p.find('input[name^="wireless_channel["]').val(v.wireless_channel);
						$p.find('input[name^="wireless_channel_width["]').val(v.wireless_channel_width);
						$p.find('input[name^="wireless_polarization["]').val(v.wireless_polarization);
					}
				}
			});
		}
		
		var type = $p.find('input[name^="type["]').val();

		if (!made)
		{
			var default_name = (type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo __('air') ?>' : '<?php echo __('cable') ?>';
			var device_id = $(this).parent().find('select[name^="connected["] option:selected').val();

			$.ajax({
				method:		'get',
				dataType:	'json',
				async:		false,
				url:		'<?php echo url_lang::base(); ?>json/get_device_name?device_id=' + device_id,
				success:	function (v)
				{
					if (v && v.name)
					{
						default_name += ' ' + v.name;
					}
				}
			});

			default_name += ' - ' + $('#device_name').val();

			$p.find('input[name^="link_id["]').val(null);
			$p.find('input[name^="link_name["]').val(default_name);
			$p.find('input[name^="link_comment["]').val(null);
			$p.find('input[name^="medium["]').val((type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? <?php echo Link_Model::MEDIUM_AIR ?> : <?php echo Link_Model::MEDIUM_CABLE ?>);
			$p.find('input[name^="bitrate["]').val((type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo Link_Model::get_wireless_max_bitrate(Link_Model::NORM_802_11_G) ?>M' :  '100M');
			$p.find('input[name^="duplex["]').val(0);
			$p.find('input[name^="wireless_ssid["]').val(null);
			$p.find('input[name^="wireless_norm["]').val((type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo Link_Model::NORM_802_11_G ?>' : null);
			$p.find('input[name^="wireless_frequency["]').val(null);
			$p.find('input[name^="wireless_channel["]').val(null);
			$p.find('input[name^="wireless_channel_width["]').val(null);
			$p.find('input[name^="wireless_polarization["]').val(null);
		}
		
		// inform user if the new connection will break some old connection (#397)
		if (type == <?php echo Iface_Model::TYPE_PORT ?> || type == <?php echo Iface_Model::TYPE_ETHERNET ?>)
		{
			$.ajax({
				method:		'get',
				dataType:	'json',
				url:		'<?php echo url_lang::base(); ?>json/get_iface_and_device_connected_to_iface?iface_id=' + iface_id,
				success:	function (v)
				{
					if (v && v.device && v.iface)
					{
						var m = '<?php echo __('Interface that you choosed is connected to another interface') ?>:\n\n';
						m += '<?php echo __('Device') ?>: ' + v.device.id + ', ' + v.device.name + '\n';
						m += '<?php echo __('Interface') ?>: ' + v.iface.name + ', ' + v.iface.mac + '\n\n';
						m += '<?php echo __('If you do not change this connected to option, link between these devices will be destroyed') ?>!';
						alert(m);
					}
				}
			});
		}
	}
	
	/**
	 * On any change in row select use button
	 */
	function use_row()
	{
		var mac = $(this).parent().parent().find('input[name^="mac["]').val();
		var subnet = $(this).parent().parent().find('select[name^="subnet["]').val();
			
		$(this).parent().parent().find('.get_connected_to_device_and_iface').toggle(mac != '' && subnet != '')
		
		$(this).parent().parent().find('input[name^="use["]')
				.attr('checked', true).trigger('change');
	}
	
	/**
	 * On change of use of row  - change all fields to required or otherwise
	 */
	function change_use()
	{		
		if (!$(this).is(':checked') && ($(this).attr('type') == 'checkbox'))
		{
			$(this).parent().parent().find('input, select').removeClass('error');
			$(this).parent().parent().find('label.error').remove();
		}
	}
	
	/**
	 * On change of device filter - change content of form 
	 */
	function change_filter_connected()
	{
		var $tr = $(this).parent().parent();
		var $select = $tr.find('select[name^="connected["]');
		
		// loader
		$select.html('<option value=""><?php echo __('Loading data, please wait') ?>...</option>');
	
		// load filtered content
		$.getJSON('<?php echo url_lang::base() ?>json/get_filtered_devices?' + urldecode($(this).val()), function (options)
		{			
			// select suggestion
			var type = $tr.find('input[name^="type["]').val();
			
			// reload suggestions
			reload_suggestions($select, false);
			
			var sug = suggest_connected_to[type];
			
			// update form
			var options_html = ['<option value="">--- <?php echo __('Select device') ?> ---</option>'];
			
			for (var i in options)
			{
				options_html.push('<optgroup label="');
				options_html.push(options[i]['user_name']);
				options_html.push('">');
				
				for (var u in options[i]['devices'])
				{
					var device = options[i]['devices'][u];
					
					options_html.push('<option value="');
					options_html.push(device['id']);
					options_html.push('"');

					if (sug != undefined && device['id'] == sug.device_id)
					{
						options_html.push(' selected="selected"');
					}

					options_html.push('>');
					options_html.push(device['name']);
					options_html.push('</option>');
				}
				
				options_html.push('</optgroup>');
			}
			
			var iface_id = (sug == undefined || sug.iface_id == undefined) ? undefined : sug.iface_id;

			$select.html(options_html.join('')).trigger('change', iface_id);
		});
	}
	
	/**
 	 * On change of IP address fields add require option to subnet if value is
	 * not empty.
	 */
	function change_ip_address()
	{
		var $subnet = $(this).parent().find('select[name="subnet["]');
		
		if ($(this).val().length)
		{
			if (!$subnet.hasClass('required'))
			{
				$subnet.addClass('required');
			}
		}
		else
		{
			$subnet.removeClass('required');
		}
	}
	
	/**
	 * Reloads suggestions stored in suggest_connected_to by values of row of form
	 * 
	 * @param e				Element in row
	 * @param async			Is request to server asynchronious [optional]
	 */
	function reload_suggestions(e, async)
	{		
		if (async == undefined)
		{
			async = true;
		}
		
		suggest_connected_to = new Array();
		
		if ('<?php echo Settings::get('device_add_auto_link_enabled') ?>' !== '1')
		{
			return; // suggestion are not enabled in settings
		}
		
		if (parseInt($('#user_id').val()))
		{
			var qs = [];
			qs.push('user_id=' + $('#user_id').val());
			qs.push('gpsx=' + urlencode($('#gpsx').val()));
			qs.push('gpsy=' + urlencode($('#gpsy').val()));
			qs.push('wmode=' + $(e).parent().parent().find('input[name^="wireless_mode["]').val());
			qs.push($(e).parent().parent().find('input[name^="_device_filter["]').val());
			
			$.ajax({
				method: 'get',
				dataType: 'json',
				async: (async == true),
				url: '<?php echo url_lang::base() ?>json/get_suggestion_for_connecting_to?' + qs.join('&'),
				success: function(data)
				{
					suggest_connected_to = data;
				}
			});
		}
	}
	
	/**
	 * Creates hidden inputs by given array.
	 * 
	 * @param inputs		Definition of inputs (key is name and value is value of field)
	 */
	function create_hidden_inputs(inputs)
	{
		var html = [];
		
		for (var k in inputs)
		{
			if(inputs.hasOwnProperty(k))
			{
				html.push('<input type="hidden" name="');
				html.push(k);
				html.push('" value="');
				html.push(inputs[k]);
				html.push('" />');
			}
		}
		
		return html.join('');
	}
	
	/**
 	 * Creates a from of group according to data. Form is indexem by global
	 * couter which initial value is given also by a parameter. The final
	 * value of the counter is returned.
	 *
	 * @param $group		Group on which the form is created
	 * @param data			Data (device templates form)
	 * @param start_index	Start index of counter
	 * @param default_iface Default iface index or -1 if there is no default in this group
	 * @return integer		The value of counter after creating
	 */
	function create_form_of_group($group, data, start_index, default_iface)
	{
		var id = substr($group.attr('id'), strlen('group-'));
		var html_buffer = ['<tr class="group-' + id + '-items"><td colspan="2">'];
		
		html_buffer.push('<table class="extended" style="width: 100%"><tr>');
		
		// header
		
		if (data['count'] == undefined)
		{
			html_buffer.push('<th style="width:10px"><?php echo __('Use') ?>?</th>');
		}
			
		if (data['has_mac'])
		{
			html_buffer.push('<th style="width: 150px"><?php echo __('Interface') ?></th>');
		}
		else if (data['type'] == <?php echo Iface_Model::TYPE_PORT ?>)
		{
			html_buffer.push('<th><?php echo __('Port') ?></th>');
		}
		
		if (data['has_ip'])
		{
			html_buffer.push('<th style="width: 150px"><?php echo __('IP address') ?></th>');
		}
			
		if (data['has_link'])
		{
			html_buffer.push('<th style="width: 180px"><?php echo __('Connected to device') ?></th>');
		}
		
		html_buffer.push('</tr>');
		
		// indicator of first row of group
		var first_row = true;
		
		// body
		var count = (data['count'] == undefined) ? data['max_count'] : data['count'];
		count += start_index;
		
		for (var i = start_index; i < count; i++)
		{
			// get current item
			var item = false;
			
			if (data['items'] != undefined)
			{
				item = data['items'].shift();
			}
			
			// build hidden elements
			var iface_hidden_a = new Array();
			iface_hidden_a['name[' + i + ']'] = (item.name == undefined) ? null : item.name;
			iface_hidden_a['comment[' + i + ']'] = null;
			iface_hidden_a['number[' + i + ']'] = (item.number == undefined) ? null : item.number;
			iface_hidden_a['port_mode[' + i + ']'] = (item.port_mode == undefined) ? null : item.port_mode;
			iface_hidden_a['type[' + i + ']'] = data['type'];
			iface_hidden_a['wireless_mode[' + i + ']'] = (item.wireless_mode == undefined) ? null : item.wireless_mode;
			iface_hidden_a['wireless_antenna[' + i + ']'] = (item.wireless_antenna == undefined) ? null : item.wireless_antenna;
			var iface_hid = create_hidden_inputs(iface_hidden_a);

			if (data['has_ip'])
			{
				var ip_hidden_a = new Array();
				ip_hidden_a['dhcp[' + i + ']'] = 0;
				ip_hidden_a['gateway[' + i + ']'] = 0;
				ip_hidden_a['service[' + i + ']'] = 0;
				var ip_hiddden = create_hidden_inputs(ip_hidden_a);
			}

			if (data['has_link'])
			{
				var link_hidden_a = new Array();
				link_hidden_a['link_autosave[' + i + ']'] = '0';
				link_hidden_a['link_id[' + i + ']'] = null;
				link_hidden_a['link_name[' + i + ']'] = null;
				link_hidden_a['link_comment[' + i + ']'] = null;
				link_hidden_a['medium[' + i + ']'] = (data['type'] == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? <?php echo Link_Model::MEDIUM_AIR ?> : <?php echo Link_Model::MEDIUM_CABLE ?>;
				link_hidden_a['bitrate[' + i + ']'] = (data['type'] == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo Link_Model::get_wireless_max_bitrate(Link_Model::NORM_802_11_G) ?>M' :  '100M';
				link_hidden_a['duplex[' + i + ']'] = 0;
				link_hidden_a['wireless_ssid[' + i + ']'] = null;
				link_hidden_a['wireless_norm[' + i + ']'] = (data['type'] == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo Link_Model::NORM_802_11_G ?>' : null;
				link_hidden_a['wireless_frequency[' + i + ']'] = null;
				link_hidden_a['wireless_channel[' + i + ']'] = null;
				link_hidden_a['wireless_channel_width[' + i + ']'] = null;
				link_hidden_a['wireless_polarization[' + i + ']'] = null;
				link_hidden_a['_device_filter[' + i + ']'] = null;
				var link_hid = create_hidden_inputs(link_hidden_a);
			}

			// make HTML
			html_buffer.push('<tr>');
			
			// not hard count => some fields may be optional
			if (data['count'] == undefined)
			{
				html_buffer.push('<th style="width:10px">')
				
				if (data['min_count'] <= (i - start_index)) // optional
				{
					html_buffer.push('<input type="checkbox" name="use[');
					html_buffer.push(i);
					html_buffer.push(']" value="1" style="width: auto;" />');
				}
				else // hard
				{
					html_buffer.push('<input type="hidden" name="use[');
					html_buffer.push(i);
					html_buffer.push(']" value="1" />');
				}
				
				html_buffer.push('</th>');
			}
			else
			{
				html_buffer.push('<input type="hidden" name="use[');
				html_buffer.push(i);
				html_buffer.push(']" value="1" />');
			}
			
			html_buffer.push('<td><label class="device_add_label"><?php echo __('Name') ?>: </label>');
			html_buffer.push('<b class="iface_name">');
			html_buffer.push(item['name']);
			html_buffer.push('</b> ');
			html_buffer.push('<a href="#" title="<?php echo __('Add details to interface') ?>" class="device_add_detail_button add_detail_to_iface">');
			html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/settings.gif')) ?>');
			html_buffer.push('</a><br />');
			
			if (data['has_mac'])
			{
				var auto_fill = false;
				
				html_buffer.push('<label class="device_add_label">MAC: </label>');
				html_buffer.push('<input type="text" name="mac[');
				html_buffer.push(i);
				html_buffer.push(']" ');
				
				<?php if (!empty($connection_request_model)): ?>
				// add mac from request
				if ((default_iface >= 0) && ((i - start_index) == default_iface))
				{
					auto_fill = true;
					html_buffer.push('value="<?php echo $connection_request_model->mac_address ?>" ');
				}
				<?php endif; ?>
				
				html_buffer.push('class="mac_address mac_address_check" style="width: 12em" />');
				
				if (!auto_fill) // auto loading of MAC addresses
				{
					html_buffer.push('<a href="#" title="<?php echo __('Automatically load mac address') ?>" class="device_add_detail_button load_mac" style="display:none">');
					html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/reload.png')) ?>');
					html_buffer.push('</a>');
				}
			}
			else if (data['type'] == <?php echo Iface_Model::TYPE_PORT ?>)
			{
				var text = 'Port ' + item.number + ', <?php echo __('Mode') ?> ' + port_modes[item.port_mode];
				html_buffer.push('<b class="port_name" width="font-size: 110%">' + text + '</b>');
			}
			
			html_buffer.push(iface_hid);
			html_buffer.push('</td>');
			
			if (data['has_ip'])
			{
				html_buffer.push('<td>');
				html_buffer.push('<label class="device_add_label">IP: </label>');
				html_buffer.push('<input type="text" name="ip[');
				html_buffer.push(i);
				html_buffer.push(']" ');
				
				<?php if (!empty($connection_request_model)): ?>
				// add ip from request
				if ((default_iface >= 0) && ((i - start_index) == default_iface))
				{
					html_buffer.push('value="<?php echo $connection_request_model->ip_address ?>" ');
				}
				<?php endif; ?>
				
				html_buffer.push('style="width:14em" class="ip_address ip_address_check" />');
				html_buffer.push(ip_hiddden);
				html_buffer.push('<a href="#" class="device_add_detail_button add_detail_to_ip" title="<?php echo __('Add details to IP address') ?>">');
				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/settings.gif')) ?>');
				html_buffer.push('</a><br />');
				html_buffer.push('<label class="device_add_label"><?php echo __('Subnet') ?>: </label>');
				html_buffer.push('<select name="subnet[');
				html_buffer.push(i);
				html_buffer.push(']" ');
				
				<?php if (!empty($connection_request_model)): ?>
				// add mac from request
				if ((default_iface >= 0) && ((i - start_index) == default_iface))
				{
					html_buffer.push('class="subnet_fill_in_connection_request_model_value" ');
				}
				<?php endif; ?>
				
				html_buffer.push('style="min-width: 14em; max-width: 22em; width: auto">');
				html_buffer.push(subnets_options);
				html_buffer.push('</select>');
				html_buffer.push('</td>');
			}
				
			if (data['has_link'])
			{
				html_buffer.push('<td style="width: 180px">');
				html_buffer.push('<select name="connected[');
				html_buffer.push(i);
				html_buffer.push(']" style="width: 16em"');
				
				if (first_row && data['type'] != '<?php echo Iface_Model::TYPE_PORT ?>')
				{
					html_buffer.push(' class="connected_first_' + data['type'] + '"');
				}
				
				html_buffer.push('>');
				html_buffer.push(devices_options);
				html_buffer.push('</select>');
				html_buffer.push('<a href="#" class="device_add_detail_button a_filter_devices" title="<?php echo __('Filter devices') ?>">');
				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/filter.png')) ?>');
				html_buffer.push('</a>');
				
/////////////////// Device map - waiting for improoving of functionality :-( ///
//				html_buffer.push('<a href="<?php echo url_lang::base() ?>devices/map?action=devices_add&name=connected[');
//				html_buffer.push(i);
//				html_buffer.push(']" class="device_add_detail_button popup_link device_map" title="<?php echo __('Select device using device map') ?>">');
//				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/map_icon.gif')) ?>');
//				html_buffer.push('</a>');
////////////////////////////////////////////////////////////////////////////////

				html_buffer.push('<br />');
				html_buffer.push(link_hid);
				html_buffer.push('<select name="connected_iface[');
				html_buffer.push(i);
				html_buffer.push(']" style="width: 16em"');
				
				if (first_row && data['type'] != '<?php echo Iface_Model::TYPE_PORT ?>')
				{
					html_buffer.push(' class="connected_iface_first_' + data['type'] + '"');
				}
				
				html_buffer.push('>');
				html_buffer.push('<option value="">---- <?php echo __('Select interface') ?> ---</option>');
				html_buffer.push('</select>');
				html_buffer.push('<a href="<?php echo url_lang::base() ?>ifaces/add" id="popup-link-');
				html_buffer.push(i + 100);
				html_buffer.push('" class="device_add_detail_button popup-add popup_link isReloadOff dispNone" title="<?php echo __('Add new interface') ?>">');
				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/ico_add.gif', 'style' => 'width:10px;height:10px')) ?>');
				html_buffer.push('</a>');
				html_buffer.push('<a href="#" class="device_add_detail_button refresh_ifaces dispNone" title="<?php echo __('Refresh interfaces of device') ?>">');
				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/refresh.png')) ?>');
				html_buffer.push('</a>');
				html_buffer.push('<a href="#" class="device_add_detail_button get_connected_to_device_and_iface');
				<?php if (!empty($connection_request_model)): ?>
				// enable getting on connection request (MAC and subnet aready filled in)
				if ((i - start_index) != default_iface)
				{
					html_buffer.push(' dispNone');
				}
				<?php endif; ?>
				html_buffer.push('" title="<?php echo __('Get Connected to device and iface') ?>">');
				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/reload.png')) ?>');
				html_buffer.push('</a>');
				html_buffer.push('<a href="#" class="device_add_detail_button add_detail_to_link" title="<?php echo __('Add details to link') ?>">');
				html_buffer.push('<?php echo html::image(array('src' => 'media/images/icons/settings.gif')) ?>');
				html_buffer.push('</a><br />');
				html_buffer.push('</td>');
			}
			
			html_buffer.push('</tr>');
			
			first_row = false;
		}		
		
		html_buffer.push('</table></td></tr>');
		
		$group.after(html_buffer.join(''));
		
		// hide group if there is no default iface
		if (default_iface === -1)
		{
			$group.find('.group-button').trigger('click');
		}
		
		return i;
	}
	
	/**
	 * Creates form according to device template
	 * 
	 * @param device_template_value		Device template
	 */
	function create_form_from_device_template(device_template_value)
	{
		var eths = device_template_value[<?php echo Iface_Model::TYPE_ETHERNET ?>];
		var wlans = device_template_value[<?php echo Iface_Model::TYPE_WIRELESS ?>];
		var ports = device_template_value[<?php echo Iface_Model::TYPE_PORT ?>];
		var internals = device_template_value[<?php echo Iface_Model::TYPE_INTERNAL ?>];
		var i = 0;
		
		// get default iface
		var default_iface = null;
		
		// not set? => choose any thing
		if (device_template_value['default_iface'] == undefined)
		{
			if (eths.count)
				default_iface = '<?php echo Iface_Model::TYPE_ETHERNET ?>:0';
			else if (wlans.count)
				default_iface = '<?php echo Iface_Model::TYPE_WIRELESS ?>:0';
			else if (ports.count)
				default_iface = '<?php echo Iface_Model::TYPE_PORT ?>:0';
			else if (internals.count)
				default_iface = '<?php echo Iface_Model::TYPE_INTERNAL ?>:0';
			else
				default_iface = '-1:-1'; // undefined
		}
		else
		{
			default_iface = device_template_value['default_iface'];
		}
		
		// default values
		var default_parts = default_iface.split(':');
		var default_eth = (default_parts[0] == '<?php echo Iface_Model::TYPE_ETHERNET ?>') ? default_parts[1] : -1;
		var default_wlan = (default_parts[0] == '<?php echo Iface_Model::TYPE_WIRELESS ?>') ? default_parts[1] : -1;
		var default_port = (default_parts[0] == '<?php echo Iface_Model::TYPE_PORT ?>') ? default_parts[1] : -1;
		var default_int = (default_parts[0] == '<?php echo Iface_Model::TYPE_INTERNAL ?>') ? default_parts[1] : -1;
		
		// trade name
		$eth_ifaces_group.before($('<input>', {
			name:	'trade_name',
			type:	'hidden',
			value:	$('#device_template_id').text()
		}));
		
		// ethernet
		if (eths['count'] > 0)
		{
			i = create_form_of_group($eth_ifaces_group, eths, i, default_eth);
			$eth_ifaces_group.show();
		}
		else
		{
			$eth_ifaces_group.hide();
			$('.eth-items').remove();
		}
		
		// wireless
		if (wlans['max_count'] > 0)
		{
			i = create_form_of_group($wlan_ifaces_group, wlans, i, default_wlan);
			$wlan_ifaces_group.show();
		}
		else
		{
			$wlan_ifaces_group.hide();
			$('.' + $wlan_ifaces_group.attr('id') + '-items').remove();
		}
		
		// port
		if (ports['count'] > 0)
		{
			i = create_form_of_group($port_group, ports, i, default_port);
			$port_group.show();
		}
		else
		{
			$port_group.hide();
			$('.' + $port_group.attr('id') + '-items').remove();
		}
		
		// internal
		if (internals['count'] > 0)
		{
			i = create_form_of_group($internal_group, internals, i, default_int);
			$internal_group.show();
		}
		else
		{
			$internal_group.hide();
			$('.' + $internal_group.attr('id') + '-items').remove();
		}
		
		// activate all actions and events
		$('.add_detail_to_iface').click(add_detail_to_iface);
		$('.add_detail_to_ip').click(add_detail_to_ip);
		$('.get_connected_to_device_and_iface').click(get_connected_to_device_and_iface);
		$('.add_detail_to_link').click(add_detail_to_link);
		$('.a_filter_devices').click(filter_devices);
		$('.refresh_ifaces').click(refresh_ifaces);
		$('input[name^="use["]').change(change_use);
		$('input[name^="mac["], input[name^="ip["], select[name^="connected["]').change(use_row);
		$('input[name^="ip["]').change(change_ip_address);
		$('select[name^="connected["]').change(change_connected);
		$('select[name^="connected_iface["]').change(change_connected_iface);
		$('input[name^="_device_filter["]').change(change_filter_connected);
		
		<?php if (!empty($connection_request_model)): ?>
		$('.subnet_fill_in_connection_request_model_value').val(<?php echo $connection_request_model->subnet_id ?>);
		<?php endif; ?>
		
	
		// set default value of filter
		var $filters = $('input[name^="_device_filter["]');
		
		if ($filters.length)
		{
			$('input[name^="_device_filter["]').val($('#filter_form').serialize());
			// reload suggestions (all suggestions are same)
			reload_suggestions($filters.get(0), false);
		}

		// suggestion for default iface (#281)
		if (device_template_value['default_iface'] != undefined)
		{
			var i = default_parts[0];
			var sug = suggest_connected_to[i];
			
			if (sug != undefined && sug.device_id != undefined && sug.iface_id != undefined)
			{
				$('.connected_first_' + i + ' option[value="' + sug.device_id + '"]').attr('selected', true);
				$('.connected_first_' + i).trigger('change', sug.iface_id);
			}
		}
		// set suggestion on first elements of each group
		else
		{
			for (var i in suggest_connected_to)
			{
				var sug = suggest_connected_to[i];

				if (sug.device_id != undefined && sug.iface_id != undefined)
				{
					$('.connected_first_' + i + ' option[value="' + sug.device_id + '"]').attr('selected', true);
					$('.connected_first_' + i).trigger('change', sug.iface_id);
				}
			}
		}
	}
	
	/**
	 * After confirming of first step of form, some fields must not be editable
	 * in second step. This function replace these fields by text elements
	 * and values of form stores in hidden fields with same names as previous
	 * fields.
	 */
	function disable_solid_fields()
	{
		var fields = [
			'device_type', 'user_id', 'device_template_id', 'town_id',
			'street_id', 'street_number', 'country_id', 'gpsx', 'gpsy',
			'active_links', 'town', 'district', 'street', 'zip'
		];
		
		for (var i in fields)
		{
			var $el = $('#' + fields[i]);
			var text = $el.val();
			
			if ($el.length)
			{
				if ($el[0].nodeName == 'SELECT')
				{
					text = $el.find('option:selected').text();
				}

				if ($el.attr('multiple') == 'multiple')
				{
					var parent_table = $el.parent().parent();

					// remove options dropdown
					parent_table.children().first().remove();
					// remove move buttons
					parent_table.children().first().remove();

					// remove search boxes
					parent_table.next().remove();

					$el.find('option').each(function(){
						var $input = $('<input>', {
							type:	'hidden',
							name:	$el.attr('name'),
							value:	$(this).val()
						});

						$el.parent().append($('<p>').html('<b>'+$(this).text()+'</b>')).append($input);
						$input.attr('id', fields[i]);
					});

					// remove dropdown
					$el.remove();
				}
				else
				{
					var $input = $('<input>', {
						type:	'hidden',
						name:	$el.attr('name'),
						value:	$el.val()
					});

					$el.after($('<b>').text(text)).after($input).remove();
					$input.attr('id', fields[i]);
				}
			}
		}
	}
	
	/// triggers code //////////////////////////////////////////////////////////
	
	// hide all
	$eth_ifaces_group.hide();
	$wlan_ifaces_group.hide();
	$port_group.hide();
	$internal_group.hide();
	
	// disable posting of form => just go to next phase
	$('#device_add_form').unbind('submit').submit(function ()
	{
		var $this = $(this);
		// validate
		if (!$this.validate().form())
		{
			return false;
		}
		// disable previous fields
		disable_solid_fields();
		// remove add buttons
		$this.find('a.popup_link').remove();
		// loader
		$this.after('<?php echo html::image(array('src' => 'media/images/icons/animations/ajax-loader.gif', 'id' => 'da_loader', 'class' => 'ajax-loader')); ?>');
		// build form
		$.getJSON('<?php echo url_lang::base() ?>json/get_device_template_value', {
			device_template_id: $('#device_template_id').val()
		}, function (d)
		{
			create_form_from_device_template(d);
			// change button
			$this.find('.submit').text('<?php echo __('Save') ?>');
			// remove this action
			$this.unbind('submit');
			// add new
			$this.submit(function ()
			{
				if ($(this).validate().form())
				{
					var isValid = true;
					// check if all IP addresses are unique
					var ips = [];
					$.each($('input[name^="ip["]'), function (i, v)
					{
						var ip = trim($(v).val());
						if (ip.length)
						{
							if (ips.indexOf(ip) == -1)
							{
								ips.push(trim($(v).val()));
							}
							else
							{
								alert('<?php echo __('Some IP addresses are same, please change them') ?>!');
								isValid = false;
							}
							
							var gateway = $(v).parent().children('input[name^="gateway["]').val();
							
							var subnet_id = $(v).parent().children('select[name^="subnet["]').val();
							
							// subnet has already have gateway
							if (in_array(subnet_id, gateway_subnets) && gateway == 1)
							{
								alert('<?php echo __('Subnet has already have gateway') ?>');
								isValid = false;
							}
						}
					});
					// check if all MAC addresses are unique
					var macs = [];
					$.each($('input[name^="mac["]'), function (i, v)
					{
						var mac = trim($(v).val());
						if (mac.length)
						{
							if (macs.indexOf(mac) == -1)
							{
								macs.push(trim($(v).val()));
							}
							else
							{
								alert('<?php echo __('Some MAC addresses are same, please change them') ?>!');
								isValid = false;
							}
						}
					});
								
					// send form
					return isValid;
				}
				
				return false;
			});
			// hide loader
			$('#da_loader').hide();
		});
		// do not send form
		return false;
	});
	
	// correct dropdown after adding of a template
	$('#device_template_id').live('addOption', function (e, new_option_id)
	{
		$('#device_type').trigger('change');
		$('#device_template_id').val(new_option_id);
	});
	
	// correct dropdown after adding of a iface
	$('select[name^="connected_iface["]').live('addOption', function (e, new_option_id)
	{
		// reload ifaces
		$(this).parent().find('select[name^="connected["]').trigger('change', new_option_id);
	});
	
	// on change type update form to proper functionality
	$('#device_type').change(function ()
	{
		var value = $(this).val(),
            text = (value) ? $(this).find('option:selected').text() : false;
		
		// reload devices templates
		$('#device_template_id').html('<option><?php echo __('Loading data, please wait') ?>...</option>');
		
		$.getJSON('<?php echo url_lang::base() ?>json/get_device_templates_by_type?type='+value, function(data)
		{
			var options = [];
			
			$.each(data, function(key, val)
			{
				options.push('<option value="' + val.id + '"');
				
				if (val.isDefault)
				{
					options.push(' selected="selected"');
				}
				
				options.push('>' + val.name + '</option>');
			});
			
			$('#device_template_id').html(options.join(''));
			
			var name_parts = $('#device_name').val().split(' ');
			
			if (name_parts.length <= 2)
			{
				$('#device_name').val(name_parts[0] + (text ? ' ' + text : ''));
			}
			
			// trigger change
			$('#device_template_id').change();
		});
		
		// change add button href for prefilled enum type in add form dialog
		var button = $('#device_template_id').parent().find('a');
		
		if (button.length)
		{
			var parts = button.attr('href').split('?');
			
			parts[1] = (parts[1] == undefined) ? '' : '?' + parts[1];
			
			button.attr('href', rtrim(parts[0], '0123456789/') + '/' + value + parts[1]);
		}
	});
	
	// on change template update selected active links
	$('#device_template_id').change(function ()
	{
		var value = $(this).val();
		
		$.getJSON('<?php echo url_lang::base() ?>json/get_device_template_active_links?template='+value, function(data)
		{
			var options = [];
			
			multiple_select_search(this.id, '');

			$('#active_links option').attr('selected', true);
			$('#active_links_right_button').click();
				
			$.each(data, function(key, val)
			{
				$('#active_links_options option[value='+val.id+']').attr('selected', true);
			});
			
			$('#active_links_left_button').click();
		});
	});
	
	// automatically load MAC address
	$('.load_mac').live('click', function ()
	{
		var $this = $(this);
		var $img = $this.find('img');
		var loader = '<?php echo url::base() ?>media/images/icons/animations/ajax-loader.gif';
		
		if ($img.attr('src') == loader)
			return false; // waiting
		
		var index = $this.parent().find('input[type="text"]').attr('name').substr('mac'.length);
		var ip = $('input[name="ip' + index + '"]').val();
		var subnet_id = $('select[name="subnet' + index + '"]').val();
		
		if (ip.length && subnet_id.length)
		{
			var oldSrc = $this.find('img').attr('src');
			$img.attr('src', loader);
			// get MAC
			$.getJSON('<?php echo url_lang::base() ?>/json/obtain_mac_address', {ip_address:ip,subnet_id:subnet_id}, function (data)
			{
				$img.attr('src', oldSrc);
				
				if (data.state)
				{
					$('input[name="mac' + index + '"]').val(data.mac);
					$('input[name="mac' + index + '"]').trigger('change');
					$this.hide();
				}
				else
				{
					alert('<?php echo __('Cannot load mac address, reason') ?>:\n' + data.message);
				}
			});
		}
		
		return false;
	});
	
	// change visibility of MAc autofill after setting of a value to IP and subnet fields
	$('input[name^="ip["], select[name^="subnet["]').live('keyup change', function ()
	{
		var ip = $(this).parent().find('input[name^="ip["]').val();
		var subnet_id = $(this).parent().find('select[name^="subnet["]').val();
		
		if (subnet_id.match(/^[0-9]+$/) && ip.match(/^([0-9]{1,3}\.){3}([0-9]{1,3})$/))
		{
			$(this).parent().parent().find('.load_mac').show();
		}
		else
		{
			$(this).parent().parent().find('.load_mac').hide();
		}
	});
	
	<?php if (!empty($connection_request_model)): ?>
	// confirm first part of form after loading
	$('#device_add_form').submit();
	<?php endif ?>
	