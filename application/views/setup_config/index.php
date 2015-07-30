<h2><?php echo __('Welcome to FreenetIS installation') ?></h2>
<?php if (!$writeable): ?>
<p><?php echo __('For successful installation, you have to change some file/directory permissions.')?></p>
<?php endif; ?>
<?php echo $file_statuses ?>
<br>
<?php if (!$config_exist): ?>
<p><?php echo __('Before getting started, we need some information on the database.')?> <?php echo __('You will need to know the following items before proceeding.') ?></p>

    <ol>
	<li><?php echo __('Database name') ?></li>
        <li><?php echo __('Database username') ?></li>
        <li><?php echo __('Database password') ?></li>
        <li><?php echo __('Database host') ?></li>
    </ol>
<?php endif; ?>
<?php echo $form ?>