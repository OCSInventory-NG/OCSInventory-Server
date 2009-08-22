<?php
/*******************************************************AFFICHAGE HTML DU HEADER*******************************************/
//require("fichierConf.class.php");


//global $l;
?>
<html>
<head>
<TITLE>OCS Inventory</TITLE>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html"; charset="UTF-8";>
<link rel="shortcut icon" href="favicon.ico" />
<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>
<script language='javascript' type='text/javascript' src='js/function.js'></script>
<?php incPicker(); 
echo "</head>"; 


echo "<body bottommargin='0' leftmargin='0' topmargin='0' rightmargin='0' marginheight='0' marginwidth='0'>";
//on affiche l'entete de la page
if( !isset($_GET["popup"] )) {
	//si unlock de l'interface
	if ($_POST['LOCK'] == 'RESET'){
		 $_SESSION["mesmachines"]=$_SESSION["TRUE_mesmachines"];
		unset($_SESSION["TRUE_mesmachines"]);
	}
//TODO: revoir ça!!! si la variable $ban_head est à 'no', on n'affiche pas l'entete... 
//echo "<font color=RED><B>ENVIRONNEMENT DE DEV</B></font>";

echo "<table  border='0' class='headfoot' ";
if ($ban_head=='no') echo "style='display:none;'";
echo "><tr><td width= 10%><table width= 50% align=center border='0'><tr>
 	<Td align='left'><a href='index.php?first'><img src='image/logo OCS-ng-48.png'></a></Td></tr></table></td><td width= 100%>";
 	
if (isset($_SESSION["loggeduser"])){
	echo "<table width= 100% align=center border='0'><tr><Td align='center' bgcolor='#f2f2f2' BORDERCOLOR='#f2f2f2' width:80%>";
	echo "<font color=red><b>ATTENTION: USE THIS VERSION ONLY FOR TEST.<BR> THIS VERSION IN DEVELOPMENTAL STAGE</b></font>";
//si un fuser est en cours, on indique avec quel compte le super admin est connecté
	if( isset($_SESSION['TRUE_USER']) )
		echo "<font color=red>".$_SESSION['TRUE_USER']." ".$l->g(889)." ".$_SESSION["loggeduser"]."</font>";
	if (isset($_SESSION["TRUE_mesmachines"])){
			echo "<br><b><font color=red>".$l->g(890)."</font></b>";
		}
	echo "</Td></tr></table>";
}
echo "</td><td width= 10%><table width= 100% align=center border='0'><tr><Td align='center'>
	<b>Ver. 1.03A &nbsp&nbsp&nbsp;</b>";
//si on est en made débug, on affiche les requêtes jouées
	if ($_POST['DEBUG_FOOTER'] == 'OFF')
		unset($_SESSION['DEBUG']);
	if ($_POST['DEBUG_FOOTER'] == 'ON')
	$_SESSION['DEBUG']='ON';
	if($_SESSION['DEBUG']=='ON'){
		echo "<br><a onclick='return pag(\"OFF\",\"DEBUG_FOOTER\",\"debug_footer\")'><img src=image/red.png></a>";
		echo "<br><font color='black'><b>CACHE:&nbsp;<font color='".($_SESSION["usecache"]?"green'><b>ON</b>":"red'><b>OFF</b>")."</font><div id='tps'>wait...</div>";
	}elseif (($_SESSION["lvluser"] == SADMIN or $_SESSION['TRUE_LVL'] == SADMIN) and !isset($_SESSION['DEBUG'])){
		echo "<br><a onclick='return pag(\"ON\",\"DEBUG_FOOTER\",\"debug_footer\")'><img src=image/green.png></a><br>";
	}
	echo "<form name='debug_footer' id='debug_footer' action='' method='post'>";
	echo "<input type='hidden' name='DEBUG_FOOTER' id='DEBUG_FOOTER' value=''>";
	echo "</form>";
}

if(isset($_SESSION["loggeduser"])&&!isset($_GET["popup"] )) {
		echo "<a onclick='return pag(\"ON\",\"LOGOUT\",\"log_out\")'>";
		echo "<img src='image/deconnexion.png' title='".$l->g(251)."' alt='".$l->g(251)."'>";
		echo "</a>";
		if (isset($_SESSION["TRUE_mesmachines"])){
			echo "<a onclick='return pag(\"RESET\",\"LOCK\",\"log_out\")'>";
			echo "<img src='image/cadena_op.png' title='".$l->g(891)."' alt='".$l->g(891)."' >";
			echo "</a>";
		}
		if (isset($_SESSION["lvluser"]) and $_SESSION["lvluser"] == SADMIN or 
			(isset($_SESSION['TRUE_LVL']) and $_SESSION['TRUE_LVL'] == SADMIN)){
			echo "&nbsp<a OnClick='window.open(\"fuser.php\",\"fuser\",\"location=0,status=0,scrollbars=0,menubar=0,resizable=0,width=550,height=350\")'>";
			echo "<img src='image/fuser.png' title='".$l->g(892)."' alt='".$l->g(892)."'>";
			echo "</a>";
		}
		echo "<form name='log_out' id='log_out' action='' method='post'>";
		echo "<input type='hidden' name='LOGOUT' id='LOGOUT' value=''>";
		echo "<input type='hidden' name='LOCK' id='LOCK' value=''>";
		echo "</form>";
			
}

echo "</Td></tr></table></td></tr>";
if (!isset($_SESSION["loggeduser"])){
	echo "<tr><td colspan=20 align=right>";
 require_once('pluggins/language/language.php');
 	echo "</td></tr>";
}
echo "</table>";		
//echo "<form name='reload_fuser' id='reload_fuser' action='' method='post'></form>";
echo "<div class='fond'>";




?>
