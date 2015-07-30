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
		data.addRows([<?php echo $js_data_array_str ?>]);

		var chart = new google.visualization.AreaChart(document.getElementById("chart"));

		chart.draw(data, {
			width: 700,
			height: 640,
			title: "<?php echo __('Graph of transmitted data of member') ?>",
			hAxis: {title: "<?php echo htmlspecialchars($title) ?>"},
			vAxis: {title: "Data (<?php echo __('in') ?> <?php echo Traffic_Controller::$units[$current_unit_id] ?>)", format:"#,###"}
		});
	});

	<?php endif ?>
	
	$(document).ready(function()
	{
		$("#type").parent().parent().parent().parent().parent().children("button").hide();
		
		$("#type").change(function()
		{
			$("#type").parent().parent().parent().parent().parent().submit();
		});
	});
	
//--></script>

<h2><?php echo __('Traffic of member') ?> <?php echo $member->name ?></h2><br />

<div id="chart"></div>

<?php echo $form ?><br />

<?php echo $grid ?><br /><br />