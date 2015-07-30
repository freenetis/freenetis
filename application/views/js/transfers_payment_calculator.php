<?php
/**
 * Payment calcultor javascript view.
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>	
	var add_base_url = '<?php echo url_lang::base() ?>transfers/add_member_fee_payment_by_cash/<?php echo $member_id ?>';
	
	$("#calculate").parent().parent().hide();
	$(".form button[type=submit]").hide();
	
	$(".form").submit(function (){
		return false;
	});

	$("#amount, #expiration_date").live('keyup', function (){
		
		$.ajaxSetup({ async: false });
		
		if (this.id == 'amount')
		{
				calculate = 'expiration_date';
				amount = parseFloat($("#amount").val());
				expiration_date = "";
		}
		else
		{
				calculate = 'amount';
				expiration_date = $("#expiration_date").val();
				amount = "";
		}
		
		if ((calculate == 'expiration_date' && amount != "") || (calculate == 'amount' && expiration_date != ""))
		{
			$.post('<?php echo url_lang::base() ?>transfers/payment_calculator/<?php echo $account_id ?>/1', {calculate: calculate, amount: amount, expiration_date: expiration_date}, function (data){
				$("#"+calculate).val(data);
			});
		}
		$(".add_link").attr('href', add_base_url+'/'+parseFloat($("#amount").val()))
	});
	
	<?php if ($can_add): ?>
	$("#amount", context).css('width', parseInt($("#amount", context).css('width'))-23);
	$("<a href="+add_base_url+" class='add_link popup_link'><img class='purse' src='<?php echo url::base() ?>media/images/icons/purse.png'></a>").insertAfter($("#amount", context));
	<?php endif ?>