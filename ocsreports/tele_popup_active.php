<?php
require ('fichierConf.class.php');
//require('req.class.php');
$ban_head='no';
require_once("header.php");
//require_once('require/function_table_html.php');
require_once('require/function_telediff.php');
//interdiction pour les users autre que SUPER ADMIN
if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
	die("FORBIDDEN");
printEnTete($l->g(465).' => '.$_GET["active"]);
$form_name="form_active";
//javascript pour vérifier que des chaps ne sont pas vides
echo "<script language='javascript'>
		function verif()
		 {
			var msg = '';
			for(i=0; i<document.".$form_name.".elements.length; i++)
			{
				if (document.".$form_name.".elements[i].value == '')	{
					document.".$form_name.".elements[i].style.backgroundColor = 'RED';
					msg = document.".$form_name.".elements[i].name;
				}else
					document.".$form_name.".elements[i].style.backgroundColor = '';

			}
			if (msg != ''){
			alert ('".$l->g(993)."');
			return false;
			}else
			return true;			
		}
	</script>";
//ouverture du formulaire
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";

//si l'activation se fait en manuel
if ($_POST['choix_activ'] == "MAN" and $_POST['valid']){	
	//on vérifie l'existance du fichier info sur le serveur désigné
	//pour être sûr que la valeur du https soit correcte
	$opensslOk = function_exists("openssl_open");
	if( $opensslOk )
		$httpsOk = @fopen("https://".$_POST["HTTPS_SERV"]."/".$_GET["active"]."/info", "r");
		
	// checking if this package contains fragments
	$reqFrags = "SELECT fragments FROM download_available WHERE fileid='".$_GET["active"]."'";
	$resFrags = mysql_query( $reqFrags, $_SESSION["readServer"] );	
	$valFrags = mysql_fetch_array( $resFrags );
	$fragAvail = ($valFrags["fragments"] > 0) ;
	
	if( $fragAvail ){
		$fragOk = @fopen("http://".$_POST["FILE_SERV"]."/".$_GET["active"]."/".$_GET["active"]."-1", "r");
	}
	else
		$fragOk = true;
	if( ! $opensslOk ) 	
		$error = "<br><center><font color=red><b>WARNING: OpenSSL for PHP is not properly installed.<br>Your https server validity was not checked !</b></font></center>";
echo "<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'><tr><td align=center>";	
	
	if( ! $httpsOk && $opensslOk ) 
		$error .= "<b><font color='red'>".$l->g(466)."<br> https://".$_POST["HTTPS_SERV"]."/".$_GET["active"]."/</font></b></td></tr><tr><td align=center>";
	if( $httpsOk ) 
		fclose( $httpsOk );		
	if( ! $fragOk ) 
		$error .= "<b><font color='red'>".$l->g(467)."<br> http://".$_POST['FILE_SERV']."/".$_GET["active"]."/</font></b></td></tr><tr><td align=center>";
	elseif( $fragAvail ) 
		fclose( $fragOk );	
		
	if (! $fragOk or ! $httpsOk){
		$error .= "<br><b><font color='red'>".$l->g(468)."</font></b></td></tr><tr><td align=center>";
		$error .= "<input type='submit' name='YES' value='".$l->g(455)."'>&nbsp&nbsp&nbsp<input type='submit' name='NO' value='".$l->g(454)."'";
	}
	if ($error != ""){
		echo $error;
	}
	echo "</td></tr></table>";	
}

if ((!$error and $_POST['valid'] and $_POST['choix_activ'] == "MAN") or $_POST['YES']){
	activ_pack($_GET["active"],$_POST["HTTPS_SERV"],$_POST['FILE_SERV']);
	echo "<script> alert('".$l->g(469)."');window.opener.document.packlist.submit(); self.close();</script>";	
}

if (!$error and $_POST['valid'] and $_POST['choix_activ'] == "AUTO"){
activ_pack_server($_GET["active"],$_POST["HTTPS_SERV"],$_POST['choix_groupserv']);
echo "<script> alert('".$l->g(469)."');window.opener.document.packlist.submit(); self.close();</script>";	
}


//balise pour permettre l'affichage 
//uniquement quand on arrive sur la popup
echo "<div ";
if (isset($error))
	echo " style='display:none;'";
echo ">";
	//Choix d'activation
	$list_choise['MAN']=$l->g(650);
	$list_choise['AUTO']=$l->g(649);
	$choix_activ="<br>".show_modif($list_choise,'choix_activ',2,$form_name)."<br><br>";
	echo $choix_activ;
	
	//recherche des valeurs par défaut si les valeurs n'existent pas.
	if (!isset($_POST['HTTPS_SERV']) or (!isset($_POST['FILE_SERV']) and $_POST['choix_activ'] == "MAN")){
		$reqdefaultvalues = "SELECT name,tvalue FROM config WHERE name ='DOWNLOAD_URI_INFO' or name='DOWNLOAD_URI_FRAG'";
		$resdefaultvalues = mysql_query( $reqdefaultvalues, $_SESSION["readServer"] );
		while( $valdefaultvalues = mysql_fetch_array($resdefaultvalues) ) {
			$defaultvalues[$valdefaultvalues["name"]]=$valdefaultvalues["tvalue"];
		}
		$_POST['HTTPS_SERV']=$defaultvalues['DOWNLOAD_URI_INFO'];
		$_POST['FILE_SERV']=$defaultvalues['DOWNLOAD_URI_FRAG'];
		$default="localhost/DOWNLOAD";
		if ($_POST['HTTPS_SERV'] == "")
		$_POST['HTTPS_SERV']=$default;
		if ($_POST['FILE_SERV'] == "")
		$_POST['FILE_SERV']=$default;
	}
	//taille des champs de saisie
	$config_input=array('MAXLENGTH'=>255,'SIZE'=>50);
	//pour une activation manuelle=>on demande le serveur ou sont les fragments
	if ($_POST['choix_activ'] == "MAN"){
		$file_serv="<tr height='30px' bgcolor='#F2F2F2'><td align='left'>".$l->g(471)."</td><td>".show_modif($_POST['FILE_SERV'],'FILE_SERV',0,'',$config_input)."/".$_GET["active"]."</td></tr>";
	}//pour une activation sur les serveurs de redistribution
	elseif($_POST['choix_activ'] == "AUTO"){
		//on cherche tous les groupes de serveurs
		$reqGroupsServers = "SELECT DISTINCT name,id FROM hardware WHERE deviceid='_DOWNLOADGROUP_'";
		$resGroupsServers = mysql_query( $reqGroupsServers, $_SESSION["readServer"] );
		$nb_group=0;
		while( $valGroupsServers = mysql_fetch_array( $resGroupsServers ) ) {
			$namefirstgroup=$valGroupsServers["name"];
			$idfirstgroup=$valGroupsServers["id"];
			$groupListServers[$valGroupsServers["id"]]=$valGroupsServers["name"];
			$nb_group++;
		}	
		$file_serv="<tr height='30px' bgcolor='#FFFFFF'><td align='left'>".$l->g(651)."</td><td>";
		//s'il y a plusieurs groupes de serveur, on propose une liste déroulante
		if ($nb_group > 1)
		$file_serv.=show_modif($groupListServers,'choix_groupserv',2);
		else{
		$file_serv.=$namefirstgroup;
		echo "<input type=hidden name='choix_groupserv' id='choix_groupserv' value='".$idfirstgroup."'";
		}
		$file_serv.="</td></tr>";
	}
	//dans les deux cas, si un choix a été fait, on demande l'emplacement du fichier INFO
	if ($_POST['choix_activ'] != ''){		
	$https_serv="<tr height='30px' bgcolor='#FFFFFF'><td align='left'>".$l->g(470)."</td><td>".show_modif($_POST['HTTPS_SERV'],'HTTPS_SERV',0,'',$config_input)."/".$_GET["active"]."</td></tr>";
	echo "<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>";
	echo $file_serv,$https_serv;
	echo "</table><br>";
	echo "<input type='submit' name='valid' id='valid' value='".$l->g(13)."' OnClick='return verif();' >";
	}
echo "</div>";
//fermeture du formulaire.
echo "</form>";


?>
