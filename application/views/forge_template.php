<script type="text/javascript"> 
	
    $(document).ready(function(){
<?php foreach ($inputs as $input): ?>
	<?php if ($input->autocomplete != ''): ?>
		$("#<?php echo $input->name ?>").autocomplete({source: "<?php echo url_lang::base().$input->autocomplete ?>"});
	<?php endif ?>
<?php endforeach ?>
	});
</script>

<?php echo $open; ?>
<table cellspacing="0" class="form" id="form_table">
<?php if ($title != ''): ?>
<caption><?php echo $title ?></caption>
<?php endif ?>
<?php

$submit = null;

$group_count = 0;
$in_group = FALSE;
$visible = TRUE;

foreach($inputs as $input):

$sub_inputs = array();

if ($input->type == 'submit')
{
        $submit = $input;
        continue;
}

if ($input->type == 'group'):
	$group_count++;
	$in_group = TRUE;
	$sub_inputs = $input->inputs;
	$visible = TRUE;

?>
<tr id="group-<?php echo $group_count ?>" class="group_title">
	<?php if($input->visible !== NULL): 
	$visible = $input->visible;
	?>
	<th colspan="2" class="group<?php echo (!$visible) ? ' disable' : '' ?>"><?php echo $input->label() ?>
	&nbsp;&nbsp;&nbsp;<img src="<?php echo url::base() ?>media/images/icons/ico_<?php echo ($visible) ? 'minus' : 'add' ?>.gif" class="group-button">
	<?php else: ?>
	<th colspan="2" class="group"><?php echo $input->label() ?>
	<?php endif ?>
</th>
</tr>
<?php if ($message = $input->message()): ?>
<tr><td colspan="2"><p class="group_message"><?php echo $message ?></p></td></tr>
<?php endif;
else:
	$in_group = FALSE;
	$sub_inputs = array($input);
endif;

foreach($sub_inputs as $input):
	if (!strstr($input->class, 'join2')):
		$tr_class = '';
		if ($in_group)
		{
			$tr_class = "group-$group_count-items";
			if (!$visible)
				$tr_class .= " dispNone";
		}
?>
<tr<?php echo ($tr_class != '') ? " class='$tr_class'" : '' ?>>
<th class="<?php echo $input->name(); echo (in_array('required', $input->rules())) ? ' label_required' : '' ?>"><?php if ($input->type != 'checkbox') echo $input->label().'&nbsp;'.$input->help() ?><?php echo (in_array('required', $input->rules())) ? ' *' : '' ?></th>
<td class="<?php echo $input->name() ?>"<?php if ($input->name() == 'password'): ?> style="widht: 100%;"<?php endif ?>>
<?php
	endif;

if ($input->name() == 'password'): ?>
	<div class="password-meter" style="float:right">
		<div class="password-meter-message">&nbsp;</div>
		<div class="password-meter-bg">
			<div class="password-meter-bar"></div>
		</div>
	</div>
<?php endif;
	
echo $input->html();

echo $input->additional_info;

if (strstr($input->class, 'ajax'))
	echo html::image(array('src'=>'media/images/icons/animations/ajax-loader.gif', 'id'=>'ajax_'.$input->name, 'class'=>'ajax-loader', 'style'=>'display:none;'));

if ($message = $input->message()):

?>
<p class="message"><?php echo $message ?></p>
<?php

endif;

foreach ($input->error_messages() as $error):

?>
<p class="error"><?php echo str_replace('*','',str_replace(':','',$error)) ?></p>
<?php

endforeach;

if (!strstr($input->class, 'join1')):
?>
</td>
</tr>
<?php

endif;

endforeach;

endforeach;
?>
</table>
<?php if ($submit): ?>
<?php echo $input->html() ?>
<?php endif ?>
<?php echo $close ?>