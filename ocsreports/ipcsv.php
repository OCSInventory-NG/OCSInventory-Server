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
//Modified on 12/13/2005
	
include("preferences.php");

$dlQuery = str_replace("h.deviceid,","",$_SESSION["query"]);
$result=mysql_query($dlQuery, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));

// iexplorer problem
if( ini_get("zlib.output-compression"))
	ini_set("zlib.output-compression","Off");
	
header("Pragma: public");
header("Expires: 0");
header("Cache-control: must-revalidate, post-check=0, pre-check=0");
header("Cache-control: private", false);
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=\"export.csv\"");
header("Content-Transfer-Encoding: binary");

$colnb = 0;
$nameIndex = -1;
$toBeWritten = "";

while( $colname = mysql_fetch_field($result) ) {
	$cols[] = $colname->name;
	$nameIndex = ($colname->name == $l->g(24) ? $colnb : $nameIndex);
	$colnb++;	
}
writeTab($fp,$cols);

while( $cont = mysql_fetch_array($result,MYSQL_ASSOC) ) {
	writeTab($fp,$cont,$nameIndex);
}

header("Content-Length: ".strlen($toBeWritten));
echo $toBeWritten;

function writeTab ($fich,$tab,$rgn=-1) {	
	global $toBeWritten;
	$colnb = 0;
	foreach($tab as $t) {
			$toBeWritten .= $t;

		$toBeWritten .= ",";
		$colnb++;
	}
	$toBeWritten .= "\r\n";
}
?>
