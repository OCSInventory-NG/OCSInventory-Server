<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2007/01/26 17:05:42 $$Author: plemmet $($Revision: 1.10 $)
require_once('require/function_computors.php');
if ($_POST['FUSION']){
	//print_r($_POST);
	foreach ($_POST as $name=>$value){
		if (substr($name,0,5) == "check"){
			$list_id_fusion[]= substr($name,5);			
		}		
	}
	if (count($list_id_fusion)<2){
			echo "<script>alert('".$l->g(922)."');</script>";
	}else{
		$afus=array();
		$i=0;
		while (isset($list_id_fusion[$i])){
			$res = mysql_query("SELECT deviceid,id,lastcome FROM hardware WHERE id=".$list_id_fusion[$i], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));		
			$afus[] = mysql_fetch_array($res,MYSQL_ASSOC);	
			$i++;
		}	
		if (isset($afus))
		fusionne($afus);		
	}
			
	
}




//gestion des restrictions par profils
if ($_SESSION['mesmachines']){
	$list_id_mes_machines=computor_list_by_tag();
	if ($list_id_mes_machines=="ERROR"){
		echo $l->g(923);
		break;
	}
	$tab_id_mes_machines=explode(",", substr(substr($list_id_mes_machines, 1),0,-1));
}else{
$list_id_mes_machines="";
$tab_id_mes_machines=array();
}

	
printEnTete($l->g(199));

//-- doublon hostname
$sql_doublon['hostname'] = "select NAME val from hardware ";

if (isset($list_id_mes_machines) and $list_id_mes_machines != "")
$sql_doublon['hostname'] .= " where id in ".$list_id_mes_machines;

$sql_doublon['hostname'] .= "  group by NAME having count(NAME)>1";

//-- doublon serial number
$sql_doublon['ssn']="select SSN val from bios where SSN not in (select serial from blacklist_serials) ";
if (isset($list_id_mes_machines) and $list_id_mes_machines != "")
$sql_doublon['ssn'] .= " and hardware_id in ".$list_id_mes_machines;

$sql_doublon['ssn'].=" group by SSN having count(SSN)>1";

//-- doublon macaddresses
$sql_doublon['macaddress']="select MACADDR val from networks where MACADDR not in (select macaddress from blacklist_macaddresses) ";

if (isset($list_id_mes_machines) and $list_id_mes_machines != "")
$sql_doublon['macaddress'] .= " and hardware_id in ".$list_id_mes_machines;

$sql_doublon['macaddress'].=" group by MACADDR having count(MACADDR)>1";
//print_r($sql_doublon);
foreach($sql_doublon as $name=>$sql_value){
	$res = mysql_query( $sql_value, $_SESSION["readServer"] );
	while( $val = mysql_fetch_object( $res ) ){
		$doublon[$name][] = $val->val;
	}
}

//recherche des id des machines en doublons s�rial number
if (isset($doublon['ssn']))
$sql_id_doublon['ssn']=" select distinct hardware_id id,SSN info1 from bios where SSN in ('".implode("','",$doublon['ssn'])."')";
//recherche des id des machines en doublons macaddresses
if (isset($doublon['macaddress']))
$sql_id_doublon['macaddress']=" select distinct hardware_id id,MACADDR info1 from networks where MACADDR in ('".implode("','",$doublon['macaddress'])."')";
//echo $sql_id_doublon['ssn']."<br><br>".$sql_id_doublon['macaddress'];
//recherche des id des machines en doublons hostname
if (isset($doublon['hostname']))
$sql_id_doublon['hostname']=" select id, NAME info1 from hardware h,accountinfo a where a.hardware_id=h.id and NAME in ('".implode("','",$doublon['hostname'])."')";
//echo $sql_id_doublon['hostname'];
//doublon hostname + serial number
$sql_id_doublon['hostname_serial']="SELECT DISTINCT h.id,h.name info1,b.ssn info2
						FROM hardware h 
						LEFT JOIN bios b ON b.hardware_id = h.id 
						LEFT JOIN hardware h2 on h.name=h2.name
						LEFT JOIN  bios b2 on b2.ssn = b.ssn
						WHERE  b2.hardware_id = h2.id 
						AND h.id <> h2.id and b.ssn not in (select serial from blacklist_serials) ";
if (isset($list_id_mes_machines) and $list_id_mes_machines != "")
$sql_id_doublon['hostname_serial'] .= " and h.id in ".$list_id_mes_machines;
//doublon hostname + mac address
$sql_id_doublon['hostname_macaddress']="SELECT DISTINCT h.id,h.name info1,n.macaddr info2
						FROM hardware h 
						LEFT JOIN networks n ON n.hardware_id = h.id 
						LEFT JOIN hardware h2 on h.name=h2.name
						LEFT JOIN  networks n2 on n2.MACADDR = n.MACADDR
						WHERE  n2.hardware_id = h2.id 
						AND h.id <> h2.id and n.MACADDR not in (select macaddress from blacklist_macaddresses)";
if (isset($list_id_mes_machines) and $list_id_mes_machines != "")
$sql_id_doublon['hostname_macaddress'] .= " and h.id in ".$list_id_mes_machines;

$sql_id_doublon['macaddress_serial']="SELECT DISTINCT h.id, n1.macaddr info1, b.ssn info2 
									  FROM hardware h 
										LEFT JOIN bios b ON b.hardware_id = h.id 
										LEFT JOIN networks n1 on b.hardware_id=n1.hardware_id
										LEFT JOIN networks n2 on n1.macaddr = n2.macaddr
										LEFT JOIN bios b2 on b2.ssn = b.ssn
									  WHERE n1.hardware_id = h.id 
										AND b2.hardware_id = n2.hardware_id 
										AND b2.hardware_id <> b.hardware_id 
										AND b.ssn not in (select serial from blacklist_serials)
										AND n1.macaddr not in (select macaddress from blacklist_macaddresses)";
if (isset($list_id_mes_machines) and $list_id_mes_machines != "")
$sql_id_doublon['macaddress_serial'] .= " and h.id in ".$list_id_mes_machines;

foreach($sql_id_doublon as $name=>$sql_value){
	if ($_SESSION['DEBUG'] == 'ON')
	echo "<br><b><font color=green>".$name."</font> ==> ".$sql_value."</b><br>";
	$res = mysql_query( $sql_value, $_SESSION["readServer"] );	
	$count_id[$name] = 0;
	while( $val = mysql_fetch_object( $res ) ) {
		//on ne compte que les machines appartenant au profil connect�
		//si on est admin, on compte toutes les machines
			if (in_array ($val->id,$tab_id_mes_machines)){
				$list_id[$name][]=$val->id;
				$count_id[$name]++;
			}elseif($list_id_mes_machines == ""){
				$list_id[$name][]=$val->id;
				$count_id[$name]++;
			}
		
	}
}
$form_name='doublon';
$table_name='DOUBLON';
echo "<form name='".$form_name."' id='".$form_name."' method='post'>";
echo "<br><table BORDER='0' WIDTH = '25%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
foreach ($count_id as $lbl=>$count_value){
	echo "<tr><td align='center'>";
	switch($lbl) {
		case "hostname_serial": echo $l->g(193); break ;
		case "hostname_macaddress": echo $l->g(194); break ;
		case "macaddress_serial": echo $l->g(195); break ;
		case "hostname": echo $l->g(196); break ;
		case "ssn": echo $l->g(197); break ;
		case "macaddress": echo $l->g(198); break ;
	}
	echo  ":&nbsp;<b>";
	if ($count_value != 0)
	echo "<a href=# onclick='pag(\"".$lbl."\",\"detail\",\"".$form_name."\");' alt='".$l->g(41)."'>";
	echo $count_value;
	if ($count_value != 0)
	echo "</a>";
	echo "</b></td></tr>";
	if ($_POST['detail'] == $lbl and $count_value == 0)
	unset($_POST['detail']);
}
echo "</table><br>";
echo "<input type=hidden name=detail id=detail value='".$_POST['detail']."'>";

//affichage des d�tails
if ($_POST['detail'] != ''){
	//if ($_POST['tri2'] == "macaddr")
	
	$_SESSION['SQL_DATA_FIXE'][$table_name]['macaddr']="select HARDWARE_ID,networks.macaddr from networks where hardware_id in (".implode(',',$list_id[$_POST['detail']]).")";
	$_SESSION['SQL_DATA_FIXE'][$table_name]['serial']="select HARDWARE_ID,bios.SSN as serial from bios where hardware_id in (".implode(',',$list_id[$_POST['detail']]).")";

	//liste des champs du tableau des doublons
	$list_fields= array(TAG_LBL=>'a.TAG',
						'macaddr'=>'networks.macaddr',
						'serial'=>'bios.SSN',
//						$l->g(36)=>'b.ssn',
						$l->g(23).": id"=>'h.ID',
						$l->g(23).": ".$l->g(46)=>'h.LASTDATE',
						'NAME'=>'h.NAME',
						$l->g(82).": ".$l->g(33)=>'h.WORKGROUP',
						$l->g(23).": ".$l->g(25)=>'h.OSNAME',
						$l->g(23).": ".$l->g(24)=>'h.USERID',
						$l->g(23).": ".$l->g(26)=>'h.MEMORY',
						$l->g(23).": ".$l->g(569)=>'h.PROCESSORS',
						$l->g(23).": ".$l->g(34)=>'h.IPADDR',
						$l->g(23).": ".$l->g(53)=>'h.DESCRIPTION',
						$l->g(23).": ".$l->g(354)=>'h.FIDELITY',
						$l->g(23).": ".$l->g(820)=>'h.LASTCOME',
						$l->g(23).": ".$l->g(351)=>'h.PROCESSORN',
						$l->g(23).": ".$l->g(350)=>'h.PROCESSORT',
						$l->g(23).": ".$l->g(357)=>'h.USERAGENT',
						$l->g(23).": ".$l->g(50)=>'h.SWAP',
						$l->g(23).": ".$l->g(111)=>'h.WINPRODKEY',
						$l->g(23).": ".$l->g(553)=>'h.WINPRODID');
	$list_fields['CHECK']='h.ID';
	
	$list_col_cant_del=array('NAME'=>'NAME','CHECK'=>'CHECK');
	$default_fields=array($l->g(23).": ".$l->g(34)=>$l->g(23).": ".$l->g(34),TAG_LBL=>TAG_LBL,'NAME'=>'NAME',$l->g(23).": ".$l->g(25)=>$l->g(23).": ".$l->g(25),'CHECK'=>'CHECK');

	//on modifie le type de champs en num�ric de certain champs
	//pour que le tri se fasse correctement
	//$tab_options['TRI']['SIGNED']['a.TAG']="a.TAG";
	$queryDetails = 'SELECT ';

	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK' and $key!='macaddr' and $key!='serial'){
				$queryDetails .= $value;
				if ($tab_options['AS'][$value])
					$queryDetails .=" as ".$tab_options['AS'][$value];	
				$queryDetails .=",";
		}				
	} 
	$queryDetails=substr($queryDetails,0,-1);
	$queryDetails .= " from hardware h left join accountinfo a on h.id=a.hardware_id ";
	$queryDetails .= " where ";
	$queryDetails .= " h.id in (".implode(',',$list_id[$_POST['detail']]).") ";
	if ($tab_id_mes_machines != ""){
		$queryDetails .= "";
	}
	$tab_options['FILTRE']=array('NAME'=>'Nom','b.ssn'=>'Num�ro de s�rie','n.macaddr'=>'Adresse MAC');

	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,'95',$tab_options);
	echo "<br><input type='submit' value='".$l->g(177)."' name='FUSION'>";
	echo "<input type=hidden name=old_detail id=old_detail value='".$_POST['detail']."'>";
}



echo "</form>";	


?>
