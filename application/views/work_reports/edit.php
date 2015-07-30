<script type="text/javascript"><!--
	
	$(document).ready(function ()
	{
		// add actions
		
		add_actions();
		
		// recalculate price after changing any of hour fields

		$(':text[name^="work_hours"], ' +
		  ':text[name^="work_km"], ' +
		  'textarea[name^="work_description"]').keyup(function ()
		{
			$(this).parent().parent().find('.clear_row').show();
		});
		
		// recalculate
		
		recalculate_hours();
		recalculate_km();
		
		// submit of works
		
		$('#article_form').submit(function ()
		{
			var valid = true;
			
			$('#description').removeClass('error');
			
			if (!$('#description').val().length)
			{
				$('#description').addClass('error');
				valid = false;
			}
			
			return valid && check_second_form();
		});
		
		<?php if (!empty($work_report->type)): ?>
		
		// mark weekends
		
		$(':text[name^="work_date"]').each(function ()
		{
			var da = $(this).val().split('-');
			
			if (da.length == 3 &&
				new Date(da[0], da[1] - 1, da[2]).getDay() % 6 == 0)
			{
				$(this).parent().parent().css('background', '#f1f1f1');
			}
		});
		
		<?php endif; ?>
		
	});
	
--></script>

<?php if ($work_report->concept): ?>
<div class="status_message_info"><?php echo __('This report is your concept, you can edit it till you think that it is ready for approval.') ?></div>	
<?php endif; ?>

<h2><?php echo __('Edit work report') ?></h2>

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
				<?php echo form::textarea(array('name' => 'description', 'value' => $work_report->description, 'style' => 'margin: 10px; width: 700px')) ?>
			</td>
		</tr>
		<tr>
			<th><label><?php echo __('Approval template') ?></label></th>
			<th><label><?php echo __('Worker') ?></label></th>
			<th><label><?php echo __('Payment type') ?></label></th>
		</tr>
		<tr>
			<td style="padding: 5px; width: 240px;">
				<?php if (count($arr_approval_templates)): ?>
				<?php echo form::dropdown(array('name' => 'approval_template_id', 'style' => 'width: 200px; margin: 10px;'), $arr_approval_templates, $work_report->approval_template_id) ?>
				<?php else: ?>
				<div style="margin: 10px;" class="bold"><?php echo $work_report->approval_template->name ?></div>
				<?php endif; ?>
			</td>
			<td style="padding: 5px; width: 240px">
				<?php echo $work_report->user->get_full_name() . ' - ' . $work_report->user->login ?>
			</td>
			<td>
				<?php echo form::dropdown(array('name' => 'payment_type', 'style' => 'width: 200px'), Job_report_Model::get_payment_types(), $work_report->payment_type) ?>
			</td>
		</tr>
	</table>

	<table cellspacing="0" class="form" id="type_table">
		<tr>
			<th><?php echo __('Type of report') ?></th>
			<th><?php echo __('Specification of type') ?></th>
		</tr>
		<?php if (!empty($work_report->type)): ?>
		<tr>
			<td style="padding: 5px; width: 260px"><?php echo __('Work report per month') ?></td>
			<td style="padding: 5px; width: 500px">
				<?php echo __(date::$months[$month]) . ' ' . $year; ?>
			</td>
		</tr>
		<?php else: ?>
		<tr>
			<td style="padding: 5px; width: 260px"><?php echo __('Grouped works') ?></td>
			<td style="padding: 5px; width: 500px">
				<?php echo __('Count of works') ?>: <input type="text" name="works_count" value="<?php echo $work_report->jobs->count() ?>" id="works_count" readonly="readonly" maxlength="2" style="width: 40px" />
				<a href="#" id="add_work" style="text-decoration: none"><?php echo html::image(array('src' => '/media/images/icons/ico_add.gif', 'alt' => __('Add'))) ?> <?php echo __('Add work') ?></a>
			</td>
		</tr>
		<?php endif; ?>
	</table>

	<table cellspacing="0" class="form" style="margin-top: 20px" id="work_table">
		
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
					<input name="price_per_hour" maxlength="5" id="price_per_hour" value="<?php echo str_replace(',', '.', $work_report->price_per_hour) ?>" style="width: 30px;" />
					<span class="normal"><?php echo __(Settings::get('currency')) ?></span>
				</th>
				<th class="left" style="padding-left: 0">
					<input name="price_per_km" maxlength="5" id="price_per_km" value="<?php echo empty($work_report->price_per_km) ? '' : str_replace(',', '.', $work_report->price_per_km); ?>" style="width: 20px" />
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
		
		<tbody>
		<?php $index = 1; foreach ($works as $job): ?>
			<tr>
				<td>
					<?php if (!empty($job)): ?>
					<input type="hidden" name="work_id[<?php echo $index ?>]" value="<?php echo (empty($job)) ? '' : $job->id ?>" />
					<?php endif; ?>
					<input type="text" name="work_date[<?php echo $index ?>]" value="<?php echo (empty($job)) ? $work_report->type . '-' . ($index < 10 ? '0' : '') . $index : $job->date ?>" <?php if (empty($work_report->type)): ?>class="date"<?php else: ?>readonly="readonly"<?php endif; ?> style="width: 80px" />
				</td>
				<td>
					<textarea name="work_description[<?php echo $index ?>]" class="one_row_textarea" style="width: 450px"><?php echo (empty($job)) ? '' : $job->description ?></textarea>
				</td>
				<td>
					<input type="text" name="work_hours[<?php echo $index ?>]" value="<?php echo (empty($job)) ? '' : str_replace(',', '.', $job->hours) ?>" maxlength="5" style="width: 30px" />
				</td>
				<td>
					<input type="text" name="work_km[<?php echo $index ?>]" value="<?php echo empty($job->km) ? '' : $job->km ?>" maxlength="6" style="width: 30px" />
				</td>
				<td>
					<?php if (empty($work_report->type)): ?>
					<a href="#" class="remove_row action_field_icon" title="<?php echo __('Remove this work') ?>">
						<?php echo html::image(array('src' => 'media/images/icons/grid_action/delete.png', 'width' => 14, 'height' => 14)) ?>
					</a>
					<?php else: ?>
					<a href="#" class="clear_row action_field_icon"<?php if (empty($job)): ?> style="display: none;"<?php endif ?> title="<?php echo __('Remove this work') ?>">
						<?php echo html::image(array('src' => 'media/images/icons/grid_action/delete.png', 'width' => 14, 'height' => 14)) ?>
					</a>
					<?php endif; ?>
				</td>
			</tr>
		<?php $index++; endforeach; ?>
		</tbody>
		
	</table>

	<br />

	<button id="b_submit" type="submit" class="submit" style="width: auto; padding: 5px"><?php echo __('Save') ?></button>

<?php echo form::close() ?>
