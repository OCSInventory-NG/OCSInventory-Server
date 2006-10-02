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
//Modified on 06/23/2006
if( isset($_GET["frompref"]) && $_GET["frompref"] == 1 ) {
	unset( $_SESSION["saveId"] );
}
else if( isset($_GET["systemid"]) ) {
	$_SESSION["saveId"] = $_GET["systemid"];
}

if( ! isset($_SESSION["saveRequest"])) {
	$_SESSION["saveRequest"] = $_SESSION["storedRequest"];
}

if( isset($_GET["affpack"])) {
	$ok = resetPack( $_GET["affpack"] );
	$ok = $ok && setPack( $_GET["affpack"] );
}

if( $_GET["retour"] == 1 || (isset($_GET["affpack"]) && $ok) ) {
	$_SESSION["storedRequest"] = $_SESSION["saveRequest"];
	unset( $_SESSION["saveRequest"] );
	if( ! isset( $_SESSION["saveId"] ) )
		echo "<script language='javascript'>window.location='index.php?redo=1".$_SESSION["queryString"]."';</script>";
	else
		echo "<script language='javascript'>window.location='machine.php?systemid=".$_SESSION["saveId"]."&sessid=".session_id()."&option=".$l->g(500)."';</script>";
	die();
}

if( isset($_GET["suppack"])) {
	@mysql_query("DELETE FROM download_enable WHERE ID=".$_GET["suppack"], $_SESSION["writeServer"]) or die(mysql_error());	
	
	$reqSupp = "DELETE FROM devices WHERE name='DOWNLOAD' AND ivalue=".$_GET["suppack"];
	if( isset($_GET["nonnot"]) )
		$reqSupp .= " AND tvalue IS NULL";
	
	@mysql_query($reqSupp, $_SESSION["writeServer"]) or die(mysql_error());	
}
	
if( isset($_GET["systemid"]))
	$nbMach = 1;
else
	$nbMach = getCount($_SESSION["saveRequest"]);

if( $nbMach > 0 ) {
	$canAc = 1;
	PrintEnTete( $l->g(477)." <font class='warn'>($nbMach ".$l->g(478).")</font>");
}
else {
	die($l->g(478));	
}

echo "<br><center><a href='#' OnClick=\"window.location='index.php?multi=24&retour=1'\"><= ".$l->g(188)."</a></center>";

if( isset($_GET["systemid"]))
	$canAc = 3; //preferences.php must set systemid in query string
	
$lbl = "pack";	
$sql = "d.fileid = e.fileid";
$whereId = "d.FILEID=e.FILEID AND d.FILEID";
$linkId = "d.FILEID";
$select = array("ID"=>$l->g(460), "d.FILEID"=>$l->g(475), "NAME"=>$l->g(49), 
"PRIORITY"=>$l->g(440),"INFO_LOC"=>$l->g(470), "PACK_LOC"=>$l->g(471), 
"FRAGMENTS"=>$l->g(480), "SIZE"=>$l->g(462), "OSNAME"=>$l->g(25));	
$selectPrelim = array("d.FILEID"=>"d.FILEID");	
$from = "download_available d, download_enable e";
$fromPrelim = "";
$group = "";
$order = "";
$countId = "e.ID";

$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,true);
ShowResults($requete,true,false,false,false,false,false,$canAc);

	function setPack( $packid ) {
		global $_GET;
		if( isset($_GET["systemid"])) {
			$val["h.id"] = $_GET["systemid"];
			if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'DOWNLOAD', $packid )", $_SESSION["writeServer"] )) {
				echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
				return false;
			}
			$_SESSION["justAdded"] = true;
		}
		else {
			$lareq = getPrelim( $_SESSION["saveRequest"] );
			if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
				return false;
			while( $val = @mysql_fetch_array($res)) {
				if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'DOWNLOAD', $packid)", $_SESSION["writeServer"] )) {
					echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
					return false;
				}
			}
		}
		return true;	
	}
	
	function resetPack( $packid ) {
		
		global $_GET;
		if( isset($_GET["systemid"])) {
			$val["h.id"] = $_GET["systemid"];
			if( ! @mysql_query( "DELETE FROM devices WHERE name='DOWNLOAD' AND IVALUE=$packid AND hardware_id='".$val["h.id"]."'", $_SESSION["writeServer"] )) {
				echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
				return false;
			}
		}
		else {
			$lareq = getPrelim( $_SESSION["saveRequest"] );
			if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
				return false;
			while( $val = @mysql_fetch_array($res)) {
			
				if( ! @mysql_query( "DELETE FROM devices WHERE name='DOWNLOAD' AND IVALUE=$packid AND hardware_id='".$val["h.id"]."'", $_SESSION["writeServer"] )) {
					echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
					return false;
				}
			}
		}

		return true;		
		//TODO: comprends pas: echo "DELETE FROM devices WHERE name='FREQUENCY' AND hardware_id IN ($lareq)";flush();		
	}
?>