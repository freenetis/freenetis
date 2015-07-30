<h2><?php echo __('Error - VoIP not enabled') ?></h2>

<br />

<p class="red" style="font-weight: bold"><?php echo __('VoIP not enabled at this moment') ?>.</p>

<br />

<?php if ($this->acl_check_edit('Settings_Controller', 'voip_settings')): ?>
<p><?php echo html::anchor(url_lang::base() . 'settings/voip', __('Enable VoIP')) ?></p>
<?php endif ?>