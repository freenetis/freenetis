<ul>
	
<li class="account"><h2><?php echo __('My profile')?></h2>
	<ul>
		<li>
			<?php if ($this->session->get('user_type') == User_Model::MAIN_USER): ?>
			    <?php echo html::anchor('members/show/'.$this->member_id, __('My profile')); ?>
			<?php else: ?>
			    <?php echo html::anchor('users/show/'.$this->user_id, __('My profile')); ?>
			<?php endif ?>
		</li>
		
		<?php if ($this->member_account_id): ?>
		<li><?php echo html::anchor('transfers/show_by_account/'.$this->member_account_id, __('My transfers')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Devices_Controller', 'devices',$this->member_id)): ?>
		<li><?php echo html::anchor('devices/show_by_user/'.$this->user_id, __('My devices')) ?></li>
		<?php endif ?>
		
		<?php if ($this->member_id != 1 && $this->acl_check_view('Users_Controller', 'work', $this->member_id)): ?>
		<li><?php echo html::anchor('works/show_by_user/'.$this->user_id, __('My works')) ?></li>
		<li><?php echo html::anchor('work_reports/show_by_user/'.$this->user_id, __('My work reports')) ?></li>
		<?php endif ?>
		
		<?php if ($this->user_has_phone_invoices): ?>
		<li>
			<?php echo html::anchor('phone_invoices/show_by_user/'.$this->user_id, __('My phone invoices')) ?>
			<?php echo html::menu_item_counter($this->count_unfilled_phone_invoices) ?>
		</li>
		<?php endif ?>
		
		<?php if ($this->user_has_voip): ?>
		<li><?php echo html::anchor('voip_calls/show_by_user/'.$this->user_id, __('My VoIP calls')) ?></li>
		<?php endif ?>
		
		<li>
			<?php echo html::anchor('mail/inbox', __('My mail')) ?>
			<?php echo html::menu_item_counter($this->unread_user_mails) ?>
		</li>
	</ul>
</li>

<?php if ($this->acl_check_view('Members_Controller', 'members')) { ?>
<li class="users"><h2><?php echo __('Users') ?></h2>
	<ul>
		<li><?php echo html::anchor('members/show_all', __('Members')) ?></li>
		
		<?php if (Settings::get('self_registration')): ?>
		<li>
			<?php echo html::anchor('members/applicants', __('Registered applicants')) ?>
			<?php echo html::menu_item_counter($this->count_of_registered_members) ?>
		</li>
		<?php endif ?>
		
		<li><?php echo html::anchor('membership_interrupts/show_all', __('Membership interrupts')) ?></li>
		<li><?php echo html::anchor('users/show_all', __('Users')) ?></li>
	</ul>
</li>
<?php } ?>

<?php if ($this->acl_check_view('Accounts_Controller', 'accounts') || $this->acl_check_view('Accounts_Controller', 'unidentified_transfers')): ?>
<li class="transfer"><h2><?php echo __('Finances') ?></h2>
	<ul>
		<?php if ($this->acl_check_view('Accounts_Controller', 'unidentified_transfers')): ?>
		<li><?php echo html::anchor('bank_transfers/unidentified_transfers/', __('Unidentified transfers')) ?></li>
		<?php endif ?>
	
		<?php if ($this->acl_check_view('Accounts_Controller', 'bank_accounts')): ?>
		<li><?php echo html::anchor('bank_accounts/show_all', __('Bank accounts')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Accounts_Controller', 'accounts')): ?>
		<li><?php echo html::anchor('accounts/show_all', __('Double-entry accounts')) ?></li>
		<li><?php echo html::anchor('transfers/show_all', __('Day book')) ?></li>
		<li><?php echo html::anchor('invoices/show_all', __('Invoices')) ?></li>
		<?php endif ?>
		
	</ul>
</li>
<?php endif ?>

<?php if ($this->acl_check_view('Users_Controller', 'work')): ?>
<li class="approval"><h2><?php echo __('Approval') ?></h2>
    <ul>
		<?php if ($this->acl_check_view('Users_Controller', 'work')): ?>
		<li>
			<?php echo html::anchor('works/pending', __('Works')) ?>
			<?php echo html::menu_item_counter($this->count_of_unvoted_works_of_voter) ?>
		</li>
		<li>
			<?php echo html::anchor('work_reports/pending', __('Work reports')) ?>
			<?php echo html::menu_item_counter($this->count_of_unvoted_works_reports_of_voter) ?>
		</li>
		<?php endif ?>
    </ul>
</li>
<?php endif ?>

<?php if ($this->acl_check_view('Devices_Controller', 'devices')): ?>
<li class="networks"><h2><?php echo __('Networks') ?></h2>
	<ul>
		<li><?php echo html::anchor('ip_addresses/show_all', __('IP addresses')) ?></li>
		<li><?php echo html::anchor('subnets/show_all', __('Subnets')) ?></li>
		<li><?php echo html::anchor('devices/show_all', __('Devices')) ?></li>
		<li><?php echo html::anchor('device_templates/show_all', __('Device templates')) ?></li>
		<li><?php echo html::anchor('ifaces/show_all', __('Interfaces')) ?></li>
		<li><?php echo html::anchor('links/show_all', __('Links')) ?></li>
		<li><?php echo html::anchor('vlans/show_all', __('Vlans')) ?></li>
		
		<?php if ($this->acl_check_view('VoIP_Controller', 'voip')): ?>
		<li><?php echo html::anchor('voip/show_all', __('VoIP')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Clouds_Controller', 'clouds')): ?>
		<li><?php echo html::anchor('clouds/show_all', __('Clouds')) ?></li>
		<?php endif ?>
		
		<li><?php echo html::anchor('tools/ssh', __('Tools')) ?></li>
		
		<?php if (Settings::get('ulogd_enabled') == 1): ?>
		<li><?php echo html::anchor('traffic/show_all', __('Traffic')) ?></li>
		<?php endif ?>
		
		<?php if (Settings::get('monitoring_enabled') == 1): ?>
		<li>
			<?php echo html::anchor('monitoring/show_all', __('Monitoring')) ?>
			<?php echo html::menu_item_counter($this->devices_down_count, 'red') ?>
		</li>
		<?php endif ?>
	</ul>
</li>
<?php endif ?>

<?php if ($this->acl_check_view('Messages_Controller', 'message')): ?>
<li class="redirection"><h2><?php echo __('Notifications') ?></h2>
	<ul>
		<li><?php echo html::anchor('redirect/show_all', __('Activated redirections')) ?></li>
		<li><?php echo html::anchor('notifications/show_whitelisted_members', __('Whitelist')) ?></li>
		<li><?php echo html::anchor('messages/show_all', __('Messages')) ?></li>
	</ul>
</li>
<?php endif ?>

<?php if ($this->acl_check_view('Settings_Controller', 'system') || $this->acl_check_view('Address_points_Controller', 'address_point')): ?>
<li class="administration"><h2><?php echo __('Administration') ?></h2>
	<ul>
		<?php if ($this->acl_check_view('Settings_Controller', 'system')): ?>
		<li><?php echo html::anchor('settings/', __('Settings')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Address_points_Controller', 'address_point')): ?>
		<li><?php echo html::anchor('address_points/show_all', __('Address points')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Settings_Controller', 'system')): ?>
		<li><?php echo html::anchor('sms/show_all', __('SMS messages')) ?></li>
		<li><?php echo html::anchor('email_queues/show_all_unsent', __('E-mails')) ?></li>
		<li><?php echo html::anchor('approval_templates/show_all', __('Approval')) ?></li>
		<li><?php echo html::anchor('acl/show_all', __('Access rights')) ?></li>
		<li><?php echo html::anchor('fees/show_all', __('Fees')) ?></li>
		<li><?php echo html::anchor('login_logs/show_all', __('Login logs')) ?></li>
		
			<?php if (Settings::get('action_logs_active') == 1): ?>
			<li><?php echo html::anchor('logs/show_all', __('Action logs')) ?></li>
			<?php endif ?>
		
		<li><?php echo html::anchor('stats/members_increase_decrease', __('Stats')) ?></li>
		<li><?php echo html::anchor('translations/show_all', __('Translations')) ?></li>
		<li><?php echo html::anchor('enum_types/show_all', __('Enumerations')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Phone_invoices_Controller', 'invoices')): ?>
		<li><?php echo html::anchor('phone_invoices/show_all', __('Phone invoices')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Settings_Controller', 'system')): ?>
		<li><?php echo html::anchor('phone_operators/show_all', __('Phone operators')) ?></li>
		<?php endif ?>
		
		<?php if ($this->acl_check_view('Settings_Controller', 'system')): ?>
		<li><?php echo html::anchor('filter_queries/show_all', __('Filter queries')) ?></li>
		<?php endif ?>
		
	</ul>
</li>
<?php endif ?>


</ul>
