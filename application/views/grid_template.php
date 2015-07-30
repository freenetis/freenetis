<div id="<?php echo $id ?>-wrapper">
<?php echo ($title) ? '<h2>'.$title.'</h2><br />' : '' ?>
<?php echo ($filter) ? $filter.'<br />' : '' ?>
<?php echo empty($buttons) ? '' : implode(' | ', $buttons).'<br /><br />' ?>

<?php if ($form)
{
	echo form::open(NULL, array('class' => 'grid_form'));
	
	if (isset($form_extra_buttons["position"]) && $form_extra_buttons["position"] == 'top')
	{
		unset($form_extra_buttons["position"]);
		foreach ($form_extra_buttons as $form_extra_button)
			echo $form_extra_button;
		$form_extra_buttons["position"] = 'top';
	}
}
?>

<?php echo ($label) ? $label.$separator : '' ?>
<table<?php echo (empty($id) ? '' : ' id="' . $id . '"') ?> class="main grid_table tablesorter" cellspacing="0">
<?php if ($show_labels): ?>
    <thead>
	<tr>
<?php foreach ($fields as $field) :
	if ($field instanceof Order_Field)
	{
	    ?>
		<th class="{sorter: false}">
			<?php echo  html::anchor($field->return_link,$field->label) ?><?php if ($field->help!='') echo $field->help ?>
		</th>
	<?php
	}
	else if ($field instanceof Callback_Field)
	{
	    ?>
	    <th<?php echo (!$field->order) ? ' class="{sorter: false}"' : ''?>><?php echo $field->label?><?php if ($field->help!='') echo $field->help ?>
	    </th>
	    <?php
	}
	else if ($field instanceof Action_Field)
	{
	    ?>
	    <th class="{sorter: false}"><?php echo $field->label?><?php if ($field->help!='') echo $field->help ?>
	    </th>
	    <?php
	}
	else if ($field instanceof Grouped_Action_Field)
	{
		if (count($field->actions_fields))
		{
			?>
			<th class="{sorter: false}"><?php echo $field->label?><?php if ($field->help!='') echo $field->help ?>
			</th>
			<?php
		}
	}
	else
	{
	    ?>
	    <th class="noprint<?php echo (!$field->order) ? ' {sorter: false}' : ''?>"><?php echo $field->label?><?php if ($field->help!='') echo $field->help ?></th>
	    <?php
	}
	?>
<?php endforeach; ?>
	</tr>
    </thead>
<?php endif ?>
    <tbody>
<!-- table body -->
<?php $i = 0; ?>
<?php if (count($items)>0 && !is_numeric($items)) foreach ($items as $item) : ?>
	<?php 
		if ($i % 2) $class_tr = ' class="even"';
		else $class_tr = '';
		$i++;
	?>
	<tr <?php echo  $class_tr ?>>
	
	<?php
	
	// coloring of transfer types
	$inbound = false;
	$outbound = false;
	
	foreach ($fields as $field)
	{
		if ($field->class != '') $class_td = ' class="'.$field->class.'"';
			else $class_td = '';

		// action field
		if ($field instanceof Action_Field)
		{
			$property = $field->name;
			
			echo '<td class="noprint">';
			echo html::anchor($field->url.'/'.$item->$property, $field->action, array
			(
				'script'	=> $field->script,
				'class'		=> $field->class
			));
			echo '</td>';
		}
		else if ($field instanceof Grouped_Action_Field)
		{
			if (!count($field->actions_fields))
				continue;
			
			echo '<td class="noprint"><table class="no_table"><tr>';
			
			$output = array();
			
			foreach ($field->actions_fields as $action_field)
			{
				// conditional action field?
				if ($action_field instanceof Action_conditional_field)
				{
					$cond = $action_field->condition;
					
					if ($cond && mb_strlen($cond))
					{
						if (!call_user_func('condition::' . $cond, $item, $field->name))
						{
							continue;
						}
					}
				}
				
				$property = $action_field->name;
				$class = array();
				
				if (!empty($action_field->class))
					$class[] = $action_field->class;
				
				if (!empty($action_field->img))
					$class[] = 'action_field_icon';
			
				$output[] = '<td>' . html::anchor(
						$action_field->url.'/'.$item->$property,
						$action_field->render(), array
						(
							'title'		=> $action_field->label,
							'class'		=> implode(' ', $class),
							'script'	=> $action_field->script,
						)
				) . '</td>';
			}
			
			echo implode('', $output);
			
			echo '</tr></table></td>';
		}
		else if ($field instanceof Link_Field || $field instanceof Order_Link_Field)
		{
			$property = $field->name;
			$href = $item->$property;
			$property = $field->data_name;
			$text = $item->$property;
			
			echo '<td class="noprint">';
			
			if (!empty($href))
			{
				echo html::anchor($field->url.'/'.$href, $text, array
				(
					'script'	=> $field->script,
					'class'		=> $field->class
				));
			}
			else
			{
				echo $text;
			}
			
			echo '</td>';
		}
		elseif ($field->bool != '')
		{
			echo '<td ' . $class_td . '>' . $field->bool[(bool)$item->$field] . '</td>';
		}
		else
		{

			echo '<td ' . $class_td . '>';
			
			if ($field instanceof Callback_Field || $field instanceof Order_Callback_Field)
			{
			    if (isset($field->callback))
					call_user_func($field->callback, $item, $field->name, $field->args['callback']);
			    else
					echo $item->$field;
			}
			elseif ($field instanceof Form_Field || $field instanceof Order_Form_Field)
			{
				$input = 'Form_'.ucfirst($field->type);

				$field->input = new $input($field->name.'['.$item->id.']');

				if (isset($field->rules))
				{
					$field->input->rules($field->rules);
				}
				$field->input->method = 'post';

				if ($field->input instanceof Form_Dropdown)
				{
					$field->input->options($field->options);
					$field->input->selected($item->$field);
				}
				else if ($field->input instanceof Form_Checkbox)
				{
					if ((bool) $item->$field)
					    $field->input->checked('checked');
				}
				else
					$field->input->value($item->$field);
				
				if (isset ($field->callback))
					call_user_func($field->callback, $item, $field->name, $field->input, $field->args['callback']);
				else
					echo $field->input->html();
			}
			// empty field
			elseif (trim($item->$field) == '')
			{
				echo '&nbsp;';
			}
			// ordinary field without special formating
			else
			{
				echo $item->$field;
			}
			
			echo '</td>';
		
		}
		
	}
	
	if (isset($item->id))
		echo form::hidden('ids['.$item->id.']',$item->id);
	
	echo '</tr>';
	
endforeach;

else echo '<tr><td colspan="'.count($fields).'">'.__('There are no items yet.').'</td></tr>';
?>

<!-- table footer -->
    </tbody>
</table>

<?php
if ($form)
{
	echo "<br />";
	
	if (!isset($form_extra_buttons["position"]) || $form_extra_buttons["position"] != 'top')
	{
		unset($form_extra_buttons["position"]);
		foreach ($form_extra_buttons as $form_extra_button)
			echo $form_extra_button;
	}

	echo form::submit('submit', $form_submit_value);
	echo form::close();
}
?>

<?php echo  $paginator; ?>
<?php echo  $selector; ?>
</div>