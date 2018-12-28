<p><?php echo $header ?>:</p>

<ul>
	<?php foreach ($actions as $action): ?>
		<li><?php echo $action ?></li>
	<?php endforeach; ?>
</ul>

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
			<tr>
				<td><?php echo html::anchor('members/show/' . $member->id, $member->id) ?></td>
				<td><?php echo $member->name ?></td>
				<td><?php echo number_format((float) $member->balance, 2, ',', ' ') ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
