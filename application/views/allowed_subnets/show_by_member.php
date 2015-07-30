<h2><?php echo $headline ?></h2>
<br />
<?php echo __('Maximum count of allowed subnets') ?>: <b><?php echo ($count) ? $count : __('unlimited') ?></b>&nbsp;&nbsp;
<?php echo html::anchor('allowed_subnets_counts/edit/' . $member_id, __('Edit'), array('title' => __('Edit'), 'class' => 'popup_link')) ?>
<br />
<br/>
<?php echo $table ?>