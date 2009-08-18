<?php
	print_item_header($l->g(273));
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	if ($_POST['OTHER_BIS'] != '')
		@mysql_query("INSERT INTO blacklist_serials (SERIAL) value ('".$_POST['OTHER_BIS']."')", $_SESSION["writeServer"]);		
	if ($_POST['OTHER'] != '')
		@mysql_query("DELETE FROM blacklist_serials WHERE SERIAL='".$_POST['OTHER']."'", $_SESSION["writeServer"]);
	$form_name="affich_bios";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(36) => 'SSN',
					   $l->g(64) => 'SMANUFACTURER',
					   $l->g(65) => 'SMODEL',
					   'Type'=> 'TYPE',
					   $l->g(284) => 'BMANUFACTURER',
					   $l->g(209) => 'BVERSION',
					   $l->g(210) => 'BDATE');
	$sql="select SSN from bios WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item = mysql_fetch_object($resultDetails);	
	$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$sql="select ID from blacklist_serials where SERIAL='".textDecode($item->SSN)."'";		
	$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	if ($_SESSION["lvluser"]==SADMIN){
		if ( mysql_num_rows($result) == 1 ){	
			$tab_options['OTHER'][$l->g(36)][textDecode($item->SSN)]=textDecode($item->SSN);
			$tab_options['OTHER']['IMG']='image/red.png';	   
		}else{
			$tab_options['OTHER_BIS'][$l->g(36)][textDecode($item->SSN)]=textDecode($item->SSN);
			$tab_options['OTHER_BIS']['IMG']='image/green.png';
		}
	}
	
	//$list_fields['SUP']= 'ID';
	$list_col_cant_del[$l->g(36)]=$l->g(36);
	$default_fields= $list_fields;
	$queryDetails  = "SELECT ";
	foreach ($list_fields as $lbl=>$value){
			$queryDetails .= $value.",";		
	}
	$queryDetails  = substr($queryDetails,0,-1)." FROM bios WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";

?>