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
//Modified on 11/29/2005

include ('req.class.php');
	$mesMachines="";
/**********************************************/
	$lbl=$l->g(2);		//Nom de la requete
	
	$rqq="SELECT a.".TAG_NAME." AS \"".TAG_LBL."\",$selectH FROM hardware h,accountinfo a,bios b WHERE a.deviceid=h.deviceid AND b.deviceid=h.deviceid 
	$mesMachines GROUP BY h.deviceid";	
	$rqqc="SELECT COUNT(h.deviceid) FROM hardware h,accountinfo a WHERE a.deviceid=h.deviceid 
	$mesMachines";	
	// Commande SQL de la requete
	$lblChmp[0]=NULL;
	$sqlChmp[0]=NULL;
	$typChmp[0]=NULL;
	
	$req=new Req($lbl,$rqq,$rqqc,$lblChmp,$sqlChmp,$typChmp,NULL,true); // Instanciation du nouvel objet de type "Req"
	$requetes[]=$req;	// On l'ajoute au tableau $requetes contenant toutes les requêtes de l'application'
/**********************************************/
		
/**********************************************/
	$lbl=$l->g(182);		//Nom de la requete
	
	$rqq="SELECT $selectH FROM hardware h, accountinfo a, bios b 
	WHERE a.deviceid=h.deviceid AND b.deviceid=h.deviceid AND a.".TAG_NAME." = 'option0' $mesMachines";
	$rqqc="SELECT COUNT(h.deviceid) FROM hardware h, accountinfo a, bios b
	WHERE a.deviceid=h.deviceid AND b.deviceid=h.deviceid AND a.".TAG_NAME." = 'option0' $mesMachines";	
		
	// Commande SQL de la requete
	$lblChmp[0]=TAG_LBL; // Nom du parametre 1
	$sqlChmp[0]="SELECT a.".TAG_NAME." FROM hardware h, accountinfo a WHERE h.deviceid=a.deviceid $mesMachines GROUP BY a.".TAG_NAME."";
	$typChmp[0]="FREE";
	
	$lblChmp[1]=NULL;
	
	$req=new Req($lbl,$rqq,$rqqc,$lblChmp,$sqlChmp,$typChmp,NULL,true); // Instanciation du nouvel objet de type "Req"
	$requetes[]=$req;	// On l'ajoute au tableau $requetes contenant toutes les requêtes de l'application'
/**********************************************/
/**********************************************/
	$lbl=$l->g(178);		
		
	$rqq="SELECT a.".TAG_NAME." AS \"".TAG_LBL."\", COUNT(h.deviceid) AS \"".$l->g(28)."\" FROM hardware h, accountinfo a WHERE h.deviceid=a.deviceid 
	$mesMachines GROUP BY a.".TAG_NAME." ORDER BY 2 DESC";
	$rqqc="SELECT COUNT(DISTINCT(a.".TAG_NAME.")) FROM hardware h, accountinfo a WHERE h.deviceid=a.deviceid 
	$mesMachines";
	
	$lblChmp[0]=NULL;	
	
	$req=new Req($lbl,$rqq,$rqqc,$lblChmp,$sqlChmp,$typChmp,NULL,false);
	$requetes[]=$req;	
/**********************************************/
?>