<?php
/**
 * Segment add javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("#action").live('change', function (){
		var is_not_delete = ($(this).val() != 'delete');
		
		$("#priority").parent().parent().toggle(is_not_delete);
	});
	
	$("#action").trigger('change');