<?php
/**
 * Devices show javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	$("<div>"+
		"<table class=state_change_all_table>" +
		"	<tr>" +
		"		<td>&nbsp;</td>" + 
		"		<td class=center><b><?php echo __("Redirection") ?>:</b></td>" + 
		"		<td class=center><b><?php echo __("E-Mail") ?>:</b></td>" + 
		"		<td class=center><b><?php echo __("SMS") ?>:</b></td>" + 
		"	</tr>" +
		"	<tr>" +
		"		<td><?php echo __("For all items") ?>:</td>" + 
		"		<td class='state_change_all' id='state_change_all_redirection_activate'><img src='<?php echo url::base() ?>media/images/icons/activate.png'> <?php echo __("Activate") ?></td>" +
		"		<td class='state_change_all' id='state_change_all_email_activate'><img src='<?php echo url::base() ?>media/images/icons/activate.png'> <?php echo __("Activate") ?></td>" +
		"		<td class='state_change_all' id='state_change_all_sms_activate'><img src='<?php echo url::base() ?>media/images/icons/activate.png'> <?php echo __("Activate") ?></td>" +
		"	</tr>" +
		"	<tr>" +
		"		<td><?php echo __("For all items") ?>:</td>" + 
		"		<td class='state_change_all' id='state_change_all_redirection_keep'><img src='<?php echo url::base() ?>media/images/icons/keep.png'> <?php echo __("Without change") ?></td>" +
		"		<td class='state_change_all' id='state_change_all_email_keep'><img src='<?php echo url::base() ?>media/images/icons/keep.png'> <?php echo __("Without change") ?></td>" +
		"		<td class='state_change_all' id='state_change_all_sms_keep'><img src='<?php echo url::base() ?>media/images/icons/keep.png'> <?php echo __("Without change") ?></td>" +
		"	</tr>" +
		"	<tr>" +
		"		<td><?php echo __("For all items") ?>:</td>" + 
		"		<td class='state_change_all' id='state_change_all_redirection_deactivate'><img src='<?php echo url::base() ?>media/images/icons/deactivate.png'> <?php echo __("Deactivate") ?></td>" +
		"	</tr>" +
		"</table>" +
		"</div>").insertBefore($(".grid_table", context));
	
	$(".state_change_all").click(function (){
		var arr = explode("_", str_replace("state_change_all_", "", this.id));
		
		$("select[name^="+arr[0]+"]").each(function () {
			
			switch (arr[1])
			{
				case "activate":
					$(this).val(<?php echo Notifications_Controller::ACTIVATE?>);
					break;
					
				case "keep":
					$(this).val(<?php echo Notifications_Controller::KEEP?>);
					break;
					
				case "deactivate":
					$(this).val(<?php echo Notifications_Controller::DEACTIVATE?>);
					break;
			}
			
			$(this).trigger("change");
		});
	});
	
	$("select[name^=redirection],select[name^=email],select[name^=sms]").each(function (){
		$(this).attr("id", str_replace("]","", str_replace("[","_", $(this).attr("name"))));
	});
	
	$("select[name^=redirection],select[name^=email],select[name^=sms]").change(function (){
			if (!$(this).hasClass("dispNone"))
			{
				$(this).addClass("dispNone");
				
				$(this).parent().attr("class", "state_change center").attr("id", "state_change_"+this.id);
				
				$(this).parent().append("<img src='' class='state_change_image' id='state_change_image_"+this.id+"'>");
			}
			
			$("#state_change_image_"+this.id).removeClass("dispNone");
		
			switch ($(this).val())
			{
				case '<?php echo Notifications_Controller::ACTIVATE ?>':
						//$(this).parent().addClass('active');
						//$(this).parent().removeClass('deactive');
						$("#state_change_image_"+this.id).attr("src", "<?php echo url::base() ?>media/images/icons/activate.png");
						title = "<?php echo __('Activate') ?>";
					break;
					
				case '<?php echo Notifications_Controller::KEEP ?>':
						//$(this).parent().removeClass('active');
						//$(this).parent().removeClass('deactive');
						$("#state_change_image_"+this.id).attr("src", "<?php echo url::base() ?>media/images/icons/keep.png");
						title = "<?php echo __('Without change') ?>";
				break;
				
				case '<?php echo Notifications_Controller::DEACTIVATE ?>':
						//$(this).parent().addClass('deactive');
						//$(this).parent().removeClass('active');
						$("#state_change_image_"+this.id).attr("src", "<?php echo url::base() ?>media/images/icons/deactivate.png");
						title = "<?php echo __('Deactivate') ?>";
				break;
			}
			$("#state_change_image_"+this.id).attr("title", title);
	});
	
	$("select[name^=redirection],select[name^=email],select[name^=sms]").trigger('change');
	
	$(".state_change").click(function (){
		
		var id = str_replace("state_change_", "", this.id);
		var value = $("#"+id+" option:selected").attr("value");
		
		value = (value % $("#"+id+" option").length) + 1;
		
		$("#"+id+" option").removeAttr("selected");
		
		$("#"+id+" option[value='"+value+"']").attr("selected", "selected");
		$("#"+id+" option[value='"+value+"']").trigger("change");
	});
	
	$(".grid_form").submit(function (){
		return window.confirm('<?php echo __('Do you really want to activate notifications') ?>?');
	});