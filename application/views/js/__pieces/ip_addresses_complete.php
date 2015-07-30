<?php
/**	
 * Javascript functionality for adding/edditing of IP addresses.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

?>
	$("#ip_address, input.ip_address").live("keydown.autocomplete", function (){
		
		var input = $(this);
		
		input.autocomplete({
			source: "<?php echo url_lang::base() ?>json/get_free_ip_addresses",
			close: function (event, ui) {
				input.trigger('keyup');
			}
		});
	});