<h2><?php echo __('Tools') ?></h2>

<ul class="tabs">
		<?php foreach ($this->sections as $section => $name): ?>
		<li class="ui-corner-all<?php echo ($current == $section) ? ' current' : '' ?>"><a id="<?php echo $section ?>-link" href="<?php echo url_lang::base().'tools/'.$section ?>"><?php echo $name ?></a></li>
		<?php endforeach; ?>
		
</ul>

<div id="tools-content" class="clear">
	
<h3><?php echo $headline ?></h3>
<br />
	
<?php echo $content ?>
	
</div>
