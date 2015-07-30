<?php if (isset($submenu)): ?>
	<?php echo $submenu; ?><br /><br />
<?php endif ?>

<?php
echo "<h2>$headline</h2>";

if (isset($status_message_info))
	echo '<br/><div class="status_message_info">'.$status_message_info.'</div>';

if (isset($link_back))
	echo "<br/>$link_back<br/>";

if (isset($description))
	echo "<p>$description</p>";

if (isset($create))
	echo $create;

if (isset($form))
	echo "<br />$form<br />";

?>
<br/>

<?php if (isset($this->sections)): ?>

<ul class="tabs">
    <?php foreach ($this->sections as $url => $name): ?>
    <li<?php echo ($current == $url) ? ' class="current"' : '' ?>><a href="<?php echo $url ?>"><?php echo $name ?></a></li>
    <?php endforeach; ?>
</ul>

<?php endif ?>

<?php echo $table ?>
