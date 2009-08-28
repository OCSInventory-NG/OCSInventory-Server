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
//Modified on $Date: 2007/02/08 16:05:52 $$Author: plemmet $($Revision: 1.13 $)



if (isset($_GET['first']) or ($_GET == null))
$_GET['multi']="console";
$sleep=1;
function getmicrotime(){
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}
$debut = getmicrotime();
error_reporting(E_ALL & ~E_NOTICE);

require("fichierConf.class.php");
//require('req.class.php');

@session_start();
//// First installation checking
//if( (!$fconf=@fopen("dbconfig.inc.php","r")) || (!function_exists('session_start')) || (!function_exists('mysql_connect'))) {
//	require('install.php');
//	die();
//}
//else
//	fclose($fconf);
////
	
//$showingReq = false;
//foreach( $_GET as $key=>$val) {
//	$_GET["key"] = urldecode($val);
//	if( $key == "lareq" ) {
//		$showingReq = true;
//	}
//}

//if( isset($_GET["pcparpage"]) ) {
//	$_SESSION["pcparpage"] = $_GET["pcparpage"];
//	setcookie( "PcParPage", $_GET["pcparpage"], time() + 3600 * 24 * 15 );
//}

require ('header.php');
?>
		<script language='javascript'>
	
			function montre(id) {
		
				var d = document.getElementById(id);
				for (var i = 1; i<=10; i++) {
					if (document.getElementById('smenu'+i)) {document.getElementById('smenu'+i).style.display='none';}
				}
				if (d) {d.style.display='block';}
			}			
			function clic(id){

				document.getElementById('ACTION_CLIC').action = id;
				document.getElementById('RESET').value=1;
				document.forms['ACTION_CLIC'].submit();

			}
		</script>
		
	<?php



require ('donnees.php');
$pcparpage = $_SESSION["pcparpage"];


if( !isset($_GET["popup"] )) {
	//si la variable RESET existe
	//c'est que l'on a cliqu� sur un ic�ne d'un menu 
	if (isset($_POST['RESET'])){
		if ($_SESSION['DEBUG'] == 'ON')
			echo "<br><b><font color=red>".$l->g(5003)."</font></b><br>";
		unset($_SESSION['DATA_CACHE']);	
	}
	//formulaire pour d�tecter le clic sur un bouton du menu
	//permet de donner la fonctionnalit�
	//de reset du cache des tableaux
	//si on reclic sur le m�me ic�ne
	echo "<form action='' name='ACTION_CLIC' id='ACTION_CLIC' method='POST'>";
	echo "<input type='hidden' name='RESET' id='RESET' value=''>";
	echo "</form>";
	
	
	echo "<table width='100%' border=0><tr><td>
	<table BORDER='0' ALIGN = 'left' CELLPADDING='0' BGCOLOR='#FFFFFF' BORDERCOLOR='#9894B5'><tr>";
	tab($l->g(2), 43);	
	tab($l->g(178), 44);	
	$i=0;
	$selectionne=0;
	//groups
	$sql_groups_4all="select workgroup from hardware where workgroup='GROUP_4_ALL' and deviceid='_SYSTEMGROUP_'";
	$res = mysql_query($sql_groups_4all, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item = mysql_fetch_object($res);
	if (isset($item->workgroup) or $_SESSION["lvluser"]==SADMIN or $_SESSION["lvluser"]==LADMIN)	
	tab($l->g(583), 37);	
	tab($l->g(765), 36);
	tab($l->g(9), 41);		
	echo "</tr></table></td><td>";	

	echo "<table BORDER='0' ALIGN = 'right' CELLPADDING='0' BGCOLOR='#FFFFFF' BORDERCOLOR='#9894B5'>";
	echo "<tr height=20px  bgcolor='white'>";
	
	flush();


	if($_SESSION["lvluser"]==SADMIN) {
 			$name_menu="smenu1";
			$packAct = array(22,23,24,27,20,21,26);
			$nam_img="pack";
			$title=$l->g(512);
			$data_list_deploy[20]=$l->g(513);
			$data_list_deploy[21]=$l->g(514);
			$data_list_deploy[34]=$l->g(662);
			menu_list($name_menu,$packAct,$nam_img,$title,$data_list_deploy);
			tab($l->g(380),14);
			tab($l->g(17) , 8);
			$name_menu="smenu2";
			$packAct = array(4,32,35);
			$nam_img="configuration";
			$title=$l->g(107);
			$data_list_config[4]=$l->g(107);
			$data_list_config[32]=$l->g(703);
			//$data_list_config[35]=$l->g(712);
			menu_list($name_menu,$packAct,$nam_img,$title,$data_list_config);	
			tab($l->g(211), 5);
			tab($l->g(928), 39);
	}
		tab($l->g(225), 9);	
 		tab($l->g(174), 3);
		tab($l->g(175), 6);
	
	if($_SESSION["lvluser"]==SADMIN) {
		tab($l->g(263), 12);
		tab($l->g(243), 45);		
 		tab($l->g(287), 13);
	}	
	tab($l->g(570), 28);
	
	
	?><script language='javascript'>montre();</script>	<?php	
	echo "</tr></table>";
	echo "</td></tr></table>";
	flush();
}

echo "<br><center><span id='wait' class='warn'><font color=red>".$l->g(332)."</font></span></center><br>";
	flush();

	if( ! isset( $_SESSION["mac"] ) ) {
		loadMac();
	}
	
	if( $_GET["multi"] != 3 )
		unset( $_SESSION["forcedRequest"] );

	//GROUP CREATION
	if( $_SESSION["lvluser"] == SADMIN ) {
		// New classic group
		if( ! empty( $_POST["cg"] ) ) {
			if( createGroup( $_POST["cg"], $_POST["desc"] ) ) {
				unset( $_POST );
			}
		}
		//New static group, with checked computers in cache
		else if( ! empty( $_POST["cgs"] ) ) {
			if( createGroup( $_POST["cgs"], $_POST["desc"], true ) ) {
				$mess=addComputersToGroup( $_POST["cgs"], $_POST );
				echo "<div align=center><font color=green><big><B>".$mess." ".$l->g(819)."</B></big></font></div>";
				unset( $_POST );
			}
		}
		// Overwrite a classic group
		else if( isset( $_POST["eg"] ) && $_POST["eg"] != "_nothing_" ) {
			createGroup( $_POST["eg"], $_POST["desc"], false, true );
			unset( $_POST );
		}
	}
		// Add checked computers to existing group
	 if( isset( $_POST["asg"] ) && $_POST["asg"] != "_nothing_" ) {
			$mess=addComputersToGroup( $_POST["asg"], $_POST );
			echo "<div align=center><font color=green><big><B>".$mess." ".$l->g(819)."</B></big></font></div>";
			unset( $_POST );
		}		
			
		
	switch($_GET["multi"]) {
 		case 3: require ('ipdiscover_new.php');	break;
 		case 4:	require ('confiGale.php');	break;
 		case 5:	require ('registre.php');	break;
 		case 6:	require ('doublons.php');	break;
 		case 8:	require ('uploadfile.php');	break;
 		case 9:	require ('donAdmini.php');	break;
 		case 12: require ('label.php');	break;
		case 13:	require ('local.php');	break;
		case 14: require ('dico.php');	break;
		case 15: require ('composants.php'); break;
		case 20: require ('tele_package.php'); break; 
		case 21: require ('tele_activate.php'); break; 
		case 22: require ('opt_param.php'); break; 
		case 23: require ('opt_ipdiscover.php'); break; 
		case 24: require ('tele_affect.php'); break; 
		case 25: require ('tele_stats.php'); break;
		case 26: require ('tele_actives.php'); break;
		case 27: require ('opt_suppr.php'); break; 
		case 29: require ('group_show.php'); break;
		case 30: require ('tele_massaffect.php'); break; 
		case 31: require ('admin_attrib.php'); break; 
		case 32: require ('blacklist.php');break;
		case 34: require ('rules_redistrib.php');break;
		//case 35: require ('admin_language.php');break;
		case 36: require ('all_soft.php');break;
		case 37: require ('groups.php');break;
		case 38: require ('show_detail.php');break;
		case 39: require ('logs.php');break;
		case 41: require ('multi.php');break;
		case 43: require ('list_computors.php');break;
		case 44: require ('repart_tag.php');break;
		case 45:require ('admins.php');break;
		case "console" : require ('console.php');break;	
 		default: require ('console.php');		
 	}

if( !isset($_GET["popup"] ))
	require ($_SESSION['FOOTER_HTML']);
	
echo "<script language='javascript'>wait(0);</script>";
function menu_list($name_menu,$packAct,$nam_img,$title,$data_list)
{
	global $_GET;
	
	echo "<td onmouseover=\"javascript:montre('".$name_menu."');\">
	<dl id=\"menu\">
		<dt onmouseover=\"javascript:montre('".$name_menu."');\">
		<a href='javascript:void(0);'>
	<img src='image/".$nam_img;
	if( in_array($_GET["multi"],$packAct) )
		echo "_a";
		echo ".png'></a></dt>
			<dd id=\"".$name_menu."\" onmouseover=\"javascript:montre('".$name_menu."');\" onmouseout=\"javascript:montre();\">
				<ul>
					<li><b>".$title."</b></li>";
					foreach ($data_list as $key=>$values){
						echo "<li><a href=\"index.php?multi=".$key."\">".$values."</a></li>";						
					}
		echo "</ul>
			</dd>
	</dl>
	</td> ";
		
}


function tab( $label, $multi) {
	$llink = "?multi=$multi";
	
	switch($multi) {

		case 2: $img = "codes";	break;
 		case 3: $img = "securite";	break;
 		case 4:	$img = "configuration";	break;
 		case 5:	$img = "regconfig";	break;
 		case 6:	$img = "doublons";	break;
 		case 8:	$img = "agent";	break;
 		case 9:	$img = "administration"; break;
 		case 12: $img = "label";	break;
		case 13: $img = "local";	break;
		case 14: $img = "dictionnaire";	break;
		case 36: $img = "ttlogiciels"; break;
		case 37: $img = "groups"; break;
		case 39: $img = "log";	break;
		case 41: $img = "recherche";	break;
		case 42: $img = "statistiques";	break;
		case 43: $img = "ttmachines";break;
		case 44: $img = "repartition";break;
		case 45: $img = "utilisateurs";	break;
		case 28: $img = "aide";		
				$llink = "http://wiki.ocsinventory-ng.org";
		        break;			
	}
	if($_GET["multi"] == $multi && $multi != "" ) {
		$img .= "_a";
	}
	
	//si on clic sur l'ic�ne, on charge le formulaire 
	//pour obliger le cache des tableaux � se vider
	echo "<td onmouseover=\"javascript:montre();\"><a onclick='clic(\"".$llink."\");'><img title=\"".htmlspecialchars($label)."\" src='image/$img.png'></a></td>";	
}

?>
