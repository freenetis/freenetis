<?php
/**
 * Membership end javascript view.
 * 
 * @author Michal Kliment
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

$("form").submit(function (){
	return confirm('<?php echo __('Do you want to end membership of this member') ?>?');
});