<h2><?php echo __('Monitoring') ?><div style="float: right; font-weight: normal; font-size: 65%"><?php echo module_state::get_state('monitoring', TRUE) ?></div></h2>
<br /><br />
<?php echo $form ?><br />
<?php echo $filter_form ?><br />
<?php if (count($labels)): ?>
<a name="top" id="groups-links-title"><?php echo __('Groups') ?>: <img src="<?php echo url::base() ?>media/images/icons/ico_minus.gif"></a><br />
<div id="groups-links">
	<?php foreach ($labels as $id => $label): ?>
	<a href="#<?php echo $id ?>"><?php echo $label ?></a><br />
	<?php endforeach ?>
</div>
<br /><br />
<?php endif ?>
<?php foreach ($grids as $i => $grid): ?>
<?php if (isset($labels[$i])): ?>
<h3>
	<a name="<?php echo $i ?>"><?php echo $labels[$i] ?></a>
	<a class="top-link" href="#top" title="<?php echo __('Top') ?>">
		<img src="<?php echo url::base() ?>media/images/icons/uparrow.png">
	</a>
</h3>
<?php endif ?>
<?php echo $grid ?>
<br /><br />
<?php endforeach ?>