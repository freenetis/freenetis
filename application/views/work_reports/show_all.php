<h2><?php echo __('Work reports') ?></h2>
<br />
<?php echo html::anchor('work_reports/pending', __('Pending work reports')) ?> |
<?php echo html::anchor('work_reports/approved', __('Approved work reports')) ?> |
<?php echo html::anchor('work_reports/rejected', __('Rejected work reports')) ?>
<br /><br />
<?php echo $grid ?>