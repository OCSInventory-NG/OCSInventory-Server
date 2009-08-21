<?php
@session_start();

require_once('fichierConf.class.php');

/***************************************************** First installation checking *********************************************************/
if( (!$fconf=@fopen("dbconfig.inc.php","r")) 
		|| (!function_exists('session_start')) 
		|| (!function_exists('mysql_connect'))) {
	require('install.php');	
	die();
}
else
	fclose($fconf);
require_once("preferences.php");
/******************************************Checking sql update*********************************************/
if (!isset($_SESSION['SQL_BASE_VERS'])){
	$sql_log="select TVALUE from config where name='GUI_VERSION'";
	$result_log = mysql_query($sql_log, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($value=mysql_fetch_array($result_log))
		$_SESSION['SQL_BASE_VERS'] = $value['TVALUE'];	
}
if (GUI_VER	> $_SESSION['SQL_BASE_VERS']){
	unset($_SESSION['SQL_BASE_VERS']);
	require('install.php');
	die();	
}
//SECURITY
$_POST=escape_string($_POST);
$_GET=escape_string($_GET);
@set_time_limit(0);
//

//pour ne pas tenir compte des erreurs renvoyés par l'identification
//pour le fuser, la variable $no_error est = 'YES'
if (!isset($no_error))
$no_error='NO';
/**************************************mise en place des répertoires de pluggins et d'auhentification************************************/
if (!isset($_SESSION['plugin_rep']) or !isset($_SESSION['CONF_MYSQL'])){
//	$rep=explode("/", $_SERVER["DOCUMENT_ROOT"].$_SERVER["PHP_SELF"]);
//	array_pop($rep);
	$_SESSION['backend']="backend/";
	$_SESSION['plugin_rep']="pluggins/";
	$_SESSION['CONF_MYSQL']="dbconfig.inc.php";
	$_SESSION['HEADER_HTML']="require/html_header.php";
	$_SESSION['FOOTER_HTML']="footer.php";
}

/*****************************************************GESTION DU LOGOUT*********************************************/
if ($_POST['LOGOUT'] == 'ON'){
	unset($_SESSION["loggeduser"],
		  $_SESSION["lvluser"],
		  $_SERVER['PHP_AUTH_USER'],
		  $_SESSION["mesmachines"],
		  $_SESSION['TRUE_USER'],
		  $_SESSION['TRUE_LVL'],
		  $_SESSION['DEBUG'],
		  $_SESSION["ipdiscover"],
		  $_SESSION["mytag"],
		  $_SESSION["LANGUAGE_FILE"],
		  $_SESSION['LOG_GUI']);
}
/**********************************************************GESTION DES COLONNES DES TABLEAUX PAR COOKIES***********************************/
require_once('require/function_cookies.php');
if (isset($_POST['SUP_COL']) and $_POST['SUP_COL'] != "" and isset($_SESSION['col_tab'][$_POST['TABLE_NAME']])){
	unset($_SESSION['col_tab'][$tab_name][$_POST['SUP_COL']]);
	cookies_add($_POST['TABLE_NAME'],implode('///',$_SESSION['col_tab'][$_POST['TABLE_NAME']]));
}
if (isset($_POST['RAZ']) and $_POST['RAZ'] != ""){
	cookies_reset($_POST['TABLE_NAME']);
}
if (isset($_POST['restCol'.$_POST['TABLE_NAME']]) and $_POST['restCol'.$_POST['TABLE_NAME']] != ''){
	$_SESSION['col_tab'][$tab_name][$_POST['restCol'.$tab_name]]=$_POST['restCol'.$tab_name];
	cookies_add($_POST['TABLE_NAME'],implode('///',$_SESSION['col_tab'][$_POST['TABLE_NAME']]));
}

/********************************************************GESTION DE LA LANGUE PAR COOKIES**********************************************/
if (isset($_POST['LANG'])){
	unset($_SESSION['LANGUAGE']);
	cookies_add('LANG',$_POST['LANG']);	
	$_SESSION['LANGUAGE']=$_POST['LANG'];
	$_SESSION["LANGUAGE_FILE"]=new language($_SESSION['LANGUAGE']);
}
//unset($_SESSION['LANGUAGE']);
//si la langue par défaut n'existe pas, on récupérer le cookie
if (!isset($_SESSION['LANGUAGE']) or !isset($_SESSION["LANGUAGE_FILE"])){
	if (isset($_COOKIE['LANG']))
	$_SESSION['LANGUAGE']=$_COOKIE['LANG'];
	if (!isset($_COOKIE['LANG']))
	$_SESSION['LANGUAGE']=DEFAULT_LANGUAGE;
	$_SESSION["LANGUAGE_FILE"]=new language($_SESSION['LANGUAGE']);
}
$l = $_SESSION["LANGUAGE_FILE"];

/*********************************************************gestion de l'authentification****************************************************/
if (!isset($_SESSION["loggeduser"]))
require_once('backend/AUTH/auth.php');

/**********************************************************gestion des droits sur les TAG****************************************************/
if (!isset($_SESSION["lvluser"]))
require_once('backend/identity/identity.php');

/**********************************************************gestion des droits sur l'ipdiscover****************************************************/
if (!isset($_SESSION["ipdiscover"]) and $_GET['multi'] == 3)
require_once('backend/ipdiscover/ipdiscover.php');
elseif($_GET['multi'] != 3)
unset($_SESSION['ipdiscover']);

/*********************************************************gestion de la suppression automatique des machines trop vieilles*************************/
//require_once('pluggins/options_config/del_old_computors.php');

/***********************************************************gestion des logs*************************************************************************/
if (!isset($_SESSION['LOG_GUI'])){
	$sql_log="select name,ivalue,tvalue from config where name= 'LOG_GUI' or name='LOG_DIR' or name='LOG_SCRIPT'";
	$result_log = mysql_query($sql_log, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($value_log=mysql_fetch_array($result_log)) {
		if ($value_log["name"] == 'LOG_GUI')
			$_SESSION['LOG_GUI'] = $value_log['ivalue'];
		if ($value_log["name"] == 'LOG_DIR')
			$_SESSION['LOG_DIR'] = $value_log['tvalue'];
		if ($value_log["name"] == 'LOG_SCRIPT')
			$_SESSION['LOG_SCRIPT'] = $value_log['tvalue'];
	}
	if (!isset($_SESSION['LOG_GUI']))
		$_SESSION['LOG_GUI']=0;
	if (!isset($_SESSION['LOG_DIR']))
		$_SESSION['LOG_DIR']='';
	if (!isset($_SESSION['LOG_SCRIPT']))
		$_SESSION['LOG_SCRIPT']='';
}
/****************END GESTION LOGS***************/

if (!isset($header_html) or $header_html != 'NO'){
	//echo $_SESSION['HEADER_HTML'];
	require_once ($_SESSION['HEADER_HTML']);
	//echo "toto";
}

if((!isset($_SESSION["loggeduser"]) or !isset($_SESSION["lvluser"]) or $_SESSION["lvluser"] == "") and $no_error != 'YES')
{		
	echo "<br><br><center><b><font color=red>".$LIST_ERROR."</font></b></center><br>";
	require_once($_SESSION['FOOTER_HTML']);
	die();
}


?>