<?php
	print_item_header($l->g(82));
		if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	if ($_POST['OTHER_BIS'] != ''){
		mysql_query("INSERT INTO blacklist_macaddresses (macaddress) value ('".$_POST['OTHER_BIS']."')", $_SESSION["writeServer"]);		
		$tab_options['CACHE']='RESET';
	}
	if ($_POST['OTHER'] != ''){
		@mysql_query("DELETE FROM blacklist_macaddresses WHERE macaddress='".$_POST['OTHER']."'", $_SESSION["writeServer"]);
		$tab_options['CACHE']='RESET';
	}
	$form_name="affich_networks";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(53) => 'DESCRIPTION',
					   $l->g(66) => 'TYPE',
					   $l->g(268) => 'SPEED',
					   $l->g(95)=> 'MACADDR',
					   $l->g(81) => 'STATUS',
					   $l->g(34) => 'IPADDRESS',
					   $l->g(208) => 'IPMASK',
					   $l->g(207)=>'IPGATEWAY',
					   $l->g(331)=>'IPSUBNET',
					   $l->g(281)=>'IPDHCP');
	if ($_SESSION["lvluser"]==SADMIN){
		//$list_fields['OTHER_GREEN']='MACADDR';
		//$list_col_cant_del['OTHER_GREEN']='OTHER_GREEN';
		//	$tab_options['LBL']['OTHER_GREEN']=$l->g(703);
		$sql="select MACADDR from networks WHERE (hardware_id=$systemid)";
		$resultDetails = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		while($item = mysql_fetch_object($resultDetails)){
			$sql="select ID from blacklist_macaddresses where macaddress='".$item->MACADDR."'";		
			$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			if (mysql_num_rows($result) == 1){
				$tab_options['OTHER'][$l->g(95)][$item->MACADDR]=$item->MACADDR;
				$tab_options['OTHER']['IMG']='image/red.png';
			}else{
				$tab_options['OTHER_BIS'][$l->g(95)][$item->MACADDR]=$item->MACADDR;
				$tab_options['OTHER_BIS']['IMG']='image/green.png';
			}
		}
	} 
	$list_col_cant_del[$l->g(34)]=$l->g(34);
	$default_fields= $list_fields;
	$queryDetails  = "SELECT ";
	foreach ($list_fields as $lbl=>$value){
			$queryDetails .= $value.",";		
	}
	$queryDetails  = substr($queryDetails,0,-1)." FROM networks WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>