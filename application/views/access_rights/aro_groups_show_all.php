<h2><?php echo $headline ?></h2>
<br />

<?php if (isset($this->sections)): ?>
<ul class="tabs">
    <?php foreach ($this->sections as $url => $name): ?>
    <li<?php echo ($current == $url) ? ' class="current"' : '' ?>><?php echo html::anchor($url, $name) ?></li>
    <?php endforeach; ?>
</ul>
<?php endif ?>

<?php echo __('Total items') ?>: <?php echo count($rows) ?>
<br /><br />

<?php
// check access
if ($this->acl_check_new('Aro_groups_Controller', 'aro_group'))
	echo html::anchor('aro_groups/add', __('Add new group')).'<br /><br />';
?>
<div>
	<table class="extended" style="float:left">
		<?php
		foreach ($rows as $row)
			echo $row;
		?>
	</table>
</div>