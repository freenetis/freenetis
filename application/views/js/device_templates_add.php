<?php
/**	
 * Device templates add JavaScript functionality. Manages dynamic form for adding
 * of template.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

?>
	
	// port mode options
	var port_mode_options = '<?php foreach (Iface_Model::get_port_modes() as $k => $v): ?><option value="<?php echo $k ?>"><?php echo $v ?></option><?php endforeach; ?>';
	// port mode options
	var wiface_mode_options = '<?php foreach (Iface_Model::get_wireless_modes() as $k => $v): ?><option value="<?php echo $k ?>"<?php echo ($k == Iface_Model::WIRELESS_MODE_CLIENT) ? ' selected="selected"' : '' ?>><?php echo $v ?></option><?php endforeach; ?>';
	// port mode options
	var wiface_antenna_options = '<?php foreach (Iface_Model::get_wireless_antennas() as $k => $v): ?><option value="<?php echo $k ?>"<?php echo ($k == Iface_Model::WIRELESS_ANTENNA_SECTIONAL) ? ' selected="selected"' : '' ?>><?php echo $v ?></option><?php endforeach; ?>';
	
	/**
	 * Makes ethernet inputs - enables to add ethrnet input name
	 * 
	 * @param parent	Parent object after which elements will be added
	 * @param count		Count of ethernets
	 */
	function make_ethernet_inputs(parent, count)
	{
		var ename = 'values_ethernet_items';
		
		// create root element
		if (!$('#' + ename).length)
		{
			parent.after($('<tr>').append(
				$('<td>').attr('colspan', '2').append(
					$('<table>').attr('id', ename).css('margin', '4px 20px')
				)
			));
		}
		
		// limit
		if (count > 99)
		{
			count = 99;
		}
		
		var e = $('#' + ename);
		var ch_count = e.find('tr').length;
		
		// inputs alread added but there is too much of them => drop some of them
		if (ch_count > count)
		{
			for (var i = count; i < ch_count; i++)
			{
				e.find('#' + ename + '_id_' + i).remove();
			}
		}
		// add missing items
		else if (ch_count < count)
		{
			for (var i = ch_count; i < count; i++)
			{
				e.append($('<tr>').attr('id', ename + '_id_' + i).append(
					$('<td>').html($('<label>').text('<?php echo __('Name') ?>:')).append(
						$('<input>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_ETHERNET ?>][items][' + i + '][name]',
							'value'	: '<?php echo Iface_Model::get_default_name(Iface_Model::TYPE_ETHERNET) ?>' + (i + 1),
							'class'	: 'textbox',
							'type'	: 'text'
						}).css('margin', '4px 15px')
					)
				));
			}
		}
	}
	
	/**
	 * Makes port inputs - enables to add port modes and numbers
	 * 
	 * @param parent	Parent object after which elements will be added
	 * @param count		Count of ports
	 */
	function make_port_inputs(parent, count)
	{
		var ename = 'values_port_items';
		
		// create root element
		if (!$('#' + ename).length)
		{
			parent.after($('<tr>').append(
				$('<td>').attr('colspan', '2').append(
					$('<table>').attr('id', ename).css('margin', '4px 20px')
				)
			));
		}
		
		// limit
		if (count > 99)
		{
			count = 99;
		}
		
		var e = $('#' + ename);
		var ch_count = e.find('tr').length;
		
		// inputs alread added but there is too much of them => drop some of them
		if (ch_count > count)
		{
			for (var i = count; i < ch_count; i++)
			{
				e.find('#' + ename + '_id_' + i).remove();
			}
		}
		// add missing items
		else if (ch_count < count)
		{
			for (var i = ch_count; i < count; i++)
			{
				e.append($('<tr>').attr('id', ename + '_id_' + i).append(
					$('<td>').html($('<label>').text('<?php echo __('Name') ?>:')).append(
						$('<input>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_PORT ?>][items][' + i + '][name]',
							'value'	: '<?php echo Iface_Model::get_default_name(Iface_Model::TYPE_PORT) ?> ' + (i + 1),
							'class'	: 'textbox',
							'type'	: 'text'
						}).css('margin', '4px 15px')
					)
				).append(
					$('<td>').html($('<label>').text('<?php echo __('Port number') ?>:')).append(
						$('<input>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_PORT ?>][items][' + i + '][number]',
							'class'	: 'textbox number',
							'type'	: 'text',
							'value' : i + 1
						}).css('width', '25px').css('margin', '4px 15px')
					)
				).append(
					$('<td>').html($('<label>').text('<?php echo __('Mode') ?>:')).append(
						$('<select>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_PORT ?>][items][' + i + '][port_mode]',
							'class'	: 'dropdown',
							'type'	: 'dropdown'
						}).html(port_mode_options).css('margin', '4px')
					)
				));
			}
		}
	}
	
	/**
	 * Makes wireless ifaces inputs - enables to add wireless antenna and mode
	 * 
	 * @param parent	Parent object after which elements will be added
	 * @param count		Max count of ifaces
	 */
	function make_wireless_iface_inputs(parent, count)
	{
		var ename = 'values_wireless_items';
		
		// create root element
		if (!$('#' + ename).length)
		{
			parent.after($('<tr>').append(
				$('<td>').attr('colspan', '2').append(
					$('<table>').attr('id', ename).css('margin', '4px 20px')
				)
			));
		}
		
		// limit
		if (count > 99)
		{
			count = 99;
		}
		
		var e = $('#' + ename);
		var ch_count = e.find('tr').length;
		
		// inputs alread added but there is too much of them => drop some of them
		if (ch_count > count)
		{
			for (var i = count; i < ch_count; i++)
			{
				e.find('#' + ename + '_id_' + i).remove();
			}
		}
		// add missing items
		else if (ch_count < count)
		{
			for (var i = ch_count; i < count; i++)
			{
				e.append($('<tr>').attr('id', ename + '_id_' + i).append(
					$('<td>').html($('<label>').text('<?php echo __('Name') ?>:')).append(
						$('<input>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_WIRELESS ?>][items][' + i + '][name]',
							'value'	: '<?php echo Iface_Model::get_default_name(Iface_Model::TYPE_WIRELESS) ?>' + (i + 1),
							'class'	: 'textbox',
							'type'	: 'text'
						}).html(wiface_mode_options).css('margin', '4px 15px')
					)
				).append(
					$('<td>').html($('<label>').text('<?php echo __('Mode') ?>:')).append(
						$('<select>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_WIRELESS ?>][items][' + i + '][wireless_mode]',
							'class'	: 'dropdown',
							'type'	: 'dropdown'
						}).html(wiface_mode_options).css('margin', '4px 15px')
					)
				).append(
					$('<td>').html($('<label>').text('<?php echo __('Antenna') ?>:')).append(
						$('<select>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_WIRELESS ?>][items][' + i + '][wireless_antenna]',
							'class'	: 'dropdown',
							'type'	: 'dropdown'
						}).html(wiface_antenna_options).css('margin', '4px 15px')
					)
				));
			}
		}
	}
	
	/**
	 * Makes internal ifaces inputs - enables to add name
	 * 
	 * @param parent	Parent object after which elements will be added
	 * @param count		Count of internal ifaces
	 */
	function make_internal_iface_inputs(parent, count)
	{
		var ename = 'values_internal_items';
		
		// create root element
		if (!$('#' + ename).length)
		{
			parent.after($('<tr>').append(
				$('<td>').attr('colspan', '2').append(
					$('<table>').attr('id', ename).css('margin', '4px 20px')
				)
			));
		}
		
		// limit
		if (count > 99)
		{
			count = 99;
		}
		
		var e = $('#' + ename);
		var ch_count = e.find('tr').length;
		
		// inputs alread added but there is too much of them => drop some of them
		if (ch_count > count)
		{
			for (var i = count; i < ch_count; i++)
			{
				e.find('#' + ename + '_id_' + i).remove();
			}
		}
		// add missing items
		else if (ch_count < count)
		{
			for (var i = ch_count; i < count; i++)
			{
				e.append($('<tr>').attr('id', ename + '_id_' + i).append(
					$('<td>').html($('<label>').text('<?php echo __('Name') ?>:')).append(
						$('<input>').attr({
							'name'	: 'values[<?php echo Iface_Model::TYPE_INTERNAL ?>][items][' + i + '][name]',
							'value'	: '<?php echo Iface_Model::get_default_name(Iface_Model::TYPE_INTERNAL) ?>' + i,
							'class'	: 'textbox',
							'type'	: 'text'
						}).css('margin', '4px 15px')
					)
				));
			}
		}
	}
	
	/** Last value of wiface_min_max_keyup (cache) */
	var wiface_min_max_keyup_cache = null;
	
	/**
	 * Processes key events of inputs that allows to fill counts of wireless ifaces
	 * 
	 * @param e		Elements who trigger event
	 */
	function wiface_min_max_keyup(e)
	{
		var emin_count = $('input[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][min_count]"]');
		var emax_count = $('input[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][max_count]"]');
		var min_count = emin_count.val();
		var max_count = emax_count.val();
		
		if (!max_count.length)
		{
			max_count = 0;
		}
		else if (isNaN(max_count) || isNaN(min_count))
		{
			if (isNaN(max_count) && isNaN(min_count))
			{
				return false;
			}
			else if (isNaN(max_count))
			{
				max_count = 0;
			}
			else if (isNaN(min_count))
			{
				min_count = 0;
			}
		}
		
		min_count = parseInt(min_count);
		max_count = parseInt(max_count);
		
		if (max_count < min_count)
		{
			max_count = min_count;
			emax_count.val(max_count);
		}
		
		if (max_count != wiface_min_max_keyup_cache)
		{
			wiface_min_max_keyup_cache = max_count;
			make_wireless_iface_inputs(emax_count.parent().parent(), max_count);
		}
	}
	
	/// triggers code //////////////////////////////////////////////////////////
	
	var e_port_count = $('input[name="values[<?php echo Iface_Model::TYPE_PORT ?>][count]"]');
	var e_ethernet_count = $('input[name="values[<?php echo Iface_Model::TYPE_ETHERNET ?>][count]"]');
	var e_wireless_min_count = $('input[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][min_count]"]');
	var e_wireless_max_count = $('input[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][max_count]"]');
	var e_internal_count = $('input[name="values[<?php echo Iface_Model::TYPE_INTERNAL ?>][count]"]');
	
	// on change of ethernet count
	e_ethernet_count.keyup(function ()
	{
		var count = $(this).val();
		
		if (!count.length)
		{
			count = 0;
		}
		else if (isNaN(count))
		{
			return false;
		}
		
		make_ethernet_inputs($(this).parent().parent(), count);
	});
	// on change of port count
	e_port_count.keyup(function ()
	{
		var count = $(this).val();
		
		if (!count.length)
		{
			count = 0;
		}
		else if (isNaN(count))
		{
			return false;
		}
		
		make_port_inputs($(this).parent().parent(), count);
	});
	// on change of min wireless ifaces count
	e_wireless_min_count.keyup(wiface_min_max_keyup);
	// on change of max wireless ifaces count
	e_wireless_max_count.keyup(wiface_min_max_keyup);
	// on change of wireless ifaces count
	e_internal_count.keyup(function ()
	{
		var count = $(this).val();
		
		if (!count.length)
		{
			count = 0;
		}
		else if (isNaN(count))
		{
			return false;
		}
		
		make_internal_iface_inputs($(this).parent().parent(), count);
	});
	
	// add buttons for increasing, decreasing
	input_add_increase_decrease_buttons(e_port_count, 0, 99);
	input_add_increase_decrease_buttons(e_ethernet_count, 0, 99);
	input_add_increase_decrease_buttons(e_wireless_min_count, 0, 99);
	input_add_increase_decrease_buttons(e_wireless_max_count, 0, 99);
	input_add_increase_decrease_buttons(e_internal_count, 0, 99);
	
	<?php if (isset($device_template_value)): ?>
	
	/// edit functionality /////////////////////////////////////////////////////
	
	e_ethernet_count.trigger('keyup');
	e_port_count.trigger('keyup');
	e_wireless_min_count.trigger('keyup');
	e_wireless_max_count.trigger('keyup');
	e_internal_count.trigger('keyup');
	
	// set values of ethernet ifaces items
	<?php $i = 0; foreach ($device_template_value[Iface_Model::TYPE_ETHERNET]['items'] as $item): ?>
	$('input[name="values[<?php echo Iface_Model::TYPE_ETHERNET ?>][items][<?php echo $i++ ?>][name]"]').val('<?php echo $item['name'] ?>');
	<?php endforeach; ?>
	
	// set values of wireless ifaces items
	<?php $i = 0; foreach ($device_template_value[Iface_Model::TYPE_WIRELESS]['items'] as $item): ?>
	$('input[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][items][<?php echo $i ?>][name]"]').val('<?php echo $item['name'] ?>');
	$('select[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][items][<?php echo $i ?>][wireless_mode]"]').val('<?php echo $item['wireless_mode'] ?>');
	$('select[name="values[<?php echo Iface_Model::TYPE_WIRELESS ?>][items][<?php echo $i++ ?>][wireless_antenna]"]').val('<?php echo $item['wireless_antenna'] ?>');
	<?php endforeach; ?>
	// set values of port items
	<?php $i = 0; foreach ($device_template_value[Iface_Model::TYPE_PORT]['items'] as $item): ?>
	$('input[name="values[<?php echo Iface_Model::TYPE_PORT ?>][items][<?php echo $i ?>][name]"]').val('<?php echo $item['name'] ?>');
	$('select[name="values[<?php echo Iface_Model::TYPE_PORT ?>][items][<?php echo $i ?>][port_mode]"]').val('<?php echo $item['port_mode'] ?>');
	$('input[name="values[<?php echo Iface_Model::TYPE_PORT ?>][items][<?php echo $i++ ?>][number]"]').val('<?php echo $item['number'] ?>');
	<?php endforeach; ?>
	
	// set values of internal ifaces items
	<?php $i = 0; foreach ($device_template_value[Iface_Model::TYPE_INTERNAL]['items'] as $item): ?>
	$('input[name="values[<?php echo Iface_Model::TYPE_INTERNAL ?>][items][<?php echo $i++ ?>][name]"]').val('<?php echo $item['name'] ?>');
	<?php endforeach; ?>
		
	<?php endif; ?>