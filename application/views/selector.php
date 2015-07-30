<p class="pagination">
	<?php echo form::open($base_url, array('class' => 'selector', 'method' => 'get')) ?> 
	<?php echo form::dropdown('record_per_page', $sel_values_array, $current, 'onchange="this.form.submit()"'); ?>
	<?php echo form::close() ?>
</p>
