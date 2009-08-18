<?php
	print_item_header($l->g(54));
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	$form_name="affich_processors";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(66) => 'PROCESSORT',
					   $l->g(377) => 'PROCESSORS',
					   $l->g(55) => 'PROCESSORN');
	$list_col_cant_del=$list_fields;
	$default_fields= $list_fields;
//	$tab_options['FILTRE']=array('NAME'=>$l->g(49),'MANUFACTURER'=>$l->g(64),'TYPE'=>$l->g(66));
	$queryDetails  = "SELECT * FROM hardware WHERE (id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>