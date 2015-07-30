<h2><?php echo __('Administration of members registrations') ?></h2><br />

<?php echo form::open(url_lang::base() . 'members/registration') ?>
<input type="hidden" name="limit_results" value="<?php echo $limit_results ?>">
<input type="hidden" name="page" value="<?php echo $page ?>">
<table class="extended" cellspacing="0">
	<tr>
		<th>
			<?php echo 'ID' ?>
		</th>
		<th>
			<?php echo __('Registration') . '?' ?>
		</th>
		<th>
			<?php echo __('Members name') ?>
		</th>
		<th>
			<?php echo __('Street') ?>
		</th>
		<th>
			<?php echo __('Street number') ?>
		</th>
		<th>
			<?php echo __('Town') ?>
		</th>
	</tr>
	<?php foreach ($members as $member): ?>
	<tr>
		<?php echo "<input type=\"hidden\" name=\"ids[]\" value=\"" . $member->id . "\">" ?>
		<td><?php echo $member->id; ?></td>
		<td><input type="checkbox" name="registrations[<?php echo $member->id ?>]" value="yes" <?php if ($member->registration == 1)
		echo 'checked' ?>></td>
		<td><?php echo $member->name; ?></td>
		<td><?php echo $member->street; ?></td>
		<td><?php echo $member->street_number; ?></td>
		<td><?php echo $member->town; ?></td>
	</tr>
	<?php endforeach; ?>
	<tr>
		<td>
		</td>
		<td colspan="6">
			<input id="contactsubmit" type="submit" value="<?php echo __('Save changes') ?>" name="registrationsubmit" />
		</td>
	</tr>
</table>
<?php echo form::close() ?>

<?php
for ($i = 1; $i <= $max_page; $i++)
{
	echo '<a href="' . url_lang::base() . 'members/registration/' . $limit_results . '/' . $i . '">' . $i . '</a> ';
}
?>

