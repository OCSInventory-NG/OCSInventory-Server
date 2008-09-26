<?php
/*
 * Created on 17 juin 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require("req.class.php");
require("fichierConf.class.php");
require_once("preferences.php");
$toBeWritten = "";
if (isset($_SESSION['cvs'][$_GET['tablename']])){
	$result=mysql_query($_SESSION['cvs'][$_GET['tablename']], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$col[]="";
	while( $colname = mysql_fetch_field($result) ) {
		$col[]= $colname->name;
		$toBeWritten .= $colname->name.";";
	}
	$toBeWritten = substr($toBeWritten,0,-1)."\r\n";
	
	while( $cont = mysql_fetch_array($result,MYSQL_ASSOC) ) {
		foreach($col as $key=>$value){
			$toBeWritten.= $cont[$value].";";		
		}
		$toBeWritten = substr($toBeWritten,0,-1)."\r\n";
	}
	$filename="export.csv";
}elseif (isset($_GET['log'])){
	
	if (file_exists($_GET['rep'].$_GET['log'])){
		$tab = file($_GET['rep'].$_GET['log']);
		while(list($cle,$val) = each($tab)) {
 		  $toBeWritten  .= $val."\r\n";
		}
		$filename=$_GET['log'];
	}
}
	
if ($toBeWritten != ""){
	// iexplorer problem
	if( ini_get("zlib.output-compression"))
		ini_set("zlib.output-compression","Off");
		
	header("Pragma: public");
	header("Expires: 0");
	header("Cache-control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-control: private", false);
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=\"".$filename."\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".strlen($toBeWritten));
	echo $toBeWritten;
}else
echo "<font color=red align=center><B>ERREUR, le fichier demandé n'existe pas.</B></font>"
?>
