<h2><?php echo __('Mail') ?></h2>

<ul class="tabs">
    <?php foreach (Mail_Controller::$sections as $section => $name): ?>
    <li<?php echo ((isset($current) && $current == $section) || (url_lang::current(2) == $section)) ? ' class="current"' : '' ?>><a href="<?php echo url_lang::base().$section ?>"><?php echo __(''.$name) ?></a></li>
    <?php endforeach; ?>
</ul>

<h3 class="clear"><?php echo $title ?></h3>
<?php echo $content ?>