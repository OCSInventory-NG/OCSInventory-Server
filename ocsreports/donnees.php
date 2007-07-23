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
//Modified on $Date: 2007-07-23 10:30:25 $$Author: plemmet $($Revision: 1.9 $)

require ('req.class.php');

$user=$_SESSION["loggeduser"];

if($_SESSION["lvluser"]==SADMIN||$_SESSION["lvluser"]==LADMIN) {
	$mesMachines="";
	$_SESSION["mesmachines"] = "";
}
else if( ! isset($_SESSION["mesmachines"] )) {
	$mescQuery = "SELECT DISTINCT(tag) FROM tags WHERE login='".$user."'";
	if( ! $mescRes = mysql_query( $mescQuery, $_SESSION["readServer"] ) ) {
		echo "<br><center><font class='warn'>".$l->g(620)."</font></center>";
		include("footer.php");
		die();	
	}

	while( $mescVal = mysql_fetch_array( $mescRes ) ) {
		$mach[] = $mescVal["tag"];
	}
	$mesMachines = "a.".TAG_NAME." IN ('".@implode("','",$mach)."') ";
	$_SESSION["mesmachines"] = $mesMachines;
}
else {
	$mesMachines = $_SESSION["mesmachines"];
}
/**********************************************/
	$lbl=$l->g(2);		//Nom de la requete	
	$sql = " deviceid<>'_SYSTEMGROUP_'"; 
	if( $mesMachines != "" ) $sql .= " AND ";
	$sql .= $mesMachines; 
	$whereId = "h.id";
	$linkId = "h.id";
	$select = array_merge( array("h.id"=>"h.id" ,"deviceid"=>"deviceid"), $_SESSION["currentFieldList"] );	
	$selectPrelim = array( "h.id"=>"h.id" );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "h.id";
	$pics = array("ttmachines.png", "ttmachines_a.png");
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,$pics,true); // Instanciation du nouvel objet de type "Req"
	$requetes[]=$req;	// On l'ajoute au tableau $requetes contenant toutes les requêtes de l'application'
/**********************************************/
		
/**********************************************/
	$lbl=$l->g(182);		//Nom de la requete

	// Commande SQL de la requete
	$lblChmp[0]=TAG_LBL; // Nom du parametre 1
	$sqlChmp[0]="SELECT a.".TAG_NAME." FROM hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id GROUP BY a.".TAG_NAME."";
	$typChmp[0]="FREE";
	
	$sql = "deviceid<>'_SYSTEMGROUP_' AND a.".TAG_NAME." = 'option0' ";
	if( $mesMachines != "" ) $sql .= " AND ";
	$sql .= $mesMachines; 
	$whereId = "h.id";
	$linkId = "h.id";
	$select = array_merge( array("h.id"=>"h.id"), $_SESSION["currentFieldList"] );
	$selectPrelim = array( "h.id"=>"h.id" );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "h.id";
	$pics = array("rien.png", "rien.png");
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,$pics,true,$lblChmp,$sqlChmp,$typChmp); // Instanciation du nouvel objet de type "Req"
	$requetes[]=$req;	// On l'ajoute au tableau $requetes contenant toutes les requêtes de l'application'
/**********************************************/

/**********************************************/
	$lbl=$l->g(178);		

	$sql = " deviceid<>'_SYSTEMGROUP_'";
	if( $mesMachines != "" ) $sql .= " AND ";
	$sql .= $mesMachines;
	$whereId = "a.".TAG_NAME;
	$linkId = TAG_LBL;	
	$select = array ( "a.".TAG_NAME=>TAG_LBL, "COUNT(h.id)"=>$l->g(28) );
	$selectPrelim = array( "a.".TAG_NAME=>TAG_LBL,"COUNT(h.id)"=>$l->g(28) );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id";
	$fromPrelim = "";
	$group = "a.".TAG_NAME;
	$order = "\"".$l->g(28)."\" DESC";
	$countId = "a.".TAG_NAME;
	$pics = array("repartition.png", "repartition_a.png");
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim, $from,$fromPrelim,$group,$order,$countId,$pics);
	$requetes[]=$req;
/**********************************************/

/**********************************************/
if($_SESSION["lvluser"]==SADMIN||$_SESSION["lvluser"]==LADMIN) {
	$lbl=$l->g(583);		//Nom de la requete	
	$sql = " deviceid='_SYSTEMGROUP_'"; 
	if( $mesMachines != "" ) $sql .= " AND ";
	$sql .= $mesMachines;
	$whereId = "h.id";
	$linkId = "h.id";
	$select = array("h.id"=>"h.id" ,"deviceid"=>"deviceid","name"=>$l->g(577),"lastdate"=>"Creation",
	"g.request<>''"=>ucfirst(strtolower($l->g(613))), "COUNT(gc.hardware_id)"=>$l->g(622), "CONCAT(LEFT(description,50),'...')"=>$l->g(53));	
	$selectPrelim = array( "h.id"=>"h.id", "g.request<>''"=>ucfirst(strtolower($l->g(613))), "COUNT(gc.hardware_id)"=>$l->g(622), "CONCAT(LEFT(description,50),'...')"=>$l->g(53));
	$from = "hardware h LEFT JOIN groups g ON g.hardware_id=h.id LEFT JOIN groups_cache gc ON gc.group_id=g.hardware_id";
	$fromPrelim = "";
	$group = "h.id";
	$order = "h.lastdate DESC";
	$countId = "h.id";
	$pics = array("groups.png", "groups_a.png");
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,$pics); // Instanciation du nouvel objet de type "Req"
	$requetes[]=$req;	// On l'ajoute au tableau $requetes contenant toutes les requêtes de l'application'
}
/**********************************************/
?>