<h2><?php echo __('Searching of term') ?> "<?php echo $keyword ?>"</h2><br />
<?php echo __('Total found items') ?>: <?php echo $total_items ?>
<?php for ($i = $from; $i <= $to; $i++): ?>
	<div class="search_result">
		<b><?php echo html::anchor(url_lang::base() . $results[$i]->link . $results[$i]->id, $results[$i]->return_value, array('class' => 'search_result_title')) ?></b><br />
		<i><?php echo $results[$i]->desc ?></i>
	</div>
<?php endfor ?>

<?php echo $pagination ?>