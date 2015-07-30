<h2><?php echo  __('User').' '.$user_data->get_full_name() ?></h2><br />

<?php 

$links = array();

if ($this->acl_check_edit('Users_Controller','users',$user_data->member_id))
	$links[] = html::anchor('users/edit/'.$user_data->id,__('Edit user'), array('class' => 'popup_link'));
if ($this->user_id == $user_data->id)
	$links[] = html::anchor('user_favourite_pages/show_all', __('Favourites'));
if (Settings::get('networks_enabled') && $this->acl_check_view('Devices_Controller', 'devices', $user_data->member_id))
	$links[] = html::anchor('devices/show_by_user/'.$user_data->id,__('Show devices'));
if (Settings::get('works_enabled') && $user_data->id <> Member_Model::ASSOCIATION &&  $this->acl_check_view('Works_Controller', 'work', $user_data->member_id))
{
	$links[] = html::anchor('works/show_by_user/'.$user_data->id,__('Show works'));
	$links[] = html::anchor('work_reports/show_by_user/'.$user_data->id,__('Show work reports'));
}
if ($user_data->id <> Member_Model::ASSOCIATION &&  $this->acl_check_view('Requests_Controller', 'request', $user_data->member_id))
{
	$links[] = html::anchor('requests/show_by_user/'.$user_data->id,__('Show requests'));
}
if ($this->acl_check_edit('Users_Controller','password',$user_data->member_id) &&	!($user_data->is_user_in_aro_group($user_data->id, Aro_group_Model::ADMINS) && $user_data->id != $this->user_id	))
	$links[] = html::anchor('users/change_password/'.$user_data->id,__('Change password'), array('class' => 'popup_link'));
if ($this->acl_check_edit('Users_Controller', 'application_password', $user_data->member_id))
	$links[] = html::anchor('users/change_application_password/'.$user_data->id, __('Change application password'), array('class' => 'popup_link'));

if ($this->acl_check_view('Login_logs_Controller', 'logs', $user_data->member_id))
	$links[] = html::anchor('users/show_login_logs/'.$user_data->id, __('Show login logs'));
	
echo implode (' | ', $links);

?>
<br /><br />

<table class="extended" style="float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Basic information') ?></th>
	</tr>
	<tr>
		<th>ID</th>
		<td><?php echo  $user_data->id ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Username') ?></th>
		<td><?php echo  $user_data->login ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Name') ?></th>
		<td><?php echo  $user_data->pre_title.' '.$user_data->name.' '.$user_data->middle_name.' '.$user_data->surname.' '.$user_data->post_title ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Member name') ?></th>
		<td><?php echo  html::anchor('members/show/'.$user_data->member_id,$user_data->member->name) ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Type') ?></th>
		<td><?php echo  ($user_data->type==1) ? __('Main') : __('Collateral') ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Birthday') ?></th>
		<td><?php echo  $user_data->birthday ?></td>
	</tr>
	<?php if ($this->acl_check_view('Users_Controller', 'application_password', $user_data->member->id)) { ?>
	<tr>
		<th><?php echo  __('Application password').'&nbsp;'.help::hint('application_password') ?></th>
		<td>
			<span id="application_password_span"><?php echo  $user_data->application_password ?></span>
			<span id="fake_application_password_span" class="dispNone">***********</span>
			<a id="show_application_password_link" class="dispNone" href="#"><?php echo __('Show') ?></a>
			<div class="clear"></div>
		</td>
	</tr>
	<?php } ?>
	<tr>
		<th><?php echo  __('Comment') ?></th>
		<td><?php echo  trim($user_data->comment)!='' ? $user_data->comment : '&nbsp;';  ?></td>
	</tr>
	<tr>
		<th colspan="2"><?php echo  __('Contact information') ?></th>
	</tr>
	<?php foreach ($contacts as $i => $contact):?>
	<tr>
		<th><?php echo  $contact_types[$i] ?></th>
		<td><?php echo  $contact->value ?></td>
	</tr>
	<?php endforeach; ?>
	<?php if ($this->acl_check_view('Users_Controller','additional_contacts',$user_data->member_id)) {	?>
	<tr>
		<td colspan="2"><?php echo  html::anchor('contacts/show_by_user/'.$user_data->id,__('Administrate additional contacts')) ?></td>
	</tr>
	<?php } ?>
</table>

<table class="extended" cellspacing="0" style="float:left; margin-left:10px; width:360px;">
<?php if (Settings::get('voip_enabled') && $user_data->id != 1) { ?>
	<tr>
		<th colspan="2"><?php echo  __('VoIP') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Phone number') ?></th>
		<td><?php echo $voip ?></td>
	</tr>
<?php } ?>
	<tr>
		<th colspan="2"><?php echo  __('Access rights') ?></th>
	</tr>
	<?php foreach ($aro_groups as $aro_group):?>
	<tr>
		<th><?php echo  __('Group') ?></th>
		<td><?php echo  __(''.$aro_group->name) ?></td>
	</tr>
	<?php endforeach; ?>
	<tr>
		<th colspan="2"><?php echo __('SSH keys').' '.help::hint('ssh') ?></th>
	</tr>
	<?php foreach ($user_data->users_keys as $key): ?>
	<tr>
		<td colspan="2">
			<div class="ssh-key">
				<?php echo $key->key ?><br />
				<?php echo html::anchor('users_keys/delete/'.$key->id, __('Delete'), array('class' => 'delete_link ssh-key-link')) ?>
				<?php echo html::anchor('users_keys/edit/'.$key->id, __('Edit'), array('class' => 'ssh-key-link')) ?>
				<div class="clear"></div>
			</div>
		</td>
	</tr>
	<?php endforeach ?>
	<tr>
		<td colspan="2"><?php echo html::anchor('users_keys/add/'.$user_data->id, __('Add new key')) ?></td>
	</tr>
</table>

<?php if ($admin_devices_grid || $engineer_devices_grid || $comments_grid): ?>

<div class="clear"></div>

<br /><br />

<?php echo __('Show').': ' ?>

<?php if ($admin_devices_grid): ?>
<a href="#admin-devices" id="admin-devices" class="switch-link"><?php echo __('Devices with user as admin') ?></a> | 
<?php endif ?>

<?php if ($engineer_devices_grid): ?>
<a href="#engineer-devices" id="engineer-devices" class="switch-link"><?php echo __('Devices with user as engineer') ?></a> | 
<?php endif ?>

<?php if ($comments_grid): ?>
<a href="#comments" id="comments" class="switch-link"><?php echo __('Comments of user') ?></a>
<?php endif ?>

<br /><br />

<?php if (!empty($admin_devices_grid)): ?>
<div id="admin-devices-box" class="switch-box">
<h3><?php echo __('Admin of devices') ?></h3>
<?php echo $admin_devices_grid ?>
</div>
<?php endif ?>

<?php if (!empty($engineer_devices_grid)): ?>
<div id="engineer-devices-box" class="switch-box">
<h3><?php echo __('Engineer of devices') ?></h3>
<br />
<?php echo $engineer_devices_grid ?>
</div>
<?php endif ?>

<?php if (!empty($comments_grid)): ?>
<div id="comments-box" class="switch-box">
<h3><?php echo __('Comments') ?></h3>
<br />
<?php echo $comments_grid ?>
</div>
<?php endif ?>

<?php endif ?>