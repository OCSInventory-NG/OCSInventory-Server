<?php
/*
 * 
 * file of function for servers'group 
 * 
 * 
 */


//function for replace generic var ($Ip$ and $NAME$) NOT USE NOW BECAUSE MOTOR DO IT
//function replace_var_generic($hardware_id,$url_group_server,$id_group=false)
//{
//
//	$count_add_ip=substr_count($url_group_server, '$IP$');
//	$count_name=substr_count($url_group_server, '$NAME$');
//	if ($count_add_ip>0 or $count_name>0){
//		$sql="select IPADDR,NAME,ID from hardware where ID";
//		if ($hardware_id != 'ALL'){
//			$sql.=" = ".$hardware_id;
//		}
//		else{
//			$sql.=" in (select hardware_id from groups_cache where group_id = ".$id_group.")";
//		}
//		$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);
//
//		while ($item = mysql_fetch_object($resdefaultvalues))
//		{
//			$url_temp=str_replace('$IP$', $item -> IPADDR, $url_group_server);
//			$url[$item -> ID]=str_replace('$NAME$', $item -> NAME, $url_temp);
//
//		}
//	}
//	elseif($hardware_id != 'ALL')
//	$url[$hardware_id]=$url_group_server;
//	else{
//		$sql="select ID from hardware where ID";
//		$sql.=" in (select hardware_id from groups_cache where group_id = ".$id_group.")";
//		$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);
//
//		while ($item = mysql_fetch_object($resdefaultvalues))
//		{
//			$url[$item -> ID]=$url_group_server;
//		}
//	}
//
//	return $url;
//
//}

//function looking for default values of servers' var
function look_default_values()
{
	$sql="select NAME,IVALUE,TVALUE from config where NAME = 'DOWNLOAD_SERVER_URI'
			union select NAME,IVALUE,TVALUE from config where NAME = 'DOWNLOAD_SERVER_DOCROOT'";
	$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);

	while($item = mysql_fetch_object($resdefaultvalues)){
			$result['name'][$item ->NAME]=$item ->NAME;
			$result['ivalue'][$item ->NAME]=$item ->IVALUE;
			$result['tvalue'][$item ->NAME]=$item ->TVALUE;
	}
	return $result;
}

//function for add machine in server's group
function add_mach($id_group,$list_mach)
{
	$default_values=look_default_values();

	foreach ($list_mach as $key=>$value){
		$reqCache = "INSERT IGNORE INTO download_servers(hardware_id, url, add_rep,GROUP_ID) 
					VALUES (".$value.",'".str_replace("\\", "\\\\", $default_values['tvalue']['DOWNLOAD_SERVER_URI'])."','".str_replace("\\", "\\\\", $default_values['tvalue']['DOWNLOAD_SERVER_DOCROOT'])."',".$id_group.")";
		$cachedRes = mysql_query( $reqCache , $_SESSION["writeServer"] )
		or die( mysql_error($_SESSION["writeServer"]) );
		
		}

}

//function for admin server
function admin_serveur($action,$name_server,$descr,$mach) {
	global $_SESSION,$l;

	if ($action == "")
	return $l->g(663); //intern problem
	if (trim($name_server) == "")
	return $l->g(638); //name of server is empty
	if ($mach == "") 
	return $l->g(665); //no mach selected. group not creat
	//verification group not have the same name
	$reqGetId = "SELECT id FROM hardware WHERE name='".$name_server."'";
     $resGetId = mysql_query( $reqGetId, $_SESSION["readServer"]);
	if( $valGetId = mysql_fetch_array( $resGetId ) )
		$idGroupServer = $valGetId['id'];
	//if we are in creat new server
	if ($action == 'new_serv'){
		//if the name not exist in the base
		if (!isset($idGroupServer)){
		$deviceid='_DOWNLOADGROUP_';
		mysql_query( "INSERT INTO hardware(deviceid,name,description,lastdate) VALUES( '$deviceid' , '".$name_server."', '".$descr."', NOW() )", $_SESSION["writeServer"] )
		or die( mysql_error($_SESSION["writeServer"]));
		//Getting hardware id
		$insertId = mysql_insert_id( $_SESSION["writeServer"] );
			add_mach($insertId,$mach);
			return $l->g(666); 
		}else
		return $l->g(621); //this name allready exist 

	}//if the machines add to the group or the group is replace
	elseif ($action == 'add_serv' or $action == 'replace_serv'){
		if ($action == 'replace_serv'){
		mysql_query( "DELETE FROM download_servers WHERE GROUP_ID=".$idGroupServer, $_SESSION["writeServer"] )
		or die( mysql_error($_SESSION["writeServer"]) );
		}
		add_mach($idGroupServer,$mach);
		return $l->g(621);
	}
}

//function for insert machine with rules
function insert_with_rules_opt($ID_HARDWARE,$cfield,$op,$compto,$serv_value){
	
	global $_POST;
	//for servers
	//recherche de tous les hardware_id des servers et des id de download_enable correspondant
	$sql_infoServ="select server_id,id from download_enable where group_id=".$_POST['GROUP_ID']." 
								and fileid=".$_POST['TIMESTAMP'];
	$res_infoServ = mysql_query( $sql_infoServ, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	
	//création de la liste des id_hardware des servers et d'un tableau de l'id de download_enable en fonction de l'hardware_id
	while( $val_infoServ = mysql_fetch_array($res_infoServ)) {
		$list_serverId[$val_infoServ['server_id']] = $val_infoServ['server_id'];
		$tab_Server[$val_infoServ['server_id']]=$val_infoServ['id'];
	}
	
	if ($compto == "NAME" or $compto =="WORKGROUP" or $compto =="USERID"){
		$tablecompto="hardware";
		$id_server="ID";
	}
	
	if ($compto == "IPSUBNET" or $compto == "IPADDRESS"){
		$tablecompto="networks";
		$id_server="HARDWARE_ID";
	}


	$sql_servValues = "select a.".$compto.",a.".$id_server.",d.id as id_download_enable from ".$tablecompto." a,download_enable d
						 where a.".$id_server." in (".implode(',',$list_serverId).") and d.server_id=a.".$id_server;
	$res_servValues = mysql_query( $sql_servValues, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	
	//echo $sql_servValues;
	while( $val_servValues = mysql_fetch_array($res_servValues)) {
		$tab_serValues[$val_servValues[$compto]]=$val_servValues[$id_server];
		$correspond_servers[$val_servValues[$id_server]]=$val_servValues['id_download_enable'];
	}
	
	//for machines
	if ($cfield == "NAME" or $cfield =="WORKGROUP" or $cfield =="USERID"){
		$tablefield="hardware";
		$id_mach="ID";
	}
	if ($cfield == "IPSUBNET" or $cfield == "IPADDRESS"){
		$tablefield="networks";		
		$id_mach="HARDWARE_ID";
	}
	
	$sql_machValue="select ".$cfield.",".$id_mach." from ".$tablefield." where ".$id_mach." in (".implode(',',$ID_HARDWARE).")";
	$res_machValue = mysql_query( $sql_machValue, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	
	$nb_notMatch=0;
	while( $val_machValue = mysql_fetch_array($res_machValue)) {
		if ($op == "EGAL"){
			//cas of egal
			if (isset($tab_serValues[$val_machValue[$cfield]])){
				$tab_final[$val_machValue[$id_mach]]=$correspond_servers[$tab_serValues[$val_machValue[$cfield]]];	
				$verif_idMach[$val_machValue[$id_mach]]=$val_machValue[$id_mach];
			}
			else{
				$not_match[$nb_notMatch]=$val_machValue[$id_mach];	
				$nb_notMatch++;		
			}		
			
			
		}
		elseif ($op == "DIFF"){
			if (!isset($tab_serValues[$val_machValue[$cfield]])){
				$tab_final[$val_machValue[$id_mach]]=$correspond_servers[$tab_serValues[$val_machValue[$cfield]]];	
				$verif_idMach[$val_machValue[$id_mach]]=$val_machValue[$id_mach];
			}
			else{
				$not_match[$nb_notMatch]=$val_machValue[$id_mach];	
				$nb_notMatch++;		
			}		

		}
		
	}
	if (isset($verif_idMach)){
		//verification des doublons
		$sql_verif="select d.hardware_id as hardware_id
			  from devices d,download_enable d_e 
			  where d.ivalue=d_e.id and fileid=".$_POST['TIMESTAMP']."
				AND d.HARDWARE_ID in (".implode(',',$verif_idMach).") and d.name='DOWNLOAD'";
		$res_verif = mysql_query( $sql_verif, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
		$nb_exist=0;
		//recupération des machines en doublon
		while( $val_verif = mysql_fetch_array($res_verif)) {	
	
			//création du tableau de doublon
			$exist[$nb_exist]=$val_verif['hardware_id'];
			
			//suppression des doublons
			unset($tab_final[$val_verif['hardware_id']]);
			$nb_exist++;
		}
		//print_r($tab_final);
		//insertion en base 
		foreach ($tab_final as $key=>$value){
		$query="INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$key."', 'DOWNLOAD','".$value."')";
		mysql_query( $query, $_SESSION["writeServer"] ) 
					or die(mysql_error($_SESSION["writeServer"]));		
		}
		
	}
	
	
	
	//retour des erreurs
	$don['not_match']=$not_match;
	$don['nb_not_match']=$nb_notMatch;
	$don['exist']=$exist;
	$don['nb_exist']=$nb_exist;
	//print_r($tab_final);
	return $don;
	
	
	
}
////affectation de paquets avec l'application des règles de serveur de redistribution
//function insert_with_rules($ID_HARDWARE,$cfield,$op,$compto,$serv_value)
//{
//	global $_POST;
//	//construction de la requete en fonction des regles
//	if ($op == "EGAL")
//	$op="=";
//	elseif ($op == "DIFF")
//	$op="!=";
//	
//	
//	if ($cfield == "NAME" or $cfield =="WORKGROUP" or $cfield =="USERID")
//		$tablefield="hardware";		
//	if ($compto == "NAME" or $compto =="WORKGROUP" or $compto =="USERID")
//		$tablecompto="hardware";
//	
//	if ($cfield == "IPSUBNET" or $cfield == "IPADDRESS"){
//		$tablefield="networks";		
//	}
//	if ($compto == "IPSUBNET" or $compto == "IPADDRESS"){
//		$tablehardware="hardware_";
//		$tablecompto="networks";
//	}
//	
//	if ($serv_value == ""){
//		$temp_tab="info_server.";
//	}
//	else{
//		$add=" and a.".$compto." ".$op." '".$serv_value."'";
//		$op='=';
//		$temp_tab="info_server.";
//	}
//	
//	$i=0;
//	$j=0;
//	$n=0;
//	//pour chaque machine on cherche le serveur qui match
//	while ($ID_HARDWARE[$i])
//	{
//		$sql="select info_server.server_id 
//				from ".$tablefield." machine,(
//					select a.".$compto.",serv_mach.server_id 
//					from ".$tablecompto." a,(select server_id from download_enable where group_id=".$_POST['GROUP_ID']." and fileid=".$_POST['TIMESTAMP'].") serv_mach
//					where a.".$tablehardware."ID = serv_mach.server_id ".$add.") info_server 
//					where machine.".$cfield." ".$op." ".$temp_tab.$compto." 
//					and  machine.".$tablehardware."id=".$ID_HARDWARE[$i];		
//		$res = mysql_query( $sql, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
//		$match_with = mysql_fetch_array($res);
//		if ($match_with['server_id'] == ""){
//		$not_match[$j]=$ID_HARDWARE[$i];
//		$j++;
//		}else{
//			$sql="select hardware_id from devices where HARDWARE_ID=".$ID_HARDWARE[$i]." and name='DOWNLOAD' and IVALUE=(select id from download_enable where SERVER_ID=".$match_with['server_id']." and fileid=".$_POST['TIMESTAMP'].")";
//			$res = mysql_query( $sql, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
//			
//			$val = mysql_fetch_array($res);
//			if (!isset($val[0]))
//			{
//				$query="INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$ID_HARDWARE[$i]."', 'DOWNLOAD',(select id from download_enable where SERVER_ID=".$match_with['server_id']." and fileid=".$_POST['TIMESTAMP'].") )";
//			mysql_query( $query, $_SESSION["writeServer"] ) 
//				or die(mysql_error($_SESSION["readServer"]));
//			}
//			else{
//			$exist[$n]=$ID_HARDWARE[$i];
//			$n++;					
//			}
//		}
//		$i++;		
//	}
//
//	$don['not_match']=$not_match;
//	$don['nb_not_match']=$j;
//	$don['exist']=$exist;
//	$don['nb_exist']=$n;
//	return $don;	
//}
//function for verification sql req
function verification($sql){
	
	foreach ($sql as $key=>$value){
		$result = mysql_query($sql[$key]['sql'], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$item = mysql_fetch_array($result);
	//echo "sql=".$sql[$key]['sql']." item=>".$item[0];echo " condition =>".$sql[$key]['condition'];echo "<br>";
		if ((isset($item[0]) and $sql[$key]['condition'] == 'EXIST')
			or (!(isset($item[0])) and $sql[$key]['condition'] == 'NOT EXIST')){
		echo "<script>alert('".$sql[$key]['MSG_ERROR']."');</script>";
		return ;
		}
				
	}
	return "OK";
}
?>
