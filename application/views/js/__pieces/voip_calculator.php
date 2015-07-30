<?php
/**
 * Calculator of VoIP calls javascript view.
 * Hadle AJAX request for getting price of call.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	/**
	 * Gets price from SIP
	 */
	function get_price()
	{
		var $calculate_voip_call_input = $('#number');
		var $output = $('#number_price');
		
		$calculate_voip_call_input.attr('disable', 'disable');
		$('#a_calculate').unbind('click');
		
		$output.css('color', '#ccc');
		$output.html('<?php echo __('Wait please') ?>...');
		
		$.get('<?php echo url_lang::base() ?>voip_calls/voip_call_price?from=<?php echo $sip_name ?>&to=' +
			$calculate_voip_call_input.val(), function(data)
		{
			$output.css('color', 'black');
			$output.html(data);
			
			$calculate_voip_call_input.removeAttr('disable');
			$('#a_calculate').click(get_price);
			$calculate_voip_call_input.focus();
		});
		
		return false;
	}
	
	/**
	 * On keyup search if input is valid
	 */
	function keyup(event)
	{
		var $calculate_voip_call_input = $('#number');
		var $calculate_voip_call = $('#a_calculate');
		$('#a_calculate').unbind('click');

		if (($calculate_voip_call_input.val().length < 3) ||
			!$calculate_voip_call_input.val().match(/^\d+$/))
		{
			$calculate_voip_call_input.css('border', '1px solid red');
			$calculate_voip_call.css('opacity', 0.5);
			$calculate_voip_call.css('cursor', 'default');
		}
		else
		{
			if (event.which == 13)
			{
				get_price();
			}
			else
			{
				$calculate_voip_call_input.css('border', '1px solid #ccc');
				$calculate_voip_call.css('opacity', 1);
				$calculate_voip_call.css('cursor', 'pointer');
				$('#a_calculate').click(get_price);
			}
		}
	}
	
	// init of calculator
	
	var $calculate_voip_call_input = $('#number');
	var $calculate_voip_call = $('#a_calculate');

	$calculate_voip_call.css('opacity', 0.5);
	$calculate_voip_call.css('cursor', 'default');

	$calculate_voip_call_input.click(function ()
	{
		$calculate_voip_call_input.val('');
		$calculate_voip_call_input.css('color', 'black');
		$calculate_voip_call_input.unbind('click');
		return false;
	});

	$calculate_voip_call_input.keyup(keyup);
	$calculate_voip_call_input.change(keyup);
