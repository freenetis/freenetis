<?php if (!$this->dialog): ?> <h2><?php echo __('Change language') ?></h2><?php endif; ?>
<br />
<p style="margin-bottom: 10px;"><?php echo __('Please choose language'); ?>:</p>
<table class='languages'>
<tbody>
<?php foreach ($langs as $lang => $name): ?>
<tr>
	<td>
		<a href="<?php echo url::base().$index_page.$lang.'/'.$uri ?>" style="text-decoration: none">
			<img src="<?php echo url::base() ?>media/images/icons/flags/<?php echo $lang ?>.png" border="0" title="<?php echo $name ?>" alt="<?php echo $name ?>">
		</a>
	</td>
	<td>
		<a href="<?php echo url::base().$index_page.$lang.'/'.$uri ?>" title="<?php echo $name ?>"><?php echo $name ?></a>
	</td>
</p>
</tr>
<?php endforeach ?>
</tbody>
</table>