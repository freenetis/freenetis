<?php
/**
 * Tableform view is a form rendered using HTML table
 * @author Tomas Dulik, Michal Kliment, Ondřej Fibich
 * @version 0.9 beta
 * 
 * Input data are in $form_def and $form_values variables
 * $form_def is an array of items, where item can be:
 * - 'tr' - will render a new table row, e.g. as </tr><tr>
 * - 'td' - will render an empty table cell, e.g. as </td><td>
 * - instance of Table_Form_Item which represents form fields, e.g.
 * 	- input field
 * 	- submit button
 * 	- selection box
 * 	- hidden fields
 */ 
echo form::open($uri, array('method'=>'get')) . "\n<table class=\"table_form\"><tr>\n";
foreach ($form_def as $value) {
	if (is_object($value))
	{

		if (!isset($form_val[$value->name])) $val="";
		else $val=$form_val[$value->name];

		// attributs of element
		$attrs = '';
		foreach ($value->attrs as $attr_name => $attr_value)
		{
			$attrs .= ' ' . htmlspecialchars($attr_name) . '="' . htmlspecialchars($attr_value) . '"';
		}

		switch ($value->type) {
			case "submit":
				echo "	<td><input name='submit' type='submit' value='"
					 .url_lang::lang("texts.".$value->label)."' class=\"submit\"" .$attrs. " /></td>\n";
				break;
			case "hidden":
				echo "  <input name='".$value->name."' value='".$value->values[0]."' type='hidden'" .$attrs. ">\n";
				break;
			case "select":
				echo "  <td><label for='".$value->name."'>".__(''.$value->label).":</label></td><td><select id='".$value->name."' name='".$value->name."'" .$attrs. ">\n";
				foreach ($value->values as $key => $val_value)
				{
					echo "  <option value='";
					echo ($key>0) ? $key."'" : "'";
					echo ($val==$key) ? ' selected' : '';
					echo ">".$val_value."</option>\n";
				}
				echo "  </select></td>\n";
				break;
			/**
			 * case "input":
			 * 		here you can put implementation of other field types, like
			 * 		selections, check buttons etc.
			 */
			default:
				echo "	<td><label for='".$value->name."'>".url_lang::lang("texts.".$value->label).":</label></td>\n"
				 	."	<td><input type='text' id='".$value->name."' name='".$value->name."' value='$val'" .$attrs. " /></td>\n";
		}
	}
	else
	{
		switch ($value) {
			case "tr":
				echo "</tr><tr>\n"; break;
			case "td":
				echo "	<td></td>\n"; break;				
			default:
				echo $value;
	
		}	
	}
	
}
echo "</tr></table>\n</form>";
?>

