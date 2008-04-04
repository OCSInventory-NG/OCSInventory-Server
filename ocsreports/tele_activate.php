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
//Modified on $Date: 2008-04-04 16:39:35 $$Author: airoine $($Revision: 1.13 $)

PrintEnTete($l->g(465));
//activate for server's group
if(isset($_POST["actpack"]) and $_POST['activat_option'] == "for_server")
{
	//recherche de la liste des machines qui ont déjà ce paquet
	$sqlDoub="select SERVER_ID from download_enable where FILEID= ".$_POST['actpack'];
	$resDoub = mysql_query( $sqlDoub, $_SESSION["readServer"] );	
	$listDoub="";
	//création de la liste pour les exclure de la requete
	while ($valDoub = mysql_fetch_array( $resDoub )){
		if ($valDoub['SERVER_ID'] != "")
		$listDoub.=$valDoub['SERVER_ID'].",";	
	}
	//si la liste est non null on crée la partie de la requete manquante
	if ($listDoub != ""){
	$listDoub = substr($listDoub,0,-1);
	$listDoub = " AND HARDWARE_ID not in (".$listDoub.")";
	}
	//on insert l'activation du paquet pour les serveurs du groupe
	$sql="insert into download_enable (FILEID,INFO_LOC,PACK_LOC,CERT_PATH,CERT_FILE,SERVER_ID,GROUP_ID)
			select ".$_POST['actpack'].",
			 '".$_POST['https_server']."',
			 url,
			 'INSTALL_PATH',
			 'INSTALL_PATH/cacert.pem',
			 HARDWARE_ID,
			 GROUP_ID
		 from download_servers
		 where GROUP_ID=".$_POST['id_server_add'].$listDoub;
	mysql_query( $sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
//POUR LA REPLICATION ENTRE BASE
	sleep($sleep);
//FIN
	
}

if( isset( $_POST["actpack"] ) and $_POST['activat_option'] != "for_server") {

	$proto = array("http://", "https://");
	$rien   = array("", "");
	$sub = array( $_POST["https"], $_POST["frag"] );

	$_POST["https"] = str_replace($proto, $rien, $_POST["https"]);
	$_POST["frag"] = str_replace($proto, $rien, $_POST["frag"]);
	
	$opensslOk = function_exists("openssl_open");
	if( $opensslOk )
		$httpsOk = @fopen("https://".$_POST["https"]."/".$_POST["actpack"]."/info", "r");
		
	// checking if this package contains fragments
	$reqFrags = "SELECT fragments FROM download_available WHERE fileid='".$_POST["actpack"]."'";
	$resFrags = mysql_query( $reqFrags, $_SESSION["readServer"] );	
	$valFrags = mysql_fetch_array( $resFrags );
	$fragAvail = ($valFrags["fragments"] > 0) ;
	
	if( $fragAvail )
		$fragOk = @fopen("http://".$_POST["frag"]."/".$_POST["actpack"]."/".$_POST["actpack"]."-1", "r");
	else
		$fragOk = true;

	if( !isset($_POST["conf"] )) {		
		
		if( ! $opensslOk ) 	
			echo "<br><center><font color=red><b>WARNING: OpenSSL for PHP is not properly installed.<br>Your https server validity was not checked !</b></font></center>";
		
		if( ! $httpsOk && $opensslOk ) echo "<br><center><b><font color='red'>".$l->g(466)." https://".$_POST["https"]."/".$_POST["actpack"]."/</font></b></center>";
		
		if( $httpsOk ) fclose( $httpsOk );
		
		if( ! $fragOk ) echo "<br><center><b><font color='red'>".$l->g(467)." http://".$_POST["frag"]."/".$_POST["actpack"]."/</font></b></center>";
		else if( $fragAvail ) fclose( $fragOk );		
	}
	
	if( (! $fragOk || ! $httpsOk || ! $opensslOk) && !isset($_POST["conf"]) ) {?>
		<br><center><b><font color='red'><?php echo $l->g(468);?></font></b></center>
		<form name='formserv' id='formserv' action='index.php?multi=21' method='POST'>
		<input type='hidden' name='actpack' value='<?php echo $_POST["actpack"]; ?>'>
		<input type='hidden' name='https' id='https' value='<?php echo $_POST["https"]; ?>'>
		<input type='hidden' name='frag' id='frag' value='<?php echo $_POST["frag"]; ?>'>
		<input type='hidden' name='conf' id='conf' value='OK'>		
		<center>
		<input type='submit' value='<?php echo $l->g(455);?>'>
		<input type='button' value='<?php echo $l->g(454);?>' OnClick='window.location="index.php?multi=21"'>
		</center>
		</form>
	<?php 
	}
	else {	
		//checking if corresponding available exists
		$reqVerif = "SELECT * FROM download_available WHERE fileid=".$_POST["actpack"];
		if( ! mysql_num_rows( mysql_query( $reqVerif, $_SESSION["readServer"]) )) {
			
			$infoTab = loadInfo( $_POST["https"], $_POST["actpack"] );
			
			$req1 = "INSERT INTO download_available(FILEID, NAME, PRIORITY, FRAGMENTS, OSNAME ) VALUES
			( '".$_POST["actpack"]."', 'Manual_".$_POST["actpack"]."',".$infoTab["PRI"].",".$infoTab["FRAGS"].", 'N/A' )";
			
			mysql_query( $req1, $_SESSION["writeServer"]);
		}
		
		$req = "INSERT INTO download_enable(FILEID, INFO_LOC, PACK_LOC, CERT_FILE, CERT_PATH ) VALUES
		( '".$_POST["actpack"]."', '".$_POST["https"]."', '".$_POST["frag"]."', 'INSTALL_PATH/cacert.pem','INSTALL_PATH')";
	
		mysql_query( $req, $_SESSION["writeServer"]);
		echo "<p align='center' class='text'><b>".$l->g(469)."</b></p>";

	}
}
else if( isset( $_GET["actpack"] )) {
	$reqGroupsServers = "SELECT DISTINCT name,id FROM hardware WHERE deviceid='_DOWNLOADGROUP_'";
					$resGroupsServers = mysql_query( $reqGroupsServers, $_SESSION["readServer"] );
					while( $valGroupsServers = mysql_fetch_array( $resGroupsServers ) ) {
						$groupListServers .= "<option value='".$valGroupsServers["id"]."'>".$valGroupsServers["name"]."</option>";
					}
	?>
<script language='javascript'>
	function verifServ() {
		if ( document.getElementById('https').value =="" || document.getElementById('frag').value ==""	)
			alert("<?php echo $l->g(239);?>");
		else document.getElementById('formserv').submit();
			
	}
</script>
<form name='formserv' id='formserv' action='index.php?multi=21' method='POST'>
<input type='hidden' name='actpack' value='<?php echo $_GET["actpack"]; ?>'>
<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
<tr height='30px'><td align='center' colspan='10'><b><?php echo $l->g(465);?> <?php echo $_GET["actpack"]; ?></b></td></tr>
<tr><td align='center' colspan='10'><?echo $l->g(649);?><input type='radio' name='activat_option' value='for_server' onclick="document.getElementById('DITRI_SERVER').style.display='block'; document.getElementById('activ').style.display='none';"></td></tr>
<tr><td align='center' colspan='10'><?echo $l->g(650);?><input type='radio' name='activat_option' value='default' onclick="document.getElementById('DITRI_SERVER').style.display='none'; document.getElementById('activ').style.display='block';" checked></td></tr>
</table>
<div id='DITRI_SERVER' style='display:none'>
<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
	<tr height='30px' bgcolor='#FFFFFF'><td align='left'><?echo $l->g(651);?></td><td><select id='server' name='id_server_add' ><option value=''><? echo $l->g(32); ?></option><? echo $groupListServers; ?></select></td></tr>
	<tr height='30px' bgcolor='#FFFFFF'><td align='left'><?php echo $l->g(470);?>:</td><td><input type='text' name='https_server' id='https_server'>/<?php echo $_GET["actpack"]; ?></td></tr>
	
	
	<tr height='30px' bgcolor='#FFFFFF'><td colspan=2 align=center><b><input type='submit' name='valid_server'></b></td></tr>
		
</table>
</div>

<div id='activ' style='display:block'>
<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
		<tr height='30px' bgcolor='#FFFFFF'><td align='left'><?php echo $l->g(470);?>:</td><td><input type='text' name='https' id='https'>/<?php echo $_GET["actpack"]; ?></td></tr>
		<tr height='30px' bgcolor='#F2F2F2'><td align='left'><?php echo $l->g(471);?>:</td><td><input type='text' name='frag' id='frag'>/<?php echo $_GET["actpack"]; ?></td></tr>
		<tr height='30px' bgcolor='#FFFFFF'><td align='center' colspan='10'><input type='button' OnClick='javascript:verifServ();' id='envoyer' value='<?php echo $l->g(13);?>'></td></tr>
</table>
</div>

</form>	
<?php }
else if( isset( $_GET["suppack"] )) {	
	$reqEnable = "SELECT id FROM download_enable WHERE FILEID='".$_GET["suppack"]."'";
	$resEnable = @mysql_query($reqEnable, $_SESSION["writeServer"]) or die(mysql_error());
	
	while($valEnable = mysql_fetch_array( $resEnable ) ) {
		$reqDelDevices = "DELETE FROM devices WHERE name='DOWNLOAD' AND ivalue=".$valEnable["id"];
		@mysql_query($reqDelDevices, $_SESSION["writeServer"]) or die(mysql_error());
	}
	$reqDelEnable = "DELETE FROM download_enable WHERE FILEID='".$_GET["suppack"]."'";
	@mysql_query($reqDelEnable, $_SESSION["writeServer"]) or die(mysql_error());
	$reqDelAvailable = "DELETE FROM download_available WHERE FILEID='".$_GET["suppack"]."'";
	@mysql_query($reqDelAvailable, $_SESSION["writeServer"]) or die(mysql_error());
	//looking for the directory for pack
	$sql_document_root="select tvalue from config where NAME='DOWNLOAD_PACK_DIR'";
	$res_document_root = mysql_query( $sql_document_root, $_SESSION["readServer"] );
	while( $val_document_root = mysql_fetch_array( $res_document_root ) ) {
		$document_root = $val_document_root["tvalue"];
	}
	//if no directory in base, take $_SERVER["DOCUMENT_ROOT"]
	if (!isset($document_root))
	$document_root = $_SERVER["DOCUMENT_ROOT"];
	
	
	if( ! @recursive_remove_directory( $document_root."/download/".$_GET["suppack"] ))  {
		echo "<br><center><b><font color='red'>".$l->g(472)." ".$document_root."/download/".$_GET["suppack"]."</font></b></center>";
	}
}

$lbl = "pack";	
$sql = "";
$whereId = "d.FILEID";
$linkId = "d.FILEID";
$select = array( "d.FILEID"=>"Timestamp", "NAME"=>$l->g(49), "PRIORITY"=>$l->g(440), "FRAGMENTS"=>$l->g(464), "SIZE"=>$l->g(462), "OSNAME"=>$l->g(25));	
$selectPrelim = array("d.FILEID"=>"d.FILEID");	
$from = "download_available d";
$fromPrelim = "";
$group = "";
$order = "d.FILEID DESC";
$countId = "d.FILEID";

$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,true);
ShowResults($requete,true,false,false,false,false,true);


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

function loadInfo( $serv, $tstamp ) {
	
	$fname = "https://".$serv."/".$tstamp."/info";
	$info = file_get_contents( $fname );
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

?>
<script language='javascript'>
	function manualActive() {
		if( isNaN(document.getElementById('tstamp').value) || document.getElementById('tstamp').value=="" )
			alert('<?php echo $l->g(473);?>');
		else {
			if( document.getElementById('tstamp').value.length != 10 )
				alert("<?php echo $l->g(474);?>");
			else
				window.location = 'index.php?multi=21&man=1&actpack=' + document.getElementById('tstamp').value;
		}

	}
</script>

<p class='text' align='center'><b><?php echo $l->g(476);?></b>
&nbsp;&nbsp;&nbsp;<?php echo $l->g(475);?>:<input id='tstamp' type='text' size='10'>
<a href='javascript:void(0);' OnClick='javascript:manualActive();'>
&nbsp;&nbsp;&nbsp;<img src='image/Gest_admin1.png'></a></p>



















