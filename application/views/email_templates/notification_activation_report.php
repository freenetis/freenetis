<p><?php echo $header ?>:</p>

<ul>
	<?php foreach ($actions as $action): ?>
		<li><?php echo $action ?></li>
	<?php endforeach; ?>
</ul>

<?php
$whitelist_member_counter = 0;
?>

<p><?php echo __('Members') ?>:</p>

<table border="1" style="border-collapse: collapse;">
	<thead>
		<tr>
			<th><?php echo __('ID') ?></th>
			<th><?php echo __('Name') ?></th>
			<th><?php echo __('Balance') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($affected_members as $member): ?>
			<?php if (!$member->whitelisted): ?>
				<tr>
					<td><?php echo html::anchor('members/show/' . $member->id, $member->id) ?></td>
					<td><?php echo $member->name ?></td>
					<td><?php echo number_format((float) $member->balance, 2, ',', ' ') ?></td>
				</tr>
			<?php else: $whitelist_member_counter++; ?>
			<?php endif; ?>
		<?php endforeach; ?>
	</tbody>
</table>

<?php if ($whitelist_member_counter > 0): ?>
	<p><?php echo __('Members with active whitelist') ?>:</p>

	<table border="1" style="border-collapse: collapse;">
		<thead>
			<tr>
				<th><?php echo __('ID') ?></th>
				<th><?php echo __('Name') ?></th>
				<th><?php echo __('Balance') ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($affected_members as $member): ?>
				<?php if ($member->whitelisted): ?>
					<tr>
						<td><?php echo html::anchor('members/show/' . $member->id, $member->id) ?></td>
						<td><?php echo $member->name ?></td>
						<td><?php echo number_format((float) $member->balance, 2, ',', ' ') ?></td>
					</tr>
				<?php endif; ?>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
