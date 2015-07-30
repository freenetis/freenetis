<h2><?php echo __('Variable symbols'); ?></h2><br />
<?php if ($can_add)
	echo html::anchor('variable_symbols/add/' . $account_id, __('Add variable symbol'), array('title' => __('Add variable symbol'), 'class' => 'popup_link')) . "<br /><br />" ?>
<?php echo $grid_variable_symbols; ?>

<br /><br />