<?php 



require ('fichierConf.class.php');
require_once('require/function_table_html.php');
require_once('require/function_config_generale.php');
if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
$def_onglets[$l->g(499)]=$l->g(499); //Serveur
$def_onglets[$l->g(728)]=$l->g(728); //Inventaire
$def_onglets[$l->g(312)]=$l->g(312); //IP Discover
$def_onglets[$l->g(512)]=$l->g(512); //Télédéploiement
$def_onglets[$l->g(628)]=$l->g(628); //Serveur de redistribution
$def_onglets[$l->g(583)]=$l->g(583); //Groupes
$def_onglets[$l->g(211)]=$l->g(211); //Registre
$def_onglets[$l->g(734)]=$l->g(734); //Fichiers d'inventaire
$def_onglets[$l->g(735)]=$l->g(735); //Filtres
$def_onglets[$l->g(760)]=$l->g(760); //Filtres
if ($_POST['Valid'] == "Valid")
$MAJ=update_default_value($_POST); //function in function_table_html.php
echo "<font color=green ><center><b>".$MAJ."</b></center></font>";
$form_name='modif_onglet';
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action='index.php?multi=4'>";
onglet($def_onglets,$form_name,'onglet',7);
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td>";
if ($_POST['onglet'] == $l->g(728) ){
	
	pageinventory($form_name);
	
}
if ($_POST['onglet'] == $l->g(499) or $_POST['onglet'] == ""){
	
 	pageserveur($form_name);
	
}
if ($_POST['onglet'] == $l->g(312)){	
	
	pageipdiscover($form_name);
}
if ($_POST['onglet'] == $l->g(512)){
	
	pageteledeploy($form_name);
}
if ($_POST['onglet'] == $l->g(628)){
	
	pageredistrib($form_name);
}
if ($_POST['onglet'] == $l->g(583)){
	
	pagegroups($form_name);
}
if ($_POST['onglet'] == $l->g(211)){
	
	pageregistry($form_name);
}
if ($_POST['onglet'] == $l->g(734)){
	
	pagefilesInventory($form_name);
}
if ($_POST['onglet'] == $l->g(735)){
	
	pagefilter($form_name);
}
if ($_POST['onglet'] == $l->g(760)){
	
	pagewebservice($form_name);
}
echo "</table></form>";