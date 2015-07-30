<!-- <?php echo count($results["errors"]) ?>:<?php echo $results["valids"]["methods"] ?>:<?php echo $results["valids"]["models"] ?> -->
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo $title ?></title>
	<style type="text/css">
	/* <![CDATA[ */
	* {padding:0;margin:0;border:0;}
	body {background:#eee;font-family:sans-serif;font-size:85%;}
	h1,h2,h3,h4 {margin-bottom:0.5em;padding:0.2em 0;border-bottom:solid 1px #ccc;color:#911;}
	h1 {font-size:2em;}
	h2 {font-size:1.5em;}
	h3 {font-size:1.2em;}
	h4 {font-size:0.8em;}
	p,pre {margin-bottom:0.5em;}
	strong {color:#700;}
	#wrap {width:90%;margin:2em auto;padding:0.5em 1em;background:#fff;border:solid 1px #ddd;border-bottom:solid 2px #aaa;}
	#stats {margin:0;padding-top: 0.5em;border-top:solid 1px #ccc;font-size:0.8em;text-align:center;color:#555;}
	.message {margin:1em;padding:0.5em;background:#dfdfdf;border:solid 1px #999;overflow: auto}
	.error {font-size: 14px; color: red}
	.green {font-size: 14px; color: green}
	.green_color {color: green}
	.red_color {color: red}
	table {border: 1px solid #ccc;}
	table th {background-color: #ccc; text-align: left;}
	/* ]]> */
	</style>
</head>
<body>
	<div id="wrap">
		<h1><?php echo $title ?></h1>
		
		<table>
			<tr>
				<th>Invalid tests</th>
				<td class="error"><?php echo count($results["errors"]) ?> invalid tests</td>
			</tr>
			<tr>
				<th>Valid tests</th>
				<td class="green"><?php echo $results["valids"]["methods"] ?> valid tests in <?php echo $results["valids"]["models"] ?> files</td>
			</tr>
		</table>
		
		<?php if (!empty($results["errors"])): ?>
		<h2><?php echo $results["title"] ?></h2>
			<?php
				$last_obj = '';
				foreach ($results["errors"] as $error):
			?>
				<?php if ($last_obj != $error["obj"]): ?>
				<h3><?php echo $error["obj"] ?></h3>
				<?php endif; ?>
				<h4><?php echo $error["type"] ?></h4>
				<pre class="message"><?php echo $error["error"] ?></pre>
			<?php 
					$last_obj = $error["obj"];
				endforeach;
			?>
		<?php endif; ?>
		
		<?php if ($stats && !empty($results["valids"])): ?>
			<br /><br />
			<table>
				<tr>
					<th colspan="2">Stats test</th>
				</tr>
			<?php			
				$last_obj = '';
				foreach ($results["valids"] as $index => $valid):
					if (!is_numeric($index)) continue;
			?>
				<?php if ($last_obj != $valid["obj"]): ?>
				<tr>
					<td colspan="2">
						<h3 class="green_color"><?php echo $valid["obj"] ?></h3>
					</td>
				</tr>
				<?php endif; ?>
				
				<tr>
					<td class="<?php echo ($valid["time"] > 0.15) ? 'red_color' : 'green_color' ?>">
						<?php echo ($valid["time"] > 0.3) ? '<b>' : '' ?>
						<?php echo $valid["time"] ?> s
						<?php echo ($valid["time"] > 0.3) ? '</b> O!' : '' ?>
					</td>
					<td>
						<?php echo $valid["type"] ?>
					</td>
				</tr>
			<?php 
					$last_obj = $valid["obj"];
				endforeach;
			?>
			</table>
		<?php endif; ?>
		
		<p id="stats"><?php echo Kohana::lang('core.stats_footer') ?></p>
	</div>
</body>
</html>
