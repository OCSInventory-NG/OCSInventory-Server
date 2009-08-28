<?php
require ('fichierConf.class.php');
$form_name='debug';
$ban_head='no';
$no_error='YES';
require_once("header.php");
//liste des modes de fonctionnement
$list_mode[1]=$l->g(1010);
$list_mode[2]=$l->g(1011);
$list_mode[3]=$l->g(1012);
$list_mode[4]=$l->g(1013);
if (!($_SESSION["lvluser"] == SADMIN or $_SESSION['TRUE_LVL'] == SADMIN))
	die("FORBIDDEN");
echo "<br><br><br>";	
$tab_typ_champ[0]['DEFAULT_VALUE']=$list_mode;
	$tab_typ_champ[0]['INPUT_NAME']="MODE";
	$tab_typ_champ[0]['INPUT_TYPE']=2;
	$tab_name[0]=$l->g(1014)." :";
tab_modif_values($tab_name,$tab_typ_champ,'',$l->g(1015),$comment="");


if (isset($_POST['Reset_modif_x'])){
	echo "<script>";
	echo "self.close();</script>";
}

//passage en mode
if (isset($_POST['Valid_modif_x']) and $_POST["MODE"] != ""){
	AddLog("MODE",$list_mode[$_POST["MODE"]]);
	if ($_POST["MODE"] == 2){
		unset($_SESSION['MODE_LANGUAGE']);
		$_SESSION['DEBUG']="ON";
	}
	elseif ($_POST["MODE"] == 3){
		unset($_SESSION['DEBUG']);
		$_SESSION['MODE_LANGUAGE']="ON";	
	}
	elseif ($_POST["MODE"] == 4){
		$_SESSION['MODE_LANGUAGE']="ON";	
		$_SESSION['DEBUG']="ON";
	}else
	unset($_SESSION['DEBUG'],$_SESSION['MODE_LANGUAGE']);

	echo "<script>";
		echo "window.opener.document.forms['log_out'].submit();";
		echo "self.close();</script>";
}
	require_once($_SESSION['FOOTER_HTML']);
?>
