<?php

echo "<script language='javascript'>
	function active(id, sens) {
		var mstyle = document.getElementById(id).style.display	= (sens!=0?\"block\" :\"none\");
	}</script>";

function javascript_pack(){
	global $_POST;
 echo "<script language='javascript'>
	function time_deploy(name,name_value,other_name,other_value){
		var tps_cycle=".$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_CYCLE_LATENCY']*$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_PERIOD_LENGTH'].";
		var nb_frag_by_cycle=".floor($_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_PERIOD_LENGTH']/$_POST['PRIORITY']).";
		if (name == 'tailleFrag'){
			var taille=name_value;
			var nb_frag=other_value;
		}
		else{
			var taille=other_value;
			var nb_frag=name_value;
		}
		var nb_cycle_for_download=nb_frag/nb_frag_by_cycle;
		var tps_cycle_for_download = nb_cycle_for_download*tps_cycle;
		var tps_frag_latency=nb_frag_by_cycle*".$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_FRAG_LATENCY']."*nb_cycle_for_download;
		var tps_period_latency=".$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_PERIOD_LATENCY']."*nb_cycle_for_download;
		var download_speed=25000;
		var tps_download_speed=taille/download_speed;
		var tps_total=tps_cycle_for_download+tps_frag_latency+tps_period_latency+tps_download_speed;
		var heure=Math.floor(tps_total/3600);
		tps_total=tps_total-heure*3600;
		var minutes=Math.floor(tps_total/60);
		tps_total=Math.floor(tps_total-minutes*60);
		var affich=heure+'h'+minutes+'m'+tps_total+'s';
		document.getElementById('TPS').value = affich;
	}

	function maj(name,other_field,siz){
		if (document.getElementById(name).value != '' &&  document.getElementById(name).value != 0){
			if ( Math.ceil(document.getElementById(name).value*1024) < siz)							
			document.getElementById(other_field).value = Math.ceil( siz / (Math.ceil(document.getElementById(name).value*1024)) );	
			else{
			document.getElementById(other_field).value = 1;
			document.getElementById(name).value=Math.ceil(siz/1024)
			}
		}else
		document.getElementById(other_field).value = '';
		time_deploy(name,document.getElementById(name).value,other_field,document.getElementById(other_field).value);
	}
	</script>";
}
function looking4config(){
	if (!isset($_SESSION['CONFIG_DOWNLOAD'])){
		$sql_config="select name,ivalue from config where name in ('DOWNLOAD_CYCLE_LATENCY',
						    'DOWNLOAD_PERIOD_LENGTH',
						    'DOWNLOAD_FRAG_LATENCY',
		    				'DOWNLOAD_PERIOD_LATENCY')";
		$res_config = mysql_query( $sql_config, $_SESSION["readServer"] );
		while ($val_config = mysql_fetch_array( $res_config ))
		$_SESSION['CONFIG_DOWNLOAD'][$val_config['name']]=$val_config['ivalue'];	
	}
}
	
	
	
function champ_select_block($name,$input_name,$input_cache)
{

		$champs="<select name='".$input_name."' id='".$input_name."'";
		$champs.=" onChange='";
		if ($input_name == "ACTION"){
			$i=0;
			while ($input_cache[$i]){
			$champs.="active(\"".$input_cache[$i]."\", false);";
			$i++;
			}
			$champs.="active(this.value + \"_div\", true);";
		}else{
			foreach ($input_cache as $key=>$value)
				$champs.="active(\"".$key."_div\", this.value==\"".$value."\");";
		}

		$champs.="'><option value=''></option>";
		foreach ($name as $key=>$value){
			$champs.= "<option value=\"".$key."\"";
			if ($_POST[$input_name] == $key )
			$champs.= " selected";
			$champs.= ">".$value."</option>";
		}
		$champs.="</select>";
		return $champs;
	
}
 
 function time_deploy(){
 	
 	$champ="<input id='TPS' name='TPS' size='10' readonly style='color:black; background-color:#e1e1e2;'> ";
 	return $champ;
 }
 
 
 
 function input_pack_taille($name,$other_field,$size,$input_size,$input_value){
	javascript_pack();
 	$champ.= "<input id='".$name."' name='".$name."' size='".$input_size."'";
	if( $size > 1024 ) { 
		$champ.= "	onKeyPress='maj(\"".$name."\",\"".$other_field."\",\"".$size."\");'
		 				onkeydown='maj(\"".$name."\",\"".$other_field."\",\"".$size."\");' onkeyup='maj(\"".$name."\",\"".$other_field."\",\"".$size."\");' value='".$input_value."'
		  				onblur='maj(\"".$name."\",\"".$other_field."\",\"".$size."\");'  onclick='maj(\"".$name."\",\"".$other_field."\",\"".$size."\");'> ";
	}else
	 	$champ.= " value=1 readonly style='color:black; background-color:#e1e1e2;'> ";
	 return $champ;
 	
 }
 
 
 
 function active_mach($list_id,$packid){
 	global $l;
 	desactive_mach($list_id,$packid);
 	$id_pack=found_id_pack($packid);
 	$sql_active="insert into devices (HARDWARE_ID, NAME, IVALUE) select ID,'DOWNLOAD','".$id_pack."' from hardware where id in (".$list_id.")";
 	$res_active = mysql_query($sql_active, $_SESSION["writeServer"]) or die(mysql_error());
	addLog($l->g(512), $l->g(601)." ".$packid." => ".$list_id );
	return( mysql_affected_rows ( $_SESSION["writeServer"] ) );

 }
 
 function desactive_mach($list_id,$packid){
 	global $l;
	$id_pack=found_id_pack($packid);
	$sql_desactive="delete from devices where hardware_id in (".$list_id.") and name='DOWNLOAD' and IVALUE=".$id_pack;
	$res_active = mysql_query($sql_desactive, $_SESSION["writeServer"]) or die(mysql_error()); 	
	addLog($l->g(512), $l->g(886)." ".$packid." => ".$list_id );
 }
 
 function found_id_pack($packid){
 	$sql_id_pack="select ID from download_enable where fileid=".$packid." and ( group_id = '' or group_id is null)";
 	$result = mysql_query( $sql_id_pack, $_SESSION["readServer"] );
	$id_pack = mysql_fetch_array( $result );
	return $id_pack['ID']; 	
 }
 
 function active_serv($list_id,$packid,$id_rule){
 	global $l;
 	require_once('function_server.php');
 	//recuperation des conditions de la règle
	$sql="select PRIORITY,CFIELD,OP,COMPTO,SERV_VALUE from download_affect_rules where rule=".$id_rule." order by PRIORITY";
	//echo $sql;
	$res_rules = mysql_query( $sql, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
	while( $val_rules = mysql_fetch_array($res_rules)) {
	$cfield[$val_rules['PRIORITY']]=$val_rules['CFIELD'];
	$op[$val_rules['PRIORITY']]=$val_rules['OP'];
	$compto[$val_rules['PRIORITY']]=$val_rules['COMPTO'];
	//$serv_value[$val_rules['PRIORITY']]=$val_rules['SERV_VALUE'];
	}
//	$i=1;
	$nb_insert=0;
	foreach ($cfield as $key=>$value)
	{
		//$i++;
		$rule_detail=array('cfield'=>$cfield[$key],'op'=>$op[$key],'compto'=>$compto[$key]);
		$result=insert_with_rules($list_id,$rule_detail,$packid);
		$nb_insert+=$result['nb_insert'];
		$m=0;
		while ($result['exist'][$m]){
			$exist[]=$result['exist'][$m];
			$m++;
		}
		$nb_exist += $result['nb_exist'];
	
		if ($result['not_match'] == "")
		break;
		else{
			unset($list_id);
			$list_id=$result['not_match'];
		}
//		echo "<br>";
//		print_r($list_id);
	}
//	print_r($result);

	if (isset($result['not_match']))
	{
		tab_list_error($result['not_match'],$result['nb_not_match']." ".$l->g(658)." ".$l->g(887)."<br>");
		//$error='YES';
	}
	
	if (isset($exist))
	{
			tab_list_error($exist,$nb_exist." ".$l->g(659)." ".$l->g(482));
			//$error='YES';
	}
//	if (!isset($error))
//	echo "<script> alert('".$l->g(558)."');</script>";
 	return $nb_insert;
 }
 
 function loadInfo( $serv, $tstamp ) {
	
	$fname = "https://".$serv."/".$tstamp."/info";
	$info =@file_get_contents( $fname );
	if( ! $info )
		return false;
		
	@preg_match_all( "/((?:\d|\w)+)=\"((?:\d|\w)+)\"/", $info, $resul );
	if( ! $resul )
		return false;
	$noms = array_flip( $resul[1] );
	foreach( $noms as $nom=>$int ) {
		$noms[ $nom ] = $resul[2][$int];
	}
	return( $noms );
}
 
function activ_pack($fileid,$https_server,$file_serv){
	global $l;
//checking if corresponding available exists
		$reqVerif = "SELECT * FROM download_available WHERE fileid=".$fileid;
		if( ! mysql_num_rows( mysql_query( $reqVerif, $_SESSION["readServer"]) )) {
			
			$infoTab = loadInfo( $https_server, $file_serv );
			if ($infoTab == ''){
				$infoTab= array("PRI"=>'10',"FRAGS"=>'0');
				
			}
			$req1 = "INSERT INTO download_available(FILEID, NAME, PRIORITY, FRAGMENTS, OSNAME ) VALUES
			( '".$fileid."', 'Manual_".$fileid."',".$infoTab["PRI"].",".$infoTab["FRAGS"].", 'N/A' )";
			mysql_query( $req1, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["readServer"]));
		}
		
		$req = "INSERT INTO download_enable(FILEID, INFO_LOC, PACK_LOC, CERT_FILE, CERT_PATH ) VALUES
		( '".$fileid."', '".$https_server."', '".$file_serv."', 'INSTALL_PATH/cacert.pem','INSTALL_PATH')";
	
		mysql_query( $req, $_SESSION["writeServer"]);
		addLog($l->g(512), $l->g(514)." ".$fileid );
		
} 
 
function activ_pack_server($fileid,$https_server,$id_server_group){
		//recherche de la liste des machines qui ont déjà ce paquet
		$sqlDoub="select SERVER_ID from download_enable where FILEID= ".$fileid;
		$resDoub = mysql_query( $sqlDoub, $_SESSION["readServer"] );	
		//création de la liste pour les exclure de la requete
		while ($valDoub = mysql_fetch_array( $resDoub )){
			if ($valDoub['SERVER_ID'] != "")
			$listDoub[]=$valDoub['SERVER_ID'];	
		}
		//si la liste est non null on crée la partie de la requete manquante
		if (isset($listDoub)){
		$listDoub = " AND HARDWARE_ID not in (".implode(',',$listDoub).")";
		}
		//on insert l'activation du paquet pour les serveurs du groupe
		$sql="insert into download_enable (FILEID,INFO_LOC,PACK_LOC,CERT_PATH,CERT_FILE,SERVER_ID,GROUP_ID)
				select ".$fileid.",
				 '".$https_server."',
				 url,
				 'INSTALL_PATH',
				 'INSTALL_PATH/cacert.pem',
				 HARDWARE_ID,
				 GROUP_ID
			 from download_servers
			 where GROUP_ID=".$id_server_group.$listDoub;
		mysql_query( $sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		
		$query="UPDATE download_available set COMMENT = '".$_POST['id_server_add']."' WHERE FILEID = ".$fileid;
		mysql_query( $query, $_SESSION["writeServer"] ) 
					or die(mysql_error($_SESSION["writeServer"]));	
}

function del_pack($fileid){
	global $l;
	//recherche de toutes les activations de ce paquet
	$reqEnable = "SELECT id FROM download_enable WHERE FILEID='".$fileid."'";
	$resEnable = @mysql_query($reqEnable, $_SESSION["readServer"]) or die(mysql_error());
	while($valEnable = mysql_fetch_array( $resEnable ) ) {
		$list_id[]=$valEnable["id"];
	}
	//suppression dans DEVICES des machines qui ont ce paquet affecté
	if ($list_id != ""){
		$reqDelDevices = "DELETE FROM devices WHERE name='DOWNLOAD' AND ivalue in (".implode(',',$list_id).")";
		@mysql_query($reqDelDevices, $_SESSION["writeServer"]) or die(mysql_error());
	}
	//suppression des activations de ce paquet
	$reqDelEnable = "DELETE FROM download_enable WHERE FILEID='".$fileid."'";
	@mysql_query($reqDelEnable, $_SESSION["writeServer"]) or die(mysql_error());

	//suppression des détails de ce paquet
	$reqDelAvailable = "DELETE FROM download_available WHERE FILEID='".$fileid."'";
	@mysql_query($reqDelAvailable, $_SESSION["writeServer"]) or die(mysql_error());
	//recherche du répertoire de création des paquets
	$sql_document_root="select tvalue from config where NAME='DOWNLOAD_PACK_DIR'";
	$res_document_root = mysql_query( $sql_document_root, $_SESSION["readServer"] );
	$val_document_root = mysql_fetch_array( $res_document_root );
	$document_root = $val_document_root["tvalue"];
	//if no directory in base, take $_SERVER["DOCUMENT_ROOT"]
	if (!isset($document_root))
	$document_root = $_SERVER["DOCUMENT_ROOT"];
	if (@opendir($document_root."/download/".$fileid)){
		//suppression de tous les fichiers correspondant au paquet
		if( ! @recursive_remove_directory( $document_root."/download/".$fileid ))  {
			echo "<br><center><b><font color='red'>".$l->g(472)." ".$document_root."/download/".$fileid."</font></b></center>";
		}
	}
	addLog($l->g(512), $l->g(888)." ".$fileid );
}

function recursive_remove_directory($directory, $empty=FALSE) {
     if(substr($directory,-1) == '/')
         $directory = substr($directory,0,-1);
     
     if(!file_exists($directory) || !is_dir($directory))
         return FALSE;
     elseif(is_readable($directory)) {     
         $handle = opendir($directory);
         while (FALSE !== ($item = readdir($handle))) {
             if($item != '.' && $item != '..') {
                 $path = $directory.'/'.$item;
                 if(is_dir($path))
				 	recursive_remove_directory($path);
                 else
                 	unlink($path);               
             }
         }
         closedir($handle);
         if($empty == FALSE) {
             if(!rmdir($directory))
                 return FALSE;
         }
     }
     return TRUE;
}

function create_pack($sql_details,$info_details){
	global $l;
	//récupération du fichier temporaire
	$fname = $sql_details['document_root'].$sql_details['timestamp']."/tmp";
	//fragmentation du paquet
	if( $size = @filesize( $fname )) {
			$handle = fopen ( $fname, "rb");			
			$read = 0;
			for( $i=1; $i<$sql_details['nbfrags']; $i++ ) {
				$contents = fread ($handle, $size / $sql_details['nbfrags'] );
				$read += strlen( $contents );
				$handfrag = fopen( $sql_details['document_root'].$sql_details['timestamp']."/".$sql_details['timestamp']."-".$i, "w+b" );
				fwrite( $handfrag, $contents );
				fclose( $handfrag );
			}	
			
			$contents = fread ($handle, $size - $read);
			$read += strlen( $contents );
			$handfrag = fopen( $sql_details['document_root'].$sql_details['timestamp']."/".$sql_details['timestamp']."-".$i, "w+b" );
			fwrite( $handfrag, $contents );
			fclose( $handfrag );
			fclose ($handle);
	
			unlink( $sql_details['document_root'].$sql_details['timestamp']."/tmp" );
		}
		
		//creation info
		$info = "<DOWNLOAD ID=\"".xml_encode($sql_details['timestamp'])."\" ".
		"PRI=\"".xml_encode($info_details['PRI'])."\" ".
		"ACT=\"".xml_encode($info_details['ACT'])."\" ".
		"DIGEST=\"".xml_encode($info_details['DIGEST'])."\" ".		
		"PROTO=\"".	xml_encode($info_details['PROTO'])."\" ".
		"FRAGS=\"".xml_encode($sql_details['nbfrags'])."\" ".
		"DIGEST_ALGO=\"".xml_encode($info_details['DIGEST_ALGO'])."\" ".
		"DIGEST_ENCODE=\"".xml_encode($info_details['DIGEST_ENCODE'])."\" ";		
		if ($info_details['ACT'] == 'STORE')
		$info .="PATH=\"".xml_encode($info_details['PATH'])."\" ";
		if ($info_details['ACT'] == 'LAUNCH')
		$info .="NAME=\"".xml_encode($info_details['NAME'])."\" ";
		if ($info_details['ACT'] == 'EXECUTE')
		$info .="COMMAND=\"".xml_encode($info_details['COMMAND'])."\" ";
		
		$info .="NOTIFY_USER=\"".xml_encode($info_details['NOTIFY_USER'])."\" ".
		"NOTIFY_TEXT=\"".xml_encode(stripslashes($info_details['NOTIFY_TEXT']))."\" ".
		"NOTIFY_COUNTDOWN=\"".xml_encode($info_details['NOTIFY_COUNTDOWN'])."\" ".
		"NOTIFY_CAN_ABORT=\"".xml_encode($info_details['NOTIFY_CAN_ABORT'])."\" ".
		"NOTIFY_CAN_DELAY=\"".xml_encode($info_details['NOTIFY_CAN_DELAY'])."\" ".
		"NEED_DONE_ACTION=\"".xml_encode($info_details['NEED_DONE_ACTION'])."\" ".		
		"NEED_DONE_ACTION_TEXT=\"".xml_encode(stripslashes($info_details['NEED_DONE_ACTION_TEXT']))."\" ".		
		"GARDEFOU=\"".xml_encode($info_details['GARDEFOU'])."\" />\n";
		
		$handinfo = fopen( $sql_details['document_root'].$sql_details['timestamp']."/info", "w+" );
		fwrite( $handinfo, $info );
		fclose( $handinfo );
		
		//suppression du paquet qui aurait le même id
		mysql_query( "DELETE FROM download_available WHERE FILEID='".$sql_details['timestamp']."'", $_SESSION["writeServer"]);
		//insertion du nouveau paquet
		$req = "INSERT INTO download_available(FILEID, NAME, PRIORITY, FRAGMENTS, SIZE, OSNAME, COMMENT) VALUES
		( '".$sql_details['timestamp']."', '".addslashes($sql_details['name'])."','".$info_details['PRI']."', '".$sql_details['nbfrags']."',
		'".$sql_details['size']."', '".$sql_details['os']."', '".xml_encode($sql_details['description'])."' )";
		mysql_query( $req, $_SESSION["writeServer"] ) or die(mysql_error($_SESSION["writeServer"]));
		addLog($l->g(512), $l->g(617)." ".$sql_details['timestamp'] );
		//message d'info
		return "<br><center><b><font color='green'>".$l->g(437)." ".$sql_details['document_root'].$sql_details['timestamp']."</font></b></center><br>";
}

function crypt_file($dir_FILES,$digest_algo,$digest_encod){
	//encryptage du fichier
	if( $digest_algo == "SHA1" )
		$digest = sha1_file($dir_FILES,true);
	else
		$digest = md5_file($dir_FILES);
	
	if( $digest_encod == "Base64" )
		$digest = base64_encode( $digest );
	return $digest;		
}

function creat_temp_file($directory,$dir_FILES){
	if (!file_exists ($directory."/tmp")){
		if (! @mkdir( $directory) 
		or !copy( $dir_FILES, $directory."/tmp" )
			)
			echo "<br><center><font color='red'><b>ERROR: can't create or write in ".$directory." folder, please refresh when fixed. <br>
			<br>(or try disabling php safe mode)</b></font></center>";
	}
}
//$val_details['priority'],$val_details['fragments'],$val_details['size']
function tps_estimated($val_details)
{
	global $l;
	if ($val_details == "")
	return;
	/*********************************DETAIL SUR LE TEMPS APPROXIMATIF DE TELEDEPLOIEMENT*****************************************/
//	$sql_config="select name,ivalue from config where name in ('DOWNLOAD_CYCLE_LATENCY',
//					    'DOWNLOAD_PERIOD_LENGTH',
//					    'DOWNLOAD_FRAG_LATENCY',
//	    				'DOWNLOAD_PERIOD_LATENCY')";
//	$res_config = mysql_query( $sql_config, $_SESSION["readServer"] );
//	while ($val_config = mysql_fetch_array( $res_config ))
//	$config[$val_config['name']]=$val_config['ivalue'];
	looking4config();
	if ($val_details['priority'] == 0)
	$val_details['priority']=1;
	//durée complète d'un cycle en seconde
	$tps_cycle=$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_CYCLE_LATENCY']*$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_PERIOD_LENGTH'];
	//nbre de téléchargement de fragment par cycle
	$nb_frag_by_cycle=floor($_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_PERIOD_LENGTH']/$val_details['priority']);
	//nombre de cycles necessaires pour le téléchargement complet
	$nb_cycle_for_download=$val_details['fragments']/$nb_frag_by_cycle;
	//temps dans le cycle
	$tps_cycle_for_download=$nb_cycle_for_download*$tps_cycle;
	//temps entre chaque fragment pour tous les cycles
	$tps_frag_latency=($nb_frag_by_cycle*$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_FRAG_LATENCY'])*$nb_cycle_for_download;
	//temps entre chaque période
	$tps_period_latency=$_SESSION['CONFIG_DOWNLOAD']['DOWNLOAD_PERIOD_LATENCY']*$nb_cycle_for_download;
	//ajout de la vitesse de téléchargement
	$download_speed=25000;
	$tps_download_speed=$val_details['size']/$download_speed;
	
	//temps total de téléchargement:
	$tps_total=$tps_cycle_for_download
				+$tps_frag_latency
				+$tps_period_latency
				+$tps_download_speed
				;
	$heure= floor($tps_total/3600);
	$tps_total-=$heure*3600;
	$minutes=floor($tps_total/60);
	$tps_total-=$minutes*60;
	//<br><br>Temps approximatif de télédéploiement :<br><font color=red>
	$tps= $heure."h ".$minutes."min ";
	if ($heure == 0 and $minutes == 0)
	$tps.=floor($tps_total)." ".$l->g(511);
	//$tps.="</b></font>";	
	return $tps;
	
}
?>
