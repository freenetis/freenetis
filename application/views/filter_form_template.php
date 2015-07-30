<script type="text/javascript"><!--
$(document).ready(function(){
	
	// filters data used by javascript
	var types = <?php echo json_encode($js_data) ?>;
	
	var selected_operations = {
<?php 
	foreach ($operations as $i => $operation): 
		if ($operation != ''):
			echo "\t\t$i: ['$operation'],\n"; endif;
	endforeach
?>
	};
	
	var selected_values = {
<?php 
	foreach ($types as $i => $type):
		if (isset($js_data[$type]['returns']) && $js_data[$type]['returns'] == 'key'):
			echo "\t\t$i: ['" . implode('\',\'', $values[$i]) . "'],\n";
		endif;
	endforeach
?>
	};

	/**
	 * Type of filter changed
	 *
	 * @author Michal Kliment
	 */
	$(".t").die("change").live("change", function (){
        // css classes
        var classes = [];
        
		// remove all items from operation's select
		$(this).next().html("");
		
		// adds only operations of current type
		var b = [];
		for (var i in types[$(this).val()]['operations'])
		{
			b.push("<option value='"+i+"'>"+types[$(this).val()]['operations'][i]+"</option>");
		}
		$(this).next().append(b.join(''));
		
		$(this).next().next().autocomplete("destroy");
		
		$(this).next().next().datepicker("destroy");
		
		// repair classes of value input
		$(this).next().next().removeClass().addClass("v");
		for (var i in types[$(this).val()]['classes'])
		{
			$(this).next().next().addClass(types[$(this).val()]['classes'][i]);
		}
		
		// add CSS classes
		for (var i in types[$(this).val()]['css_classes'])
		{
			$(this).next().next().addClass(types[$(this).val()]['css_classes'][i]);
            classes.push(types[$(this).val()]['css_classes'][i]);
		}
		
		// type returns key (not value), change value input to select
		if (types[$(this).val()]['returns'] == 'key')
		{
			if (!$(this).next().next().is("select"))
			{
				// adds button to switch to multiple select
				$(this).next().next().after("<img src='<?php echo url::base() ?>media/images/icons/ico_add.gif' class='expand-button' title='<?php echo __("Multiple choice") ?>'>");
			}
			
            classes.push('v');
			$(this).next().next().replaceWith("<select class='" + classes.join(' ') + "' name='"+$(this).next().next().attr("name")+"'></select>");
			
			b = [];
			for (var i in types[$(this).val()]['values'])
			{
				b.push("<option value='"+i+"'>"+types[$(this).val()]['values'][i]+"</option>");
			}
			$(this).next().next().append(b.join(''));
		}
		// type returns value (not key), change value select to input
		else
		{	
			if (!$(this).next().next().is("input"))
			{
                classes.push('v');
				$(this).next().next().replaceWith("<input class='" + classes.join(' ') + "' name='"+$(this).next().next().attr("name")+"'>");
				$(this).next().next().next().remove();
			}
		}
		
		// type has callback
		if (types[$(this).val()]['callback'] != null)
		{
			$(this).next().next().autocomplete({
				source: "<?php echo url_lang::base() ?>"+types[$(this).val()]['callback']
			});
		}
	});
	
	// click on expand button
	$(".expand-button").die("click").live("click", function (){
	
		var is_multiple = ($(this).prev().attr("multiple") == 'multiple');
	
		if ($(this).prev().attr("multiple") != 'multiple')
		{
			var length = $(this).prev().children("option").length;
			
			$(this).prev().attr("multiple", true);
			$(this).prev().attr("size", Math.min(length, 10));
			$(this).attr("src", "<?php echo url::base() ?>media/images/icons/ico_minus.gif");
		}
		else
		{
			$(this).prev().attr("multiple", false);
			$(this).prev().attr("size", 1);
			$(this).attr("src", "<?php echo url::base() ?>media/images/icons/ico_add.gif");
		}
		
		var title = is_multiple ? "<?php echo __("Multiple choice") ?>" : "<?php echo __("Single choice") ?>";
		
		$(this).attr('title', title);
	});
	
	$(".v").die('focus').live('focus', function (){
		var is_multiple = ($(this).attr("multiple") == 'multiple');
		
		if (is_multiple)
			$(this).attr("size", Math.min(10, $(this).children("option").length));
	});
	
	$(".v").die('blur').live('blur', function (){
		var is_multiple = ($(this).attr("multiple") == 'multiple');
		var length = $(this).children("option").length;
		
		if (is_multiple)
			$(this).attr("size", Math.min(10, length));
	});
	
	/**
	 * Adds new filter
	 */
	$("#add_button").die("click").live("click", function (){
		
		var i = $(".filter_div").length;
		
		$(".filter_div:last").show();
		
		$(".filter_div:last .t").trigger("change");
		
		$(".filter_div:last").clone().appendTo("#filters");
		
		$(".filter_div:last").hide();
		
		$(".filter_div:last").attr("id", "filter-div-"+i);
		
		$(".filter_div:last .number").text("#"+(i+1));
		
		$(".filter_div:last .n").attr("name", "on["+i+"]");
		$(".filter_div:last .n").removeAttr("checked");
		$(".filter_div:last .t").attr("name", "types["+i+"]");
		$(".filter_div:last .o").attr("name", "opers["+i+"]");
		$(".filter_div:last .v").attr("name", "values["+i+"][0]");
		$(".filter_div:last .b").attr("name", "tables["+i+"]");
		$(".filter_div:last .d").attr("name", "default["+i+"]");
		
		return false;
	});
	
	/**
	 * Resets filter
	 */
	$("#reset_button").live("click", function (){
		
		var i = $(".filter_div").length;
		for (x=i; x>1; x--) {
			$(".filter_div:last").remove();
		}
		
		$(".filter_div:last .t").val(0);
		
		if ($(".filter_div:last .v").is("input"))
			$(".filter_div:last .v").val("");
		else
			$(".filter_div:last .v").val(0);
		
		$(".filter_div:last .t").trigger("change");
		$("#add_button").click();
		$(".filter_div:first .n").removeAttr("checked");
        
		return false;
	});
	
	if (window['filter_form_template_loaded'] == undefined)
	{
		window['filter_form_template_loaded'] = true;

		$(".filter_div:last").hide();

		// add button for adding new filter
		$("<a id='add_button'><img src='<?php echo url::base() ?>media/images/icons/ico_add.gif'> <?php echo __('Add new filter') ?></a>").insertBefore("#filters");

		// add button for reset filter
		$("<a id='reset_button'><img src='<?php echo url::base() ?>media/images/icons/voip-terminating.png'> <?php echo __('reset_filters') ?></a>").insertBefore("#filters");

		$(".t").trigger("change");

		$("#add_button").trigger("click");

		$(".t, .o, .v").live("change", function (){
			$(this).parent().children(".n").attr("checked", "checked");
			$("filter_div:last .n").removeAttr("checked");
		});

		for (i in selected_values)
		{
			if (selected_values[i].length > 1)
				$("#filter-div-"+i+" .expand-button").trigger('click');

			$("#filter-div-"+i+" .v").val(selected_values[i]);
		}

		for (i in selected_operations)
			$("#filter-div-"+i+" .o").val(selected_operations[i]);

		$("#filter_form fieldset legend").click(function(){
			$("#filters-div").toggle("fast");
		});

		$("#filter-query-select").removeAttr('name');

		$("#filter-query-select").change(function (){
			window.location.href = '<?php echo url::base(TRUE).url::current(FALSE) ?>?query='+$(this).val();
		});
	
	}
	
});
//--></script>
		
<?php echo form::open(url::base(TRUE).url::current(FALSE), array('method' => 'get', 'id' => 'filter_form')); ?>
	<fieldset>
		<legend><?php echo __('Filters') ?></legend>
<?php if (count($queries)): ?>
		<div id="filter-queries-div">
		<?php echo __('Saved queries') ?>:
		<select name="query" id="filter-query-select">
			<option value="NULL">----- <?php echo __("Choose query") ?> -----</option>
<?php foreach ($queries as $query): ?>
			<option value="<?php echo $query->id ?>"><?php echo $query->name ?></option>
<?php endforeach ?>
		</select>
		</div>
<?php endif ?>
		
		<div id="filters-div">
		<div id="filters">
<?php foreach ($types as $i => $type): ?>
<div class="filter_div" id="filter-div-<?php echo $i ?>">
	<span class="number">#<?php echo ($i+1) ?></span>
	<?php echo form::checkbox ('on['.$i.']', 1, $states[$i], " class='n'") ?>
	<?php echo form::dropdown ('types['.$i.']', $type_options, $type, " class='t'") ?>
	<?php echo form::dropdown ('opers['.$i.']', $operation_options, $operations[$i], " class='o'") ?>
	<?php echo form::input(array('name' => 'values['.$i.'][]', 'value' => $values[$i][0], 'class' => implode(' ', array_merge(array('v'), $classes)))) ?>
</div>
<?php endforeach ?>
			
<?php foreach ($tables as $type => $table): ?>
	<?php echo form::hidden('tables['.$type.']', $table, " class='b'") ?>
<?php endforeach ?>
		</div>
			
		<button type="submit" class="submit filter-button">
			<?php echo html::image(array('src' => 'media/images/icons/filter.png', 'width' => 14, 'height' => 14, 'style' => 'float:left')) ?>&nbsp;
			<?php echo __('Filter') ?>
		</button>
			
		<?php if ($can_add): ?>
			<a href="<?php echo url_lang::base().'filter_queries/add'.server::query_string().'&url='.$base_url ?>" class="save-button popup_link submit">
				<?php echo html::image(array('src' => 'media/images/icons/save.png', 'width' => 14, 'height' => 14, 'style' => 'float:left')) ?>&nbsp;
				<?php echo __('Save filter') ?>
			</a>
		<?php endif; ?>
	</div>
	
</fieldset>
	
<?php echo form::close() ?>