<h2><?php echo __('Growth of members') ?></h2>
<?php echo $link_back ?><br /><br />
<a href="http://chart.apis.google.com/chart?chg=10,10,5,5&cht=lc&chd=t:<?php echo implode(',', $values) ?>&chs=1000x300&chl=<?php echo implode('|', $labels) ?>&chxt=x,y&chxr=1,0,<?php echo $max ?>&chds=0,<?php echo $max ?>" target="_blank">
	<img src='http://chart.apis.google.com/chart?chg=10,10,5,5&cht=lc&chd=t:<?php echo implode(',', $values) ?>&chs=700x300&chl=<?php echo implode('|', $labels) ?>&chxt=x,y&chxr=1,0,<?php echo $max ?>&chds=0,<?php echo $max ?>'>
</a>
<br />
<br />
<h3><?php echo __('Table') ?></h3><br />
<table class="main">
	<tr><th><?php echo __('Month') ?></th><th><?php echo __('Count') ?></th></tr>
	<?php foreach ($months as $i => $month): ?>
		<tr><td class="center"><?php echo $month ?></td><td class="center"><?php echo $values[$i] ?></td></tr>
	<?php endforeach ?>
</table>