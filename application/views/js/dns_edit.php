<?php
/**
 * Creates DNS records on zone edit page
 * 
 * @author David Raška
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	/**
	 * Adds existing records to list of DNS records
	 */
	$(function(){
		var dns_records = null;
		
		<?php if (isset($records) && $records): ?>
		dns_records = <?php echo json_encode($records); ?>;
		<?php endif; ?>
			
		<?php if (isset($dns_zone_id) && $dns_zone_id): ?>
		dns_zone_id = <?php echo $dns_zone_id; ?>;
		<?php endif; ?>
		
		for (var i = 0; i < dns_records.length; i++)
		{
			$('#add_new_record').click();
			var r = $('.dns_record:last');
			r.find('.id').attr('value',dns_records[i].id);
			r.find('.n').val(dns_records[i].name);
			if ($('#ttl').val().length === 0)
			{
				r.find('.ttl').val(dns_records[i].ttl);
			}
			r.find('.t').val(dns_records[i].type).change();
			r.find('.p').val(dns_records[i].param);
			if (dns_records[i].param !== 'on')
			{
				r.find('.ptr').removeAttr('checked');
			}
			r.find('.d').val(dns_records[i].value);
		}
		
		update_fqdns();
		show_new_record_buttons();
		$('#nxttl').keyup();
	});