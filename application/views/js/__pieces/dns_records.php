<?php
/**
 * Javascript editor for DNS records
 * 
 * @author David Raška
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	// record types in select box
	var record_types = [
		{
			value: 'A'
		},
		{
			value: 'AAAA'
		},
		{
			value: 'CNAME'
		},
		{
			value: 'NS'
		},
		{
			value: 'MX'
		}
	];
	
	var dns_form = $('.group_title').parent().parent();
	var dns_records = $('.group_title:last');
	var dns_record_count = 0;
	var dns_ns_record_ns = null;
	var dns_ns_record_a = null;
	
	var dns_zone_xhr = null;
	var dns_zone_id = null;

	/**
	 * Function creates template record and button for adding records
	 */
	$(function() {
		// create button
		dns_records.find('.group').html($('.group_title:last .group').text()+
				'<a id="add_new_record" class="action_field_icon" title="<?php echo __('Add new DNS record')?>">\n\
				<img src="<?php echo url::base()?>media/images/icons/ico_add.gif"/ ></a>');

		// prepare record types
		var len = record_types.length;
		var options = '';

		for (var i = 0 ; i < len; i++)
		{
			options += '<option>'+record_types[i].value+'</option>';
		}

		// create record template
		var template = $('<tr id="record_template">\n\
			<th colspan="2">\n\
			<input class="id" type="hidden" value="0" />\n\
			<input class="n domain_name" type="text" value="" />\n\
			<span class="n_fqdn">&nbsp;</span>\n\
			<input class="ttl required" type="text" />\n\
			<span>IN</span>\n\
			<select class="t">'+options+'</select>\n\
			<input class="p required" />\n\
			<input class="d required" />\n\
			<span class="d_fqdn">&nbsp;</span>\n\
			<span class="ptr_container">\n\
			<input class="ptr" type="checkbox" title="<?php echo __('Generate PTR record'); ?>" checked="checked" />\n\
			</span>\n\
			<a class="action_field_icon remove_record" title="<?php echo __('Delete DNS record')?>">\n\
			<img class="r" src="<?php echo url::base()?>media/images/icons/grid_action/delete.png"/ >\n\
			</a>\n\
			<a class="action_field_icon add_record hide" title="<?php echo __('Add new DNS record')?>">\n\
			<img class="r" src="<?php echo url::base()?>media/images/icons/grid_action/new.png"/ >\n\
			</a>\n\
			</th>\n\
			</tr>');
					
		var deleted = $('<div id="deleted_records"></div>');
					
		template.insertAfter(dns_form);
		deleted.insertAfter(dns_form);
		create_default_ns_record();
		
		$('#zone').addClass('zone_name_check');
	});
	
	function create_default_ns_record()
	{
		$('#add_new_record').click();
		dns_ns_record_ns = dns_form.find('tr').last();
		dns_ns_record_ns.find('.n').attr('disabled','disabled');
		dns_ns_record_ns.find('.ttl').attr('disabled','disabled').css('background-color', 'transparent').addClass('ns');
		dns_ns_record_ns.find('.t').val('NS').attr('disabled','disabled').css('background-color', 'transparent').change();
		dns_ns_record_ns.find('.d').attr('disabled','disabled').css('background-color', 'transparent');
		dns_ns_record_ns.find('.ptr_container').remove();
		dns_ns_record_ns.find('.remove_record').remove();
		dns_ns_record_ns.find('.add_record').remove();
		
		$('#add_new_record').click();
		dns_ns_record_a = dns_form.find('tr').last();
		dns_ns_record_a.find('.n').attr('disabled','disabled');
		dns_ns_record_a.find('.ttl').attr('disabled','disabled').css('background-color', 'transparent').addClass('ns');
		dns_ns_record_a.find('.t').attr('disabled','disabled').css('background-color', 'transparent');
		dns_ns_record_a.find('.d').attr('disabled','disabled').css('background-color', 'transparent');
		dns_ns_record_a.find('.ptr_container').remove();
		dns_ns_record_a.find('.remove_record').remove();
		dns_ns_record_a.find('.add_record').remove();
		$('#primary').change();
		$('#nameserver').keyup();
	}
	
	function get_origin()
	{
		var parent  = dns_form.find('#parent').val();
		if (typeof parent === 'undefined')
		{
			parent = ".";
		}
		
		return dns_form.find('#zone').val()+parent;
	}
	
	function create_fqdn(domain)
	{
		if (domain === "" || domain === "@")
		{
			domain = get_origin();
		}
		else if (domain[domain.length-1] !== ".")
		{
			domain += "."+get_origin();
		}
		
		return domain;
	}
	
	function show_new_record_buttons()
	{
		$('.d').each(function(){
			var type = $(this).parent().find('.t').val();
			
			if (type === "CNAME" ||
				type === "MX" ||
				type === "NS")
			{
				var d = $(this).val();
				var fqdn = create_fqdn(d);
				
				var found = false;
				if (fqdn === d)
				{
					found = true;
				}
				
				$('.dns_record .n').each(function(){
					var t = $(this).parent().find('.t').val();
					
					if (t === "A" || 
						t === "AAAA" ||
						t === "CNAME")
					{
						if (create_fqdn($(this).val()) === fqdn)
						{
							found = true;
						}
					}
				});

				if (found && (d !== "" || d !== "."))
				{
					// hide
					$(this).parent().find('.add_record').addClass('hide');
				}
				else
				{
					// show
					$(this).parent().find('.add_record').removeClass('hide');
				}
			}
			else
			{
				// hide
				$(this).parent().find('.add_record').addClass('hide');
			}
		});
	}
	
	/**
	 * Updates FQDN hints
	 */
	function update_fqdns()
	{
		var w = 0;
		$('.dns_record span.n_fqdn').removeAttr('style');
		$('.dns_record span.n_fqdn').each(function(){
			$(this).text(create_fqdn($(this).parent().find('.n').val()).toLowerCase());
			
			if ($(this).width() > w)
			{
				w = $(this).width();
			}
		});
		$('.dns_record span.n_fqdn').width(w);
		
		w = 0;
		$('.dns_record span.d_fqdn').removeAttr('style');
		$('.dns_record span.d_fqdn').each(function(){
			var type = $(this).parent().find('.t').val();
			
			if (type === "CNAME" ||
				type === "NS" ||
				type === "MX")
			{
				$(this).text(create_fqdn($(this).parent().find('.d').val()).toLowerCase());
			}
			else
			{
				$(this).html('&nbsp;');
			}
			
			if ($(this).width() > w)
			{
				w = $(this).width();
			}
		});
		$('.dns_record span.d_fqdn').width(w);
	}
	
	/**
	 * Sets default NS record
	 */
	$('#primary').on('keyup change', function(){
		dns_ns_record_a.find('.d').val($('#primary option:selected').text());
	});
	
	$('#nameserver').on('keyup', function(){
		dns_ns_record_ns.find('.d').val($(this).val()+'.');
		dns_ns_record_a.find('.n').val($(this).val()+'.');
	});
	
	$('#nxttl').on('keyup', function(){
		if (!$('#ttl').val())
		{
			// sets ns TTL value based on SOA record
			$('.dns_record input.ttl.ns').each(function(){
				$(this).val($('#nxttl').val());
			});
		}
	});
	
	/**
	 * Sets zone TTL for each record
	 */
	$("#ttl").on('keyup', function() {
		$('.dns_record .ttl').removeClass('error');
		
		if ($(this).val())
		{
			// sets TTL, disables input and removes 'required' rule
			$('.dns_record .ttl').val($(this).val()).attr('disabled', 'disabled').removeClass('required').next('label').hide();
		}
		else
		{
			// clears TTL, enables input and adds 'required' rule
			$('.dns_record input.ttl:not(.ns)').each(function(){
				if ($(this).hasClass('required'))
				{
					$(this).val('');
				}
				
				$(this).removeAttr('disabled').addClass('required');
			});
			$('#nxttl').keyup();
		}
	});
	
	// update primary name server based on zone name
	$('#zone').on('keyup', function() {
		update_fqdns();
		show_new_record_buttons();
	});
	
	$('#zone, #nameserver').focusout(function(){
		while ($(this).val()[$(this).val().length - 1] === ".")
		{
			$(this).val($(this).val().substr(0, $(this).val().length - 1));
		}
		
		update_fqdns();
	});
	
	// to lowercase
	$('.n, .d, #zone, #nameserver').live('change', function() {
		$(this).val($.trim($(this).val()).toLowerCase());
	});
	
	/**
	 * Name and value callback updates FQDNs
	 */
	$(".n, .d").live('keyup', function() {
		update_fqdns();
		show_new_record_buttons();
	});
	
	/**
	 * Value change callback validates data
	 */
	$(".d").live('change', function() {
		$(this).valid();
	});
	
	/**
	 * Type change callbacks
	 */
	$('.t').live('change', function() {
		// change MX record style to default
		$(this).parent().find('.p').removeClass('mx');
		$(this).parent().find('.d').removeClass('mx');
		
		update_fqdns();
		show_new_record_buttons();
		
		// remove validators and PTR record checkbox
		$(this).parent().find('.d').removeClass('ip_address ipv6_address domain_name valid');
		$(this).parent().find('.ptr').hide();
		
		
		// Add validators
		if ($(this).val() === "MX")
		{
			var count = $('.dns_record select.t option[value="MX"]:selected').length;
			
			// increase priority of MX record
			$(this).parent().find('.p').addClass('mx').val(count * 10);
			
			$(this).parent().find('.d').addClass('mx domain_name');
		}
		else if ($(this).val() === "A")
		{
			$(this).parent().find('.d').addClass('ip_address');
			$(this).parent().find('.ptr').show();
		}
		else if ($(this).val() === "AAAA")
		{
			$(this).parent().find('.d').addClass('ipv6_address');
			$(this).parent().find('.ptr').show();
		}
		else if ($(this).val() === "CNAME")
		{
			$(this).parent().find('.d').addClass('domain_name');
		}
		
		// validate data
		var data = $(this).parent().find('.d');
		if (data.val().length > 0)
		{
			$(this).parent().find('.d').valid();
		}
	});
	
	// creates new record based on record template
	$('#add_new_record').live('click', function() {
		var record = $('#record_template').clone().removeAttr('id');
		record.find('th').addClass('dns_record');
		//add names
		record.find('.id').attr("name", "id["+dns_record_count+"]");
		record.find('.n').attr("name", "name["+dns_record_count+"]");
		record.find('.ttl').attr("name", "rttl["+dns_record_count+"]");
		record.find('.t').attr("name", "type["+dns_record_count+"]");
		record.find('.p').attr("name", "priority["+dns_record_count+"]");
		record.find('.d').attr("name", "data["+dns_record_count+"]");
		record.find('.ptr').attr("name", "ptr["+dns_record_count+"]");
		
		record.insertAfter(dns_form.find('tr').last());
		
		dns_record_count++;
		if ($("#ttl").val() !== "")
		{
			$('#ttl').keyup();
		}
		
		record.find('.t').change();
	});
	
	/**
	 * Creates new record from current record
	 */
	$('.add_record').live('click',function(){ 
		var value = $(this).parent().find('.d').val();
		$('#add_new_record').click();
		
		// fill name
		var row = dns_form.find('tr').last();
		row.find('.n').val(value);
		
		if ($("#ttl").val() === "")
		{
			row.find('.ttl').val($(this).parent().find('.ttl').val());
		}
		
		row.find('.d').focus();
		
		update_fqdns();
		show_new_record_buttons();
	});

	/**
	 * Removes record
	 */
	$('.remove_record').live('click', function() {
		var db_val = $(this).parent().find('.id').val();
		if (db_val !== "0")
		{
			$('#deleted_records').append('<input type="hidden" value="'+db_val+'" name="deleted['+$('#deleted_records input').length+']">');
		}
		
		$(this).parent().parent().remove();
		update_fqdns();
		show_new_record_buttons();
	});
	
	/**
	  * Fix of multiple select, where adding or removing removes disable attribute
	  */
	function disable_option() {
		$('#secondary_options option').removeAttr('disabled');
		$('#secondary_options option[value="'+$('#primary').val()+'"]').attr('disabled', 'disabled');
	 };
	 
	 $('#secondary_right_button, #secondary_left_button, #secondary_options_button_search_clear').live('click', function(){
		 disable_option();
	 });
	 
	 $('#secondary_options_button_search').live('keyup', function() {
		 disable_option();
	 });
	
	/**
	 * Prevent selecting same primary and secondary DNS server
	 */
	 $('#primary').change(function(){
		// remove from selected
		$('#secondary option').removeAttr('selected');
		$('#secondary option[value="'+$(this).val()+'"]').attr('selected', true);
		$('#secondary_right_button').click();
	 });
	 
	 $('#primary').keydown(function(){
		 $(this).change();
	 });
	 
	 disable_option();
	 