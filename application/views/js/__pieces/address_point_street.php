<?php
/**
 * Application address point street callback.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var street_add_url = $('#street_id').next().attr('href');

	$('#town_id, #domicile_town_id').live("change", function ()
	{
		var options;
		var $street;
		var prev_val;
			
		if ($(this).attr('id') == 'town_id')
		{
			$street = $('#street_id');
		}
		else
		{
			$street = $('#domicile_street_id');
		}
		
		prev_val = $street.val();
		
		$street.html('');
		
		if ((options = town_dropdown_change($(this))) !== false)
		{	
			$street.append($('<option></option>')
					.attr('value', '')
					.text('--- <?php echo __('Without street') ?> ---'));
			
			// hack key is swaped with value because of sorting asociative array
			// by street name
			$.each(options, function(k, v)
			{
				var o = $('<option></option>').attr('value', v).text(k)
				
				if (prev_val == v)
				{
					o.attr('selected', true);
				}
				
				$street.append(o);
			});
			
			$street.show();
			
			if (street_add_url)
			{
				$street.next().show();

				var url_pieces = explode('?', street_add_url);

				url_pieces[0] += '/' + $(this).val();

				$street.next().attr('href', implode("?", url_pieces));
			}
		}
		else
		{
			$street.hide();
			
			if (street_add_url)
			{
				$street.next().hide();
			}
		}
	});
	
	$('#town_id, #domicile_town_id').trigger('change');
	
	$('#street_id').live('change', function()
	{
		$('#town_id').trigger('change');
	});
	
	$('#domicile_street_id').live('change', function()
	{
		$('#domicile_town_id').trigger('change');
	});
	
	$('#user_id').live('change', function()
	{
		$.ajax({
			url: '<?php echo url_lang::base() ?>json/get_user_address',
			async: false,
			data: {user_id: $(this).val()},
			dataType: 'json',
			success: function(data)
			{
				$('#town_id').val(data['town_id']).change();
				$('#street_id').val(data['street_id']);
				$('#country_id').val(data['country_id']);
				$('#street_number').val(data['street_number']).change();
			}
		});
	});
	
	function town_dropdown_change(el)
	{
		var val = parseInt(el.val(), 10);
		var ajax_data = false;
		
		if (isNaN(val) || val <= 0)
		{
			return false;
		}
		
		$.ajax({
			url: '<?php echo url_lang::base() ?>json/get_streets_by_town',
			async: false,
			data: {town_id: val},
			dataType: 'json',
			success: function(data)
			{
				ajax_data = data
			}
		});
		
		return ajax_data;
	}
