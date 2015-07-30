<h2><?php echo __('Show work') ?></h2>
<?php if ($links): ?>
<?php echo $links ?>
<br />
<?php endif ?>
<br />
<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $work->id ?></td>
	</tr>
	<?php if ($work->previous_rejected_work_id): ?>
	<tr>
		<th><?php echo __('Previous rejected work') ?></th>
		<td><?php echo html::anchor('works/show/' . $work->previous_rejected_work_id, text::limit_chars($work->previous_rejected_work->description, 40)) ?></td>
	</tr>
	<?php endif; ?>
	<tr>
		<th><?php echo __('User') ?></th>
		<td><?php echo html::anchor('users/show/' . $work->user->id, $work->user->get_full_name()) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Description') ?></th>
		<td><?php echo nl2br($work->description) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date') ?></th>
		<td><?php echo $work->date ?></td>
	</tr>
	<tr>
		<th><?php echo __('Hours') ?></th>
		<td><?php echo $work->hours ?></td>
	</tr>
	<tr>
		<?php if ($work->km): ?>
			<th><?php echo __('Km') ?></th>
			<td><?php echo $work->km ?></td>
		</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Suggest amount') ?></th>
		<td><b><?php echo number_format($work->suggest_amount, 2, ',', ' ') . ' ' . __($this->settings->get('currency')) ?></b></td>
	</tr>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><b><?php echo $state_text ?></b></td>
	</tr>
	<?php if ($work->state == Vote_Model::STATE_APPROVED && isset($transfer) && $transfer->id): ?>
		<tr>
			<th><?php echo __('Confirmed time') ?></th>
			<td><?php echo $transfer->creation_datetime ?></td>
		</tr>
		<tr>
			<th><?php echo __('Rating') ?></th>
			<td><?php echo html::anchor('transfers/show/' . $transfer->id, number_format($transfer->amount, 2, ',', ' ') . ' ' . __($this->settings->get('currency'))) ?></td>
		</tr>
	<?php endif ?>
</table>

<br /><br />
<br /><br />

<?php foreach ($vote_grids as $i => $vote_grid): ?>
	<h3><?php echo __('Approval') ?> - <?php echo __('' . $vote_groups[$i]) ?></h3>
	<b><?php echo $percents[$i] ?>% (<?php echo $agrees[$i] ?>/<?php echo $total_votes[$i] ?>)</b><br /><br />
	<?php echo $vote_grid ?><br /><br />
<?php endforeach ?>

<?php if (isset($comments_grid)): ?>
	<h3><?php echo __('Comments') ?></h3>
	<?php echo $comments_grid ?>
<?php endif ?>