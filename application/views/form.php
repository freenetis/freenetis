<h2><?php echo $headline ?></h2>

<?php
	if (isset($link_back) && $link_back != '' && !$this->popup)
	{
		echo $link_back . '<br />';
	}
?>

<br />

<?php echo $form ?>

<br />

<?php
	if (isset($aditional_info))
	{
		if (!is_array($aditional_info))
			$aditional_info = array($aditional_info);
			
		foreach ($aditional_info as $row)
		{
			echo $row . '<br />';
		}
	}
?>

