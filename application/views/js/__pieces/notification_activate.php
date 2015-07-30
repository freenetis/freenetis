<?php
/**
 * Devices show javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	
	var notif_dd_img_src = {
		<?php echo Notifications_Controller::ACTIVATE ?>	: '<?php echo url::base() ?>media/images/icons/activate.png',
		<?php echo Notifications_Controller::KEEP ?>		: '<?php echo url::base() ?>media/images/icons/keep.png',
		<?php echo Notifications_Controller::DEACTIVATE ?>	: '<?php echo url::base() ?>media/images/icons/deactivate.png'
	};
	
	var notif_dd_img_title = {
		<?php echo Notifications_Controller::ACTIVATE ?>	: '<?php echo __('Activate') ?>',
		<?php echo Notifications_Controller::KEEP ?>		: '<?php echo __('Keep') ?>',
		<?php echo Notifications_Controller::DEACTIVATE ?>	: '<?php echo __('Deactivate') ?>'
	};
	
	$("<div> \
		<table class=state_change_all_table> \
			<tr> \
				<td>&nbsp;</td> \
				<?php if (Settings::get('redirection_enabled')): ?>		<td class=center><b><?php echo __("Redirection") ?>:</b></td> <?php endif ?> \
				<?php if (Settings::get('email_enabled')): ?>			<td class=center><b><?php echo __("E-mail") ?>:</b></td> <?php endif ?> \
				<?php if (Settings::get('sms_enabled')): ?>				<td class=center><b><?php echo __("SMS") ?>:</b></td> <?php endif ?> \
			</tr> \
			<tr> \
				<td><?php echo __("For all items") ?>:</td> \
				<?php if (Settings::get('redirection_enabled')): ?>		<td class='state_change_all' id='state_change_all_redirection_activate'><img src='<?php echo url::base() ?>media/images/icons/activate.png'> <?php echo __("Activate") ?></td><?php endif ?> \
				<?php if (Settings::get('email_enabled')): ?>			<td class='state_change_all' id='state_change_all_email_activate'><img src='<?php echo url::base() ?>media/images/icons/activate.png'> <?php echo __("Activate") ?></td><?php endif ?> \
				<?php if (Settings::get('sms_enabled')): ?>				<td class='state_change_all' id='state_change_all_sms_activate'><img src='<?php echo url::base() ?>media/images/icons/activate.png'> <?php echo __("Activate") ?></td><?php endif ?> \
			</tr> \
			<tr> \
				<td><?php echo __("For all items") ?>:</td> \
				<?php if (Settings::get('redirection_enabled')): ?>		<td class='state_change_all' id='state_change_all_redirection_keep'><img src='<?php echo url::base() ?>media/images/icons/keep.png'> <?php echo __("Without change") ?></td><?php endif ?> \
				<?php if (Settings::get('email_enabled')): ?>			<td class='state_change_all' id='state_change_all_email_keep'><img src='<?php echo url::base() ?>media/images/icons/keep.png'> <?php echo __("Without change") ?></td><?php endif ?> \
				<?php if (Settings::get('sms_enabled')): ?>				<td class='state_change_all' id='state_change_all_sms_keep'><img src='<?php echo url::base() ?>media/images/icons/keep.png'> <?php echo __("Without change") ?></td><?php endif ?> \
			</tr> \
			<tr> \
				<td><?php echo __("For all items") ?>:</td> \
				<?php if (Settings::get('redirection_enabled')): ?>		<td class='state_change_all' id='state_change_all_redirection_deactivate'><img src='<?php echo url::base() ?>media/images/icons/deactivate.png'> <?php echo __("Deactivate") ?></td><?php endif ?> \
			</tr> \
		</table> \
		</div>").insertBefore($(".grid_table", context));
	
	$(".state_change_all").click(function ()
	{
		var arr = explode("_", str_replace("state_change_all_", "", this.id));
		
		$("select[name^="+arr[0]+"]").each(function ()
		{
			switch (arr[1])
			{
				case "activate":
					$(this).val(<?php echo Notifications_Controller::ACTIVATE ?>);
					break;
					
				case "keep":
					$(this).val(<?php echo Notifications_Controller::KEEP ?>);
					break;
					
				case "deactivate":
					$(this).val(<?php echo Notifications_Controller::DEACTIVATE ?>);
					break;
			}
			notif_dd_change(false, $(this));
		});
	});
	
	$("select[name^=redirection],select[name^=email],select[name^=sms]").each(function (){
		$(this).attr("id", str_replace("]","", str_replace("[","_", $(this).attr("name"))));
		notif_dd_change(false, $(this));
	});
	
	function notif_dd_change(eventObj, elem)
	{
		var $this = (elem === undefined) ? $(this) : elem;
		
		var val = $this.val();
		var id = $this.attr('id');

		if (!$this.hasClass("dispNone"))
		{
			var title = notif_dd_img_title[val];
			
			if ($this.attr('title'))
			{
				title = $this.attr('title');
			}		
			
			$this.addClass("dispNone").parent()
					.attr("class", "state_change center")
					.attr("id", "state_change_" + id)
					.append("<img src='" + notif_dd_img_src[val] + "' title='" + title +
							"' class='state_change_image' id='state_change_image_" + id + "'>");
		}
		else
		{
			$("#state_change_image_" + id).removeClass("dispNone")
					.attr("src", notif_dd_img_src[val])
					.attr("title", notif_dd_img_title[val]);
		}
	}
	
	$("select[name^=redirection],select[name^=email],select[name^=sms]").change(notif_dd_change);
	
	$(".state_change").click(function ()
	{
		var id = str_replace("state_change_", "", this.id);
		var value = $("#"+id+" option:selected").attr("value");
		
		value = (value % $("#"+id+" option").length) + 1;
		
		$("#"+id+" option").removeAttr("selected");
		
		$("#"+id+" option[value='"+value+"']").attr("selected", "selected")
				.trigger("change");
	});
	
	$(".grid_form").submit(function ()
	{
		return window.confirm('<?php echo __('Do you really want to activate notifications') ?>?');
	});