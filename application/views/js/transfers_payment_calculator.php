<?php
/**
 * Payment calcultor javascript view.
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

?>
	$('#calculate').parent().parent().hide();
	$('.form button[type=submit]').hide();
	
	$('.form').submit(function ()
	{
		return false;
	});
	
	var transfer_payment_calculator_cache = {expiration_date:'', amount:''};

	$('#amount, #expiration_date').live('keyup', function ()
	{
		var calculate, amount, expiration_date;
		
		if (this.id === 'amount')
		{
			calculate = 'expiration_date';
			amount = parseFloat($('#amount').val());
			expiration_date = '';
		}
		else
		{
			calculate = 'amount';
			expiration_date = $('#expiration_date').val();
			amount = '';
		}
		
		if ((calculate === 'expiration_date' && amount !== '' && !isNaN(amount)) ||
			(calculate === 'amount' && /^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}$/.test(expiration_date)))
		{
			if (calculate === 'amount')
			{
				if (transfer_payment_calculator_cache[calculate] === expiration_date) return;
				transfer_payment_calculator_cache[calculate] = expiration_date;
			}
			else
			{
				if (transfer_payment_calculator_cache[calculate] === amount) return;
				transfer_payment_calculator_cache[calculate] = amount;	
			}
				
			$.ajax({
				type:		'POST',
				url:		'<?php echo url_lang::base() ?>transfers/payment_calculator/<?php echo $account_id ?>/1',
				data:		{calculate: calculate, amount: amount, expiration_date: expiration_date},
				success:	function (data) {
					$('#'+calculate).val(data);
					<?php if($show_qr): ?>
						var accountNumber = "<?php echo $account_nr ?>";
						var bankCode = "<?php echo $bank_nr ?>";
						var	currency = "<?php echo Settings::get('currency') ?>";
						var vs = "<?php echo $variable_symbol ?>";
						<?php if ($bank_nr != ''): ?>
						$('#qrpay').attr('src', 'http://api.paylibo.com/paylibo/generator/czech/image?accountNumber='+accountNumber+'&bankCode='+bankCode+'&amount='+$('#amount').val()+'&currency='+currency+'&vs='+vs+'&size=100');
						<?php endif; ?>
					<?php endif; ?>
				},
				error:		function () {
					$('#'+calculate).val('');
					$('#qrpay').attr('src', '');
					alert('<?php echo __('Error during obtaining data from server') ?>');
				},
				dataType:	'html',
				async:		false
			});
		}
		else
		{
			$('#'+calculate).val('');
			$('#qrpay').attr('src', '');
		}
		
		$('.add_link').attr('href', '<?php echo url_lang::base() ?>transfers/add_member_fee_payment_by_cash/<?php echo $member_id ?>/'+parseFloat($('#amount').val()))
	});
	
	<?php if ($can_add): ?>
	$('#amount', context).css('width', parseInt($('#amount', context).css('width'))-23);
	$('<a href="<?php echo url_lang::base() ?>transfers/add_member_fee_payment_by_cash/<?php echo $member_id ?>" class="add_link popup_link"><img class="purse" src="<?php echo url::base() ?>media/images/icons/purse.png" width="16" height="16"></a>').insertAfter($('#amount', context));
	<?php endif ?>
		
	$('#amount').parents('form').after('<img id="qrpay" src="" />');