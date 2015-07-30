<?php if ($total_items > 0): ?>
	<?php foreach ($results as $result): ?>
		<a href="<?php echo url_lang::base() . $result->link . $result->id ?>" class="whisper_search_result">
			<b><?php echo text::highligth($keyword, $result->return_value) ?></b><br />
			<i><?php echo text::highligth($keyword, $result->desc) ?></i>
		</a>
	<?php endforeach ?>
<?php else: ?>
	<div class="whisper_search_result"><?php echo __('No items found.') ?></div>
<?php endif;