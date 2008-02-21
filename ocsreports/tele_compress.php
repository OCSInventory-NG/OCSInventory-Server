<?php
header("content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=selection.zip");
if(isset($_GET["timestamp"])){
	require_once("libraries/zip.lib.php");
	$zipfile = new zipfile();
	$rep = $_SERVER["DOCUMENT_ROOT"]."/download/".$_GET["timestamp"]."/";
	//$rep = "../download/".$_GET["timestamp"];
	echo $rep;
	$dir = opendir($rep);
	while($f = readdir($dir))
	   if(is_file($rep.$f))
	     $zipfile -> addFile(implode("",file($rep.$f)),basename($rep.$f));
	closedir($dir);
	flush();
	print $zipfile -> file();
	exit();
}
?>
