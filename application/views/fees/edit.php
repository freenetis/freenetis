<h2><?php echo  __('Edit fee') ?></h2><br />

<?php echo form::open(url_lang::base().'fees/edit/'.$fee_id, '', 'POST', array('id' => 'article_form')) ?>
<table cellspacing="3" class="form">
<tr>
<th><?php echo form::label('type_id',__('Type').':') ?></th>
<td><?php echo form::dropdown('type_id', $types, $type_id, 'style="width:200px"'); ?>
<p class="error"><?php echo $errors['type_id'] ?></p></td>
</tr>
<tr>
<th><?php echo form::label('name',__('Tariff name (optional)').':') ?></th>
<td><?php echo form::input('name', $name); ?></td>
</tr>
<tr>
<th><?php echo form::label('fee',__('Fee').':') ?></th>
<td><?php echo form::input('fee', $fee); ?>
<p class="error"><?php echo (empty ($errors['fee'])) ? '' : $errors['fee'] ?></p></td>
</tr>
<tr>
<th><?php echo form::label('from',__('Date from').':') ?></th>
<td><?php echo form::dropdown('from[day]', $days, $from['day']); ?><?php echo form::dropdown('from[month]', $months, $from['month']); ?><?php echo form::dropdown('from[year]', $years, $from['year']); ?>
<p class="error"><?php echo (empty ($errors['from'])) ? '' : $errors['from'] ?></p></td>
</tr>
<tr>
<th><?php echo form::label('to',__('Date to').':') ?></th>
<td><?php echo form::dropdown('to[day]', $days, $to['day']); ?><?php echo form::dropdown('to[month]', $months, $to['month']); ?><?php echo form::dropdown('to[year]', $years, $to['year']); ?>
<p class="error"><?php echo $errors['to'] ?></p></td>
</tr>
<tr>
<th></th>
<td><?php echo form::submit('submit', __('Edit'), ' class=submit') ?></td>
</tr>
</table>
<?php echo form::close() ?>
