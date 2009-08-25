<?php
require ('fichierConf.class.php');
$form_name='admin_search';
$ban_head='no';
require_once("header.php");
if ($_POST['onglet'] != $_POST['old_onglet']){
	$onglet=$_POST['onglet'];
	$old_onglet=$_POST['old_onglet'];
	unset($_POST);
	$_POST['old_onglet']=$old_onglet;
	$_POST['onglet']=$onglet;
}
if ($_GET['origine']!= "mach" and $_GET['origine']!= "group"){
	if (isset($_GET['idchecked']) and $_GET['idchecked'] != ""){
		$choise_req_selection['REQ']=$l->g(584);
		$choise_req_selection['SEL']=$l->g(585);
		$select_choise=show_modif($choise_req_selection,'CHOISE',2,$form_name);	
	}
	echo "<font color=red><b>";
	if ($_POST['CHOISE'] == 'REQ' or $_GET['idchecked'] == '' or $_POST['CHOISE'] == ''){
		echo $l->g(901);
		$list_id=$_SESSION['ID_REQ'];
	}
	if ($_POST['CHOISE'] == 'SEL'){
		echo $l->g(902);
		$list_id=$_GET['idchecked'];
	}
	
	//gestion tableau
	if (is_array($list_id))
	$list_id=implode(",", $list_id);
}else
$list_id=$_GET['idchecked'];
echo "</b></font>";
if ($list_id != ""){
if (strpos($_GET['img'], "config_search.png"))
include ("opt_param.php");
if (strpos($_GET['img'], "groups_search.png"))
include ("opt_groups.php");
if (strpos($_GET['img'], "tele_search.png"))
include ("opt_pack.php");
if (strpos($_GET['img'], "sup_search.png"))
include ("opt_sup.php");
if (strpos($_GET['img'], "cadena_ferme.png")){
include ("opt_lock.php");
}
}else
echo "<br><br><b><font color=red size=4>".$l->g(954)."</font></b>";

?>
