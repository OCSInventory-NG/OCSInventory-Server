<?php
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	print_item_header($l->g(91));
	$form_name="affich_inputs";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(66) => 'TYPE',
					   $l->g(64) => 'MANUFACTURER',
					   $l->g(80) => 'CAPTION',
					   $l->g(53) => 'DESCRIPTION',
					   $l->g(84) => 'INTERFACE');
	$list_col_cant_del=array($l->g(66)=>$l->g(66),$l->g(84)=>$l->g(84));
	$default_fields= $list_fields;
	$queryDetails  = "SELECT * FROM inputs WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>