<?php
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	print_item_header($l->g(79));
	$form_name="affich_ports";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(49) => 'NAME',
					   $l->g(278) => 'DRIVER',
					   $l->g(279) => 'PORT');
	$list_col_cant_del=$list_fields;
	$default_fields= $list_fields;
	$tab_options['FILTRE']=array('NAME'=>$l->g(49),'DRIVER'=>$l->g(278),'PORT'=>$l->g(279));
	$queryDetails  = "SELECT * FROM printers WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>