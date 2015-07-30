<?php echo __('Example') . ': example.com, 198.182.196.48, AS220' ?>
<br />
<br />

<?php echo form::open(url_lang::base() . 'tools/whois/', array('method' => 'post')); ?>
<table>
	<tr>
		<td><?php echo form::input('query', $hostname); ?></td>
		<td><?php echo form::submit(array('submit' => 'field_name', 'value' => __('Whois'), 'class' => 'submit')); ?></td>
	</tr>
</table>
<?php echo form::close(); ?>
<br />
<?php print $winfo; ?>