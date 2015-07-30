<?php if (!$this->dialog): ?> <h2><?php echo __('Change language') ?></h2><?php endif; ?>
<br />
<p style="margin-bottom: 15px;"><?php echo __('Please choose language'); ?>:</p>
<?php foreach ($langs as $lang => $name): ?>
<p style="margin-bottom: 15px;">
    <a href="<?php echo url::base().$index_page.$lang.'/'.$uri ?>" style="text-decoration: none">
		<img src="<?php echo url::base() ?>media/images/icons/flags/<?php echo $lang ?>.jpg" border="0" title="<?php echo $name ?>" alt="<?php echo $name ?>">
	</a>
	<a href="<?php echo url::base().$index_page.$lang.'/'.$uri ?>" title="<?php echo $name ?>"><?php echo $name ?></a>
</p>
<?php endforeach ?>