<?php
/**
 * Application address point database callback
 * 
 * @author David Raška
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var apd_xhr = null;
	var apd_search_again = false;
	var apd_context = null;
	var apd_show = false;
	
	function get_el(id, context) {
		if (typeof context !== 'undefined')
		{
			apd_context = context;
		}
		
		if (apd_context.element.attr('id').indexOf('domicile') === 0)
		{
			return $('#domicile_'+id);
		}
		else
		{
			return $('#'+id);
		}
	};
	
	function apd_town_render( ul, item ) {
		return $( "<li>" )
			.data("item.autocomplete", item)
			.append( "<a><span class='label details'>" + item.label + "</span></a>")
			.appendTo( ul );
	};
	
	function apd_district_render( ul, item ) {
		var details = '';
		var cls = '';

		if (typeof item.town !== 'undefined' &&
			item.town.length)
		{
			details = item.town;
		}

		if (details !== '')
		{
			details = "<br><span class='details'>" + details + "</span></a>";
			cls = " details";
		}

		return $( "<li>" )
			.data("item.autocomplete", item)
			.append( "<a><span class='label"+cls+"'>" + item.label + "</span>" + details)
			.appendTo( ul );
	};
	
	function apd_street_render( ul, item ) {
		var details = '';
		var number = '';
		var cls = '';
		var detail_array = new Array();

		if (typeof item.town !== 'undefined' &&
			item.town.length)
		{
			detail_array.push(item.town);
		}

		if (typeof item.district !== 'undefined' &&
			item.town !== item.district &&
			item.district.length)
		{
			detail_array.push(item.district);
		}

		if (typeof item.zip !== 'undefined' &&
			item.zip.length)
		{
			detail_array.push(item.zip);
		}

		if (typeof item.number !== 'undefined' &&
			item.number.length)
		{
			number = " " + item.number;
		}

		details = detail_array.join(' - ');

		if (details !== '')
		{
			details = "<br><span class='details'>" + details + "</span></a>";
			cls = " details";
		}

		return $( "<li>" )
			.data("item.autocomplete", item)
			.append( "<a><span class='label"+cls+"'>" + item.label + number + "</span>" + details)
			.appendTo( ul );
	};
	
	$(function() {
		$("#street, #domicile_street").autocomplete({
			source: function( request, response ) {
				if (apd_xhr !== null)
					apd_xhr.abort();

				apd_xhr = $.ajax({
					url: '<?php echo url::base() ?>cs/json/get_address',
					dataType: "json",
					data: "street="+encodeURIComponent(get_el('street', this).val())+"&country="+encodeURIComponent(get_el('country_id').val())+"&town="+encodeURIComponent(get_el('town').val())+"&district="+encodeURIComponent(get_el('district').val()),
					success: function( data ) {
						get_el('street', this._parent).removeClass('ui-autocomplete-loading');
						response( $.map(data, function ( item ) {
								return {
									label: item.street,
									value: item.street + ((typeof item.street !== 'undefined' && item.street.length) ? ' ' : '') + ((typeof item.number !== 'undefined') ? item.number : ''),
									town: item.town_name,
									district: item.district_name,
									street: item.street,
									number: item.number,
									zip: item.zip_code
							}
						}));
						
						apd_xhr = null;
					}
				});
			},
			select: function (event, ui) {
				var street = '';
				if (typeof ui.item.street !== 'undefined' &&
					ui.item.street.length)
				{
					street = ui.item.street + " ";
				}

				if (typeof ui.item.number !== 'undefined' &&
					ui.item.number.length)
				{
					street += ui.item.number;
				}
				else
				{
					apd_search_again=true;
				}
				
				get_el('street').val(street);

				if (typeof ui.item.town !== 'undefined' &&
					ui.item.town.length)
				{
					get_el('town').val(ui.item.town);
				}

				if (typeof ui.item.district !== 'undefined' &&
					ui.item.district.length)
				{
					get_el('district').val(ui.item.district);
				}

				if (typeof ui.item.zip !=='undefined' &&
					ui.item.zip.length)
				{
					get_el('zip').val(ui.item.zip);
				}
				
				get_el('street').change();

				return false;
			},
			close: function ( event,ui ) {
				if (apd_search_again)
				{
					get_el('street').autocomplete("close");
					get_el('street').autocomplete("search", get_el('street').val());

					apd_search_again=false;
				}
			}
		});
				
		$("#district, #domicile_district").autocomplete({
			source: function( request, response ) {
				if (apd_xhr !== null)
					apd_xhr.abort();

				apd_xhr = $.ajax({
					url: '<?php echo url::base() ?>cs/json/get_address',
					dataType: "json",
					data: "town="+encodeURIComponent(get_el('town', this).val())+"&district="+encodeURIComponent(get_el('district').val())+"&country="+encodeURIComponent(get_el('country_id').val()),
					success: function( data ) {
						get_el('district', this._parent).removeClass('ui-autocomplete-loading');
						response( $.map(data, function ( item ) {
								return {
									label: item.district_name,
									item: item,
									town: item.town_name,
									district: item.district_name
							}
						}));
						
						apd_xhr = null;
					}
				});
			},
			select: function (event, ui) {
				if (typeof ui.item.town !== 'undefined' &&
					ui.item.town.length)
				{
					get_el('town').val(ui.item.town);
				}

				if (typeof ui.item.district !== 'undefined' &&
					ui.item.district.length)
				{
					get_el('district').val(ui.item.district);
				}
				
				get_el('street').focus();
				
				return false;
			},
			minLength: 0
		});

		$("#town, #domicile_town").autocomplete({
			source: function( request, response ) {
				if (apd_xhr !== null)
					apd_xhr.abort();

				apd_xhr = $.ajax({
					url: '<?php echo url::base() ?>cs/json/get_address',
					dataType: "json",
					data: "town="+encodeURIComponent(get_el('town', this).val())+"&country="+encodeURIComponent(get_el('country_id').val()),
					success: function( data ) {
						get_el('town', this._parent).removeClass('ui-autocomplete-loading');
						response( $.map(data, function ( item ) {
								return {
									label: item.town_name,
									item: item,
									town: item.town_name,
									district: item.district_count
								}
						}));
						
						apd_xhr = null;
					}
				});
			},
			select: function (event, ui) {
				if (typeof ui.item.town !== 'undefined' &&
					ui.item.town.length)
				{
					get_el('town').val(ui.item.town);
				}
				
				get_el('district').val('');

				// has town any district?
				if (ui.item.district > 1)
				{
					// enable district field and set focus
					get_el('district').removeAttr('disabled');
					apd_show = true;
					get_el('district').focus();
					apd_show = false;
				}
				else
				{
					// disable district fiels and set focus to street
					get_el('district').attr('disabled', 'disabled');
					get_el('street').focus();
				}

				return false;
			},
			minLength: 2
		});

		$("#street").data( "autocomplete" )._renderItem = apd_street_render;

		$("#district").data( "autocomplete" )._renderItem = apd_district_render;
		
		$("#town").data( 'autocomplete' )._renderItem = apd_town_render;
		
		if ($("#domicile_street").length)
		{
			$("#domicile_street").data( "autocomplete" )._renderItem = apd_street_render;
		}
		
		if ($("#domicile_district").length)
		{
			$("#domicile_district").data( "autocomplete" )._renderItem = apd_district_render;
		}
		
		if ($("#domicile_town").length)
		{
			$("#domicile_town").data( 'autocomplete' )._renderItem = apd_town_render;
		}
		
		$('#town, #domicile_town').keyup(function(){
			var value = $(this).val();
			var object = new Object();
			object.element = $(this);
			
			if (!get_el('district', object).attr('disabled')
				&& value.match(' -$'))
			{
				$(this).val(substr(value, 0, value.length - 2));
				
				var district = get_el('district');
				district.val('');
				district.focus();
				
				district.autocomplete("search", '');
			}
			
			get_el('district', object).removeAttr('disabled');
		});
		
		$('#district, #domicile_district').keyup(function(){
			if ($(this).val() === ' ')
			{
				$(this).val('');
			}
		});
		
		$('#district, #domicile_district').focusin(function(){
			if (apd_show)
			{
				var object = new Object();
				object.element = $(this);

				get_el('district', object).autocomplete("search", '');
			}
			
			apd_show = false;
		});
		
		$('#town, #domicile_town').focusout(function(){
			var object = new Object();
			object.element = $(this);
			
			if (get_el('town', object).val().length > 2)
			{
				apd_show = true;
				$.ajax({
					url: '<?php echo url::base() ?>cs/json/get_address',
					dataType: "json",
					data: "town="+get_el('town', object).val()+"&country="+get_el('country_id').val(),
					success: function( data ) {
						if (data !== null && 
							typeof data[0] !== 'undefined' &&
							data[0].district_count > 1)
						{
							get_el('district', object).removeAttr('disabled');
						}
						else
						{
							get_el('district', object).autocomplete('close');
							get_el('district').attr('disabled', 'disabled');
							get_el('district').val(get_el('town').val());
							get_el('street').focus();
						}
					}
				});
			}
		});
		
		if ($('#gpsy').length)
		{
			$('#gpsy').after('<div id="ap_map"><div class="ap_form_map_container no_map"></div></div>');
		}

		if ($('#domicile_gpsy').length)
		{
			$('#domicile_gpsy').after('<div id="domicile_ap_map"><div class="ap_form_map_container no_map"></div></div>');
		}
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
				$('#country_id').val(data['country_id']);
				$('#town').val(data['town']);
				$('#district').val(data['quarter']);
				
				if (data['street'] === '')
				{
					$('#street').val(data['number']);
				}
				else
				{
					$('#street').val(data['street']+' '+data['street_number']);
				}
				$('#zip').val(data['zip']).change();
			}
		});
	});
	
$(document).ready(function(){
	$('#street').change();
	$('#domicile_street').change();
});
