<?php
require_once("preferences.php");
header("content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=selection.zip");
if(isset($_GET["timestamp"])){
	require_once("libraries/zip.lib.php");
	$zipfile = new zipfile();
	//looking for the directory for pack
	$sql_document_root="select tvalue from config where NAME='DOWNLOAD_PACK_DIR'";
	$res_document_root = mysql_query( $sql_document_root, $_SESSION["readServer"] );
	while( $val_document_root = mysql_fetch_array( $res_document_root ) ) {
		$document_root = $val_document_root["tvalue"];
	}
	//if no directory in base, take $_SERVER["DOCUMENT_ROOT"]
	if (!isset($document_root))
	$document_root = $_SERVER["DOCUMENT_ROOT"];
	$rep = $document_root."/download/".$_GET["timestamp"]."/";
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
