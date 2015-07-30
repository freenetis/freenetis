<h2><?php echo __('Detail of access control rule') ?></h2>
<br />

<?php
$links = array();
$links[] = html::anchor('acl/edit/'.$acl->id, __('Edit'));
$links[] = html::anchor('acl/delete/'.$acl->id, __('Delete'));
		
echo implode(' | ',$links);
?><br /><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $acl->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Description') ?></th>
		<td><?php echo $acl->note ?></td>
	</tr>
</table>
<br /><br />

<div style="float:left; width: 330px;">
	<h3><?php echo __('ACO') ?> <?php echo help::hint('aco') ?></h3>
	<?php echo $aco_grid ?>
</div>

<div style="float:left;">
	<h3><?php echo __('ARO groups') ?> <?php echo help::hint('aro_groups') ?></h3>
	<?php echo $aro_groups_grid ?>
</div>

<div class="clear"></div>
<br /><br />

<h3><?php echo __('AXO') ?> <?php echo help::hint('axo') ?></h3>
<?php echo $axo_grid ?>
