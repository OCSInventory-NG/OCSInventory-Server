<?php
	print_item_header($l->g(61));
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	$form_name="affich_videos";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(49) => 'NAME',
					   $l->g(276) => 'CHIPSET',
					   $l->g(26)." (MB)" => 'MEMORY',
					   $l->g(62) => 'RESOLUTION');
	$list_col_cant_del=array($l->g(49)=>$l->g(49));
	$default_fields= $list_fields;
	//$tab_options['FILTRE']=array('NAME'=>$l->g(212),'REGVALUE'=>$l->g(213));;
	$queryDetails  = "SELECT * FROM videos WHERE (hardware_id = $systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>