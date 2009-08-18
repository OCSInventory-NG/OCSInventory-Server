<?php
	print_item_header($l->g(26));
	if (!isset($_POST['SHOW']))
	$_POST['SHOW'] = 'NOSHOW';
	$form_name="affich_memories";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(80) => 'CAPTION',
					   $l->g(53) => 'DESCRIPTION',
					   $l->g(83)." (MB)" => 'CAPACITY',
					   $l->g(283) => 'PURPOSE',
					   $l->g(66) => 'TYPE',
					   $l->g(268) => 'SPEED',
					   $l->g(94) => 'NUMSLOTS');
	$list_col_cant_del=array($l->g(80)=>$l->g(80),$l->g(83)=>$l->g(83));
	$default_fields= $list_fields;
	$queryDetails  = "SELECT * FROM memories WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>