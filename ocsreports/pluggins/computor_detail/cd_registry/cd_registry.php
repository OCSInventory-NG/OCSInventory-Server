<?php
	print_item_header($l->g(211));
	$form_name="affich_registry";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(212) => 'NAME',
					   $l->g(213) => 'REGVALUE');
	$list_col_cant_del=$list_fields;
	$default_fields= $list_fields;
	$tab_options['FILTRE']=array('NAME'=>$l->g(212),'REGVALUE'=>$l->g(213));;
	$queryDetails  = "SELECT * FROM registry WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>