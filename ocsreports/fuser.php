<?php
require ('fichierConf.class.php');
$form_name='fuser';
$ban_head='no';
$no_error='YES';
require_once("header.php");
if (!($_SESSION["lvluser"] == SADMIN or $_SESSION['TRUE_LVL'] == SADMIN))
	die("FORBIDDEN");
echo "<br><br><br>";	
$tab_typ_champ[0]['DEFAULT_VALUE']=$_POST['FUSER'];
$tab_typ_champ[0]['INPUT_NAME']="FUSER";
$tab_typ_champ[0]['INPUT_TYPE']=0;
$tab_name[0]=$l->g(926)." ";
tab_modif_values($tab_name,$tab_typ_champ,'',$l->g(925),$comment="");

//si l'utilisation a cliqué sur annuler et qu'il était déjà en fuser avant
if (isset($_POST['Reset_modif_x']) and isset($_SESSION['TRUE_USER'])){
	AddLog("FUSER_END",$_POST["FUSER"]);
	$_SESSION["loggeduser"]=$_SESSION['TRUE_USER'];
	$_SESSION["lvluser"]=$_SESSION['TRUE_LVL'];
	unset($_SESSION["mesmachines"],$_SESSION["mytag"],$_SESSION['TRUE_USER'],$_SESSION['TRUE_LVL'],$_SESSION["ipdiscover"]);	
	echo "<script>";
	echo "window.opener.document.forms['log_out'].submit();";
	echo "self.close();</script>";
//sinon, il n'etait pas en fuser
}elseif (isset($_POST['Reset_modif_x'])){
	echo "<script>";
	echo "self.close();</script>";
}

//passage en fuser
if (isset($_POST['Valid_modif_x'])){
	AddLog("FUSER",$_POST["FUSER"]);
	if (!isset($_SESSION['TRUE_USER'])){
		$_SESSION['TRUE_USER']=$_SESSION["loggeduser"];
		$_SESSION['TRUE_LVL']=$_SESSION["lvluser"];
	}
	$_SESSION["loggeduser"]=$_POST["FUSER"];
	echo "<script>";
		echo "window.opener.document.forms['log_out'].submit();";
		echo "self.close();</script>";
	unset($_SESSION["lvluser"],$_SESSION["ipdiscover"]);	
}
	require_once($_SESSION['FOOTER_HTML']);
?>
