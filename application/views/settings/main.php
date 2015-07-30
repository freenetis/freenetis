<h2><?php echo __('Settings') ?></h2>

<?php if (isset($create)) echo $create; ?>

<?php if(isset($this->sections)): ?>
<ul class="tabs">
		<?php foreach ($this->sections as $section => $name): ?>
		<li class="ui-corner-all<?php echo ($current == $section) ? ' current' : '' ?>"><a id="<?php echo $section ?>-link" href="<?php echo url_lang::base().'settings/'.$section ?>"><?php echo $name ?></a></li>
		<?php endforeach; ?>
		
</ul>
<?php else: ?>
</br>
<?php endif ?>

<div id="settings-content" class="clear">

<h3><?php echo $headline ?></h3>
<br />

<?php echo (isset($warning) && $warning != '') ? '<p class="red">'.$warning.'</p><br />' : '' ?>

<?php echo (isset($description) && $description != '') ? '<p>'.$description.'</p><br />' : '' ?>

<?php echo $content ?>

<?php echo (isset($additional_info) && $additional_info != '') ? '<br /><br />'.$additional_info : '' ?>

</div>
