<?php
	print_item_header($l->g(97));
	if (!isset($_POST['SHOW']))
	$_POST['SHOW'] = 'NOSHOW';
	$form_name="affich_monitors";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(64) => 'MANUFACTURER',
					   $l->g(80) => 'CAPTION',
					   $l->g(360) => 'DESCRIPTION',
					   $l->g(66) => 'TYPE',
					   $l->g(36)=> 'SERIAL');
	$list_col_cant_del=array($l->g(64)=>$l->g(64),$l->g(36)=>$l->g(36));
	$default_fields= $list_fields;
	$queryDetails  = "SELECT * FROM monitors WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>