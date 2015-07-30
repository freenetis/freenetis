<h2><?php
    echo __('History of phone services between') ?> <?php
    echo $user->name; ?> <?php
    echo $user->surname; ?> <?php
    echo __('and') ?> <?php
    echo $number; ?></h2>

<br /><h3><?php echo __('Calls') ?></h3><br />
<?php echo $grid_calls ?>

<br /><h3><?php echo __('Fixed calls') ?></h3><br />
<?php echo $grid_fixed_calls ?>

<br /><h3><?php echo __('SMS messages') ?></h3><br />
<?php echo $grid_sms_messages ?>
