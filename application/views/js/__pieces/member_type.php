<?php
/**
 * Member type javascript view.
 * During adding/editing of member, toogle fields according to member type.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	// on change of type change the form
	$('select#type').change(function ()
	{
		var val = $(this).val();
		var $entrance_date_row = $('th.entrance_date').parent();
		
		// applicant - hide registration
		if (val == '<?php echo Member_Model::TYPE_APPLICANT ?>')
		{
			$entrance_date_row.hide();
		}
		// default - show all
		else
		{
			$entrance_date_row.show();
		}
		
	}).trigger('change');	
