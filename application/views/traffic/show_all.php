<script type="text/javascript"><!--
	
	<?php if ($total_traffics > 1): ?>

	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(function ()
	{
		var data = new google.visualization.DataTable();
		data.addColumn("string", "Traffic");
		data.addColumn("number", "<?php echo __('Total upload') ?> (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)");
		data.addColumn("number", "<?php echo __('Total download') ?> (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)");
		data.addColumn("number", "<?php echo __('Local upload') ?> (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)");
		data.addColumn("number", "<?php echo __('Local download') ?> (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)");
		data.addColumn("number", "<?php echo __('Foreign upload') ?> (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)");
		data.addColumn("number", "<?php echo __('Foreign download') ?> (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)");
		data.addRows([<?php echo $total_js_data_array_str ?>]);

		var total_chart = new google.visualization.AreaChart(document.getElementById("total_chart"));

		total_chart.draw(data, {
			width: 700,
			height: 640,
			title: "<?php echo __('Graph of transmitted data of all members') ?>",
			hAxis: {title: "<?php echo htmlspecialchars($title) ?>"},
			vAxis: {title: "Data (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)", format:"#,###"}
		});
	});

	<?php endif ?>
	
//--></script>

<h2><?php echo $title ?><div style="float: right; font-weight: normal; font-size: 65%"><?php echo module::get_state('logging', TRUE) ?></div></h2><br />

<ul class="tabs">
    <?php foreach ($this->sections as $url => $name): ?>	
    <li<?php echo ($url == url_lang::base().url_lang::current(2)) ? ' class="current"' : '' ?>><a href="<?php echo $url ?>"><?php echo $name ?></a></li>
    <?php endforeach; ?>
</ul>
<div class="clear"></div>

<div id="total_chart"></div>

<?php echo $form ?><br />

<?php echo $grid ?><br /><br />