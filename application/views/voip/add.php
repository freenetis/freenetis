<h2><?php echo __('Add VoIP account') ?></h2>
<br />
<p class="red"><?php echo __('Warning') . ': ' . __('Activated number has not been changed.') . '<br />' . __('Please choose carefully your number.'); ?></p>
<br />
<?php echo form::open(url_lang::base() . '/voip/add/' . $user_id, array('method' => 'post')); ?>
<table>
	<tr>
		<td><?php echo __('Number interval') . ':'; ?></td>
		<td><?php echo $ranges; ?></td>
		<td><?php echo form::submit(array('submit' => 'activate', 'value' => __('Activate'), 'class' => 'submit')); ?></td>
	</tr>
</table>
<?php echo form::close(); ?>