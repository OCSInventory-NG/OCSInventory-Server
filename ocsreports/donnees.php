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
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.5 $)

include ('req.class.php');
//GEND
$user=$_SESSION["loggeduser"];
$mesMachines="a.".TAG_NAME." IN (SELECT DISTINCT(cm.code) FROM ocsweb.codeunite cm WHERE cm.login='$user')";
			
if($_SESSION["lvluser"]==SADMIN||$_SESSION["lvluser"]==LADMIN)
{
	$mesMachines="";
}
//FGEND	
/**********************************************/
	$lbl=$l->g(2);		//Nom de la requete	
	$sql = $mesMachines;
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
	
	$sql = "a.".TAG_NAME." = 'option0'";
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

	$sql = $mesMachines;
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
?>