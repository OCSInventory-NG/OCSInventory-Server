<?php
	print_item_header($l->g(96));
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	$form_name="affich_sounds";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(49) => 'NAME',
					   $l->g(64) => 'MANUFACTURER',
					   $l->g(53) => 'DESCRIPTION');
	$list_col_cant_del=array($l->g(49)=>$l->g(49));
	$default_fields= $list_fields;
//	$tab_options['FILTRE']=array('NAME'=>$l->g(49),'MANUFACTURER'=>$l->g(64),'TYPE'=>$l->g(66));
	$queryDetails  = "SELECT * FROM sounds WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>