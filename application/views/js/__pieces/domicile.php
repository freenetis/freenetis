<?php
/**
 * Domicile javascript view.
 * During adding/editing of member, toogle domicile fields.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	// toogle domicile fields on member editing/adding
	function update_domicile_fields()
	{
		if ($("#use_domicile:checked").val() != null)
		{
			$("[id^='domicile']").parent().parent().show();
		}
		else
		{
			$("[id^='domicile']").parent().parent().hide();
		}
	}
	// toogle domicile
	update_domicile_fields();
	// toogle domical on use_domicile change
	$("#use_domicile").change(update_domicile_fields);
