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
$_GET['biere']="console";
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

require ('header.php');

?>


<script language='javascript'>
	
	function montre(id) {
	
		var d = document.getElementById(id);
		for (var i = 1; i<=10; i++) {
			if (document.getElementById('smenu'+i)) { document.getElementById('smenu'+i).style.display='none'; }
		}
		if (d) { d.style.display='block'; }
	}
			
	function clic(id) {

		document.getElementById('ACTION_CLIC').action = id;
		document.getElementById('RESET').value=1;
		document.forms['ACTION_CLIC'].submit();

	}

</script>

		
<?php

require ('donnees.php');
require_once('require/function_index.php');
$pcparpage = $_SESSION["pcparpage"];


//Initiating icons

if( !isset($_GET["popup"] )) {
	//si la variable RESET existe
	//c'est que l'on a clique sur un icone d'un menu 
	if (isset($_POST['RESET'])){
		if ($_SESSION['DEBUG'] == 'ON')
			echo "<br><b><font color=red>".$l->g(5003)."</font></b><br>";
		unset($_SESSION['DATA_CACHE']);	
	}
	//formulaire pour detecter le clic sur un bouton du menu
	//permet de donner la fonctionnalite
	//de reset du cache des tableaux
	//si on reclic sur le meme icone
	echo "<form action='' name='ACTION_CLIC' id='ACTION_CLIC' method='POST'>";
	echo "<input type='hidden' name='RESET' id='RESET' value=''>";
	echo "</form>";
	
	
	echo "<table width='100%' border=0><tr><td>
	<table BORDER='0' ALIGN = 'left' CELLPADDING='0' BGCOLOR='#FFFFFF' BORDERCOLOR='#9894B5'><tr>";
	
	echo$icons_list['all_computers'];
	echo $icons_list['repart_tag'];
	
	//groups
	$sql_groups_4all="select workgroup from hardware where workgroup='GROUP_4_ALL' and deviceid='_SYSTEMGROUP_'";
	$res = mysql_query($sql_groups_4all, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item = mysql_fetch_object($res);
	if (isset($item->workgroup) or $_SESSION["lvluser"]==SADMIN or $_SESSION["lvluser"]==LADMIN)	
	
	echo $icons_list['groups'];
	echo $icons_list['all_soft'];
	echo $icons_list['multi_search'];
	
	echo "</tr></table></td><td>";	

	echo "<table BORDER='0' ALIGN = 'right' CELLPADDING='0' BGCOLOR='#FFFFFF' BORDERCOLOR='#9894B5'>";
	echo "<tr height=20px  bgcolor='white'>";
	
	flush();


	if($_SESSION["lvluser"]==SADMIN) {

			//Special code for teledploy
 			$name_menu="smenu1";
			$packAct = array(22,23,24,27,20,21,26);
			$nam_img="pack";
			$title=$l->g(512);
			$data_list_deploy['mere_noel']=$l->g(513);
			$data_list_deploy['grimbergen']=$l->g(514);
			$data_list_deploy['leffe']=$l->g(662);
			menu_list($name_menu,$packAct,$nam_img,$title,$data_list_deploy);

			echo $icons_list['dict'];
			echo$icons_list['upload_file'];

			//Special code for config 
			$name_menu="smenu2";
			$packAct = array(4,32,35);
			$nam_img="configuration";
			$title=$l->g(107);
			$data_list_config[1664]=$l->g(107);
			$data_list_config['westmalle']=$l->g(703);
			//$data_list_config[35]=$l->g(712);
			menu_list($name_menu,$packAct,$nam_img,$title,$data_list_config);	
			
			echo $icons_list['regconfig'];
			echo $icons_list['logs'];
			echo $icons_list['admininfo'];
        	        echo $icons_list['ipdiscover'];
	                echo $icons_list['doubles'];
			echo $icons_list['label'];
	                echo $icons_list['users'];
        	        echo $icons_list['local'];
			echo $icons_list['help'];

	}

	else {  //not clean for the moment...a second if will be better !! :) 
	
		//Icon for user profile	
		echo $icons_list['admininfo'];
		echo $icons_list['ipdiscover'];
		echo $icons_list['doubles'];
		echo $icons_list['help'];
	}	
	
	?>

	<script language='javascript'>montre();</script>

	<?php	

	echo "</tr></table>";
	echo "</td></tr></table>";
	flush();
}

echo "<br><center><span id='wait' class='warn'><font color=red>".$l->g(332)."</font></span></center><br>";
	flush();

	if( ! isset( $_SESSION["mac"] ) ) {
		loadMac();
	}
	
	if( $_GET["biere"] != 3 )
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
			
		
	switch($_GET["biere"]) {
 		case 'kro': require ('ipdiscover_new.php');	break;
 		case 1664: require ('confiGale.php');	break;
 		case 'kwak': require ('registre.php');	break;
 		case 'tripel':	require ('doublons.php');	break;
 		case 'cuvee_troll': require ('uploadfile.php');	break;
 		case 'calsberg': require ('donAdmini.php');	break;
 		case 'guinness': require ('label.php');	break;
		case 'gouden': require ('local.php');	break;
		case 'livinus': require ('dico.php');	break;
		case 'stella': require ('composants.php'); break;
		case 'mere_noel': require ('tele_package.php'); break; 
		case 'grimbergen': require ('tele_activate.php'); break; 
		case 'brigand': require ('opt_param.php'); break; 
		case 'becasse': require ('opt_ipdiscover.php'); break; 
		case 'grottenbier': require ('tele_affect.php'); break; 
		case 'foster': require ('tele_stats.php'); break;
		case 'petasse': require ('tele_actives.php'); break;
		case 'bonnet_rouge': require ('opt_suppr.php'); break; 
		case 'chapeau_faro': require ('group_show.php'); break;
		case 'bourgogne_des_flandres': require ('tele_massaffect.php'); break; 
		case 'moinette': require ('admin_attrib.php'); break; 
		case 'westmalle': require ('blacklist.php');break;
		case 'leffe': require ('rules_redistrib.php');break;
		//case 35: require ('admin_language.php');break;
		case 'delirium': require ('all_soft.php');break;
		case 'gueuze': require ('groups.php');break;
		case 'vondel': require ('show_detail.php');break;
		case 'duchesse_ane': require ('logs.php');break;
		case 'gauloise': require ('multi.php');break;
		case 'hoegaarden': require ('list_computors.php');break;
		case 'chti': require ('repart_tag.php');break;
		case 'corsendonk':require ('admins.php');break;
		case 'malheur' : require ('console.php');break;	
 		default: require ('console.php');		
 	}

if( !isset($_GET["popup"] ))
	require ($_SESSION['FOOTER_HTML']);
	
echo "<script language='javascript'>wait(0);</script>";

?>
