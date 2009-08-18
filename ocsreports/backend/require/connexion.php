<?php
function connexion_local()
{	
	global $link_ocs,$db_ocs;
	require_once($_SESSION['CONF_MYSQL']);
 	//require_once($_SESSION['NAME_MYSQL']);
	//connection OCS
	$db_ocs = DB_NAME;
	//lien sur le serveur OCS
	$link_ocs=mysql_connect($_SESSION["SERVER_READ"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);

	if(!$link_ocs) {
			echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error()."</b></font></center>";
			die();
		}

		
	//fin connection OCS	
}
?>
