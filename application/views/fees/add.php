<?php if ($this->popup && isset($fee_model) && isset($fee_name)): ?>
<script type="text/javascript"><!--
	
	var fee_from_date = new Date(<?php echo $fee_model->from ?>);
	var fee_to_date = new Date(<?php echo $fee_model->to ?>);
	var fee = opener.document.getElementById('fee_id');
	var from = opener.document.getElementById('from');
	if (from.value == '')
		from.value = '<?php echo $fee_model->from ?>';
	else
	{
		var from_date = new Date(from.value);
		if (from_date.getTime() < fee_from_date.getTime() || from_date.getTime() > fee_to_date.getTime())
			from.value = '<?php echo $fee_model->from ?>';
	}
	var to = opener.document.getElementById('to');
	if (to.value == '')
		to.value = '<?php echo $fee_model->to ?>';
	else
	{
		var to_date = new Date(to.value);
		if (to_date.getTime() > fee_to_date.getTime() || to_date.getTime() < fee_from_date.getTime())
			to.value = '<?php echo $fee_model->to ?>';
	}
	fee.options[fee.options.length] = new Option('<?php echo $fee_name ?>', '<?php echo $fee_model->id ?>', true);
	fee.selectedIndex = fee.options.length-1;
	opener.fee_intervals[<?php echo $fee_model->id ?>] = new Array();
	opener.fee_intervals[<?php echo $fee_model->id ?>]['from'] = '<?php echo $fee_model->from ?>';
	opener.fee_intervals[<?php echo $fee_model->id ?>]['to'] = '<?php echo $fee_model->to ?>';
	self.close();

--></script>
<?php endif; ?>

<h2><?php echo __('Add new fee') ?></h2><br />

<?php echo form::open(url::base(TRUE) . url::current(TRUE), '', 'POST', array('id' => 'article_form')) ?>
<table cellspacing="3" class="form">
	<tr>
		<th><?php echo form::label('type_id', __('Type') . ':') ?></th>
		<td><?php echo form::dropdown('type_id', $types, $type_id, 'style="width:200px"'); ?>
			<p class="error"><?php echo $errors['type_id'] ?></p>
		</td>
	</tr>
	<tr>
		<th><?php echo form::label('name', __('Tariff name (optional)') . ':') ?></th>
		<td><?php echo form::input('name', $name); ?></td>
	</tr>
	<tr>
		<th><?php echo form::label('fee', __('Fee') . ':') ?></th>
		<td><?php echo form::input('fee', $fee); ?>
			<p class="error"><?php echo (empty($errors['fee'])) ? '' : $errors['fee'] ?></p>
		</td>
	</tr>
	<tr>
		<th><?php echo form::label('from', __('Date from') . ':') ?></th>
		<td><?php echo form::dropdown('from[day]', $days, $from['day']); ?><?php echo form::dropdown('from[month]', $months, $from['month']); ?><?php echo form::dropdown('from[year]', $years, $from['year']); ?>
			<p class="error"><?php echo (empty($errors['from'])) ? '' : $errors['from'] ?></p>
		</td>
	</tr>
	<tr>
		<th><?php echo form::label('to', __('Date to') . ':') ?></th>
		<td><?php echo form::dropdown('to[day]', $days, $to['day']); ?><?php echo form::dropdown('to[month]', $months, $to['month']); ?><?php echo form::dropdown('to[year]', $years, $to['year']); ?>
			<p class="error"><?php echo $errors['to'] ?></p>
		</td>
	</tr>
	<tr>
		<th></th>
		<td><?php echo form::submit('submit', __('Add'), ' class=submit') ?></td>
	</tr>
</table>
<?php echo form::close() ?>