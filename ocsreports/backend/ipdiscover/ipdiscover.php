<?php
/*page de calcul des droits Ipdiscover
 * 
 * 
 */
 require_once($_SESSION['backend'].'require/connexion.php');
 
$list_methode=array(0=>"local.php");

if (!isset($_SESSION["ipdiscover"])){
	$i=0;
	//methode pour le calcul des droits
	while ($list_methode[$i]){
		require_once('methode/'.$list_methode[$i]);
		//on garde les erreurs présentes
		//entre chaque méthode
		if (isset($INFO)){
			$tab_info[$list_methode[$i]]=$INFO;
			unset($INFO);
		}
		//on garde les droits de l'utilisateur sur l'ipdiscover
		if (isset($list_ip)){
			$tab_ip[$list_methode[$i]]=$list_ip;
			unset($list_ip);
		}
		$i++;
	}
	
}
unset($list_ip);
if (isset($tab_ip)){
	foreach ($list_methode as $prio=>$script){
		if (isset($tab_ip[$script])){
			foreach ($tab_ip[$script] as $ip=>$lbl){
			$list_ip[$ip]=$lbl;			
			}
		}
	}	
	if (isset($list_ip)){
		$_SESSION["ipdiscover"]=$list_ip;	
		$_SESSION["ipdiscover_methode"]=$list_methode[0];	
		$_SESSION["ipdiscover_id"]=$id_subnet;
	}
}

if (isset($tab_info) and !isset($_SESSION["ipdiscover"])){
	$_SESSION["ipdiscover"]=$tab_info;	
}
?>
