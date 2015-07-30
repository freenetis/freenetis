<?php
/**	
 * Javascript functionality for adding/edditing of interfaces.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

?>
	
	/**
	 * Updates options - options are stored in global variable located in
	 * js/base view.
	 */
	function update_options(id)
	{
		select_multiple[id] = new Array();
				
		$("#"+id).children().each(function ()
		{
			select_multiple[id].push({
				'key': $(this).attr('value'),
				'value': $(this).html()
			});
		});
	}
	
	/**
	 * Opens dialog with filter for connected to devices.
	 */
	function filter_devices()
	{
		$('#loading-overlay').show();
		
		setTimeout(function()
		{
			// dialog button action submit
			$('#filter_form').unbind('submit').submit(function ()
			{
				$('#device_filter').val($(this).serialize()).trigger('change');
				$('#dialog_filter_devices').dialog('close');
				return false;
			});
		
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
	 * On change of device filter - change content of form 
	 */
	function change_filter_connected()
	{
		var $select = $('#connected_to');
		
		// loader
		$select.html('<option value=""><?php echo __('Loading data, please wait') ?>...</option>');
	
		// load filtered content
		$.getJSON('<?php echo url_lang::base() ?>json/get_filtered_devices?' + urldecode($(this).val()), function (options)
		{			
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
					options_html.push('">');
					options_html.push(device['name']);
					options_html.push('</option>');
				}
				
				options_html.push('</optgroup>');
			}
			
			$select.html(options_html.join('')).trigger('change');
		});
	}
	
	/**
	 * Opens dialog for specifing of details of link of iface. Data are stored
	 * in hidden fields. 
	 */
	function add_detail_to_link()
	{
		$('#dialog_link_detail form').validate();
		
		// set form with current values
		$('#link_name_input').val($('#link_name').val());
		$('#link_comment_input').val($('#link_comment').val());
		$('#eth_medium_input').val($('#medium').val());
		$('#wl_medium_input').val($('#medium').val());
		$('#port_medium_input').val($('#medium').val());
		
		var bitrate = $('#bitrate').val();
		$('#bitrate_input').val(substr(bitrate, 0, bitrate.length-1) || '');
		$('#bitrate_unit_input').val(substr(bitrate, -1));
		
		$('#duplex_input').val($('#duplex').val());
		$('#ssid_input').val($('#wireless_ssid').val());
		$('#norm_input').val($('#wireless_norm').val());
		$('#frequency_input').val($('#wireless_frequency').val());
		$('#channel_input').val($('#wireless_channel').val());
		$('#channel_width_input').val($('#wireless_channel_width').val());
		$('#polarization_input').val($('#wireless_polarization').val());
		
		// dialog button action submit
		$('#dialog_link_detail form button').unbind('click').click(function ()
		{
			if ($('#dialog_link_detail form').valid())
			{
				// fill in hidden fields
				switch (parseInt($('#itype').val()))
				{
					case <?php echo Iface_Model::TYPE_WIRELESS ?>:
						$('#medium').val($('#wl_medium_input').val());
						break;
					case <?php echo Iface_Model::TYPE_ETHERNET ?>:
						$('#medium').val($('#eth_medium_input').val());
						break;
					case <?php echo Iface_Model::TYPE_PORT ?>:
						$('#medium').val($('#port_medium_input').val());
						break;
				};
				$('#link_name').val($('#link_name_input').val());
				$('#link_comment').val($('#link_comment_input').val());
				$('#bitrate').val($('#bitrate_input').val() + $('#bitrate_unit_input').val());
				$('#duplex').val($('#duplex_input').val());
				$('#wireless_ssid').val($('#ssid_input').val());
				$('#wireless_norm').val($('#norm_input').val());
				$('#wireless_frequency').val($('#frequency_input').val());
				$('#wireless_channel').val($('#channel_input').val());
				$('#wireless_channel_width').val($('#channel_width_input').val());
				$('#wireless_polarization').val($('#polarization_input').val());
				
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
		
		switch (parseInt($('#itype').val()))
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
	 * On change of connected iface to device dropdown, set link
	 * 
	 * @param $e		Element
	 */
	function change_connected_iface($e)
	{
		var $link_add_detail = $e.parent().find('.add_detail_to_link');
		var iface_id = $e.val();
		var device_id = $('#connected_to').val();
		var made = false;
		var cache = (iface_id ? iface_id : '') + ':' + (device_id ? device_id : '');
		
		// do not load same shit again
		if (window['cache_change_connected_iface'] != undefined &&
			cache == cache_change_connected_iface)
		{
			return false;
		}
		
		cache_change_connected_iface = cache;
		
		if (parseInt(iface_id))
		{
			$link_add_detail.show();
			
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
						$('#link_id').val(v.id);
						$('#medium').val(v.medium);
						$('#link_name').val(v.name);
						$('#link_comment').val(v.comment);
						$('#bitrate').val(v.bitrate);
						$('#duplex').val(v.duplex);
						$('#wireless_ssid').val(v.wireless_ssid);
						$('#wireless_norm').val(v.wireless_norm);
						$('#wireless_frequency').val(v.wireless_frequency);
						$('#wireless_channel').val(v.wireless_channel);
						$('#wireless_channel_width').val(v.wireless_channel_width);
						$('#wireless_polarization').val(v.wireless_polarization);
					}
				}
			});
		}
		else
		{
			$link_add_detail.hide();
		}
		
		var type = $('#itype').val();

		if (!made)
		{
			var default_name = (type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo __('air') ?>' : '<?php echo __('cable') ?>';

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

			default_name += ' - ' + $('#_device_name').val();

			$('#link_id').val(null);
			$('#link_name').val(default_name);
			$('#link_comment').val(null);
			$('#medium').val((type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? <?php echo Link_Model::MEDIUM_AIR ?> : <?php echo Link_Model::MEDIUM_CABLE ?>);
			$('#bitrate').val((type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo Link_Model::get_wireless_max_bitrate(Link_Model::NORM_802_11_G) ?>M' :  '100M');
			$('#duplex').val((type != <?php echo Iface_Model::TYPE_WIRELESS ?> && type != <?php echo Iface_Model::TYPE_VIRTUAL_AP ?>) ? 1 : 0);
			$('#wireless_ssid').val(null);
			$('#wireless_norm').val((type == <?php echo Iface_Model::TYPE_WIRELESS ?>) ? '<?php echo Link_Model::NORM_802_11_G ?>' : null);
			$('#wireless_frequency').val(null);
			$('#wireless_channel').val(null);
			$('#wireless_channel_width').val(null);
			$('#wireless_polarization').val(null);
		}
		
		// inform user if the new connection will break some old connection (#397)
		if (type == <?php echo Iface_Model::TYPE_PORT ?> || type == <?php echo Iface_Model::TYPE_ETHERNET ?>)
		{
			$.ajax({
				method:		'get',
				dataType:	'json',
				url:		'<?php echo url_lang::base(); ?>json/get_iface_and_device_connected_to_iface?iface_id=' + iface_id<?php if ($iface_id): echo " + '&parent_iface_id=$iface_id'"; endif; ?>,
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
	
	// on add of new item to one of multiple selectboxes for port - vlan relationship
	$('#port_vlan_id, #tagged_vlan_id, #untagged_vlan_id').live('addOption', function (e, new_option_id)
	{
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
				var port_vlan_id = $('#port_vlan_id', context).val();
				reload_element('#port_vlan_id', '<?php echo url_lang::base().url_lang::current(0,1) ?>');
				$('#port_vlan_id', context).val(port_vlan_id);
				update_options('tagged_vlan_id');
				break;
				
			case 'untagged_vlan_id':
				multiple_select_add_option('tagged_vlan_id', new_option_id);
				update_options('tagged_vlan_id_options');
				reload_element('#port_vlan_id', '<?php echo url_lang::base().url_lang::current(0,1) ?>');
				var port_vlan_id = $('#port_vlan_id', context).val();
				reload_element('#port_vlan_id', '<?php echo url_lang::base().url_lang::current(0,1) ?>');
				$('#port_vlan_id', context).val(port_vlan_id);
				update_options('untagged_vlan_id');
				break;
		}
		
	});
	
	// on change of port mode - set vlan options accoding to mode
	$('#port_mode', context).live('change', function ()
	{
		switch ($(this).val())
		{
			case '<?php echo Iface_Model::PORT_MODE_ACCESS ?>':
				$('#tagged_vlan_id', context).parent().parent().parent().parent().parent().parent().hide();
				$('#untagged_vlan_id', context).parent().parent().parent().parent().parent().parent().show();
				break;
			
			case '<?php echo Iface_Model::PORT_MODE_TRUNK ?>':
				$('#tagged_vlan_id', context).parent().parent().parent().parent().parent().parent().show();
				$('#untagged_vlan_id', context).parent().parent().parent().parent().parent().parent().hide();
				break;
			
			case '<?php echo Iface_Model::PORT_MODE_HYBRID ?>':
				$('#tagged_vlan_id', context).parent().parent().parent().parent().parent().parent().show();
				$('#untagged_vlan_id', context).parent().parent().parent().parent().parent().parent().show();
				break;
				
			default: // if empty => hide
				$('#tagged_vlan_id', context).parent().parent().parent().parent().parent().parent().hide();
				$('#untagged_vlan_id', context).parent().parent().parent().parent().parent().parent().hide();
				break;
		}
	});
	
	// show/hide items if group showned
	$('#port_vlan_id', context).parents('table').find('.group-button').bind('groupShowed', function ()
	{
		$('#port_mode', context).trigger('change');
	});
	
	// on change of connected to
	$('#connected_to', context).live('change', function (e, iface_id)
	{
		var $connected_to_iface = $('#connected_to_interface');
		var $link_add_iface = $connected_to_iface.parent().parent().find('.popup-add[href*="/ifaces/add"]');
		var val = $(this).val();
		
		$('a.device_show').remove();
		
		if (parseInt(val))
		{
			var iadd_href = $link_add_iface.attr('href').split('?');
			var new_href = '<?php echo url_lang::base() ?>ifaces/add/' + val + '/null/1/<?php echo intval($itype) ? $itype : $connect_type ?>';
			iadd_href[1] = (iadd_href[1] == undefined) ? '' : '?' + iadd_href[1];
			$link_add_iface.attr('href', new_href + iadd_href[1]).show();
			
			// add link for showing of device
			$('.a_filter_devices').after($('<a>', {
				href:	'<?php echo url_lang::base() ?>devices/show/' + val,
				title:	'<?php echo __('Show selected device') ?>'
			}).addClass('device_add_detail_button popup_link device_show').html('<?php echo html::image(array('src' => '/media/images/icons/grid_action/show.png')) ?>'));
			
			$connected_to_iface.html('<option value=""><?php echo __('Loading data, please wait') ?>...</option>');
			
			var wmode = <?php if ($itype == Iface_Model::TYPE_WIRELESS): ?>$('#wireless_mode', context).val();<?php else: ?>null<?php endif; ?>;
			
			if (!wmode)
			{
				wmode = '<?php echo Iface_Model::WIRELESS_MODE_CLIENT ?>';
			}
			
			$.getJSON('<?php echo url_lang::base() ?>json/get_ifaces', {
				data: val, itype: '<?php echo intval($itype) ? $itype : $connect_type ?>', wmode: wmode
			}, function (data)
			{
				var options = ['<option value="">---- <?php echo __('Select interface') ?> ---</option>'];

				for (var i in data)
				{
					options.push('<option value="');
					options.push(data[i].id);
					options.push('"');
					
					if (((data.length == 1) && !iface_id) ||
						(iface_id && (iface_id == data[i].id)))
					{
						options.push(' selected="selected"');
					}
					
					options.push('>');
					options.push(data[i].name);
					options.push('</option>');
				}

				$connected_to_iface.html(options.join(''));
				$connected_to_iface.trigger('change');
			});
		}
		else
		{
			$link_add_iface.hide();
			$connected_to_iface.html('<option value="">--- <?php echo __('Select interface') ?> ---</option>');
			$connected_to_iface.trigger('change');
		}
	});
	
	// on change of connected to interface
	$('#connected_to_interface', context).change(function ()
	{
		change_connected_iface($(this));
	});
	
	// remove interface from link
	$('#remove_from_link', context).live('click', function()
	{
		$('#loading-overlay').show();
		var $this = $(this);
		var url = $(this).attr('href');
		
		setTimeout(function()
		{
			$.ajax({
				async: false,
				type: 'POST',
				url: url+'?noredirect=1',
				success: function ()
				{
					$('#loading-overlay').hide();
					$this.parent().remove();
					return false;
				}
			});
		}, 2);
		return false;
	});
	
	// change ifaces on selecting different mode
	$('#wireless_mode', context).live('change', function ()
	{
		$('#connected_to').trigger('change', $('#connected_to_interface').val());
	});
	
	// correct dropdown after adding of a iface
	$('#connected_to_interface').live('addOption', function (e, new_option_id)
	{
		// reload ifaces
		$('#connected_to').trigger('change', new_option_id);
	});
	
	// hide add button for ifaces at start
	if (!$('.popup-add[href*="/ifaces/add"]').hasClass('isReloadOff'))
	{
		$('.popup-add[href*="/ifaces/add"]').addClass('isReloadOff');
		
		if (!parseInt($('#connected_to').val()))
		{
			$('.popup-add[href*="/ifaces/add"]').hide();
			$('.add_detail_to_link').hide();
		}
		<?php if(!isset($_GET['sended'])): ?>
		else
		{
			$('#connected_to').trigger('change', $('#connected_to_interface').val());
		}
		<?php endif ?>
	}
	
	// add filter button
	$('#connected_to', context).parent().find('.popup-add').after(
		'<a href="#" class="device_add_detail_button a_filter_devices" title="<?php echo __('Filter devices') ?>">' +
		'<?php echo html::image(array('src' => 'media/images/icons/filter.png')) ?></a>'
	);
	// click
	$('.a_filter_devices', context).live('click', filter_devices);
	// filter
	$('#device_filter').live('change', change_filter_connected);
	
	// add link detail button
	$('#connected_to_interface', context).parent().find('.popup-add').after(
		'<a href="#" class="device_add_detail_button add_detail_to_link" title="<?php echo __('Add details to link') ?>">' +
		'<?php echo html::image(array('src' => 'media/images/icons/settings.gif')) ?></a>'
	);
	// click
	$('.add_detail_to_link').click(add_detail_to_link);

<?php if (isset($is_edit) && $is_edit): ?>
	// on change of interface type - reload form to proper edit form
	$('#itype', context).change(function()
	{
		var itype = $(this).val();
		var url_parts = window.location.pathname.split('?');
		var url = rtrim(url_parts[0], '/');
		
		if (url.match(/[0-9]+\/[0-9]+$/))
		{
			url = rtrim(url, '0123456789');
			url = rtrim(url, '/');
		}
		
		url += '/' + itype;
		
		if (url_parts[1])
		{
			url += '?' + url_parts[1];
		}
		
		window.location = url; 
		
		return false;
	});
	
	// change of indicator of changing of link
	$('#change_link').change(function ()
	{
		if ($(this).is(':checked'))
		{
			$('#connected_to').parent().parent().show();
			$('#connected_to_interface').parent().parent().show();
		}
		else
		{
			$('#connected_to').parent().parent().hide();
			$('#connected_to_interface').parent().parent().hide();
		}
	});
	
	// add require fields at start
	$('#port_mode', context).trigger('change');
	$('#change_link').trigger('change');
	
	// add link for device
	if ($('#connected_to').val())
	{
		$('.a_filter_devices').after($('<a>', {
			href:	'<?php echo url_lang::base() ?>devices/show/' + $('#connected_to').val(),
			title:	'<?php echo __('Show selected device') ?>'
		}).addClass('device_add_detail_button popup_link device_show').html('<?php echo html::image(array('src' => '/media/images/icons/grid_action/show.png')) ?>'));
	}
<?php endif ?>
	
<?php if (isset($add_button) && $add_button): ?>
	// if mid-step form opened dialog, on select, just open detailed form
	$('#type').live('change', function ()
	{
		var device_id = parseInt($('#device_id', context).val());
		var type = parseInt($(this).val());
		
		if (type && device_id)
		{
			// update link for openning of dialog - nice h@ck
			var href = '<?php echo url_lang::base() ?>ifaces/add/' + device_id + '/' + type + '/1';
			var dialog = dialogs.get($(this).parents('.dialog'));
			var $a = dialog.getLink();
			$a.attr('href', href);
			$a.attr('id', $a.attr('id') + '00');
			
			// close mid-step dialog
			$(this).parents('.dialog').dialog('close');
			
			// open new dialog
			$a.trigger('click');			
		}
		
		return false;
	});
	// select default port vlan by default - h@ck for multiple selectbox functionality
	$('#untagged_vlan_id option').attr('selected', true);
<?php endif; ?>

	