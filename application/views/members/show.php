<h2><?php echo $title ?></h2>
<br />

<?php echo $member_links ?>

<br />
<br />
<table class="extended" style="float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Basic information') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Member ID')?></th>
		<td><?php echo $member->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Member name') ?></th>
		<td><?php echo $member->name ?></td>
	</tr>
	<?php if (!$is_association && isset($variable_symbols)) { ?>
	<tr>
		<th><?php echo __('Variable symbols').'&nbsp;'.help::hint('variable_symbol') ?></th>
		<td>
		    <?php foreach ($variable_symbols as $i => $variable_s):?>
			<?php echo  $variable_s->variable_symbol ?><br />
		    <?php endforeach; ?>
		</td>
	</tr>
	<?php if ($this->acl_check_view('Variable_Symbols_Controller', 'variable_symbols') || ($member->id == $this->session->get('user_id') && $this->acl_check_view('Variable_Symbols_Controller', 'variable_symbols', $member->id))) { ?>
	<tr>
		<td colspan="2"><?php echo  html::anchor(url_lang::base().'variable_symbols/show_by_account/'.$account->id,__('Administrate variable symbols')) ?></td>
	</tr>
	<?php if (Settings::get('self_registration') && $member->applicant_connected_from) { ?>
	<tr>
		<th><?php echo  __('Connected from') ?></th>
		<td><?php echo $member->applicant_connected_from ?></td>
	</tr>
	<?php } ?>
	<?php }} ?>
	<?php if ($this->acl_check_view('Members_Controller', 'entrance_date', $member->id) && $member->type != Member_Model::TYPE_APPLICANT) { ?>
	<tr>
		<th><?php echo  __('Entrance date').'&nbsp;'.help::hint('entrance_date', Settings::get('deduct_day')) ?></th>
		<td><?php echo  $member->entrance_date ?></td>
	</tr>
	<?php if ($this->acl_check_view('Membership_transfers_Controller', 'membership_transfer', $member->id) && $membership_transfer_to_member): ?>
	<tr>
		<th><?php echo  __('Membership transfer from').' '.help::hint('membership_transfer') ?></th>
		<td><?php echo html::anchor(url_lang::base().'members/show/'.$membership_transfer_to_member->member_id, $membership_transfer_to_member->member_name) ?></td>
	</tr>
	<?php endif ?>
	<?php } ?>
	<?php if ($this->acl_check_view('Members_Controller', 'leaving_date', $member->id)  &&  $member->leaving_date != '0000-00-00') { ?>
	<tr>
		<th><?php echo  __('Leaving date') ?></th>
		<td><?php echo  $member->leaving_date ?></td>
	</tr>
	<?php } ?>
	<?php if ($this->acl_check_view('Membership_transfers_Controller', 'membership_transfer', $member->id) && $membership_transfer_from_member): ?>
	<tr>
		<th><?php echo  __('Membership transfer to').' '.help::hint('membership_transfer') ?></th>
		<td><?php echo html::anchor(url_lang::base().'members/show/'.$membership_transfer_from_member->member_id, $membership_transfer_from_member->member_name) ?></td>
	</tr>
	<?php endif ?>
	<?php if ($member->organization_identifier && $this->acl_check_view('Members_Controller', 'organization_id')) { ?>
	<tr>
		<th><?php echo __('Organization identifier') ?></th>
		<td><?php echo trim($member->organization_identifier) ?></td>
	</tr>
	<?php } ?>
	<?php if ($member->vat_organization_identifier && $this->acl_check_view('Members_Controller', 'vat_organization_identifier')) { ?>
	<tr>
		<th><?php echo __('VAT organization identifier') ?></th>
		<td><?php echo trim($member->vat_organization_identifier) ?></td>
	</tr>
	<?php } ?>
	<tr>
		<th>
				<?php echo ($member->members_domicile->address_point->id) ? 
							__('Address of connecting place').'&nbsp;'.help::hint('address_point_member_connecting_place') :
							__('Address') ?>
		</th>
		<th>
			<table style="float: right">
				<tr>
					<td style="border: none; padding: 0;">
						<a href="http://mapy.cz?q=<?php echo urlencode($map_query) ?>&z=18" target="_blank" style="float: right; text-decoration: none;">
							<?php echo html::image(array
								(
									'width'		=> 16,
									'height'	=> 16,
									'alt'		=> 'Map ico',
									'src'		=> 'media/images/icons/map_icon.gif',
									'style'		=> 'margin-right: 5px; vertical-align:middle; border: none;'
								)) . __('Map') ?>
						</a>
					</td>
					<?php if ($this->acl_check_view('Address_points_Controller', 'address_point')): ?>
					<td style="border: none; padding: 0 0 0 10px;">
						<a href="<?php echo url_lang::base() ?>address_points/show/<?php echo $member->address_point->id ?>" style="float: right; text-decoration: none;" class="popup_link">
							<?php echo html::image(array
								(
									'width'		=> 16,
									'height'	=> 16,
									'alt'		=> 'Address point ico',
									'src'		=> 'media/images/icons/address_point.png',
									'style'		=> 'margin-right: 5px; vertical-align:middle; border: none;'
								)) . __('Address point') ?>
						</a>
					</td>
					<?php endif; ?>
				</tr>
			</table>
		</th>
	</tr>
	<tr>
		<th><?php echo __('Street') ?></th>
		<td><?php echo  $address ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Town') ?></th>
		<td><?php echo  $town ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Country') ?></th>
		<td><?php echo  $country ?></td>
	</tr>
	<?php if (!empty($gps)): ?>
	<tr>
		<th><?php echo  __('GPS') ?></th>
		<td><?php echo  $gps ?></td>
	</tr>
	<?php endif ?>
	<?php if (isset($domicile_address) && $domicile_address != ''): ?>
	<tr>
		<th><?php echo __('Address of domicile').'&nbsp;'.help::hint('address_point_member_domicile') ?></th>
		<th>
			<table style="float: right">
				<tr>
					<td style="border: none; padding: 0;">
						<a href="http://mapy.cz?q=<?php echo urlencode($map_domicile_query) ?>" target="_blank" style="float: right; text-decoration: none;">
							<?php echo html::image(array
								(
									'width'		=> 16,
									'height'	=> 16,
									'alt'		=> 'Map ico',
									'src'		=> 'media/images/icons/map_icon.gif',
									'style'		=> 'margin-right: 5px; vertical-align:middle; border: none;'
								)) . __('Map') ?>
						</a>
					</td>
					<?php if ($this->acl_check_view('Address_points_Controller', 'address_point')): ?>
					<td style="border: none; padding: 0 0 0 10px;">
						<a href="<?php echo url_lang::base() ?>address_points/show/<?php echo $member->members_domicile->address_point->id ?>" target="_blank" style="float: right; text-decoration: none;" class="popup_link">
							<?php echo html::image(array
								(
									'width'		=> 16,
									'height'	=> 16,
									'alt'		=> 'Address point ico',
									'src'		=> 'media/images/icons/address_point.png',
									'style'		=> 'margin-right: 5px; vertical-align:middle; border: none;'
								)) . __('Address point') ?>
						</a>
					</td>
					<?php endif; ?>
				</tr>
			</table>
		</th>
	</tr>
	<tr>
		<th><?php echo __('Street') ?></th>
		<td><?php echo  $domicile_address ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Town') ?></th>
		<td><?php echo  $domicile_town ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Country') ?></th>
		<td><?php echo  $domicile_country ?></td>
	</tr>
	<?php if (!empty($domicile_gps)): ?>
	<tr>
		<th><?php echo  __('GPS') ?></th>
		<td><?php echo  $domicile_gps ?></td>
	</tr>
	<?php endif ?>
	<?php endif ?>
	<tr>
		<th colspan="2"><?php echo  __('Other information') ?></th>
	</tr>
	<?php if (!$is_association) { ?>
	<tr>
		<th><?php echo  __('Registration') ?></th>
		<td><?php echo ($member->registration) ? __('Yes') : __('NO') ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Access to system').'&nbsp;'.help::hint('access_to_system') ?></th>
		<td><?php echo  $member->locked ? __('Locked'):__('Unlocked') ?></td>
	</tr>
	<?php } ?>
	<?php if ($member->user_id && $this->acl_check_view('Users_Controller', 'users', $member->user->member_id)) { ?>
	<tr>
		<th><?php echo  __('Added by') ?></th>
		<td><?php echo  html::anchor(url_lang::base().'users/show/'.$member->user_id, $member->user->name.' '.$member->user->surname) ?></td>
	</tr>
	<?php } ?>
	<?php if ($this->acl_check_view('Members_Controller', 'comment', $member->id)) { ?>
	<tr>
		<th><?php echo  __('Comment') ?></th>
		<td style="padding:0px;"><textarea rows="3" cols="100" readonly="readonly" style="border:0px;width:200px;"><?php echo  $member->comment ?></textarea></td>
	</tr>
	<?php } ?>
</table>
<table class="extended" cellspacing="0" style="float:left; margin-left:10px; width:360px;">
	<?php if (!$is_association && isset($account)) { ?>
	<tr>
		<th colspan="2"><?php echo  __('Account information') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Current credit').'&nbsp;'.help::hint('current_credit') ?></th>
		<td>
			<?php
			$class = ($comments != '') ? 'help more' : '';
			
			echo "<span class='".$class."' title='".$comments."'>".number_format((float) $account->balance, 2, ',', ' ').' '.$this->settings->get('currency')."</span> ";
					
			if ($this->acl_check_new('Accounts_Controller', 'transfers'))
					echo html::anchor(url_lang::base().'transfers/add_member_fee_payment_by_cash/'.$member->id, html::image(array('src' => url::base().'media/images/icons/purse.png', 'width' => 16, 'height' => 16)), array('title' => __('Add member fee payment by cash'), 'class' => 'action-icon popup_link'));
			if ($member->type != Member_Model::TYPE_APPLICANT)
			{
				echo html::anchor('transfers/payment_calculator/'.$account->id, html::image(array('src' => url::base().'media/images/icons/calculate.png')), array('title' => __('Calculate'), 'class' => 'action-icon popup_link'));
			}

			if ($this->acl_check_view ('Members_Controller','comment',$member->id))
					echo html::anchor(($account->comments_thread_id ? (url_lang::base().'comments/add/'.$account->comments_thread_id) : (url_lang::base().'comments/add_thread/account/'.$account->id)), html::image(array('src' => url::base().'media/images/icons/comment_add.png', 'width' => 16, 'height' => 16)), array('title' => __('Add comment to financial state of member'), 'class' => 'action-icon popup_link'));
			?>
		</td>
	</tr>
	<?php if ($member->type != Member_Model::TYPE_APPLICANT) { ?>
	<?php if (isset($expiration_date) && ($entrance_fee_paid == $member->entrance_fee)) { ?>
	<tr>
		<th><?php echo __('Payed to').'&nbsp;'.help::hint('payed_to') ?></th>
		<td><?php echo $expiration_date ?></td>
	</tr>
	<?php } ?>
	<?php if (isset($fee)) { ?>
	<tr>
		<th><?php echo __('Monthly member fee').'&nbsp;'.help::hint('monthly_member_fee') ?></th>
		<td><?php echo number_format((float)$fee, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php } ?>
	<tr>
		<th><?php echo __('Entrance fee').'&nbsp;'.help::hint('entrance_fee') ?></th>
		<td><?php echo number_format((float)$member->entrance_fee, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php if ($member->debt_payment_rate && $member->debt_payment_rate != $member->entrance_fee):  ?>
	<tr>
		<th><?php echo __('Monthly instalment of entrance').'&nbsp;'.help::hint('entrance_fee_instalment') ?></th>
		<td><?php echo number_format((float)$member->debt_payment_rate, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php endif ?>
	<?php if ($entrance_fee_paid != $member->entrance_fee): ?>
	<tr>
		<th><?php echo __('Paid amount from entrance').'&nbsp;'.help::hint('entrance_fee_paid') ?></th>
		<td><?php echo number_format((float)$entrance_fee_paid, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php endif ?>
	<?php if ($entrance_fee_left): ?>
	<tr>
		<th><?php echo __('Remaining amount from entrance').'&nbsp;'.help::hint('entrance_fee_left') ?></th>
		<td><?php echo number_format((float)$entrance_fee_left, 2, ',', ' ').' '.$this->settings->get('currency') ?></td>
	</tr>
	<?php endif ?>
	<?php } ?>
	<?php } ?>
	<?php if (Settings::get('voip_enabled') && $billing_has_driver && ($billing_account != null))
	{ ?>
	<tr>
		<th colspan="2"><?php echo  __('VoIP information') ?></th>
	</tr>
	<tr>
		<th><?php echo  __('State') ?></th>
		<td><?php echo ($billing_account->state == 'active') ? '<b style="color:green">'.__('Active').'</b>' : '<b style="color:red">'.__('Inactive').'</b>' ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Current credit') ?></th>
		<td><?php echo $billing_account->ballance.' '.$billing_account->currency.((!$is_association)? '&nbsp;&nbsp;-&nbsp;&nbsp;'.html::anchor(url_lang::base().'transfers/add_voip/'.$account->id, __('Recharge')):''); ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Limit') ?></th>
		<td><?php echo $billing_account->limit.' '.$billing_account->currency.'&nbsp;&nbsp;-&nbsp;&nbsp;'.html::anchor(url_lang::base().'voip/change_member_limit/'.$member->id, __('Change')); ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Type') ?></th>
		<td><?php echo ($billing_account->type == 'prepaid') ? __('Prepaid') : __('Postpaid') ?></td>
	</tr>
	<?php }
	elseif ($count_voip != 0)
	{ ?>
	<tr>
		<th colspan="2"><?php echo  __('VoIP information') ?></th>
	</tr>
	<tr>
		<th><?php echo  __('State') ?></th>
		<td><?php echo '<b style="color:darkorange" title="'.__('Registration will be activated after midnight.').'">'.__('Waiting for registration').'</b>' ?></td>
	</tr>
	<?php } ?>
	<?php if ($this->acl_check_view('Members_Controller', 'qos_ceil', $member->id) &&
			$this->acl_check_view('Members_Controller', 'qos_rate', $member->id) &&
			$member->speed_class_id
		)
	{ ?>
	<tr>
		<th colspan="2"><?php echo  __('QoS') ?></th>
	</tr>
	<tr>
		<th><?php echo  __('Speed class') ?></th>
		<td><?php echo $member->speed_class->name ?></td>
	</tr>
	<tr>
		<th><?php echo  __('QoS ceil') ?>&nbsp;<?php echo help::hint('qos_ceil') ?></th>
		<td><?php callback::speed_class_ceil_field($member->speed_class, 'ceil') ?></td>
	</tr>
	<tr>
		<th><?php echo  __('QoS rate') ?>&nbsp;<?php echo help::hint('qos_rate') ?></th>
		<td><?php callback::speed_class_rate_field($member->speed_class, 'rate') ?></td>
	</tr>
	<?php } ?>
	<?php if (Settings::get('ulogd_enabled') && $this->acl_check_view('Ulogd_Controller', 'member', $member->id)) { ?>
	<tr>
		<th colspan="2"><?php echo  __('Traffic') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Today traffic') ?></th>
		<td><?php echo network::size($today_traffic->upload) ?> / <?php echo network::size($today_traffic->download) ?></td>
	</tr>
	<?php } ?>
	<?php if (Settings::get('ulogd_enabled') && $this->acl_check_view('Ulogd_Controller', 'member', $member->id)) { ?>
	<tr>
		<th><?php echo __('This month traffic') ?></th>
		<td><?php echo network::size($month_traffic->upload) ?> / <?php echo network::size($month_traffic->download) ?></td>
	</tr>
	<?php } ?>
	<?php if (Settings::get('ulogd_enabled') && $this->acl_check_view('Ulogd_Controller', 'member', $member->id)) { ?>
	<tr>
		<th><?php echo __('Total traffic').'&nbsp'.help::hint('total_traffic') ?></th>
		<td><?php echo network::size($total_traffic->upload) ?> / <?php echo network::size($total_traffic->download) ?></td>
	</tr>
	<?php } ?>
	<?php if (Settings::get('ulogd_enabled') && $this->acl_check_view('Ulogd_Controller', 'member', $member->id)) { ?>
	<tr>
		<td colspan="2"><?php echo html::anchor(url_lang::base().'traffic/show_by_member/'.$member->id, __('Show more information about traffic of this member')) ?></td>
	</tr>
	<?php } ?>
</table>

<br class="clear" /><br />

<h3><?php echo __('Information about main user')?></h3>
<br />
<?php echo $user_links ?>
<br />
<br />
<table class="extended" style="float:left; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Basic information') ?></th>
	</tr>
	<tr>
		<th><?php echo  __('User ID') ?></th>
		<td><?php echo  $user->id ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Username') ?></th>
		<td><?php echo  $user->login ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Name') ?></th>
		<td><?php echo  html::anchor(url_lang::base().'users/show/'.$user->id, $user_name) ?></td>
	</tr>
	<tr>
		<th><?php echo  __('Birthday') ?></th>
		<td><?php echo  $user->birthday ?></td>
	</tr>
	<?php if ($this->acl_check_view('Users_Controller', 'application_password', $member->id)) { ?>
	<tr>
		<th><?php echo  __('Application password').'&nbsp;'.help::hint('application_password') ?></th>
		<td>
			<span id="application_password_span"><?php echo  $user->application_password ?></span>
			<span id="fake_application_password_span" class="dispNone">***********</span>
			<a id="show_application_password_link" class="dispNone" href="#"><?php echo __('Show') ?></a>
			<div class="clear"></div>
		</td>
	</tr>
	<?php } ?>
	<?php if ($this->acl_check_view('Users_Controller', 'comment', $member->id)) { ?>
	<tr>
		<th><?php echo  __('Comment') ?></th>
		<td style="padding:0px"><textarea rows="3" cols="100" readonly="readonly" style="border:0px; width:200px;"><?php echo  $user->comment ?></textarea></td>
	</tr>
	<?php } ?>
</table>
<table class="extended" cellspacing="0" style="float:left; margin-left:10px; width:360px;">
	<tr>
		<th colspan="2"><?php echo  __('Contact information') ?></th>
	</tr>
	<?php foreach ($contacts as $i => $contact):?>
	<tr>
		<?php if ($contact->type == Contact_Model::TYPE_PHONE): ?>
		<th><table class="picturebox"><tr>
		<td><?php echo  __('Phone') ?></td>
		<td>
	        <?php
			if (Settings::get('sms_enabled') && $this->acl_check_new('Sms_Controller', 'sms') && valid::phone($contact->value))
			{
				echo html::anchor('sms/send/'.$contact->value, html::image(array('src' => 'media/images/icons/send_sms.png', 'alt' => __('Send SMS'), 'title' => __('Send SMS'))), array('title' => __('Send SMS')));
			}
	        ?>
		</td></tr></table></th>
		<td><?php echo  $contact->value ?></td>
		<?php elseif ($contact->type == Contact_Model::TYPE_EMAIL): ?>
		<th>
    		<table class="picturebox">
    			<tr>
        			<td><?php echo  __('E-mail') ?></td>
        			<td><?php if (Settings::get('email_enabled')):
						echo  form::open(url_lang::base().'email') ;
						echo  form::hidden('email_member_id', $member->id );
						echo  form::hidden('address', $contact->value );
						echo  form::imagebutton('submit', url::base().'media/images/icons/write_email.png', array('title' => __('Send e-mail'), 'style' => 'width:16px; height:16px; border-width: 0px 0px 0px 0px; border-spacing: 0px;'));
						echo  form::close();
					endif ?></td>
				</tr>
			</table>
		</th>
		<td><?php echo $contact->value ?></td>
		<?php else: ?>
		<th><?php echo  $contact_types[$i] ?></th>
		<td><?php echo  $contact->value ?></td>
		<?php endif; ?>
	</tr>
	<?php endforeach; ?>
	<?php if ($this->acl_check_view('Users_Controller', 'additional_contacts') || ($member->id == $this->session->get('user_id') && $this->acl_check_view('Users_Controller', 'additional_contacts', $member->id))) { ?>
	<tr>
		<td colspan="2"><?php echo  html::anchor(url_lang::base().'contacts/show_by_user/'.$user->id,__('Administrate additional contacts')) ?></td>
	</tr>
	<?php }	?>
</table>
<br class = "clear" />
<br/>

<?php if (!empty($users_grid)): ?>
<h3><?php echo __('Users')?></h3>
<?php echo $users_grid ?>
<br />
<?php endif ?>

<?php if (!empty($redir_grid)): ?>
<h3><?php echo __('IP addresses')?></h3>
<?php echo $redir_grid ?>
<br />
<?php endif ?>

<?php if (Settings::get('voip_enabled') && !empty($voip_grid)): ?>
<h3><?php echo __('VoIP')?></h3>
<?php echo $voip_grid ?>
<br />
<?php endif ?>

<?php if ($member->type != Member_Model::TYPE_APPLICANT && !empty($membership_interrupts_grid)): ?>
<h3><?php echo __('Membership interrupts')?></h3>
<?php echo $membership_interrupts_grid ?>
<?php endif ?>
