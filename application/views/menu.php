<ul>
    <?php foreach ($groups as $group): ?>
	<li class="<?php echo $group->name ?>"><h2><?php echo $group->label ?></h2>
	<ul>
	    <?php foreach ($group->items as $item): ?>
	    <li>
		<?php $attributes = null; ?>
		<?php if (isset($item->default)): $attributes = array( 'class' => 'bold'); endif; ?>
		<?php echo html::anchor($item->url, $item->label, $attributes); ?>
		<?php if (isset($item->count)): ?>
		<?php echo html::menu_item_counter($item->count, (isset($item->color) ? $item->color : '')) ?>
		<?php endif ?>
	    </li>
	    <?php endforeach ?>
	</ul>
    <?php endforeach ?>
</ul>