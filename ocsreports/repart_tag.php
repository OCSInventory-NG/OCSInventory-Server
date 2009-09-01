<?php
//cas d'une suppression de machine
if ($_POST['SUP_PROF'] != ''){	
	deleteDid($_POST['SUP_PROF']);
	$tab_options['CACHE']='RESET';
}

	$form_name="repart_tag";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields = array ( TAG_LBL   => "ID", 
						   'Nbr_mach'=>'c');
	$tab_options['FILTRE']['a.tag']=TAG_LBL;
//	$tab_options['NO_TRI']['LBL_UNIT']='LBL_UNIT';
//	$tab_options['LBL']['LBL_UNIT']="libell� unit�";
	$tab_options['LIEN_LBL']['Nbr_mach']="index.php?".PAG_INDEX."=".$pages_refs['all_computers']."&filtre=a.tag&value=";
	$tab_options['LIEN_CHAMP']['Nbr_mach']="ID";
	$list_col_cant_del=array(TAG_LBL=>TAG_LBL);
	$default_fields= $list_fields;
	$queryDetails  = "SELECT count(hardware_id) c, a.tag as ID from accountinfo a ";
	
	if (isset($_SESSION["mesmachines"]) and $_SESSION["mesmachines"] != '')
		$queryDetails  .= "WHERE ".$_SESSION["mesmachines"];
	$queryDetails  .= "group by TAG ";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,95,$tab_options);
	echo "</form>";

?>