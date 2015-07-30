<?php
/**
 * Members show all javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	<?php if ($registrations): ?>
		$("a").click(function (){
			return window.confirm('<?php echo __('Do you really want to end editing of registrations') ?>?');
		});
	<?php endif; ?>