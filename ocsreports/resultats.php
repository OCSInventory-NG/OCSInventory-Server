<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2006-12-12 10:43:49 $ by $Author: plemmet $ (version: $Revision: 1.3 $)

	global $query;		 	
	$okReq=0;
	foreach($requetes as $req) // On met dans $req la requete demandée
	{		
		if((isset($_GET["lareq"])&&$req->label==$_GET["lareq"])||
		(!isset($_GET["lareq"])&&isset($_SESSION["lareq"])&&$req->label==$_SESSION["lareq"])) {
			$okReq=1;
			break;
		}		
	}    	
	
	if($okReq==0) {
		include("footer.php");
		die();
	}
	
	echo $req->toHtml($link);	
	
	if(isset($_POST["sub"])&&!isset($_GET["cuaff"])) {
		unset($_GET["c"]);
		unset($_SESSION["query"]);
		unset($_SESSION["queryC"]);
		$_SESSION["pageCur"] = 1;
	}
	
	if(isset($_POST["sub"])||!isset($req->labelChamps[0])||isset($_GET["c"])) 
		ShowResults($req,true,false,false,true,false);
?>
	
	
	