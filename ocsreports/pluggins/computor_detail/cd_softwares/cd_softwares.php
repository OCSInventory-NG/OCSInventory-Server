<?php
	print_item_header($l->g(20));
	$form_name="affich_soft";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(69) => 'PUBLISHER',
					   $l->g(49) => 'NAME',
					   $l->g(277) => 'VERSION',
					   $l->g(51)=>'COMMENTS');
	$list_col_cant_del=array($l->g(49)=>$l->g(49));
	$default_fields= $list_fields;
	$tab_options['FILTRE']=array('NAME'=>$l->g(49),'VERSION'=>$l->g(277),'PUBLISHER'=>$l->g(69));
	$queryDetails  = "SELECT * FROM softwares WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>