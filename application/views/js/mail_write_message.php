<?php
/**
 * Mail write message javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	// list of users
	var users_list = <?php echo $users_list ?>;
	
	// functions for parsing input text
	var mail_write_message =
	{
		split: function( val )
		{
			return val.split( /,\s*/ );
		},

		extractLast: function( term )
		{
			return mail_write_message.split( term ).pop();
		}
	}
	
	// add jQuery UI autocomplete
	$('#to.autocomplete').bind('keydown', function(event)
		{
			if (event.keyCode === $.ui.keyCode.TAB &&
				$(this).data('ui-autocomplete').menu.active)
			{
				event.preventDefault();
			}
		}).autocomplete(
		{
			minLength: 0,
			source: function(request, response)
			{
				response ($.ui.autocomplete.filter(
					users_list, mail_write_message.extractLast(request.term)));
			},
			focus: function()
			{
				return false;
			},
			select: function(event, ui)
			{
				// add multiple labels
				var labels = mail_write_message.split(this.value);
				labels.pop();
				labels.push(ui.item.login);
				labels.push("");
				this.value = labels.join(", ");
				
				return false;
			}
		});
	
	