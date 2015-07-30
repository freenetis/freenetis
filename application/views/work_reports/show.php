<?php if ($work_report->concept): ?>

<div class="status_message_info"><?php echo __('This report is your concept, you can edit it till you think that it is ready for approval.') ?></div>

<?php echo form::open(url_lang::base() . 'work_reports/concept_change/' . $work_report->id, array('id' => 'article_form', 'method' => 'get')) ?>
	<button type="submit" class="submit" style="width: auto; margin: 5px 0 15px; padding: 5px;"><?php echo __('Send this concept for approval.') ?></button>
	<input type="hidden" name="path_qsurl" value="<?php echo url_lang::current() ?>">
<?php echo form::close() ?>
	
<?php endif; ?>

<h2><?php echo __('Show work report') ?></h2>

<?php if ($links): ?>
<br />
<?php echo $links ?>
<br />
<?php endif ?>

<br />

<table class="extended" cellspacing="0">
	<tr>
		<th><?php echo __('ID') ?></th>
		<td><?php echo $work_report->id ?></td>
	</tr>
	<tr>
		<th><?php echo __('Worker') ?></th>
		<td><?php echo html::anchor(url_lang::base() . 'users/show/' . $work_report->user_id, $work_report_model->user->get_full_name()) ?></td>
	</tr>
	<?php if ($work_report->added_by_id && ($work_report->user_id != $work_report->added_by_id)): ?>
	<tr>
		<th><?php echo __('Added by') ?></th>
		<td><?php echo html::anchor(url_lang::base() . 'users/show/' . $work_report->added_by_id, $work_report_model->added_by->get_full_name()) ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Type') ?></th>
		<td>
			<?php if (empty($work_report->type)): ?>
				<?php echo __('Grouped works') ?>
			<?php else: ?>
				<?php echo __('Work report per month') ?> <?php echo __('for', '', 1) ?>
				<b><?php echo __(date::$months[intval(substr($work_report->type, 5, 6))]) ?> <?php echo substr($work_report->type, 0, 4) ?></b>
			<?php endif ?>
		</td>
	</tr>
	<tr>
		<th><?php echo __('Description') ?></th>
		<td><?php echo $work_report->description ?></td>
	</tr>
	<tr>
		<th><?php echo __('Payment type') ?></th>
        <td><?php echo Job_report_Model::get_name_of_payment_type($work_report_model->payment_type) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date from') ?></th>
		<td><?php echo date('j.n. Y', strtotime($work_report->date_from)) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Date to') ?></th>
		<td><?php echo date('j.n. Y', strtotime($work_report->date_to)) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Hours') ?></th>
		<td><?php echo round($work_report->hours, 2) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Payment per hour') ?></th>
		<td><?php echo number_format($work_report->price_per_hour, 2, ',', ' ') . ' ' . __($this->settings->get('currency')) ?></td>
	</tr>
	<?php if ($work_report->km): ?>
	<tr>
		<th><?php echo __('Km') ?></th>
		<td><?php echo round($work_report->km, 2) ?></td>
	</tr>
	<tr>
		<th><?php echo __('Price per kilometre') ?></th>
		<td><?php echo number_format($work_report->price_per_km, 2, ',', ' ') . ' ' . __($this->settings->get('currency')) ?></td>
	</tr>
	<?php endif ?>
	<tr>
		<th><?php echo __('Suggest amount') ?></th>
		<td><b><?php echo number_format($work_report->suggest_amount, 2, ',', ' ') . ' ' . __($this->settings->get('currency')) ?></b></td>
	</tr>
	<tr>
		<th><?php echo __('State') ?></th>
		<td><b><?php echo $state_text ?></b></td>
	</tr>
	<?php if ($work_report->state == Vote_Model::STATE_APPROVED): ?>
		<?php if(Settings::get('finance_enabled') && $work_report->payment_type == Job_report_Model::PAYMENT_BY_CREDIT): ?>
		<tr>
			<th><?php echo __('Confirmed time') ?></th>
			<td><?php echo $transfer->creation_datetime ?></td>
		</tr>
		<tr>
			<th><?php echo __('Rating') ?></th>
			<td><?php echo html::anchor(url_lang::base() . 'transfers/show/' . $transfer->id, number_format($transfer->amount, 2, ',', ' ') . ' ' . __($this->settings->get('currency'))) ?></td>
		</tr>
		<?php else: ?>
		<tr>
			<th><?php echo __('Rating') ?></th>
			<td><b><?php echo number_format($work_report_model->get_rating(), 2, ',', ' ') . ' ' . __($this->settings->get('currency')) ?></b></td>
		</tr>
		<?php endif ?>
	<?php endif ?>
</table>

<br /><br />
<h3><?php echo __('Works') ?></h3>

<?php echo $works_grid ?>