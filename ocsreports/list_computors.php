<?php

if (isset($_GET['filtre'])){
	$_POST['FILTRE']=$_GET['filtre'];
	$_POST['FILTRE_VALUE']=$_GET['value'];	
}

//cas d'une suppression de machine
if ($_POST['SUP_PROF'] != ''){	
	deleteDid($_POST['SUP_PROF']);
	$tab_options['CACHE']='RESET';
}

if (!isset($_POST['tri2']) or $_POST['tri2'] == ""){
	$_POST['tri2']="h.lastdate";
	$_POST['sens']="DESC";
}
	$form_name="show_all";
	$table_name="list_show_all";
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields = array ( TAG_LBL   => "a.tag", 
						   $l->g(46) => "h.lastdate", 
						   $l->g(949) => "ID",
						   $l->g(24) => "h.userid",
						   $l->g(25) => "h.osname",
						   $l->g(568) => "h.memory",
						   $l->g(569) => "h.processors",
						   $l->g(33) => "h.workgroup",
						   $l->g(275) => "h.osversion",
						   $l->g(286) => "h.oscomments",
						   $l->g(350) => "h.processort",
						   $l->g(351) => "h.processorn",
						   $l->g(50) => "h.swap",
						   $l->g(352) => "lastcome",
						   $l->g(353) => "h.quality",
						   $l->g(354) => "h.fidelity",
						   $l->g(53) => "h.description",
						   $l->g(355) => "h.wincompany",
						   $l->g(356) => "h.winowner",
						   $l->g(357) => "h.useragent",
						   $l->g(64) => "e.smanufacturer",
						   $l->g(284) => "e.bmanufacturer",
						   $l->g(36) => "e.ssn",
						   $l->g(65) => "e.smodel",
						   $l->g(209) => "e.bversion",
						   $l->g(34) => "h.ipaddr",
						   $l->g(557) => "h.userdomain");
	$tab_options['FILTRE']=array_flip($list_fields);
	$tab_options['FILTRE']['h.name']=$l->g(23);
	asort($tab_options['FILTRE']); 
	$list_fields['NAME'] = "h.name";
	$reqAc = "SHOW COLUMNS FROM accountinfo";
	$resAc = mysql_query($reqAc, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($colname=mysql_fetch_array($resAc)){
		if ($colname["Field"] != 'TAG' and $colname["Field"] != 'HARDWARE_ID')
		$list_fields[$colname["Field"]]='a.'.$colname["Field"];
		
	}
	$list_col_cant_del=array('SUP'=>'SUP');
	$default_fields= array(TAG_LBL=>TAG_LBL,$l->g(46)=>$l->g(46),'NAME'=>'NAME',$l->g(23)=>$l->g(23),
							$l->g(24)=>$l->g(24),$l->g(25)=>$l->g(25),$l->g(568)=>$l->g(568),
							$l->g(569)=>$l->g(569));
	$queryDetails  = "SELECT h.id, ";
	foreach ($list_fields as $lbl=>$value){
		$queryDetails .= $value;
				$queryDetails .=",";		
	}
	$queryDetails  = substr($queryDetails,0,-1)." from hardware h 
					LEFT JOIN accountinfo a ON a.hardware_id=h.id 
					LEFT JOIN bios e ON e.hardware_id=h.id where deviceid<>'_SYSTEMGROUP_' AND deviceid<>'_DOWNLOADGROUP_' ";
	if (isset($_SESSION["mesmachines"]) and $_SESSION["mesmachines"] != '')
		$queryDetails  .= "AND ".$_SESSION["mesmachines"];
	//$queryDetails  .=" limit 200";
	$list_fields['SUP']='h.id';
	$tab_options['LBL_POPUP']['SUP']='name';
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,95,$tab_options);
	echo "</form>";

?>