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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.9 $)

if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
	
if( isset($_POST["systemid"]) )
	$_GET["systemid"] = $_POST["systemid"];

//deletion confirmed	
if( isset($_POST["del"])) {
	// Posted computers, must delete all of them
	if( isset( $_POST["sysdelete1"] ) ) {		
		foreach( $_POST as $key=>$val ) {
			if( strpos ( $key, "sysdelete" ) !== false ) {
						$name_id_supp=deleteDid( $val );
					addLog("DELETE",$val.' => '.$name_id_supp);
			}
		}
	}
	// On single computer to be deleted
	else if( isset($_GET["systemid"])) {
		$name_id_supp=deleteDid( $_GET["systemid"] );
		addLog("DELETE",$_GET["systemid"].' => '.$name_id_supp);
	}
	// Every computer matching the latest query must be deleted
	else {
		deleteAll();		
	}
	//going back
	echo "<script language='javascript'>window.location='index.php?redo=1".$_SESSION["queryString"]."';</script>";
	die();	
}

$nbMach = 0;

if( isset($_GET["systemid"]))
	$nbMach = 1;
else if( isset( $_POST["maxcheck"] ) ) {
	foreach( $_POST as $key=>$val ) {
		if( strpos ( $key, "checkmass" ) !== false ) {
			$tbd[] = $val;
			$nbMach++;
		}		
	}
}
if( empty( $tbd ) )
	$nbMach = getCount($_SESSION["storedRequest"]);

PrintEnTete( $l->g(122)." <font class='warn'>($nbMach ".$l->g(478).")</font>");

echo "<br><center><a href='#' OnClick='window.location=\"index.php?redo=1".$_SESSION["queryString"]."\";'><= ".$l->g(188)."</a></center>";
echo "<br><br><form action='index.php?multi=27' method='post'><center><b>".$l->g(525)."</b></center>";
if( isset($_GET["systemid"]) ) {
	//reposting computer for confirm
	echo "<input type='hidden' value='".$_GET["systemid"]."' name='systemid'>";
}
else if( isset( $tbd ) ) {
	$indexSys = 1;
	//reposting computerS for confirm
	foreach( $tbd as $sys ) {
		echo "<input type='hidden' value='".$sys."' name='sysdelete$indexSys'>";
		$indexSys++;
	}
}

echo "<br><center><input type='submit' value=\"".$l->g(455)."\" name='del'></form></center>";
	
	function deleteAll( ) {		
		$lareq = getPrelim( $_SESSION["storedRequest"] );
		if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
			return false;
		$name_id_supp="";
		while( $val = @mysql_fetch_array($res)) {
			$name_id_supp.=" / ";
			$name_id_supp.=deleteDid( $val["h.id"] );
		}
		addLog("MASS DELETE",$name_id_supp);
		return true;		
	}
?>

