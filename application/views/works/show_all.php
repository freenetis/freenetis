<h2><?php echo __('Works') ?></h2>
<br />
<?php echo html::anchor('works/pending', __('Pending works')) ?> |
<?php echo html::anchor('works/approved', __('Approved works')) ?> |
<?php echo html::anchor('works/rejected', __('Rejected works')) ?>
<br /><br />
<?php echo $grid ?>