<script type="text/javascript"><!--
	
	google.load("visualization", "1", {packages:["corechart"]});
	google.setOnLoadCallback(drawChart);

	function drawChart()
	{
		var data = new google.visualization.DataTable();
		data.addColumn("string", "Traffic");
		data.addColumn("number", "<?php echo __('Increase') ?>");
		data.addColumn("number", "<?php echo __('Decrease') ?>");
		data.addRows([<?php echo $js_data_array_str ?>]);

		var chart = new google.visualization.AreaChart(document.getElementById("chart"));

		chart.draw(data, {
			width:	750,
			height:	640,
			title:	"<?php echo __('Graph of increase and decrease of members') ?>",
			hAxis:	{title: "<?php echo __('Month') ?>"},
			vAxis:	{title: "<?php echo __('Count') ?>", format:"#,###"}
		});
	}
	
--></script>

<h2><?php echo __('Increase and decrease of members') ?></h2>
<?php echo $link_back ?><br /><br />

<?php echo $filter_form ?>

<div id="chart"></div>

<br />
<br />
<h3><?php echo __('Table') ?></h3><br />
<?php echo $grid ?>