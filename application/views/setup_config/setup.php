<?php if (isset($error) && $error): ?>
<h2><?php echo __('Cannot connect to database') ?></h2>
<?php if (isset($error_cause) && $error_cause): ?>
<p class="error"><?php echo __('Error') . ': ' . $error_cause ?></p>
<?php endif ?>
<p><?php echo __('It can means that username/password/host are bad or host is unavailable.') ?></p>
<ul>
    <li><?php echo __('Are you really sure that you use correct username and password?') ?></li>
    <li><?php echo __('Are you really sure that you entered correct address database server?') ?></li>
    <li><?php echo __('Are you really sure that this database server is working fine?') ?></li>
</ul>
<p><a href="<?php echo url_lang::base() ?>setup_config/setup">&lt;&lt;&lt; <?php echo __('Back to the form') ?></a></p>
<?php else: ?>
<h2><?php echo __('Setup config done') ?></h2>
<p><?php echo __('Setup config done') .'. '. __('If your database is not prepared, on next page you will be asked to set some information about main user and organization'); ?>
</p>
<?php echo form::open(url_lang::base().'login') ?>
<?php echo form::submit('submit', __('Next step').' >>>', ' class="submit"') ?>
<?php echo form::close() ?>
<?php endif ?>