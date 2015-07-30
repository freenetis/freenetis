<p class="pagination">
	<?php echo form::open($base_url.server::query_string(), array('class' => 'selector', 'method' => 'post')) ?> 
	<?php echo form::dropdown('record_per_page', $sel_values_array, $current, 'onchange="this.form.submit()"'); ?>
	<?php echo form::close() ?>
</p>
