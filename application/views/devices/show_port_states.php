<h2><?php echo $headline ?></h2><br />

<div id="switch" style="background-color: black; padding: 10px; width: <?php echo 30*$cols ?>px;">
<?php for ($i=1; $i <= $rows; $i++): ?>
	<?php for ($j=0; $j < $cols; $j++): ?>
		<?php echo html::anchor(
			'ifaces/show/'.$ports[$j*2+$i]['id'],
			html::image(array
			(
			    'src' => 'media/images/icons/ifaces/'.$ports[$j*2+$i]['type'].'-'.$ports[$j*2+$i]['state'].'.png',
			)),
			array
			(
			    'title' => $ports[$j*2+$i]['name'],
			    'class' => 'popup_link'
			)
		) ?>
	<?php endfor ?>
	<br />
<?php endfor ?>
</div>