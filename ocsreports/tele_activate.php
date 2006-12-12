<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2006
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.4 $)

PrintEnTete($l->g(465));

if( isset( $_POST["actpack"] )) {
	
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
		<br><center><b><font color='red'><? echo $l->g(468);?></font></b></center>
		<form name='formserv' id='formserv' action='index.php?multi=21' method='POST'>
		<input type='hidden' name='actpack' value='<? echo $_POST["actpack"]; ?>'>
		<input type='hidden' name='https' id='https' value='<? echo $_POST["https"]; ?>'>
		<input type='hidden' name='frag' id='frag' value='<? echo $_POST["frag"]; ?>'>
		<input type='hidden' name='conf' id='conf' value='OK'>		
		<center>
		<input type='submit' value='<? echo $l->g(455);?>'>
		<input type='button' value='<? echo $l->g(454);?>' OnClick='window.location="index.php?multi=21"'>
		</center>
		</form>
	<?
	}
	else {	
		$req = "INSERT INTO download_enable(FILEID, INFO_LOC, PACK_LOC, CERT_FILE, CERT_PATH ) VALUES
		( '".$_POST["actpack"]."', '".$_POST["https"]."', '".$_POST["frag"]."', 'INSTALL_PATH/cacert.pem','INSTALL_PATH')";
	
		mysql_query( $req, $_SESSION["writeServer"]);
		echo "<p align='center' class='text'><b>".$l->g(469)."</b></p>";

	}
}
else if( isset( $_GET["actpack"] )) {?>
<script language='javascript'>
	function verifServ() {
		if ( document.getElementById('https').value =="" || document.getElementById('frag').value ==""	)
			alert("<? echo $l->g(239);?>");
		else document.getElementById('formserv').submit();
			
	}
</script>
<br>
	<form name='formserv' id='formserv' action='index.php?multi=21' method='POST'>
	<input type='hidden' name='actpack' value='<? echo $_GET["actpack"]; ?>'>
	<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
		<tr height='30px'><td align='center' colspan='10'><b><? echo $l->g(465);?> <? echo $_GET["actpack"]; ?></b></td></tr>
		<tr height='30px' bgcolor='#FFFFFF'><td align='left'><? echo $l->g(470);?>:</td><td><input type='text' name='https' id='https'>/<? echo $_GET["actpack"]; ?></td></tr>
		<tr height='30px' bgcolor='#F2F2F2'><td align='left'><? echo $l->g(471);?>:</td><td><input type='text' name='frag' id='frag'>/<? echo $_GET["actpack"]; ?></td></tr>
		<tr height='30px' bgcolor='#FFFFFF'><td align='right' colspan='10'><input type='button' OnClick='javascript:verifServ();' id='envoyer' value='<? echo $l->g(13);?>'></td></tr>
	</table>
	</form>	
<?}
else if( isset( $_GET["suppack"] )) {
	@mysql_query("DELETE FROM download_available WHERE FILEID='".$_GET["suppack"]."'", $_SESSION["writeServer"]) or die(mysql_error());	
	if( ! recursive_remove_directory( $_SERVER["DOCUMENT_ROOT"]."/download/".$_GET["suppack"] ))  {
		echo "<br><center><b><font color='red'>".$l->g(472)." ".$_SERVER["DOCUMENT_ROOT"]."/download/".$_GET["suppack"]."</font></b></center>";
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
$order = "";
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

?>
<script language='javascript'>
	function manualActive() {
		if( isNaN(document.getElementById('tstamp').value) || document.getElementById('tstamp').value=="" )
			alert('<? echo $l->g(473);?>');
		else {
			if( document.getElementById('tstamp').value.length != 10 )
				alert("<? echo $l->g(474);?>");
			else
				window.location = 'index.php?multi=21&man=1&actpack=' + document.getElementById('tstamp').value;
		}

	}
</script>

<p class='text' align='center'><b><? echo $l->g(476);?></b>
&nbsp;&nbsp;&nbsp;<? echo $l->g(475);?>:<input id='tstamp' type='text' size='10'>
<a href='javascript:void(0);' OnClick='javascript:manualActive();'>
&nbsp;&nbsp;&nbsp;<img src='image/Gest_admin1.png'></a></p>



















