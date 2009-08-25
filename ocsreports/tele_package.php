<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2006
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2007/07/23 10:30:26 $$Author: plemmet $($Revision: 1.12 $)

//TODO: A TRADUIRE AVEC LE FICHIER DE LANGUE

require_once('require/function_telediff.php');
//print_r($_POST);
if( isset( $_POST["VALID_END"] ) ) {
	$sql_details=array('document_root'=>$_POST['document_root'],
					   'timestamp'=>$_POST['timestamp'],
					   'nbfrags'=>$_POST["nbfrags"],
					   'name'=>$_POST['NAME'],
					   'os'=>$_POST['OS'],
					   'description'=>$_POST['DESCRIPTION'],
					   'size'=>$_POST['SIZE']);
					   
	$info_details=array('PRI'=>$_POST['PRIORITY'],
						'ACT'=>$_POST['ACTION'],
						'DIGEST'=>$_POST['digest'],
						'PROTO'=>$_POST['PROTOCOLE'],
						'DIGEST_ALGO'=>$_POST["digest_algo"],
						'DIGEST_ENCODE'=>$_POST["digest_encod"],
						'PATH'=>$_POST['ACTION_INPUT'],
						'NAME'=>$_POST['ACTION_INPUT'],
						'COMMAND'=>$_POST['ACTION_INPUT'],
						'NOTIFY_USER'=>$_POST['NOTIFY_USER'],
						'NOTIFY_TEXT'=>$_POST['NOTIFY_TEXT'],
						'NOTIFY_COUNTDOWN'=>$_POST['NOTIFY_COUNTDOWN'],
						'NOTIFY_CAN_ABORT'=>$_POST['NOTIFY_CAN_ABORT'],
						'NOTIFY_CAN_DELAY'=>$_POST['NOTIFY_CAN_DELAY'],
						'NEED_DONE_ACTION'=>$_POST['NEED_DONE_ACTION'],
						'NEED_DONE_ACTION_TEXT'=>$_POST['NEED_DONE_ACTION_TEXT'],
						'GARDEFOU'=>"rien");
	$msg=create_pack($sql_details,$info_details);
	if ($_POST['REDISTRIB_USE'] == 1){
		$timestamp_redistrib= time();
		$server_dir=$_POST['download_rep_creat'];
		//création du fichier zip pour les serveurs de redistribution
		require_once("libraries/zip.lib.php");
		$zipfile = new zipfile();
		$rep = $_POST['document_root'].$sql_details['timestamp']."/";
		@mkdir($server_dir);
		@mkdir($server_dir.$timestamp_redistrib);
		$dir = opendir($rep);
		while($f = readdir($dir)){
		   if(is_file($rep.$f))
		     $zipfile -> addFile(implode("",file($rep.$f)),$sql_details['timestamp']."/".basename($rep.$f));
		}
		closedir($dir);
		flush();
		$handinfo = fopen( $server_dir.$timestamp_redistrib."/".$timestamp_redistrib."_redistrib.zip", "w+" );
		fwrite( $handinfo, $zipfile -> file() );
		fclose( $handinfo );
	
		//encryptage du fichier
		$digest=crypt_file($server_dir.$timestamp_redistrib."/".$timestamp_redistrib."_redistrib.zip",$_POST["digest_algo"],$_POST["digest_encod"]);
		//renommage du fichier en tmp pour utiliser la fonction de création de paquet
		rename($server_dir.$timestamp_redistrib."/".$timestamp_redistrib."_redistrib.zip", $server_dir.$timestamp_redistrib."/tmp");
		//création du fichier temporaire
		//creat_temp_file($server_dir.$sql_details['timestamp'],$server_dir.$sql_details['timestamp']."/".$sql_details['timestamp']."_redistrib.zip");
		$fSize = filesize( $server_dir.$timestamp_redistrib."/tmp");
		$sql_details=array('document_root'=>$server_dir,
					   'timestamp'=>$timestamp_redistrib,
					   'nbfrags'=>$_POST['nbfrags_redistrib'],
					   'name'=>$_POST['NAME'].'_redistrib',
					   'os'=>$_POST['OS'],
					   'description'=>'[PACK REDISTRIBUTION '.$_POST['timestamp'].']',
					   'size'=>$fSize);
					   
		$info_details=array('PRI'=>$_POST['REDISTRIB_PRIORITY'],
						'ACT'=>'STORE',
						'DIGEST'=>$digest,
						'PROTO'=>$_POST['PROTOCOLE'],
						'DIGEST_ALGO'=>$_POST["digest_algo"],
						'DIGEST_ENCODE'=>$_POST["digest_encod"],
						'PATH'=>$_POST['download_server_docroot'],//aller chercher en base le répertoire de stockage des fichiers
						'NAME'=>'',
						'COMMAND'=>'',
						'NOTIFY_USER'=>'0',
						'NOTIFY_TEXT'=>'',
						'NOTIFY_COUNTDOWN'=>'',
						'NOTIFY_CAN_ABORT'=>'0',
						'NOTIFY_CAN_DELAY'=>'0',
						'NEED_DONE_ACTION'=>'0',
						'NEED_DONE_ACTION_TEXT'=>'',
						'GARDEFOU'=>"rien");
		create_pack($sql_details,$info_details);
	}
	unset($_POST,$_SESSION['DATA_CACHE']);
	echo $msg;
}
$lign_begin="<tr height='30px' bgcolor='white'><td>";
$td_colspan2=":</td><td colspan='2'>";
$lign_end="</td></tr>";
$form_name="create_pack";
//ouverture du formulaire
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action='' enctype='multipart/form-data'>";


if (isset($_POST['valid'])){
	looking4config();
	//vérification de l'existance du fichier
	$fSize = @filesize( $_FILES["teledeploy_file"]["tmp_name"]);
	if( $fSize <= 0 and $_POST['ACTION'] != 'EXECUTE') 
		$error=$l->g(436)." ".$_FILES["teledeploy_file"]["tmp_name"];

	
	//vérification de doublon du nom
	$verifN = "SELECT fileid FROM download_available WHERE name='".$_POST["NAME"]."'";
	$resN = mysql_query( $verifN, $_SESSION["readServer"] ) or die(mysql_error());
	if( mysql_num_rows( $resN ) != 0 )
	$error=$l->g(551);
		
	if ($error){
		echo "<script language='javascript'>alert('".$error."');</script>";
		unset($_POST['valid']);
	}
	else{	
		
		//javascript pour vérifier que des chaps ne sont pas vides
		echo "<script language='javascript'>
			function verif2()
			 {
				var msg = '';
				if (document.getElementById(\"tailleFrag\").value == ''){
					 document.getElementById(\"tailleFrag\").style.backgroundColor = 'RED';
					 msg='NULL';					
				}

				if (document.getElementById(\"nbfrags\").value == ''){
					 document.getElementById(\"nbfrags\").style.backgroundColor = 'RED';
					 msg='NULL';					
				}
		
				if (document.getElementById(\"tailleFrag_redistrib\").name){
					if (document.getElementById(\"tailleFrag_redistrib\").value == ''){
					 document.getElementById(\"tailleFrag_redistrib\").style.backgroundColor = 'RED';
					 msg='NULL';
					}
					if (document.getElementById(\"nbfrags_redistrib\").value == ''){
					 document.getElementById(\"nbfrags_redistrib\").style.backgroundColor = 'RED';
					 msg='NULL';
					}
				}
				if (msg != ''){
				alert ('".$l->g(1001)."');
				return false;
				}else
				return true;			
			}
		</script>";
		
		
		
	//récupération du fichier et traitement
	$size = $_FILES["teledeploy_file"]["size"];
	//encryptage du fichier
	$digest=crypt_file($_FILES["teledeploy_file"]["tmp_name"],$_POST["digest_algo"],$_POST["digest_encod"]);
	//création du fichier temporaire
	creat_temp_file($_POST['document_root'].$_POST['timestamp'],$_FILES["teledeploy_file"]["tmp_name"]);

	$digName = $_POST["digest_algo"]. " / ".$_POST["digest_encod"];
	
	$title_creat="<tr height='30px'><td colspan='10' align='center'><b>".$l->g(435)."[".$_POST['NAME']."]</b></td></tr>";
	$name_file=$lign_begin.$l->g(446).$td_colspan2.$_FILES["teledeploy_file"]["name"].$lign_end;
	$ident=$lign_begin.$l->g(460).$td_colspan2.$_POST['timestamp'].$lign_end;
	$view_digest=$lign_begin.$l->g(461)." ".$digName.$td_colspan2.$digest.$lign_end;
	$total_ko=$lign_begin.$l->g(462).$td_colspan2.round($size/1024)." ".$l->g(516).$lign_end;
	
	//création du champ de taille de fragments
	$taille_frag=$lign_begin.$l->g(463).$td_colspan2;
	$taille_frag.= input_pack_taille("tailleFrag","nbfrags",round($size),'8',round($size/1024));
	$taille_frag.=$l->g(516).$lign_end;	
	$tps=$lign_begin.$l->g(1002).$td_colspan2;
	$tps.= time_deploy();
	$tps.=$lign_end;
		
	//création du champ de nombre de fragments
	$nb_frag=$lign_begin.$l->g(464).$td_colspan2;
	$nb_frag.= input_pack_taille("nbfrags","tailleFrag",round($size),'5','1');
	$nb_frag.=$lign_end;	
	echo "<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>";
	echo $title_creat.$name_file.$ident.$view_digest.$total_ko.$taille_frag.$nb_frag.$tps;	
	if($_POST['REDISTRIB_USE'] == 1){
		$title_creat_redistrib="<tr height='30px'><td colspan='10' align='center'><b>".$l->g(1003)."</b></td></tr>";
		//création du champ de taille de fragments
		$taille_frag_redistrib=$lign_begin.$l->g(463).$td_colspan2;
		$taille_frag_redistrib.= input_pack_taille("tailleFrag_redistrib","nbfrags_redistrib",round($size),'8',round($size/1024));
		$taille_frag_redistrib.=$l->g(516).$lign_end;	
		//création du champ de nombre de fragments
		$nb_frag_redistrib=$lign_begin.$l->g(464).$td_colspan2;
		$nb_frag_redistrib.= input_pack_taille("nbfrags_redistrib","tailleFrag_redistrib",round($size),'5','1');
		$nb_frag_redistrib.=$lign_end;		
		echo $title_creat_redistrib.$taille_frag_redistrib.$nb_frag_redistrib;
	
	}
	echo "</table>";
	echo "<br><input type='submit' name='VALID_END' id='VALID_END' OnClick='return verif2();' value='".$l->g(13)."'>";
	echo "<input type='hidden' name='digest' value='".$digest."'>";
	echo "<input type='hidden' name='SIZE' value='".$size."'>";
	}
}

//valeurs par défault;
$default_value=array('OS'=>'WINDOWS',
					 'PROTOCOLE'=>'HTTP',
					 'PRIORITY'=>'5',
					 'ACTION'=>'STORE',
					 'REDISTRIB_PRIORITY'=>'5');
//gestion des valeurs par défaut					 
if (!$_POST){
	//récupération du timestamp
	$_POST['timestamp'] = time();

	foreach ($default_value as $key=>$value)
		$_POST[$key]=$value;	
	//recherche du répertoire de création des paquets
	$sql_document_root="select tvalue from config where NAME='DOWNLOAD_PACK_DIR'";
	$res_document_root = mysql_query( $sql_document_root, $_SESSION["readServer"] );
	while( $val_document_root = mysql_fetch_array( $res_document_root ) ) {
		$document_root = $val_document_root["tvalue"];
	}
	//if no directory in base, take $_SERVER["DOCUMENT_ROOT"]
	if (!isset($document_root))
		$document_root = $_SERVER["DOCUMENT_ROOT"];
	$rep_exist=file_exists($document_root."/download/"); 
	//création du répertoire si n'existe pas
	if (!$rep_exist){
		$creat=@mkdir($document_root."/download/");	
		if (!$creat){
			echo "<font color=red size=4><b>".$document_root."/download/"."<br>".$l->g(1004).". 
					<br>".$l->g(1005)."</b></font>";	
			return;
		}
	}			
	//vérification que l'on ai les droits d'écriture sur ce répertoire
	$rep_ok=is_writable ($document_root."/download/");
	if (!$rep_ok){
		echo "<font color=red size=4><b>".$l->g(1007)." ".$document_root."/download/ ".$l->g(1004).". 
				<br>".$l->g(1005)."</b></font>";	
		return;
	}
	$_POST['document_root']=$document_root."/download/";
}
//on garde en hidden le répertoire ou sont créés les paquets de télédéploiement
echo "<input type='hidden' name='document_root' value='".$_POST['document_root']."'>	  
	 <input type='hidden' id='timestamp' name='timestamp' value='".$_POST['timestamp']."'>";

//javascript pour vérifier que des champs ne sont pas vides
echo "<script language='javascript'>
		function verif()
		 {
			var msg = '';
			champs = new Array('NAME','DESCRIPTION','OS','PROTOCOLE','PRIORITY','ACTION','ACTION_INPUT','REDISTRIB_USE');
			champs_OS = new Array('NOTIFY_USER','NEED_DONE_ACTION');
			champs_ACTION=new Array('teledeploy_file');
			champs_REDISTRIB_USE=new Array('REDISTRIB_PRIORITY');
			champs_NOTIFY_USER=new Array('NOTIFY_TEXT','NOTIFY_COUNTDOWN','NOTIFY_CAN_ABORT','NOTIFY_CAN_DELAY');
			champs_NEED_DONE_ACTION=new Array('NEED_DONE_ACTION_TEXT');
			


		
			for (var n = 0; n < champs.length; n++)
			{
				if (document.getElementById(champs[n]).value == ''){
				 document.getElementById(champs[n]).style.backgroundColor = 'RED';
				 msg='NULL';
				 }
				else
				 document.getElementById(champs[n]).style.backgroundColor = '';
			}

			for (var n = 0; n < champs_OS.length; n++)
			{
				if (document.getElementById('OS').value == 'WINDOWS' && document.getElementById(champs_OS[n]).value == ''){
				 document.getElementById(champs_OS[n]).style.backgroundColor = 'RED';
				 msg='NULL';
				 }
				else
				 document.getElementById(champs_OS[n]).style.backgroundColor = '';
			}
			for (var n = 0; n < champs_ACTION.length; n++)
			{
				var name_file=document.getElementById(champs_ACTION[n]).value;
				name_file=name_file.toUpperCase();
				if (document.getElementById(\"OS\").value == 'WINDOWS')
					var debut=name_file.length-3;
				else
					var debut=name_file.length-6;
				if (document.getElementById('ACTION').value != 'EXECUTE' && document.getElementById(champs_ACTION[n]).value == ''){
				 document.getElementById(champs_ACTION[n]).style.backgroundColor = 'RED';
				 msg='NULL';
				 }
				else if (name_file.substring(debut,name_file.length) != 'ZIP' && document.getElementById(\"OS\").value == 'WINDOWS'){
					alert('le format de fichier doit être en ZIP');
					document.getElementById(champs_ACTION[n]).style.backgroundColor = 'RED';
					msg='NULL';
				}else if (name_file.substring(debut,name_file.length) != 'TAR.GZ' && document.getElementById(\"OS\").value != 'WINDOWS'){
					alert('le format de fichier doit être en TAR.GZ');
					document.getElementById(champs_ACTION[n]).style.backgroundColor = 'RED';
					msg='NULL';
				}
				 document.getElementById(champs_ACTION[n]).style.backgroundColor = '';

			}
			
			for (var n = 0; n < champs_REDISTRIB_USE.length; n++)
			{
				if (document.getElementById('REDISTRIB_USE').value == 1 && document.getElementById(champs_REDISTRIB_USE[n]).value == ''){
				 document.getElementById(champs_REDISTRIB_USE[n]).style.backgroundColor = 'RED';
				 msg='NULL';
				 }
				else
				 document.getElementById(champs_REDISTRIB_USE[n]).style.backgroundColor = '';
			}

			for (var n = 0; n < champs_NOTIFY_USER.length; n++)
			{
				if (document.getElementById('NOTIFY_USER').value == 1 && document.getElementById(champs_NOTIFY_USER[n]).value == ''){
				 document.getElementById(champs_NOTIFY_USER[n]).style.backgroundColor = 'RED';
				 msg='NULL';
				 }
				else
				 document.getElementById(champs_NOTIFY_USER[n]).style.backgroundColor = '';
			}

			for (var n = 0; n < champs_NEED_DONE_ACTION.length; n++)
			{
				if (document.getElementById('NEED_DONE_ACTION').value == 1 && document.getElementById(champs_NEED_DONE_ACTION[n]).value == ''){
				 document.getElementById(champs_NEED_DONE_ACTION[n]).style.backgroundColor = 'RED';
				 msg='NULL';
				 }
				else
				 document.getElementById(champs_NEED_DONE_ACTION[n]).style.backgroundColor = '';
			}

			if (msg != ''){
			alert ('".$l->g(1001)."');
			return false;
			}else
			return true;			
		}
	</script>";
echo "<div ";
if ($_POST['valid'])
echo " style='display:none;'";
echo ">";
printEnTete($l->g(434));
echo "<br>";

$config_input=array('MAXLENGTH'=>255,'SIZE'=>50);
$title_creat="<tr height='30px'><td colspan='10' align='center'><b>".$l->g(438)."</b></td></tr>";
$title_user="<tr height='30px' BGCOLOR='#C7D9F5'><td align='center' colspan='10'><b>".$l->g(447)."</b></td></tr>";
$title_redistrib="<tr height='30px' BGCOLOR='#C7D9F5'><td align='center' colspan='10'><b>".$l->g(628)."</b></td></tr>";
$list_os['WINDOWS']="WINDOWS";
$list_os['LINUX']="LINUX";
$list_os['MAC']="MACOS";
$list_proto['HTTP']="HTTP";
$i=0;
while ($i<10){
	$list_prio["$i"]="$i";
	$i++;
}
$list_action['STORE']=$l->g(457);
$list_action['EXECUTE']=$l->g(456);
$list_action['LAUNCH']=$l->g(458);
$yes_no['0']=$l->g(454);
$yes_no['1']=$l->g(455);

$sous_tab_beg="<table BGCOLOR='#C7D9F5' BORDER='3'><tr><td>";
$sous_tab_end="</td></tr></table>";
$nom= $lign_begin.$l->g(49).$td_colspan2.show_modif($_POST['NAME'],'NAME',0,'',$config_input).$lign_end;
$descr=$lign_begin.$l->g(53).$td_colspan2.show_modif($_POST['DESCRIPTION'],'DESCRIPTION',1).$lign_end;
$os=$lign_begin.$l->g(25).$td_colspan2.champ_select_block($list_os,'OS',array('OS'=>'WINDOWS')).$lign_end;
$proto=$lign_begin.$l->g(439).$td_colspan2.show_modif($list_proto,'PROTOCOLE',2,'').$lign_end;
$prio=$lign_begin.$l->g(440).$td_colspan2.show_modif($list_prio,'PRIORITY',2,'').$lign_end;
$file=$lign_begin.$l->g(549).$td_colspan2."<input id='teledeploy_file' name='teledeploy_file' type='file' accept='archive/zip'>".$lign_end;

$action=$lign_begin.$l->g(443).":</td><td>".champ_select_block($list_action,'ACTION',array('EXECUTE_div','STORE_div','LAUNCH_div'))."</td><td align=center>
<div id='EXECUTE_div' style='display:none'>".$l->g(444).": </div>
<div id='STORE_div' style='display:block'>".$l->g(445).": </div>
<div id='LAUNCH_div' style='display:none'>".$l->g(446).": </div>".show_modif($_POST['ACTION_INPUT'],'ACTION_INPUT',0,'').$lign_end;
$notify_user="<tr height='30px' bgcolor='white'><td colspan='2'>".$l->g(448).":</td><td>".champ_select_block($yes_no,'NOTIFY_USER',array('NOTIFY_USER'=>1)).$lign_end;
$redistrib="<tr height='30px' bgcolor='white'><td colspan='2'>".$l->g(1008).":</td><td>".champ_select_block($yes_no,'REDISTRIB_USE',array('REDISTRIB_USE'=>1)).$lign_end;


echo "<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5' >";

echo $title_creat.$nom.$descr.$os.$proto.$prio.$file.$action;
//redistrib
echo $title_redistrib.$redistrib;

	$sql="select NAME,TVALUE from config where NAME ='DOWNLOAD_REP_CREAT'
		  union select NAME,TVALUE from config where NAME ='DOWNLOAD_SERVER_DOCROOT'";
	$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);
	while($item = mysql_fetch_object($resdefaultvalues))
			$default[$item ->NAME]=$item ->TVALUE;
	if (!$default['DOWNLOAD_REP_CREAT'])
	$default['DOWNLOAD_REP_CREAT'] = $_SERVER["DOCUMENT_ROOT"]."/download/server/";

//	if (!$default['DOWNLOAD_PRIORITY'])
//	$default['DOWNLOAD_PRIORITY'] = "5";
	if (!$_POST['REDISTRIB_REP'])
	$_POST['REDISTRIB_REP']=$default['DOWNLOAD_REP_CREAT'];
	if (!$_POST['REDISTRIB_PRIORITY'])
	$_POST['REDISTRIB_PRIORITY']=$default['DOWNLOAD_PRIORITY'];
	$redistrib_rep=$lign_begin.$l->g(829).$td_colspan2.$default['DOWNLOAD_REP_CREAT'].$lign_end;
	$redistrib_rep_distant=$lign_begin.$l->g(1009).$td_colspan2.$default['DOWNLOAD_SERVER_DOCROOT'].$lign_end;
	$redistrib_prio=$lign_begin.$l->g(440).$td_colspan2.show_modif($list_prio,'REDISTRIB_PRIORITY',2,'').$lign_end;
	echo "<tr><td colspan='3' align=center><div id='REDISTRIB_USE_div' style='display:".($_POST["REDISTRIB_USE"] == 1 ? " block" : "none")."'>";
	echo $sous_tab_beg;
		echo $redistrib_rep.$redistrib_rep_distant.$redistrib_prio;
		echo $sous_tab_end;
	echo "</div>";


//affichage de cette partie que si on est dans un systeme windows
echo "<tr><td colspan='3'>";
echo "<div id='OS_div' style='display:block'>";
echo "<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5' >";
	echo $title_user.$notify_user;

		$notify_txt=$lign_begin.$l->g(449).$td_colspan2.show_modif($_POST['NOTIFY_TEXT'],'NOTIFY_TEXT',1).$lign_end;
		$notify_count_down=$lign_begin.$l->g(450).$td_colspan2.show_modif($_POST['NOTIFY_COUNTDOWN'],'NOTIFY_COUNTDOWN',0,'',array('MAXLENGTH'=>4,'SIZE'=>4)).$l->g(511).$lign_end;
		$notify_can_abord=$lign_begin.$l->g(451).$td_colspan2.show_modif($yes_no,'NOTIFY_CAN_ABORT',2).$lign_end;
		$notify_can_delay=$lign_begin.$l->g(452).$td_colspan2.show_modif($yes_no,'NOTIFY_CAN_DELAY',2).$lign_end;
		echo "<tr><td colspan='3' align=center><div id='NOTIFY_USER_div' style='display:".($_POST["NOTIFY_USER"] == 1 ? " block" : "none")."'>";
		echo $sous_tab_beg;
		echo $notify_txt.$notify_count_down.$notify_can_abord.$notify_can_delay;
		echo $sous_tab_end;
		echo "</div></td></tr>";

	$need_done_action="<tr height='30px' bgcolor='white'><td colspan='2'>".$l->g(453).":</td><td>".champ_select_block($yes_no,'NEED_DONE_ACTION',array('NEED_DONE_ACTION'=>1)).$lign_end;
	echo $need_done_action;
	
		$need_done_action_txt=$lign_begin.$l->g(449).$td_colspan2.show_modif($_POST['NEED_DONE_ACTION_TEXT'],'NEED_DONE_ACTION_TEXT',1).$lign_end;
		echo "<tr><td colspan='3' align=center><div id='NEED_DONE_ACTION_div' style='display:".($_POST["NEED_DONE_ACTION"] == 1 ? " block" : "none")."'>";
		echo $sous_tab_beg;
		echo $need_done_action_txt;
		echo $sous_tab_end;
	echo "</div></td></tr>";
echo "</table></td></tr>";
echo "</div>";

echo "</table>";
echo "<br><input type='submit' name='valid' id='valid' value='Suivant' OnClick='return verif();' >";
echo "<input type='hidden' id='digest_algo' name='digest_algo' value='MD5'>
	  <input type='hidden' id='digest_encod' name='digest_encod' value='Hexa'>
	  <input type='hidden' id='download_rep_creat' name='download_rep_creat' value='".$default['DOWNLOAD_REP_CREAT']."'>
	  <input type='hidden' id='download_server_docroot' name='download_server_docroot' value='".$default['DOWNLOAD_SERVER_DOCROOT']."'>";
	  
echo "</form></div>";

?>

