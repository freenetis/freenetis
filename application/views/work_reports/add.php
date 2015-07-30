<script type="text/javascript"><!--
	
	$(document).ready(function ()
	{		
		// onchange radio button
		$('#report_type1, #report_type2').change(function ()
		{
			var $this = $(this);
			var $td = $this.parent().parent().find('.report_type_spect');
			var other_id = '#report_type' + ($this.attr('id') == 'report_type1' ? '2' : '1');
			var $td_other = $(other_id).parent().parent().find('.report_type_spect');
			
			if ($this.is(':checked'))
			{
				$td.find('input, select').removeAttr('disabled', true);
				$td.css('opacity', 1.0);
				$td_other.find('input, select').attr('disabled', true);
				$td_other.css('opacity', 0.5);
				
				if ($this.attr('id') == 'report_type1')
					$('#report_month').focus();
				else
					$('#works_count').focus();
			}
			else
			{
				$td.find('input, select').attr('disabled', true);
				$td.css('opacity', 0.5);
				$td_other.find('input, select').removeAttr('disabled', true);
				$td_other.css('opacity', 1.0);
			}
		});
		
		// onchange after load
		$('#report_type1').attr('checked', true);
		$('#report_type1').change();
		
		// add actions
		add_actions();
		
		// click to end first part of form
		$('#continue_button').click(function ()
		{
			var $description_edit = $('#description_edit');
			var $user_id_edit = $('#user_id_edit');
			var $works_count = $('#works_count');
			var $payment_type_edit = $('#payment_type_edit');
			
			// form validation
			
			if ($description_edit.val() == '')
			{
				$description_edit.addClass('error');
				$description_edit.focus();
				return false;
			}
			
			$description_edit.removeClass('error');
			
			if ($('#report_type2').is(':checked'))
			{
				if ($works_count.val() == '' ||
					isNaN($works_count.val()) ||
					$works_count.val() <= 0)
				{
					$works_count.addClass('error');
					$works_count.focus();
					return false;
				}

				$works_count.removeClass('error');
			}
			
			// store values to hidden
			
			$('#description').attr('value', $description_edit.val());
			$('#user_id').attr('value', $user_id_edit.val());
			$('#payment_type').attr('value', $payment_type_edit.val());
			
			if ($('#report_type1').is(':checked'))
			{
				$('#type').attr('value', sprintf('%04d-%02d', $('#report_year').val(), $('#report_month').val()));
			}
			else
			{
				$('#type').attr('value', '');
			}
			
			// transform form to read only
			
			$(this).hide();
			$description_edit.attr('readonly', true);
			$user_id_edit.attr('disabled', true);
			$payment_type_edit.attr('disabled', true);
			$('#type_table input').attr('readonly', true);
			$('#report_type1').attr('disabled', true);
			$('#report_type2').attr('disabled', true);
			$('#report_year').attr('disabled', true);
			$('#report_month').attr('disabled', true);
			$works_count.attr('readonly', true);
			
			// ready works form
			
			if ($('#report_type2').is(':checked'))
			{
				for (var i = 0; i < $works_count.val(); i++)
				{
					add_work_row(i + 1);
				}
				
				$('#add_work').show();
			}
			else
			{
				var date_count = new Date(
						$('#report_year').val(),
						$('#report_month').val(), 0
				).getDate();
				
				var date = $('#report_year').val() + '-' + $('#report_month').val() + '-';
				
				for (var i = 0; i < date_count; i++)
				{
					add_work_row(i + 1, date + (i + 1).toString());
				}
			}
			
			// add methods
			
			add_actions();
			
			// show work form
			
			$('#work_table').show();
			$('#b_submit').show();
			
		});
		
		// submit of works
		
		$('#article_form').submit(function ()
		{
			return check_second_form();
		});
		
	});
	
--></script>

<h2><?php echo __('Add new work report') ?></h2>

<br />

<?php echo form::open(url::base(TRUE) . url::current(TRUE), array('id' => 'article_form')) ?>

	<table cellspacing="0" class="form">
		<tr>
			<th colspan="3" class="group" style="text-align: left"><?php echo __('Work report details') ?></th>
		</tr>
		<tr>
			<th colspan="3"><label><?php echo __('Description of work report') ?></label></th>
		</tr>
		<tr>
			<td colspan="3">
				<?php echo form::textarea(array('name' => 'description_edit', 'style' => 'margin: 10px; width: 700px')) ?>
				<?php echo form::hidden('description'); ?>
			</td>
		</tr>
		<tr>
			<th><label><?php echo __('Approval template') ?></label></th>
			<th><label><?php echo __('Worker') ?></label></th>
			<th><label><?php echo __('Payment type') ?></label></th>
		</tr>
		<tr>
			<td>
				<?php if (count($arr_approval_templates)): ?>
				<?php echo form::dropdown(array('name' => 'approval_template_id', 'style' => 'width: 200px; margin: 10px;'), $arr_approval_templates, Settings::get('default_work_approval_template')) ?>
				<?php else: ?>
				<div style="width: 200px; margin: 10px;" class="bold"><?php echo ORM::factory('approval_template', Settings::get('default_work_approval_template'))->name ?></div>
				<?php endif; ?>
			</td>
			<td>
				<?php echo form::dropdown(array('name' => 'user_id_edit', 'style' => 'width: 200px'), $arr_users, $selected_user) ?>
				<?php echo form::hidden('user_id'); ?>
			</td>
			<td>
				<?php echo form::dropdown(array('name' => 'payment_type_edit', 'style' => 'width: 200px'), Job_report_Model::get_payment_types()) ?>
				<?php echo form::hidden('payment_type'); ?>
			</td>
		</tr>
	</table>

	<table cellspacing="0" class="form" id="type_table">
		<tr>
			<th><?php echo __('Type of report') ?></th>
			<th><?php echo __('Specification of type') ?></th>
		</tr>
		<tr>
			<td><input type="radio" name="report_type" class="radio" id="report_type1" /> <label for="report_type1" class="bold"><?php echo __('Work report per month') ?></label></td>
			<td style="width: 500px" class="report_type_spect">
				<?php echo form::hidden('type'); ?>
				<?php echo __('Year') ?>: <?php echo form::dropdown(array('name' => 'report_year'), date::years(date('Y') - 1, date('Y')), date('Y')) ?>
				<?php echo __('Month') ?>: <?php echo form::dropdown(array('name' => 'report_month'), array_map('__', date::$months), date('m')) ?>
			</td>
		</tr>
		<tr>
			<td><input type="radio" name="report_type" class="radio" id="report_type2" /> <label for="report_type2" class="bold"><?php echo __('Grouped works') ?></label></td>
			<td class="report_type_spect">
				<?php echo __('Count of works') ?>: <input type="text" name="works_count" id="works_count" maxlength="2" style="width: 40px" />
				<a href="#" id="add_work" style="text-decoration: none; display: none;"><?php echo html::image(array('src' => '/media/images/icons/ico_add.gif', 'alt' => __('Add'))) ?> <?php echo __('Add work') ?></a>
			</td>
		</tr>
	</table>

	<table class="form">
		<tr>
			<td>
				<button id="continue_button" type="button" class="submit" style="width: auto"><?php echo __('Continue with works filling') ?></button>
			</td>
		</tr>
	</table>

	<table cellspacing="0" class="form" style="margin-top: 20px; display: none;" id="work_table">
		
		<thead>
			<tr>
				<th colspan="5" class="group" style="text-align: left"><?php echo __('Works of report'); ?></th>
			</tr>
			<tr>
				<th><?php echo __('Date'); ?></th>
				<th><?php echo __('Description of work'); ?></th>
				<th><?php echo __('Hours'); ?></th>
				<th><?php echo __('km'); ?></th>
				<th></th>
			</tr>
		</thead>
		
		<tfoot>
			<tr>
				<th></th>
				<th style="text-align: right; padding-right: 10px"><?php echo __('Total count') ?>:</th>
				<th><div id="total_hours_count" class="bold left">0 h</div></th>
				<th><div id="total_km_count" class="bold left">0 km</div></th>
				<th></th>
			</tr>
			<tr>
				<th></th>
				<th style="text-align: right; padding-right: 10px"><?php echo __('Price per one hour, kilometre') ?>:</th>
				<th class="left" style="padding-left: 0">
					<input name="price_per_hour" maxlength="5" id="price_per_hour" style="width: 30px;" />
					<span class="normal"><?php echo __(Settings::get('currency')) ?></span>
				</th>
				<th class="left" style="padding-left: 0">
					<input name="price_per_km" maxlength="5" id="price_per_km" style="width: 20px" />
					<span class="normal"><?php echo __(Settings::get('currency')) ?></span>
				</th>
				<th></th>
			</tr>
			<tr>
				<th><?php echo __('Total price') ?>:</th>
				<th><div id="total_price" style="font-size: 13px" class="bold">0<span class="normal"> <?php echo __(Settings::get('currency')) ?></span></div></th>
				<th><div id="total_hours_price" class="bold left">0 <?php echo __(Settings::get('currency')) ?></div></th>
				<th><div id="total_km_price" class="bold left">0 <?php echo __(Settings::get('currency')) ?></div></th>
				<th></th>
			</tr>
		</tfoot>
		
		<tbody></tbody>
		
	</table>

	<br />
	<br />

	<button id="b_submit" type="submit" class="submit" style="width: auto; padding: 5px; display: none"><?php echo __('Save concept') ?></button>

<?php echo form::close() ?>
