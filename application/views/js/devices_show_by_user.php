<?php
/**
 * Devices show all javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("#device_button_plus, #device_button_minus").click(function ()
	{
		if (this.id == "device_button_plus")
		{
			$(".device").removeClass("dispNone");
			$(".device_button").attr("src", "<?php echo url::base() ?>media/images/icons/ico_minus.gif");
			$(".device_button").attr("title", "<?php echo __('Hide this table') ?>");
		}
		else
		{
			$(".device").addClass("dispNone");
			$(".device_button").attr("src", "<?php echo url::base() ?>media/images/icons/ico_add.gif");
			$(".device_button").attr("title", "<?php echo __('Show this table') ?>");
		}
	});
	
	$(".device_button").click(function ()
	{
		var name = substr(this.id,0,strrpos(this.id,"button")-1);
		if ($("#"+name).hasClass("dispNone"))
		{
			$("#"+name+"_button").attr("src", "<?php echo url::base() ?>media/images/icons/ico_minus.gif");
			$("#"+name+"_button").attr("title", "<?php echo __('Hide this table') ?>");
			$("#"+name).removeClass("dispNone");
		}
		else
		{
			$("#"+name+"_button").attr("src", "<?php echo url::base() ?>media/images/icons/ico_add.gif");
			$("#"+name+"_button").attr("title", "<?php echo __('Show this table') ?>");
			$("#"+name).addClass("dispNone");
		}
	});
	