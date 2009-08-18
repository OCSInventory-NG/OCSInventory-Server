<?php
function desactive_mach_serv($list_id,$packid){
	$id_pack=found_id_pack_serv($packid);
	$sql_desactive="delete from devices where hardware_id in (".$list_id.") and name='DOWNLOAD' and IVALUE in (".implode(',',$id_pack).")";
	$res_active = mysql_query($sql_desactive, $_SESSION["writeServer"]) or die(mysql_error()); 	
	addLog($l->g(512), $l->g(876).$packid." => ".$list_id );
 }
 
 function found_id_pack_serv($packid){
 	$sql_id_pack="select ID from download_enable where fileid=".$packid." and ( group_id != '' and group_id is not null)";
 	$result = mysql_query( $sql_id_pack, $_SESSION["readServer"] );
	while($id_pack = mysql_fetch_array( $result )){
		
		$id_paquets[]=$id_pack['ID'];
		
	}
	return $id_paquets; 	
 }






//fonction qui permet de savoir 
//le nombre de serveur déjà dans un groupe
//et dans lesquels ils se trouvent
function exist_server($list_id){
	if (is_array($list_id))
	$list_id=implode(",", $list_id);
	
	$sql="SELECT count(hardware_id) c,group_id,name 
		  FROM download_servers,hardware
			where hardware.id=download_servers.group_id
			and hardware_id in (".$list_id.")
			group by group_id ";
	$res= mysql_query( $sql, $_SESSION["readServer"]);
	$msg= "";
	while( $val = mysql_fetch_array( $res ) ){
		$msg.= $val['c']." machine(s) sont déjà présentes dans le groupe de serveur ".$val['name']."<br>";
	}
	if ($msg != ""){
	 echo "<br><div align=center><font color=RED size=3><b>".$l->g(877)." <br>".$msg." ".$l->g(878)."</b></font></div><br>";	
	}
}
// fonction qui permet de supprimer des serveurs 
// d'un groupe de redistribution
function remove_list_serv($id_group,$list_id){
	if (is_array($list_id))
	$list_id=implode(",", $list_id);
	$sql_del="DELETE FROM download_servers WHERE group_id='".$id_group."' and hardware_id in (".$list_id.")";
	mysql_query( $sql_del, $_SESSION["writeServer"] ) or die( mysql_error($_SESSION["writeServer"]) );
	$cached = mysql_affected_rows($_SESSION["writeServer"]);
	return $cached;
}

function replace_var_generic($hardware_id,$url_group_server,$id_group=false)
{

	$count_add_ip=substr_count($url_group_server, '$IP$');
	$count_name=substr_count($url_group_server, '$NAME$');
	if ($count_add_ip>0 or $count_name>0){
		$sql="select IPADDR,NAME,ID from hardware where ID";
		if ($hardware_id != 'ALL'){
			$sql.=" = ".$hardware_id;
		}
		else{
			$sql.=" in (select hardware_id from groups_cache where group_id = ".$id_group.")";
		}
		$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);

		while ($item = mysql_fetch_object($resdefaultvalues))
		{
			$url_temp=str_replace('$IP$', $item -> IPADDR, $url_group_server);
			$url[$item -> ID]=str_replace('$NAME$', $item -> NAME, $url_temp);

		}
	}
	elseif($hardware_id != 'ALL')
	$url[$hardware_id]=$url_group_server;
	else{
		$sql="select ID from hardware where ID";
		$sql.=" in (select hardware_id from groups_cache where group_id = ".$id_group.")";
		$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);

		while ($item = mysql_fetch_object($resdefaultvalues))
		{
			$url[$item -> ID]=$url_group_server;
		}
	}

	return $url;

}

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
	if (is_array($list_mach)){
		foreach ($list_mach as $key=>$value){
			$reqCache = "INSERT IGNORE INTO download_servers(hardware_id, url, add_rep,GROUP_ID) 
						VALUES (".$value.",'".str_replace("\\", "\\\\", $default_values['tvalue']['DOWNLOAD_SERVER_URI'])."','".str_replace("\\", "\\\\", $default_values['tvalue']['DOWNLOAD_SERVER_DOCROOT'])."',".$id_group.")";
			$cachedRes = mysql_query( $reqCache , $_SESSION["writeServer"] ) or die( mysql_error($_SESSION["writeServer"]) );		
		}
	}else{
		$reqCache = "INSERT IGNORE INTO download_servers(hardware_id, url, add_rep,GROUP_ID) 
						select id,'".str_replace("\\", "\\\\", $default_values['tvalue']['DOWNLOAD_SERVER_URI'])."'
							     ,'".str_replace("\\", "\\\\", $default_values['tvalue']['DOWNLOAD_SERVER_DOCROOT'])."',".$id_group."
					    from hardware where id in (".$list_mach.")";
		$cachedRes = mysql_query( $reqCache , $_SESSION["writeServer"] ) or die( mysql_error($_SESSION["writeServer"]) );	
	}
	return mysql_affected_rows($_SESSION["writeServer"]);

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
	if (is_numeric($name_server))
	$idGroupServer=$name_server;
	else{
		//verification group not have the same name
		$reqGetId = "SELECT id FROM hardware WHERE name='".$name_server."'";
	     $resGetId = mysql_query( $reqGetId, $_SESSION["readServer"]);
		if( $valGetId = mysql_fetch_array( $resGetId ) )
			$idGroupServer = $valGetId['id'];
	}
	//if we are in creat new server
	if ($action == 'new_serv'){
		//if the name not exist in the base
		if (!isset($idGroupServer)){
		$deviceid='_DOWNLOADGROUP_';
		$sql="INSERT INTO hardware(deviceid,name,description,lastdate) VALUES( '$deviceid' , '".$name_server."', '".$descr."', NOW() )";
		mysql_query( $sql, $_SESSION["writeServer"] )
		or die( mysql_error($_SESSION["writeServer"]));
		//Getting hardware id
		$insertId = mysql_insert_id( $_SESSION["writeServer"] );
			exist_server($mach);
			$nb_mach=add_mach($insertId,$mach);
			return "<font color=green><b>".$l->g(880)."</b><br>".$nb_mach." ".$l->g(881)."</font>"; 			
		}else
		return "<font color=red>".$l->g(621)."</font>"; //this name allready exist 

	}//if the machines add to the group or the group is replace
	elseif ($action == 'add_serv' or $action == 'replace_serv'){
		if ($action == 'replace_serv'){
			$lblreturn="<font color=green><b> ".$l->g(879)." </b></font><br>";
			mysql_query( "DELETE FROM download_servers WHERE GROUP_ID=".$idGroupServer, $_SESSION["writeServer"] )
			or die( mysql_error($_SESSION["writeServer"]) );
		}
		exist_server($mach);
		$nb_mach=add_mach($idGroupServer,$mach);
		return $lblreturn."<font color=green><b>".$nb_mach." ".$l->g(881)."</b></font>"; 
	}elseif($action == 'del_serv'){
		$nb_mach=remove_list_serv($idGroupServer,$mach);
		return "<font color=green><b>".$nb_mach." ".$l->g(882)."</b></font>"; 
	}
}

//function for insert machine with rules
function insert_with_rules_opt($ID_HARDWARE,$cfield,$op,$compto,$serv_value){
	
	global $_POST;
	if (is_array($ID_HARDWARE))
	$list_id_hardware=implode(',',$ID_HARDWARE);
	else
	$list_id_hardware=$ID_HARDWARE;
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
	
	$sql_machValue="select ".$cfield.",".$id_mach." from ".$tablefield." where ".$id_mach." in (".$list_id_hardware.")";
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
	//	print_r($tab_final);
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
//affectation de paquets avec l'application des règles de serveur de redistribution
//$rule_detail=array($cfield[$key],$op[$key],$compto[$key]);
function insert_with_rules($list_id,$rule_detail,$fileid){
	if (is_array($list_id))
		$list_id_hardware=implode(',',$list_id);
	else
		$list_id_hardware=$list_id;
		
	if ($list_id_hardware == "")
	return ;
	//for servers
	//recherche de tous les hardware_id des servers et des id de download_enable correspondant
	$sql_infoServ="select server_id,id from download_enable where group_id != '' 
								and fileid=".$fileid;
	//echo $sql_infoServ;
	$res_infoServ = mysql_query( $sql_infoServ, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	
	//création de la liste des id_hardware des servers et d'un tableau de l'id de download_enable en fonction de l'hardware_id
	while( $val_infoServ = mysql_fetch_array($res_infoServ)) {
		$list_serverId[$val_infoServ['server_id']] = $val_infoServ['server_id'];
		$tab_Server[$val_infoServ['server_id']]=$val_infoServ['id'];
	}
	
	if ($rule_detail['compto'] == "NAME" or $rule_detail['compto'] =="WORKGROUP" or $rule_detail['compto'] =="USERID"){
		$tablecompto="hardware";
		$id_server="ID";
	}
	
	if ($rule_detail['compto'] == "IPSUBNET" or $rule_detail['compto'] == "IPADDRESS"){
		$tablecompto="networks";
		$id_server="HARDWARE_ID";
	}


	$sql_servValues = "select a.".$rule_detail['compto'].",a.".$id_server.",d.id as id_download_enable from ".$tablecompto." a,download_enable d
						 where a.".$id_server." in (".implode(',',$list_serverId).") and d.server_id=a.".$id_server." and fileid='".$fileid."'";
	$res_servValues = mysql_query( $sql_servValues, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	
	//echo $sql_servValues."<br><br>";
	while( $val_servValues = mysql_fetch_array($res_servValues)) {
		$tab_serValues[$val_servValues[$rule_detail['compto']]]=$val_servValues[$id_server];
		$correspond_servers[$val_servValues[$id_server]]=$val_servValues['id_download_enable'];
	}
	
	//for machines
	if ($rule_detail['cfield'] == "NAME" or $rule_detail['cfield'] =="WORKGROUP" or $rule_detail['cfield'] =="USERID"){
		$tablefield="hardware";
		$id_mach="ID";
	}
	if ($rule_detail['cfield'] == "IPSUBNET" or $rule_detail['cfield'] == "IPADDRESS"){
		$tablefield="networks";		
		$id_mach="HARDWARE_ID";
	}
	
	$sql_machValue="select ".$rule_detail['cfield'].",".$id_mach." from ".$tablefield." where ".$id_mach." in (".$list_id_hardware.")";
	$res_machValue = mysql_query( $sql_machValue, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	
	//print_r($tab_serValues);
	while( $val_machValue = mysql_fetch_array($res_machValue)) {
		if ($rule_detail['op'] == "EGAL"){
			
			//echo "<br>".$val_machValue[$rule_detail['cfield']]."<br>";
			//cas of egal
			if (isset($tab_serValues[$val_machValue[$rule_detail['cfield']]])){
				$tab_final[$val_machValue[$id_mach]]=$correspond_servers[$tab_serValues[$val_machValue[$rule_detail['cfield']]]];	
				$verif_idMach[$val_machValue[$id_mach]]=$val_machValue[$id_mach];
			}
			else{
				$not_match[$val_machValue[$id_mach]]=$val_machValue[$id_mach];	
				//$nb_notMatch++;		
			}		
			
			
		}
		elseif ($rule_detail['op'] == "DIFF"){
			if (!isset($tab_serValues[$val_machValue[$rule_detail['cfield']]])){
				$tab_final[$val_machValue[$id_mach]]=$correspond_servers[$tab_serValues[$val_machValue[$rule_detail['cfield']]]];	
				$verif_idMach[$val_machValue[$id_mach]]=$val_machValue[$id_mach];
			}
			else{
				$not_match[$val_machValue[$id_mach]]=$val_machValue[$id_mach];	
				//$nb_notMatch++;		
			}		

		}
		
	}
	if (isset($verif_idMach)){
		//verification des doublons
		$sql_verif="select d.hardware_id as hardware_id
			  from devices d,download_enable d_e 
			  where d.ivalue=d_e.id and fileid=".$fileid."
				AND d.HARDWARE_ID in (".implode(',',$verif_idMach).") and d.name='DOWNLOAD'";
		$res_verif = mysql_query( $sql_verif, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
		//$nb_exist=0;
		//recupération des machines en doublon
		while( $val_verif = mysql_fetch_array($res_verif)) {	
	
			//création du tableau de doublon
			$exist[$val_verif['hardware_id']]=$val_verif['hardware_id'];
			
			//suppression des doublons
			//unset($tab_final[$val_verif['hardware_id']]);
			//$nb_exist++;
		}
		//suppression des doublons pour remettre le statut a attente de notification
		if ($exist != '')
			desactive_mach_serv(implode(',',$exist),$fileid);
		//insertion en base 
		$nb_insert=0;
		foreach ($tab_final as $key=>$value){
			$query="INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$key."', 'DOWNLOAD','".$value."')";
			mysql_query( $query, $_SESSION["writeServer"] ) 
					or die(mysql_error($_SESSION["writeServer"]));	
			$insert[$key]=$value;
			$nb_insert++;	
		}	
		
	}
	$not_found=array();
	foreach($not_match as $key=>$value){
		$not_found[]=$value;		
	}
	
	$already_exist=array();
	foreach($exist as $key=>$value){
		if (!isset($insert[$key]))
		$already_exist[]=$value;
	}
	
	
	//retour des erreurs
	$don['not_match']=$not_found;
	$don['nb_not_match']=count($not_found);
	$don['exist']=$already_exist;
	$don['nb_exist']=count($already_exist);
	$don['nb_insert']=$nb_insert;
	//print_r($don);
	return $don;
	
}

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
