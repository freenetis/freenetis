<h2><?php echo __('Show request') ?></h2>
<?php if ($links): ?>
<?php echo $links ?>
<br />
<?php endif ?>
<br />
<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $request->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('User') ?></th>
		<td><?php echo html::anchor('users/show/' . $request->user->id, $request->user->get_full_name()) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td><?php echo Request_Model::get_type_name($request->type) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Description') ?></th>
		<td><?php echo nl2br($request->description) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date') ?></th>
		<td><?php echo $request->date ?></td>
	</tr>
	<?php if ($request->type != Request_Model::TYPE_SUPPORT): ?>
	<tr>
		<th><?php echo __('Suggest amount') ?></th>
		<td><b><?php echo number_format($request->suggest_amount, 2, ',', ' ') . ' ' . __($this->settings->get('currency')) ?></b></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><b><?php echo $state_text ?></b></td>
	</tr>
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