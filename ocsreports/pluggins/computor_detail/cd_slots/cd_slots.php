<?php
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	print_item_header($l->g(271));
	$form_name="affich_slots";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(49) => 'NAME',
					   $l->g(53) => 'DESCRIPTION',
					   $l->g(70) => 'DESIGNATION');
	$list_col_cant_del=$list_fields;
	$default_fields= $list_fields;
	//$tab_options['FILTRE']=array('NAME'=>$l->g(212),'REGVALUE'=>$l->g(213));;
	$queryDetails  = "SELECT * FROM slots WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>