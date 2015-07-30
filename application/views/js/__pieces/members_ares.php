<?php
/**
 * Members add.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	// on enable of VS generation, disable VS field
	$('#variable_symbol_generate', context).change(function ()
	{
		if ($(this).is(':checked'))
		{
			$('#variable_symbol', context).attr('disabled', true);	
		}
		else
		{
			$('#variable_symbol', context).removeAttr('disabled').focus();
		}
	}).trigger('change');
	
	$("#organization_identifier").parent().append('<img id="load-from-ares-button" src="<?php echo url::base() ?>media/images/icons/reload.png" title="<?php echo __('Load data about member from ARES') ?>">');
	
	/**
	 * Loads inputs from ARES data
	 * 
	 * @author Michal Kliment
	 * @param {type} parameters
	 * @param {type} button
	 * @returns {Boolean}
	 */
	function load_inputs_from_ares_data (parameters, button)
	{
		var loader = '<?php echo url::base() ?>media/images/icons/animations/ajax-loader.gif';
		
		if (button.attr('src') == loader)
			return false; // waiting
	
		var oldSrc = button.attr('src');
		button.attr('src', loader);
		
		$.getJSON('<?php echo url_lang::base() ?>/json/load_member_data_from_ares/', parameters, function (data)
		{
			if (data.state)
			{				
				$("#membername").val(data.name);
				$("#partner_name").val(data.name);
				$("#name").val(data.firstname);
				$("#surname").val(data.lastname);
				$("#organization_identifier").val(data.organization_identifier);
				$("#vat_organization_identifier").val(data.vat_organization_identifier);

				$("#town_id").val(data.town_id);
				$("#town_id").trigger('change');
				$("#street_id").val(data.street_id);
				$("#street_number").val(data.street_number);
				$("#street_number").trigger('change');

				// for invoice
				$("#partner_street").val(data.street);
				$("#partner_street_number").val(data.street_number);
				$("#partner_town").val(data.town);
				$("#partner_zip_code").val(data.zip_code);
			}
			else
			{
				alert (data.text);
			}
			
			button.attr('src', oldSrc);
		});
	}
	
	$("#load-from-ares-button").live('click', function (){
		
		var organization_identifier = $("#organization_identifier").val();
		var name = $("#membername").val();
		var town_id = $("#town_id").val();
		var $this = $(this);
		
		// organization identifier is set
		if (organization_identifier != '')
		{	
			// try find data by organization identifier
			load_inputs_from_ares_data ({'organization_identifier':organization_identifier}, $this);
		}
		else
		{
			// try find data by name and town
			load_inputs_from_ares_data({'name':name, 'town_id':town_id}, $this);	
		}
		
	});