<?php 
	echo "<table class=\"main\" cellspacing=\"0\">\n";		
	$first=true; $header="";
	foreach ($table_data as $row) {
		echo "	<tr>"; 
		$data="";
		foreach ($row as $col_name=>$val) {
			if ($first)
				$header.="<th>$col_name</th>";
			$data.="<td>$val</td>";						
		}
		if ($first) {
			echo $header . "</tr>\n";				
			$first=false;
		}
		echo $data . "</tr>\n";				
	}
	echo "\n</table><br/>\n"
?>
