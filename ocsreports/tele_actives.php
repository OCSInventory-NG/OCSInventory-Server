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
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.3 $)

if( isset($_GET["suppack"])) {
	@mysql_query("DELETE FROM download_enable WHERE ID=".$_GET["suppack"], $_SESSION["writeServer"]) or die(mysql_error());	
	
	$reqSupp = "DELETE FROM devices WHERE name='DOWNLOAD' AND ivalue=".$_GET["suppack"];
	if( isset($_GET["nonnot"]) )
		$reqSupp .= " AND tvalue IS NULL";
	
	@mysql_query($reqSupp, $_SESSION["writeServer"]) or die(mysql_error());	
}
	
$canAc = 2;
PrintEnTete( $l->g(481) );	
	
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

echo "<center>".$l->g(552)."</center>";

?>