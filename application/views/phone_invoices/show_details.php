<h2>
	<?php echo __('Details of') ?> <?php echo $phone_invoice_user->phone_number ?>
</h2><br />

<h3>
	<?php echo __('Since') ?> <?php echo $phone_invoice->billing_period_from ?>
	<?php echo __('until') ?> <?php echo $phone_invoice->billing_period_to ?>
</h3><br />

<table class="extended" cellspacing="0">
	<tr>
		<th></th>
		<th><?php echo __('Calls') ?></th>
		<th><?php echo __('Fixed calls') ?></th>
		<th><?php echo __('VPN calls') ?></th>
		<th><?php echo __('Connections') ?></th>
		<th><?php echo __('SMS messages') ?></th>
		<th><?php echo __('Roaming SMS messages') ?></th>
		<th><?php echo __('Pays') ?></th>
		<th><?php echo __('Price out of tax') ?></th>
	</tr>
	<tr>
		<th><?php echo __('Company') ?></th>
		<td><?php echo number_format($prices->phone_calls_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_fixed_calls_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_vpn_calls_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_connections_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_sms_messages_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_roaming_sms_messages_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_pays_company, 2, ',', ' ') ?></td>
		<td><?php echo number_format($price_company, 2, ',', ' ') ?></td>
	</tr>
	<tr>
		<th><?php echo __('Private') ?></th>
		<td><?php echo number_format($prices->phone_calls_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_fixed_calls_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_vpn_calls_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_connections_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_sms_messages_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_roaming_sms_messages_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($prices->phone_pays_private, 2, ',', ' ') ?></td>
		<td><?php echo number_format($price_private, 2, ',', ' ') ?></td>
	</tr>
</table><br />

<h3><?php echo __('Payment') ?>
	<?php if ($user)
		echo html::anchor('users/show/' . $user->id, $user->name . ' ' . $user->middle_name . ' ' . $user->surname); ?></h3><br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('Price out of tax') ?></th>
		<td><?php echo number_format($price_private, 2, ',', ' ') ?> <?php echo __(Settings::get('currency')) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Tax rate') ?></th>
		<td><?php echo $tax_rate ?>%</td>
	</tr>
	<?php if ($phone_invoice_user->transfer_id): ?>
	<tr>
		<th><?php echo __('Price vat') ?></th>
		<td><b><?php echo html::anchor('transfers/show/' . $phone_invoice_user->transfer_id, number_format($phone_invoice_user->transfer->amount, 2, ',', ' ')) ?></b> <?php echo __(Settings::get('currency')) ?></td>
	</tr>
	<?php else: ?>
	<tr>
		<th><?php echo __('Price vat') ?></th>
		<td><b><?php echo number_format($price, 2, ',', ' ') ?></b> <?php echo __(Settings::get('currency')) ?></td>
	</tr>
	<?php endif ?>
</table>

<br />

	<?php if ($phone_invoice_user_lock_enabled && $phone_invoice->locked == 0): ?>
	<form method="post">
			<?php if ($phone_invoice_user->locked == 1): ?>
			<button type="submit" name="phone_user_invoice_lock" value="unlock" class="button_big">
				<?php echo html::image(array('src' => 'media/images/states/locked.png')); ?>
			<?php echo __('Umark this invoice') ?>
			</button>
			<?php echo help::hint('phone_invoice_user_unlock') ?>
			<?php else: ?>
			<button type="submit" name="phone_user_invoice_lock" value="lock" class="button_big">
			<?php echo __('Mark this invoice as filled in') ?>
			</button>
			<?php echo help::hint('phone_invoice_user_lock') ?>
	<?php endif; ?>
	</form>
<?php endif; ?>

<br />

<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/calls', __('Calls')) ?> |
<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/fixed_calls', __('Fixed calls')) ?> |
<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/vpn_calls', __('VPN calls')) ?> |
<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/connections', __('Connections')) ?> |
<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/sms_messages', __('SMS messages')) ?> |
<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/roaming_sms_messages', __('Roaming SMS messages')) ?> |
<?php echo html::anchor('phone_invoices/show_details/' . $phone_invoice_user->id . '/pays', __('Pays')) ?>
<br /><br />
<h2><?php echo $heading; ?></h2><br />

<?php if ($edit_enabled): ?>

	<script type="text/javascript"><!--

	    $(document).ready(function () {
			$('#a_select_all').click(function () {
				select_all();
				return false;
			});

			$('#a_deselect_all').click(function () {
				deselect_all();
				return false;
			});

			$('#a_inteligent_select').click(function () {
				inteligent_select();
				return false;
			});
		
			$('input[name^="private"]').click(function () {
				if ($('#smart_checking').attr('checked'))
				{
					// tr > td > label > checkbox
					var number = $(this).parent().parent().parent().find('b').text();

					if (number)
					{
						if (this.checked)
							select(number);
						else
							deselect(number);
					}
				}
			});

			$('#smart_checking').change(function () {
				if ($(this).attr('checked'))
				{
					if (read_cookie('phone_invoice_smart_checking') == null)
						create_cookie('phone_invoice_smart_checking', '1', 24);
				}
				else
					erase_cookie('phone_invoice_smart_checking');
			});

			// automatic sumbit on click for private contacts
			$('.link_private_contact_add, \
		   .link_private_contact_edit, \
		   .link_private_contact_delete').click(function () {
		    
				if (!save_selection_ajax())
				{
					alert('<?php echo __('Cannot save form') ?>.\n<?php echo __('Please use button to save form') ?>.');
					return false;
				}
			});

			if (read_cookie('phone_invoice_smart_checking') == '1')
			{
				$('#smart_checking').attr('checked', 'checked');
			}
	    });

	    function select_all()
	    {
			$('input[name^="private"]').attr('checked', 'checked');
	    }

	    function deselect_all()
	    {
			$('input[name^="private"]').removeAttr('checked');
	    }

	    function select(number)
	    {
			$('b[title="' + number + '"]').each(function () {
				// tr > td > b
				$(this).parent().parent().find('input[name^="private"]').attr('checked', 'checked');
			});
	    }

	    function deselect(number)
	    {
			$('b[title="' + number + '"]').each(function () {
				// tr > td > b
				$(this).parent().parent().find('input[name^="private"]').removeAttr('checked');
			});
	    }

	    /**
	     * Inteligent select
	     * Create AJAX request to server and then set up all checkboxes 
	     */
	    function inteligent_select()
	    {
			var response = $.ajax({
				type: 'GET',
				async: false,
				dataType: 'text',
				url: '<?php echo url_lang::base() ?>phone_invoices/intelligent_select_ajax/<?php echo $phone_invoice_user->id ?>/<?php echo $detail_of ?>'
			}).responseText;

			if (response == '0')
			{
				alert('<?php echo __('Error - cannot load intelligent selection') ?>.');
			}
			else
			{
				deselect_all();

				var array = response.split(',');
				for (i = 0; i < array.length; i++)
				{
					if (parseInt(array[i], 10))
					{
						$('input[name="private[' + array[i] + ']"]').attr('checked', 'checked');
					}
				}
			}
	    }

	    function create_cookie(name, value, days)
	    {
	        if (days) {
				var date = new Date();
				date.setTime(date.getTime()+(days*24*60*60*1000));
				var expires = "; expires="+date.toGMTString();
	        }
	        else var expires = "";
	        document.cookie = name+"="+value+expires+"; path=/";
	    }

	    function read_cookie(name)
	    {
	        var nameEQ = name + "=";
	        var ca = document.cookie.split(';');
	        for(var i=0;i < ca.length;i++) {
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	        }
	        return null;
	    }

	    function erase_cookie(name)
	    {
			var d = new Date();
			document.cookie = name + ";expires=" + d.toGMTString() + "; path=/";
	    }

	    /**
	     * AJAX save of private services
	     * @return boolean  True on success
	     */
	    function save_selection_ajax()
	    {
			var priv_data = '';
			var ret;

			$('input:checked[name^="private"]').each(function () {
				priv_data = priv_data + $(this).attr('name') + '=1&';
			});

			ret = $.ajax({
				type: 'POST',
				async: false,
				dataType: 'text',
				url: '<?php echo url_lang::base() ?>phone_invoices/user_details_set_private_ajax/<?php echo $phone_invoice_user->id ?>/<?php echo $detail_of ?>/',
				data: priv_data
			}).responseText;

			return (ret == '1');
	    }

		//--></script>

	<?php if ($intelligent_select_on)
		echo html::anchor(url_lang::current(), __('Inteligent select'), array('id' => 'a_inteligent_select')) . ' | '; ?>
	<?php echo html::anchor(url_lang::current(), __('Select all'), array('id' => 'a_select_all')); ?> |
	<?php echo html::anchor(url_lang::current(), __('Deselect all'), array('id' => 'a_deselect_all')); ?>
	<br />
	<input type="checkbox" name="smart_checking" id="smart_checking" class="checkbox" />
	<label for="smart_checking"><?php echo __('Group selection by same phone number') ?></label>
	<br /><br />
<?php endif; ?>
<?php echo $grid ?>
