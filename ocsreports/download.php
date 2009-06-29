<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on 10/24/2006
require_once("preferences.php");

if($_GET["o"]&&$_GET["v"]&&$_GET["n"]&&$_GET["dl"])
{
	$dlQuery="SELECT content FROM ";
	if( strtolower($_GET["n"]) == "ocsagent.exe" ) {
		$dlQuery .= "deploy WHERE name='".$_GET["n"]."'";
		$fname = "ocsagent.exe";
	}
	else if( strtolower($_GET["n"]) == "ocspackage.exe" ) {
		$dlQuery .= "deploy WHERE name='".$_GET["n"]."'";
		$fname = "ocspackage.exe";
	}
	else {
		$dlQuery .= "files WHERE name='".mysql_escape_string($_GET["n"])."' AND os='".mysql_escape_string($_GET["o"])."' AND version='".mysql_escape_string($_GET["v"])."'";
		if($_GET["o"]=="windows")
		{
			$ext="zip";
		}
		else if($_GET["n"]=="agent")
		{
			$ext="";
		}	
		else
		{
			$ext="pl";
		}
		$fname=$_GET["o"]."_".$_GET["n"]."_".$_GET["v"].".".$ext;
	}	
	
	$result=mysql_query($dlQuery, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$cont=mysql_fetch_array($result);

	header("Pragma: public");
	header("Expires: 0");
	header("Cache-control: must-revalidate, post-check=0, pre-check=0");
	header("Cache-control: private", false);
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=\"$fname\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: ".strlen($cont["content"]));
	echo $cont["content"];
}



?>