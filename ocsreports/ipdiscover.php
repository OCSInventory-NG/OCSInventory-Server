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
//Modified on $Date: 2007-07-23 10:30:25 $$Author: plemmet $($Revision: 1.12 $)

@set_time_limit(0);
$nbpop=0;
$fipdisc = "ipdiscover-util.pl" ;
if( $scriptPresent = @stat($fipdisc) ) {
	$filePresent = true;
	if( ! is_executable($fipdisc) ) {
		$scriptPresent = false;
	}
	else if( ! is_writable(".") ) {
		$scriptPresent = false;
	}	
}

if( !isset( $_GET["mode"] )) {
	$_SESSION["retour"] = "multi=3";
}

$_SESSION["pas"] = isset($_GET["pas"]) ? $_GET["pas"] : $_SESSION["pas"];
$nomRez = getNameFromRes($_SESSION["pas"]);

switch( $_GET["mode"] ) {
	case 12: $_SESSION["retour"] = "multi=3&mode=12"; break;
	case 6:  $_SESSION["retour"] = "multi=3&mode=6&pas=".$_GET["pas"]."&nam".$_GET["nam"]; break;
	case 2:  $_SESSION["retour"] = "multi=3&mode=2&pas=".$_GET["pas"]."&nam".$_GET["nam"]; 
			if( $filePresent ) {
				if( ! is_executable($fipdisc) ) {
					echo "<br><center><b><font color='red'>$fipdisc ".$l->g(341)."</b></center>";
				}
				else if( ! is_writable(".") ) {
					echo "<br><center><b><font color='red'>".$l->g(342)." $fipdisc</b></center>";
				}	
			}
			break;
			
	case 8:  
			$_SESSION["direct"] = isset($_GET["direct"]) ? $_GET["direct"] : $_SESSION["direct"];
			if( $_GET["direct"] == 2 )
				$_SESSION["retour"] = "multi=3&mode=1";
			else if( isset($_GET["direct"]) ) {												
						$toPage =  $_GET["direct"] == 3 ? 2 : 6 ;
						$_SESSION["retour"] = "multi=3&mode=$toPage&pas=".$_SESSION["pas"]; break;
			}
	default;	
}

if( isset($_GET["delmac"]) ) {
	mysql_query("DELETE FROM netmap WHERE mac='".$_GET["delmac"]."'", $_SESSION["writeServer"] ) or die(mysql_error());
}
	
if( ! $scriptPresent && ! $_GET["mode"] ) {
	$scriptPresent = false ;
}

if(!isset($_GET["mode"])) {

	printEnTete($l->g(43));
	echo "<br><table width='400px' border='0' align='center'>";
	echo "<tr align='center'><td width='40px'><img src='image/Gest_admin1.png'></td><td align='left'><a href='?multi=3&mode=1'>".$l->g(289)."</a></img></td></tr>";
	if( $scriptPresent ) {
		echo "<tr align='center'><td width='40px'><img src='image/Gest_admin1.png'></td><td align='left'><a href='?multi=3&mode=7'>".$l->g(290)."</a></img></td></tr>";
	}	
	echo "<tr align='center'><td width='40px'><img src='image/Gest_admin1.png'></td><td align='left'><a href='?multi=3&mode=9'>".$l->g(107)."</a></img></td></tr></table>";
}
else if( $_GET["mode"] == 12 ) {
	printEnTete($l->g(219));
	echo "<br><center><a href=index.php?multi=3><= ".$l->g(188)."</a></center>";
	$req = "SELECT ip,mac,mask,date FROM netmap LEFT JOIN(networks) ON mac=macaddr WHERE macaddr IS NULL";
	$reqC = "SELECT COUNT(ip) FROM netmap LEFT JOIN(networks) ON mac=macaddr WHERE macaddr IS NULL";
	
	$requete = new Req($l->g(219),$req,$reqC,"","","");
	ShowResults($requete,true,false,false,false,false);
}
else if( $_GET["mode"] == 9 ) {
	$_SESSION["fromdet"] = false;
	printEnTete($l->g(107));
?>	
	<br><center><a href=index.php?multi=3><= <?php echo $l->g(188);?></a></center><br>
	<table width='400px' border='0' align='center'>
	<tr align='center'><td width='40px'><img src='image/Gest_admin1.png'></td><td align='left'><a href='?multi=3&mode=10'><?php echo $l->g(293);?></a></img></td></tr>
	<tr align='center'><td width='40px'><img src='image/Gest_admin1.png'></td><td align='left'><a href='?multi=3&mode=11'><?php echo $l->g(294);?></a></img></td></tr>
	</table>
<?php 	
}
else if( $_GET["mode"] == 11 ) {
	if( isset($_GET["ipa"]) && ! isset($_GET["self"]) ) {
		$_SESSION["fromdet"] = true;
	}
	
	if( isset( $_POST["subRez"] ) ) {
		if( $_POST["nomrez"] == "" || $_POST["dpt"] == "" || $_POST["ipa"] == "" || $_POST["ipm"] == "" ) {
			echo "<center><font color='red'><b>".$l->g(298)."</b></font></center>";
		}
		else if( ! ereg("^([0-9]{1,3}\.){3}[0-9]{1,3}$",$_POST["ipa"] )) {
			echo "<center><font color='red'><b>".$l->g(299)."</b></font></center>";
		}
		else if( (! ereg("^([0-9]{1,3}\.){3}[0-9]{1,3}$", $_POST["ipm"] )) && ((! ereg("^[0-9]{1,2}$", $_POST["ipm"] ) )||($_POST["ipm"]>32)) ) {
			echo "<center><font color='red'><b>".$l->g(300)."</b></font></center>";
		}	
		else {
			$newRez = true;
			$exist = true;
			unset($_SESSION["lastTri"]);
			$reqDelete = "DELETE FROM subnet WHERE netid ='".$_POST["ipa"]."'";
			@mysql_query( $reqDelete, $_SESSION["writeServer"] );
			$reqInsert = "INSERT INTO subnet(name, id, netid, mask) VALUES ('".$_POST["nomrez"]."', '".$_POST["dpt"]."', '".$_POST["ipa"]."','".$_POST["ipm"]."')";
			@mysql_query( $reqInsert, $_SESSION["writeServer"] );
			if(mysql_affected_rows()>0)
				echo "<br><center><font color='green'><b>".$l->g(301)." (".htmlentities(stripslashes($_POST["nomrez"]))."  ".$_POST["dpt"]."  ".$_POST["ipa"]." / ".$_POST["ipm"].")</b></font></center>";
			else
				echo "<br><center><font color='red'><b>".$l->g(362)." ".$_POST["ipa"]." ".$l->g(363)."</b></font></center>";
		}
	}
	
	if( isset($_GET["delrez"])) {
		unset($_SESSION["lastTri"]);
		$reqSupp = "DELETE FROM subnet WHERE netid='".$_GET["delrez"]."';";
		mysql_query( $reqSupp, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		echo "<center><font color='green'><b>".$l->g(302)." (netid :".$_GET["delrez"]." )</b></font></center>";
	}	
	
	$tab[0] = Array($l->g(295),$l->g(305),$l->g(34),$l->g(208));
	$tailles = Array( 300, 30, 150,150 );
	$types = Array("SORT_STRING","SORT_NUMERIC","SORT_STRING","SORT_STRING");
	
	$reqSubnet = "SELECT  name, id, netid, mask FROM subnet";
	$resSubnet = mysql_query($reqSubnet, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	while( $valSubnet = mysql_fetch_array($resSubnet) ) {
		$tab[] = array( "<a href=index.php?multi=3&self=1&mode=11&ipa=".urlencode($valSubnet["netid"])."&nomrez=".urlencode($valSubnet["name"])."&dpt=".urlencode($valSubnet["id"])."&ipm=".urlencode($valSubnet["mask"]).">".$valSubnet["name"]."</a>",
			$valSubnet["id"],$valSubnet["netid"],  $valSubnet["mask"] ,
		"<a href='index.php?multi=3&mode=11&tri=".$_GET["tri"]."&delrez=".urlencode($valSubnet["netid"])."'><img src=image/supp.png></a>");
		$exist = true;
	}
	
	printEnTete($l->g(303));
	$tri = isset($_GET["tri"])?$_GET["tri"]:$_POST["tri"];
	$ValNomRez =  stripslashes(isset($_POST["nomrez"]) ? $_POST["nomrez"] : (isset($_GET["nomrez"]) ? $_GET["nomrez"] : "")) ;
	$ValDpt    =  isset($_POST["dpt"])? $_POST["dpt"] : (isset($_GET["dpt"]) ? $_GET["dpt"] : "") ;
	$ValIpa    =  isset($_POST["ipa"])? $_POST["ipa"] : (isset($_GET["ipa"]) ? $_GET["ipa"] : "") ;
	$ValIpm    =  isset($_POST["ipm"])? $_POST["ipm"] : (isset($_GET["ipm"]) ? $_GET["ipm"] : "") ;
	?>
	<br><form name='formip' action='index.php?multi=3&mode=11' method='POST'>
	<table align='center'>
		<tr><td><?php echo $l->g(304);?> :</td><td><input type='text' size='50' name='nomrez' value="<?php echo htmlentities($ValNomRez);?>"></td><td>&nbsp;&nbsp;
		<?php echo $l->g(305);?> :</td><td><input type='text' size='3' name='dpt' value='<?php echo $ValDpt;?>'></td></tr>
		</tr><tr><td><?php echo $l->g(34);?> :</td><td><input type='text' name='ipa' value='<?php echo $ValIpa;?>'</td><td>&nbsp;&nbsp;<?php echo $l->g(208);?>:
		</td><td><input type='text' name='ipm' value='<?php echo $ValIpm;?>'></td></tr>
		<tr><td align='right' colspan='4'><input type='submit' name='subRez' value='<?php echo $l->g(13);?>'>
		<input type='hidden' name='tri' value='<?php echo $tri;?>'></td></tr>
		</tr>
	</table>
	</form>
	<br><center><a href=index.php?multi=3&mode=<?php echo $_SESSION["fromdet"]==true ? "1" : "9" ; ?>><= <?php echo $l->g(188);?></a></center>
	<?php 
	if( $exist ) {
		printEnTete($l->g(306));
		echo "<br>";
		$toPrint = $tab;
		
		if( $tri != "" )
			$toPrint = trieTab($tab,$tri,$types );
			
		printTab($toPrint,false,$tailles);
	}
	
}
else if( $_GET["mode"] == 10 ) {
	printEnTete($l->g(307));
	
	if( isset( $_POST["subTyp"]) && $_POST["nomtyp"]!="" ) {
		$val = addslashes( $_POST["nomtyp"] );
		unset($_SESSION["lastTri"]);
		$reqAddTyp = "INSERT INTO devicetype(name) VALUES('$val')";
		mysql_query( $reqAddTyp , $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}
	
	if( isset( $_GET["deltyp"] )) {
		$reqDelTyp = "DELETE FROM devicetype WHERE name='".urldecode($_GET["deltyp"])."'";
		mysql_query( $reqDelTyp, $_SESSION["writeServer"] ) or die(mysql_error($_SESSION["writeServer"]));
	}
		
	?>	
	<br><center><a href='index.php?multi=3<?php echo ($scriptPresent?"&mode=9":""); ?>'><= <?php echo $l->g(188);?></a></center><br>
	<br><form name='formip' action='index.php?multi=3&mode=10' method='POST'>
	<table align='center'>
		<tr><td><?php echo $l->g(308);?> :</td><td><input type='text' size='50' name='nomtyp'></td></tr>
		<tr><td align='right' colspan='4'><input type='submit' name='subTyp' value='<?php echo $l->g(13);?>'></td></tr>
	</table>
	</form>

	<?php 
	$reqTypes = "SELECT DISTINCT(name) FROM devicetype";
	$resType = mysql_query( $reqTypes, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
	$tab[0] = Array("Type","");
	$tailles = Array(200,30);
	$cptligne = 1;
	while( $valType = mysql_fetch_array($resType) ) {
		$tab[] = Array( stripslashes($valType["name"]) , "<a href='index.php?multi=3&mode=10&deltyp=".urlencode($valType["name"])."'><img src=image/supp.png></a>");
		$cptligne++;
		$exist = true;
	}
	if( $exist ) {
		printEnTete($l->g(309));
		echo "<br>";
		printTab($tab,false,$tailles);
	}

}
// Page with all detailed networks
else if( $_GET["mode"] == 1 ) {
	
	$reqIpConf = "SELECT ivalue FROM config WHERE name='IPDISCOVER'";
	$resIpConf = mysql_query( $reqIpConf, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
	$valIpConf = mysql_fetch_array( $resIpConf );
	$maxIpConfig = $valIpConf["ivalue"];
	
	if(isset($_POST["maxipd"])) {
		if( $_POST["maxipd"] == $l->g(215) ) {
			unset( $_POST["maxipd"] );
			unset( $_SESSION["maxipd"] );
		}
		else {
			$_SESSION["maxipd"] = $_POST["maxipd"];
		}
	}
	else
		$_SESSION["maxipd"] = $maxIpConfig;
	
	$totNinvReq = "SELECT COUNT(DISTINCT mac) as total FROM netmap WHERE mac NOT IN (SELECT DISTINCT(macaddr) FROM networks)";
	$totNinvRes = mysql_query( $totNinvReq, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$totNinvVal = mysql_fetch_array( $totNinvRes );

	$t[0]    = Array("",$l->g(295),"","Uid","",$l->g(34),"",$l->g(364),"",$l->g(365),"",$l->g(312),"",$l->g(366));
	$types   = Array("","SORT_STRING","","SORT_NUMERIC","","SORT_STRING","","SORT_NUMERIC","","SORT_NUMERIC","","SORT_NUMERIC","","SORT_NUMERIC");
	$tailles = Array( 0,250,0,50,0,250,0,70,0,70,0,70,0,70);	
	
	$cptL = 1;
	if( isset($_GET["uid"]) && is_numeric($_GET["uid"]) ) {
		$dpt = $_GET["uid"];
	}
	else {
		$dpt = $_COOKIE["DefNetwork"];
	}
		
	if( !isset($dpt) || $_GET["uid"]==$l->g(215) || ( !isset($_GET["uid"]) && $_COOKIE["DefNetwork"]==$l->g(215)) ) {
		$reqGateway = "SELECT ipsubnet as nbrez, COUNT(hardware_id) AS nbc FROM networks WHERE ipsubnet<>'0.0.0.0' AND description NOT LIKE '%PPP%' GROUP BY(ipsubnet)";
		$strEnTete = $l->g(289)."<br><br>(<font color='red'>".$totNinvVal["total"]."</font> ".$l->g(219).")";
		$tout = true;
		$dpt = -1;
	}
	else {				
		$totNinvReqLoc = "
			SELECT COUNT(DISTINCT mac) AS total 
			FROM netmap n 
			LEFT OUTER JOIN networks        ns ON ns.macaddr = mac 
			LEFT OUTER JOIN network_devices nd ON nd.macaddr = mac
			INNER      JOIN subnet          s  ON s.netid    = n.netid 
			WHERE s.id='$dpt'
			AND ns.macaddr IS NULL 
			AND nd.macaddr IS NULL;
		";

		$totNinvResLoc = mysql_query( $totNinvReqLoc, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$totNinvValLoc = mysql_fetch_array( $totNinvResLoc );
		
		echo "<center><b></b></center><br>";
		$reqGateway = "SELECT ipsubnet as nbrez, COUNT(hardware_id) AS nbc FROM networks n,subnet s
		WHERE ipsubnet<>'0.0.0.0' AND description NOT LIKE '%PPP%' AND n.ipsubnet=s.netid AND s.id = '$dpt' GROUP BY(ipsubnet) ";
		
		$strEnTete =  $l->g(562)." ".$dpt."<br>";
		$strEnTete .= "<br>(<font color='red'>".$totNinvValLoc["total"]."</font> ".$l->g(219).")";
	}	
	printEnTete($strEnTete);

	echo "<table align='center' width='30%'><tr><td><center><a href=index.php?multi=3><= ".$l->g(188)."</a></center></td>";
	
	echo "<td width='50%'><table width='100%' align='center'><tr><td align='center'><b>".$l->g(305).":</b></td></tr>
		<tr><td align='center'><form id='formDpt' name='formDpt' action='index.php' method='GET'>
		<input type='hidden' name='multi' value='3'>
		<input type='hidden' name='mode' value='1'>
		<select name='uid' onchange='document.getElementById(\"formDpt\").submit();'>
		<option".($tout?" selected":"").">".$l->g(215)."</option>";
	$reqDropDown = "SELECT DISTINCT(id) FROM subnet ORDER BY id";
	$resDropDown = mysql_query( $reqDropDown, $_SESSION["readServer"] );
	while( $valDropDown = mysql_fetch_array( $resDropDown ) ) {
		echo "<option".($valDropDown["id"]==$dpt?" selected":"")." value='".$valDropDown["id"]."'>".$valDropDown["id"]."</option>";
	}
	echo "</select></form></td></tr></table></td></tr>";	
	echo "</table>";
	$auMoinsUnRezo = false;
	$resGateway = mysql_query($reqGateway, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	

	while( $arrGateway = mysql_fetch_array($resGateway) ) {
		$auMoinsUnRezo = true;
		$resIpd = mysql_query("SELECT COUNT(*) AS nbi FROM devices WHERE name='IPDISCOVER' AND tvalue='".$arrGateway["nbrez"]."'", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$arrIpd = mysql_fetch_array( $resIpd );

		if( ! ereg("^([0-9]{1,3}\.){3}[0-9]{1,3}$",$arrGateway["nbrez"]) ) {
			continue ;
		}
		$masque = getMask( $arrGateway["nbrez"] ) ;							

		$reqSubnet = "SELECT name,id,mask FROM subnet WHERE NETID='".$arrGateway["nbrez"]."'";
		$resSubnet = mysql_query($reqSubnet, $_SESSION["readServer"]) or die(mysql_error());
		if( $valSubnet = mysql_fetch_array( $resSubnet ) ) {			
			$t[ $cptL ][] = "";
			$t[ $cptL ][] = "<a href=index.php?multi=3&mode=11&ipa=".urlencode($arrGateway["nbrez"])."&nomrez=".urlencode($valSubnet["name"])."&dpt=".urlencode($valSubnet["id"])."&ipm=".urlencode($valSubnet["mask"]).">".$valSubnet["name"]."</a>";
			$t[ $cptL ][] = "";
			$t[ $cptL ][] = $valSubnet["id"];
		}
		else {				
			$t[ $cptL ][] = "";
			$t[ $cptL ][] = "<a href=index.php?multi=3&mode=11&ipa=".$arrGateway["nbrez"].">-> ".$l->g(295)." <-</a>";
			$t[ $cptL ][] = "";
			$t[ $cptL ][] = "-";
		}
		
		$t[ $cptL ][] = "";
		$t[ $cptL ][] = $arrGateway["nbrez"];
		
		$t[ $cptL ][] = popup("multi=3&mode=4&pas=".urlencode($arrGateway["nbrez"]));
		$t[ $cptL ][] = $arrGateway["nbc"];
		
		$reqNonInv = "SELECT COUNT(*) AS nbnoninv FROM netmap WHERE NETID='".$arrGateway["nbrez"]."' 
		AND mac NOT IN (SELECT DISTINCT(macaddr) FROM networks WHERE macaddr IS NOT NULL) AND mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices)";
		
		$resNonInv = mysql_query($reqNonInv, $_SESSION["readServer"]) or die(mysql_error());
		
		if( $valNonInv = mysql_fetch_array( $resNonInv ) ) {	
			if( $valNonInv["nbnoninv"] > 0)
				$t[ $cptL ][] = popup("multi=3&mode=2&pas=".$arrGateway["nbrez"]);
			else
				$t[ $cptL ][] = "";
				
			$t[ $cptL ][] = $valNonInv["nbnoninv"]."</a>";
		}
		else {
			$t[ $cptL ][] = "";
			$t[ $cptL ][] = "-";
		}
		
		if($arrIpd["nbi"]>0) {
			$t[ $cptL ][] = popup("multi=3&mode=5&pas=".urlencode($arrGateway["nbrez"]));
			$t[ $cptL ][] = $arrIpd["nbi"]."</a>";
		}
		else {
			$t[ $cptL ][] = "" ;
			$t[ $cptL ][] = "0" ;
		}

		$reqSais = "SELECT COUNT(id) as nbSais FROM network_devices n,netmap a WHERE a.netid='".$arrGateway["nbrez"]."' AND n.macaddr=a.mac";
		
		$resSais = mysql_query($reqSais, $_SESSION["readServer"]) or die(mysql_error());
		if( ($valSais = mysql_fetch_array( $resSais )) && ($valSais["nbSais"] > 0)) {
			$t[ $cptL ][] = popup("multi=3&mode=8&direct=2&pas=".urlencode($arrGateway["nbrez"]));
			$t[ $cptL ][] = $valSais["nbSais"]."</a>";
		}
		else {
			$t[ $cptL ][] = "" ;
			$t[ $cptL ][] = "0" ;
		}
		
		$cptL++;
	}	
	$toPrint = $t;
	
	if( $auMoinsUnRezo ) {
		if( isset($_GET["tri"] ) )
			$toPrint = trieTab($t,$_GET["tri"],$types );			
		
		printTab($toPrint,false,$tailles,true,true);
	}
	else
		echo "<div align=center><b>".$l->g(42)."</b></div>";
}
else if( $_GET["mode"] == 4 ) {

	$strEnTete = $l->g(367)." ".$_GET["pas"]."<br><br>".$nomRez;
	printEnTete($strEnTete);
	//echo "<br><center><a href=index.php?multi=3&mode=1><= ".$l->g(188)."</a></center><br>";
	if($_GET["pas"] == ""){
		$finSubnet = "is null";
	}
	else
		$finSubnet = "='".$_GET["pas"]."'";

	$lbl = $l->g(313)." ".$_GET["pas"];	
	$sql = "n.ipsubnet $finSubnet";
	$whereId = "n.id";
	$linkId = "n.id";
	$select = array_merge( $_SESSION["currentFieldList"], array("h.id"=>"h.id", "h.deviceid"=>"deviceid","n.ipmask"=>$l->g(208),"n.ipgateway"=>$l->g(207),"quality"=>$l->g(353),"fidelity"=>$l->g(354)) );	
	$selectPrelim = array( "n.id"=>"n.id" );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id LEFT JOIN networks n ON n.hardware_id=h.id";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "h.id";
	
	$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,NULL,true);
	ShowResults($requete,true,false,false,true);
}
else  if( $_GET["mode"] == 5 ) {

	$strEnTete = $l->g(367)." ".$_GET["pas"]."<br><br>".$nomRez;
	printEnTete($strEnTete);
	//echo "<br><center><a href=index.php?multi=3&mode=1><= ".$l->g(188)."</a></center><br>";
	if($_GET["pas"] == ""){
		$finGateway = "is null";
	}
	else
		$finGateway = "='".$_GET["pas"]."'";
	
	$lbl = $l->g(314)." ".$_GET["pas"];	
	$sql = "d.name='IPDISCOVER' AND (d.ivalue=1||d.ivalue=2) AND d.tvalue ='".$_GET["pas"]."'";
	$whereId = "d.name='IPDISCOVER' AND h.id";
	$linkId = "h.id";
	$select = array_merge( array("h.id"=>"h.id", "h.deviceid"=>"deviceid","quality"=>$l->g(353),"fidelity"=>$l->g(354)), $_SESSION["currentFieldList"] );	
	$selectPrelim = array( "h.id"=>"h.id" );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id LEFT JOIN devices d ON d.hardware_id = h.id";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "h.id";
	
	$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,NULL,true);
	ShowResults($requete,true,false,false,true);
}
// Network analyze
else  if( $_GET["mode"] == 3 ) {
	
	$tabBalises = Array("LABEL","UID","NETNAME","NETNUMBER");
	printEnTete($l->g(315));
	$buf = runCommand();
	$ret = getXmlFromBuffer($buf, "NETWORK", $tabBalises );	 
	$ret[0] = Array($l->g(295),$l->g(305),$l->g(82),$l->g(55));
	$tailles = Array( "300", "20", "200", "40");		
	
	$total = 0;
	for( $li = 1 ; $li < sizeof( $ret ) ; $li++ ) {
		$link = "<a href=index.php?multi=3&mode=2&pas=".$ret[$li][2]."&nam=".urlencode($ret[$li][0]).">";
		$total += $ret[$li][3];
		for( $c = 0 ; $c < sizeof( $ret[ $li ] ) ; $c++ ) {				
			$ret[$li][$c] = $link.$ret[$li][$c]."</a>";
		}		
	}
	
	echo "<center><b>".$l->g(87)." $total</b></center>";
	echo "<br><center><a href=index.php?multi=3><= ".$l->g(188)."</a></center>";
	echo "<br><br>";
	printTab($ret,FALSE,$tailles);
}
else  if( $_GET["mode"] == 2 ) {
	
	printEnTete($l->g(316)." ".$_GET["pas"]."<br><br>".$nomRez);
?>
	<br><center><form name='analyse' action='index.php' method='GET'>
<?php 
if($scriptPresent) {
?>
	<input type='submit' name='subbutton' value='<?php echo $l->g(317);?>'>
<?php }?>
	<input type='hidden' name='multi' value='3'>
	<input type='hidden' name='mode' value='6'>
	<input type='hidden' name='pas' value='<?php echo $_GET["pas"]?>'>
	<input type='hidden' name='popup' value='1'>
	</form>
	</center>
<?php 
	$reqRez = "SELECT ip, mac, mask, date, name FROM netmap WHERE netid='".$_GET["pas"]."' AND mac NOT IN (SELECT DISTINCT(macaddr) FROM networks) 
	AND mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices)";
	$resRez = mysql_query( $reqRez, $_SESSION["readServer"] ) or die(mysql_error());
	$_SESSION["forcedRequest"] = $reqRez;
	echo "<center><a href='ipcsv.php' target=_blank>(".$l->g(183).")</a></center><br>";
	$t[0]    = Array($l->g(34),$l->g(95),$l->g(318),$l->g(232),$l->g(563));
	$types   = Array("SORT_STRING","SORT_STRING","SORT_STRING","SORT_STRING","SORT_STRING");
	$cptL = 1;
	while( $valRez = mysql_fetch_array($resRez) )  {
		$t[ $cptL ] = array($valRez["ip"],$valRez["mac"],$valRez["name"],$valRez["date"],getConstructor($valRez["mac"]));
		$cptL++;
	}
	
	if( isset($_GET["delmac"]) )
		$_SESSION["lastTri"] = "";
	
	if( isset($_SESSION["triEnreg"]) && ! isset($_GET["tri"])) {
		$_GET["tri"] = $_SESSION["triEnreg"];
	}
	
	if( isset($_GET["tri"] ) ) {
		$tri = trieTab($t,$_GET["tri"],$types );
		$_SESSION["triEnreg"] = $_GET["tri"];
	}
	else
		$tri = $t;

	printTab($tri,true,null,false,false,true);
	
}
//ANALYZE MODE
else  if( $_GET["mode"] == 6 ) {
	
	$pas = isset( $_GET["pas"] ) ? $_GET["pas"] : $_POST["pas"];
	$rez = $nomRez;
	printEnTete($l->g(319)." $pas<br><br>$rez");
	echo "<br><center><a href='index.php?popup=1&multi=3&mode=2&pas=$pas'><= ".$l->g(188)."</a></center>";
	$fname = "ipd/$pas.ipd";
	$buf = "";
	if( $_GET["nocache"] != 1 || $_SESSION["fromEnreg"] )
		if( $hndl = @fopen ( $fname , "r" ) ) {
			
			$dateF = filemtime( $fname );
			$dte = date( "j/m/Y \à H:m:s ", $dateF );
			$txt = $l->g(320). " $dte, ".$l->g(321);
echo <<<END
			<script language='javascript'>
				if ( !confirm('$txt') ) {
					window.location = 'index.php?multi=3&mode=6&popup=1&nocache=1&pas=$pas&nam=$nam';
				}
			</script>
END;
			flush();
			if( filesize ($fname) == 0 )
				unlink( $fname );
			else {	
				$buf = fread($hndl, filesize ($fname));
				echo "<br><center><font color=red><b>".$l->g(322)." (".$l->g(323)." $dte)</b></font></center>";
				
				fclose($hndl);
			}
		}
	$_SESSION["fromEnreg"] = false;
	$tabBalises = Array("IP","MAC","NAME","DATE","TYPE");
	
	if( $buf == "" ) {
		$buf = runCommand("-cache -net=".$_GET["pas"]);
	}
		
	$ret = getXmlFromBuffer($buf, "HOST" , $tabBalises);
	$ret[0] = Array($l->g(34),$l->g(95),$l->g(318)."/NetBIOS",$l->g(232),$l->g(563));
	
	if( isset($_SESSION["mac"]) ) {
		$ret[0][5] = $ret[0][4];
		$ret[0][4] = $l->g(563);
		for( $cptRet=1; $cptRet<count($ret); $cptRet++ ) {
			$ret[$cptRet][5] = $ret[$cptRet][4];
			$ret[ $cptRet ][4] = getConstructor( $ret[ $cptRet ][1] );
		}
	}
	$types = Array("SORT_STRING","SORT_NUMERIC","SORT_STRING","SORT_STRING","SORT_STRING");
	$tabTypes = Array("WINDOWS","LINUX","NETWORK","PHANTOM","FILTERED");
	foreach( $tabTypes as $tt ) {	
		$win = getLignes($ret, $tt, 5);
		if( sizeof($win) > 1 ) {
			echo "<br><br><center><b>".$l->g(324)." $tt</b></center><br>";
			printTab($win,true,null,false,false,true);		
		}
	}
	echo "<br>";
	
}
else  if( $_GET["mode"] == 7 ) {
	require_once("preferences.php");
	if( ! isset($_GET["modepopup"] ) ) {
		printEnTete($l->g(290));
		echo "<br><center><a href=index.php?multi=3><= ".$l->g(188)."</a></center>";
?>
	<br><form name='formip' action='index.php?multi=3&mode=7' method='POST'>
	<table align='center'>
		<tr><td><?php echo $l->g(34);?> :</td><td><input type='text' name='ipa' 
		<?php echo ( isset($_POST["ipa"])?"value='".$_POST["ipa"]."'":""); ?>></td><td>&nbsp;&nbsp;<?php echo $l->g(208);?>:</td><td><input type='text' name='ipm' 
		<?php echo ( isset($_POST["ipm"])?"value='".$_POST["ipm"]."'":""); ?>></td></tr>
		<tr><td align='right' colspan='4'><input type='submit' value='<?php echo $l->g(13);?>'></td></tr>
		</tr>
	</table>
	</form>
<?php 
	}
	$ipa = isset( $_POST["ipa"] ) ?  $_POST["ipa"] :  ( isset ( $_GET["ipa"] ) ? $_GET["ipa"] : null );
	$ipm = isset( $_POST["ipm"] ) ?  $_POST["ipm"] :  ( isset ( $_GET["ipm"] ) ? $_GET["ipm"] : null );
	
	if( $ipa ) {
		
		if( ! ereg("^([0-9]{1,3}\.){3}[0-9]{1,3}$",$ipa )) {
			echo "<script language='javascript'>alert('".$l->g(299)."');</script>";
		}
		else if( (! ereg("^([0-9]{1,3}\.){3}[0-9]{1,3}$", $ipm )) && (! ereg("^[0-9]{2}$", $ipm ) ) && $ipm != "") {
			echo "<script language='javascript'>alert('".$l->g(300)."');</script>";
		}	
		else {
			$tabBalises = Array("DISCOVERED","DNS","INVENTORIED","IP","NETBIOS","NETNAME","NETNUM","TYPE");
			$command = "-ip=".$ipa."/". ( $ipm !="" ? $ipm : "00" ); 
			$buf = runCommand($command);
			$ret = getXmlFromBuffer($buf, "IP" , $tabBalises );
			echo "<br><br>";
			
			$disc = $ret[1][0]; $dns = $ret[1][1]; $inv = $ret[1][2]; 
			$ip = $ret[1][3]; $net= $ret[1][4]; $nam = $ret[1][5]; $num = $ret[1][6];$typ = $ret[1][7];
			$ipmask = $ipa." (".$l->g(208).": ". ( $ipm != "" ? $ipm : $l->g(325) ) .")";
			$coulDisc = ( $disc == "yes" ? "green" : "red" );
			$coulInv = ( $inv == "yes" ? "green" : "red" );
			$err = $ipm !="" ? "" : "<font color='red'>".$l->g(326)."</font>";
			if( $err != "" ) $num ="";
			$width = isset($_GET["modepopup"]) ? "90" : "50";			
			
echo "<table width='$width%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
<tr height='20px'><td colspan='2' align='center'><b>".$l->g(327)." $ipmask</b></td></tr>";

		if( ! isset($_GET["modepopup"] )) {
echo "<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(328).":</font></td><td align='center'><font color='$coulDisc'><b>$disc</b></font></td></tr>
<tr height='20px' bgcolor='#F2F2F2'><td align='center'>".$l->g(329).":</font></td><td align='center'><font color='$coulInv'><b>$inv</b></font></td></tr>";
		}
echo "<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(66).":</td><td align='center'><b>$typ</b></font></td></tr>
<tr height='20px' bgcolor='#F2F2F2'><td align='center'>".$l->g(318).":</td><td align='center'><b>$dns</b></font></td></tr>
<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(330).":</td><td align='center'><b>$net</b></font></td></tr>
<tr height='20px' bgcolor='#F2F2F2'><td align='center'>".$l->g(304).":</td><td align='center'><b>$nam</b></font></td></tr>
<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(331).":</td><td align='center'><b>$err$num</b></font></td></tr>
</table><br>
<script language=javascript>document.getElementById(\"wait\").innerHTML=\"\";</script>";
		}
	}
}
else  if( $_GET["mode"] == 8 ) { // insertion network device
	
	if( isset($_POST["pas"]) )
		$_GET["pas"] = $_POST["pas"];
		
	$_SESSION["fromEnreg"] = true;

	if( $_GET["direct"] != 2 && $_SESSION["retour"] != "multi=3&mode=1" ) 
		echo "<br><center><a href=index.php?popup=1&".$_SESSION["retour"]."><= ".$l->g(188)."</a></center>";	
				
	if( isset( $_POST["macaddr"] )) {
		// cherche l'ip correspondante
		$reqIp = "SELECT ip,netid FROM netmap WHERE mac = '".$_POST["macaddr"]."'";
		$resIp = mysql_query($reqIp, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$valIp = mysql_fetch_array( $resIp );
		unset($_SESSION["lastTri"]);
		if( checkNetwork(addslashes($_POST["macaddr"])) ) {
			$reqNet = "INSERT INTO network_devices(description, type, macaddr, user)  		
			VALUES ('".addslashes($_POST["description"])."','".addslashes($_POST["type"])."','".addslashes($_POST["macaddr"])."'
			,'".$_SESSION["loggeduser"]."');";
			mysql_query($reqNet, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		}
		else
			echo "<br><center><font color=red><b>".$_POST["macaddr"]." ".$l->g(363)."</b></font></center><br>";
	}
	
	if( isset($_GET["mac"]) ) {
		printEnTete($l->g(333));		
		echo "<br><br>";
		
		echo "<form action='index.php?multi=3&mode=8&popup=1' method='POST'>
			<table BORDER='0' WIDTH='30%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
			<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(95).":</td><td align='left'>
			<input type='hidden' name='popup' value='1'>";
		if( isset($_GET["mac"]) ) {
			echo $_GET["mac"];
			echo "<input type='hidden' name='macaddr' value='".$_GET["mac"]."'>";
		}
		else echo "<input type='text' size='17' name='macaddr' value='".$_GET["mac"]."'>";
		
		if( isset($_GET["pas"]) )
			echo "<input type='hidden' name='pas' value='".$_GET["pas"]."'>";
		
		echo "</td>
			<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(53).":</td><td align='left'><input type='text' size='30' maxlength='120' name='description' value='".$_GET["nam"]."'></td></tr>
			<tr height='20px' bgcolor='#FFFFFF'><td align='center'>".$l->g(66).":</td><td align='left'><select name='type'>";
		
		$reqTypes = "SELECT DISTINCT(name) FROM devicetype";
		$resType = mysql_query( $reqTypes, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
		while( $valType = mysql_fetch_array($resType) ) {
			echo "<option>".$valType["name"]."</option>\n";
		}
		
		echo "</select></td></tr>
			<tr height='30px' bgcolor='#FFFFFF'><td align=right colspan='2'><input type='submit' value='".$l->g(13)."'></td></tr>
			</table>
			</form>";		

	}

	$ent = $l->g(334)." ".$l->g(368)." ".$_GET["pas"]."<br><br>".$nomRez;

	printEnTete($ent);echo "<br>";	
	if( isset($_GET["pas"]) ) {
		$sql  = "a.netid='".$_GET["pas"]."'";
	}
	
	$lbl = $l->g(314)." ".$_GET["pas"];	
	$whereId = "n.id";
	$linkId = "n.id";
	$select = array( "n.macaddr"=>$l->g(95), "a.ip"=>$l->g(34), "a.netid"=>$l->g(331), "n.type"=>$l->g(335), 
	"n.description"=>$l->g(336), "n.user"=>$l->g(369));	
	$selectPrelim = array( "n.id"=>"n.id" );
	$from = "network_devices n LEFT JOIN netmap a ON a.mac=n.macaddr"; 
	$fromPrelim = "";
	$group = "";
	$order = "n.macaddr";
	$countId = "n.id";
	
	$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId);
	ShowResults($requete);
}

if(!$filePresent&&$_GET["mode"]==2) echo "<br><font color=red><center><b>".$l->g(338)."</b></font><br>".$l->g(339)."</center>";

function trieTab($t,$colTri,$types) {

	if( sizeof($t)<=1 ) return $t;
	
	// tris inversés
	if( !isset($_SESSION["orderIpdisc"]) )
		$_SESSION["orderIpdisc"] = "SORT_ASC";
	
	if( $_SESSION["lastTri"] == $colTri ) {
		$_SESSION["orderIpdisc"] = ($_SESSION["orderIpdisc"]=="SORT_ASC" ? "SORT_DESC" : "SORT_ASC") ;
	}

	$_SESSION["lastTri"] = $colTri;
	//	
		
	foreach ($t[0] as $c) {			
		$enTete[] = array_shift( $t[0] );	
	}
	array_shift( $t );
	
	foreach($t as $ligne) {
		$col = 0;
		foreach($ligne as $case) {
			$inv[$col][] = $case;
			$col++;
		}		
	}
	
	$strSort = "array_multisort(";
	$colNbr = 0;
	
	$strSort .= "\$inv[$colTri],".$_SESSION["orderIpdisc"].",".$types[$colTri];
	foreach($inv as $col) {
		if( $colNbr == $colTri ) {
			$colNbr++;
			continue;
		}
		
		$strSort .= ",\$inv[$colNbr],";
		$strSort .= $types[$colNbr] != "" ? $types[$colNbr] : "SORT_REGULAR" ;
		$colNbr++;
	}
	$strSort .="); ";
	//echo $strSort;
	eval($strSort);
	
	foreach($inv as $ligne) {
		$cl = 1;
		foreach($ligne as $case) {
			$ret[$cl][] = $case;
			$cl++;
		}
	}
	$ret[0] = $enTete ;
	return $ret;
}

function printTab($t ,$modeReg=false, $tailles=null, $unSurDeux=false, $scroll = false, $modeDel = false) {
	global $_GET;
	$lesget="";
	foreach($_GET as $nom=>$val) {
		if($nom == "tri"||$nom == "delrez")
			continue;
			
		$lesget.="&$nom=".urlencode($val);
	}
			
	if( $scroll ) {
		$ftd = "</font></td>";
		echo "<div id='headers' style='position:absolute'>
		<table BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
		<tr height=25px>";
		$col = 0;
		foreach($t[0] as $case) {
			if( $unSurDeux && $col%2==0 ) {
				$col++;
				continue;
			}
					
			$taille = $tailles!=null ? $tailles[$col] : "200px" ;
			$tdsp = "<td align='center' width='{$taille}px'>";			
			echo "$tdsp<a href=?tri=$col{$lesget}><b>$case</b></a>$ftd";
			$col++;
		}
		echo "</tr></table></div>";
	}
	else
		echo "<div id='headers'></div>";

	global $l;
	$totCol = sizeof( $t[0] );
	$_SESSION["history"] = 0 ;
	echo "<table BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	
	$td = "<td align='center'>";
	
	$lnum = 0 ;
	$ligne = 0;
	while( isset($t[$ligne]) ) { // chque ligne		
			
		if($ligne==0) {
			echo "<TR height=25px>";
		}
		else	
			echo "<TR height=25px bgcolor='". ($ligne%2 == 1 ? "#FFFFFF" : "#F2F2F2") ."'>";

		for( $col=0 ; $col < sizeof($t[$ligne]) ; $col++) {
		
			$c = $t[$ligne][$col];
			
			if( $unSurDeux && $col%2==0 ) {
				$ah = $c;
				continue;
			}
			
			if($ligne==0) {
				$taille = $tailles!=null ? $tailles[$col] : "200px" ;
				$tdsp = "<td align='center' width='{$taille}px'>";
				echo "$tdsp<a href=?tri=$col{$lesget}><b>$c</b></a>$ftd";
			}
			else {
				echo $td.$ah.$c.$ftd;
			}
		}
		
		if( $modeReg ) {
			if($ligne==0)
				echo "<td align='center' width='50px'><a href=\"javascript:void(0)\"><b>".$l->g(114)."</b></a></td>";
			else if( $_GET["mode"] == 6 )			
				echo "<td align=center><a href=index.php?popup=1&multi=3&mode=8&direct=4&mac=".$t[$ligne][1]."&nam=".$t[$ligne][2]."&pas=".urlencode($_GET["pas"])."><img src='image/Gest_admin1.png'></a></td>";
			else
				echo "<td align=center><a href=index.php?popup=1&multi=3&mode=8&direct=3&mac=".$t[$ligne][1]."&nam=".$t[$ligne][2]."&pas=".urlencode($_GET["pas"])."><img src='image/Gest_admin1.png'></a></td>";
		
		}
		
		if( $modeDel ) {
			if($ligne==0)
				echo "<td align='center' width='30px'>&nbsp;</td>";
			else
				echo "<td align=center><a href=index.php?popup=1&multi=3&mode=".$_GET["mode"]."&delmac=".$t[$ligne][1]."&pas=".urlencode($_GET["pas"])."><img src='image/supp.png'></a></td>";
		}
		
		echo "</tr>";
			
		$ligne++;
	}
	echo "</table>";
	
}

function runCommand($command="") {
	global $l;
	$command = "perl ipdiscover-util.pl $command -xml -h=".$_SESSION["SERVEUR_SQL"]." -u=".$_SESSION["COMPTE_BASE"]." -p=".$_SESSION["PSWD_BASE"];
	//echo $command."<br>";
	$fd = popen($command,"r");	
	if($fd==FALSE) {
		echo "pas de handle";
		return FALSE;
	}
	$buffer = "";
	while (!feof($fd)) {
	    $buffer .= fgets($fd, 4096);	     
	}
	
	pclose ($fd);
	
	if($buffer == "") {
		echo "<br><center><font color='red'><b>".$l->g(337)."</b></font></center>";
		return FALSE;
	}
	else if( strstr ( $buffer, "ERROR")) {
		$tabBalises[] = "MESSAGE";
		$ret = getXmlFromBuffer($buffer, "ERROR" , $tabBalises);
		echo "<br><center><font color='red'><b>".$l->g($ret[1][0])."</b></font></center>";
		return FALSE;
	}
	
	return $buffer;
}
	
function getXmlFromBuffer($buffer, $baliseLigne , $balisesInt) {	
	$ret = null;
	$p = xml_parser_create();
	xml_parse_into_struct($p,$buffer,$vals,$index);
	xml_parser_free($p);
	$ligne = 1;
	foreach($vals as $val) {
		if( $val["tag"] == $baliseLigne && $val["type"] == "open" ) {
			$cOuvert = true;
			continue;
		}			
		if( $val["tag"] == $baliseLigne && $val["type"] == "close" ) {
			$cOuvert = false;
			$ligne++;
			continue;
		}
		
		if( in_array($val["tag"],$balisesInt) && $val["type"] == "complete" && $cOuvert ) {			
			$temp = array_flip ( $balisesInt );	
			$ret[$ligne][ $temp[ $val["tag"] ] ] = $val["value"];			
		}
	}
	
	return $ret;
}

function getLignes($t, $val, $saufCol=null) {
	
	if( isset($saufCol) ) {
		unset( $t[0][$saufCol] );
	}
	$ret[] = $t[0];		
	for( $l = 1 ; $l < sizeof( $t ) ; $l++ ) {
		for( $c = 0 ; $c < sizeof( $t[$l] ) ; $c++ ) {
			if( $t[$l][$c] == $val || $val == -1 ) {
				if( isset($saufCol) ) {
					unset( $t[$l][$saufCol] );
				}
				$ret[] = $t[$l];				
			}
		}
	}	
	return $ret;
}

function getMask( $ip ) {
	$reqMsk = "SELECT ipmask FROM networks WHERE ipsubnet='$ip' AND ipmask <>''";
	$resMsk = mysql_query( $reqMsk, $_SESSION["readServer"] ) or die( mysql_error($_SESSION["readServer"]) );
	while( $ligMsk = mysql_fetch_array( $resMsk ) ) {
		if( ereg("^([0-9]{1,3}\.){3}[0-9]{1,3}$",$ligMsk[0]) ) {
			return $ligMsk[0];
		}
	}
}

function popup($val) {
	global $l,$nbpop;
	$nbpop++;
	return "<a href=\"index.php?popup=1&$val\" OnClick=\"window.open('index.php?popup=1&$val','popup$nbpop','location=0,status=0,scrollbars=1,menubar=0,resizable=1,width=1024,height=668');return false;\">";
}

function getNameFromRes($nbrez) {
	$reqSub = "SELECT name FROM subnet s WHERE s.netid='$nbrez'";
	$resSub = mysql_query($reqSub, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
	if($valSub = mysql_fetch_array( $resSub )){
		return $valSub["name"];
	}
	return "";
}

function checkNetwork($mac) {
	$reqMac = "SELECT id FROM network_devices WHERE macaddr='$mac'";
	$resMac = mysql_query( $reqMac, $_SESSION["readServer"] ) or die(mysql_error(mysql_error($_SESSION["readServer"])));	
	return( mysql_num_rows( $resMac ) == 0 );
}
?>
