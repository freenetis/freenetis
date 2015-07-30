<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo __('Address map') ?> | <?php echo $this->settings->get('title') ?></title>
		<?php echo html::link('media/images/favicon.ico', 'shorcut icon', 'image/x-icon', FALSE); ?>
		<?php echo html::stylesheet('media/css/address_map.css', 'screen') ?>
		<?php echo html::stylesheet('media/css/jquery.autocomplete.css') ?>
	</head>
	<body <?php echo isset($onload) ? 'onload="' . $onload . '"' : '' ?> >
		<h1><?php echo __('Address map') ?> <?php echo help::hint('address_map') ?></h1>
		<?php
		foreach ($subnets as $a => $arr_a)
		{
			foreach ($arr_a as $b => $arr_b)
			{
				?>
				<h2><?php echo isset($ranges[$a][$b]['address']) ? $ranges[$a][$b]['address'] : "$a.$b.0.0/16" ?></h2>

				<div id="d-box-all">
					<div class="d-box"></div>
					<?php for ($i = 0; $i < 256; $i = $i + 4): ?>
						<div class="d-box"><?php echo $i ?></div>
					<?php
					endfor;

					$i = isset($ranges[$a][$b]['start']) ? $ranges[$a][$b]['start'] : 0;
					$height = 1;
					
					$end = isset($ranges[$a][$b]['end']) ? $ranges[$a][$b]['end'] : 255;
					
					while ($i <= $end)
					{
						?>
					</div>

					<div id="c-box-all">
						<div class="c-box"><?php echo $i ?></div>
						<?php
						$j = 0;
						while ($j < 256)
						{
							if (isset($arr_b[$i][$j]))
							{
								if (($length = $lengths[$a][$b][$i][$j] % 256) == 0)
									$length = 256;
								
								$height = ceil($lengths[$a][$b][$i][$j]/$length);
								?>
								<a href="<?php echo url_lang::base() ?>subnets/show/<?php echo $arr_b[$i][$j]->subnet_id ?>" title="<?php echo $arr_b[$i][$j]->subnet_name ?> (<?php echo $arr_b[$i][$j]->cidr_address ?>)">
									<div class="used-box" style="border-color: #color; background-color: #<?php echo $background_colors[$a][$b][$i][$j] ?>; width: <?php echo $length * 7.5 ?>px; height: <?php echo $height * 30 ?>px">
										<div class="subnet-name" style="margin-top: <?php echo $height*10 ?>px"><?php echo $arr_b[$i][$j]->subnet_name ?></div>
									</div>
								</a>
								<?php
								$j += $lengths[$a][$b][$i][$j];
							}
							else
							{
								?>
								<div class="unused-box"></div>
								<?php
								$j++;
							}
						}
						?>


					</div>
					<?php
					$i += $height;
					
					$height = 1;
				}
				?>
				<br clear="all">
					<?php
				}
			}
			?>

			<div id="footer"></div>
	</body>
</html>