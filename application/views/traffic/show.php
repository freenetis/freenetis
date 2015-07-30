<h2><?php echo $headline ?><div style="float: right; font-weight: normal; font-size: 65%"><?php echo module::get_state('logging', TRUE) ?></div></h2>

<ul class="tabs">
    <?php foreach ($this->sections as $url => $name): ?>	
    <li<?php echo ($url == url_lang::base().url_lang::current(2)) ? ' class="current"' : '' ?>><a href="<?php echo $url ?>"><?php echo $name ?></a></li>
    <?php endforeach; ?>
</ul>
<div class="clear"></div>

<?php echo (isset($text)) ? "$text<br /><br />" : "" ?>

<?php echo $grid ?>