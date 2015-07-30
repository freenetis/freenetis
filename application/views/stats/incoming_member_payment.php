<h2><?php echo __('Incoming member payment') ?></h2>
<?php echo $link_back ?><br /><br />
<?php echo form::open() ?>
<?php echo form::label('start_year', __('From')) . ': ' ?>
<?php echo form::dropdown('start_year', $years, $start_year) ?> 
<?php echo form::label('end_year', __('Until')) . ': ' ?>
<?php echo form::dropdown('end_year', $years, $end_year) ?> 
<?php echo form::submit('submit', __('Send')) ?>
<?php echo form::close() ?>
<br />
<a href="http://chart.apis.google.com/chart?chg=<?php echo $x_rate ?>,<?php echo $y_rate ?>,5,5&cht=lc&chd=t:<?php echo implode(',', $values) ?>&chs=750x400&chl=<?php echo implode('|', $labels) ?>&chxt=x,y&chxr=1,0,<?php echo $max ?>&chds=0,<?php echo $max ?>" target="_blank">
	<img src='http://chart.apis.google.com/chart?chg=<?php echo $x_rate ?>,<?php echo $y_rate ?>,5,5&cht=lc&chd=t:<?php echo implode(',', $values) ?>&chs=750x300&chl=<?php echo implode('|', $labels) ?>&chxt=x,y&chxr=1,0,<?php echo $max ?>&chds=0,<?php echo $max ?>'>
</a>
<br />
<br />
<h3><?php echo __('Table') ?></h3><br />
<table class="main tablesorter">
	<thead>
		<tr><th><?php echo __('Month') ?></th><th><?php echo __('Amount') ?></th></tr>
	</thead>
	<tbody>
		<?php foreach ($months as $i => $month): ?>
			<tr><td class="center"><?php echo $month ?></td><td class="center"><?php echo $values[$i] ?></td></tr>
		<?php endforeach ?>
	</tbody>
</table>