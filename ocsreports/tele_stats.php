<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2008-06-18 13:26:31 $$Author: airoine $($Revision: 1.15 $)
@session_start();
	unset($_SESSION['LANGUAGE']);
$header_html = 'NO';
require_once("header.php");
require('require/function_stats.php');
if($_SESSION["lvluser"]==SADMIN){
	if( isset($_GET["delsucc"]) ) {		
		$resSupp = mysql_query("DELETE FROM devices WHERE name='DOWNLOAD' AND tvalue LIKE 'SUCCESS%' AND
		ivalue IN (SELECT id FROM download_enable WHERE fileid='".$_GET["stat"]."') AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_')", $_SESSION["writeServer"]);
	}
	else if( isset($_GET["deltout"]) ) {		
		$resSupp = mysql_query("DELETE FROM devices WHERE name='DOWNLOAD' AND tvalue IS NOT NULL AND  
		ivalue IN (SELECT id FROM download_enable WHERE fileid='".$_GET["stat"]."') AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_')", $_SESSION["writeServer"]);
	}
	else if( isset($_GET["delnotif"]) ) {		
		$resSupp = mysql_query("DELETE FROM devices WHERE name='DOWNLOAD' AND tvalue IS NULL AND 
		ivalue IN (SELECT id FROM download_enable WHERE fileid='".$_GET["stat"]."') AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_')", $_SESSION["writeServer"]);
	}
}
if ($_POST['selOpt'] == "GROUP" or $_GET['option']=="GROUP"){
$sql_group="select hardware_id from groups_cache where group_id=".$_GET['group'];
$res_group = mysql_query($sql_group, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$machines_group="(";
	while ($item_group = mysql_fetch_object($res_group)){
		$machines_group.= $item_group->hardware_id.",";	
	}
	$machines_group=" IN ".substr($machines_group,0,-1).")";		
}
if ($_SESSION["mesmachines"] != ""){
	$sql_mesMachines="select hardware_id from accountinfo a where ".$_SESSION["mesmachines"];
	$res_mesMachines = mysql_query($sql_mesMachines, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$mesmachines="(";
	while ($item_mesMachines = mysql_fetch_object($res_mesMachines)){
		$mesmachines.= $item_mesMachines->hardware_id.",";	
	}
	$mesmachines=" IN ".substr($mesmachines,0,-1).")";	
	
}
$sqlStats="SELECT COUNT(id) as 'nb', tvalue as 'txt' 
			FROM devices d, download_enable e 
			WHERE e.fileid='".$_GET["stat"]."'
 				AND e.id=d.ivalue 
				AND name='DOWNLOAD' 
				AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_' or deviceid='_DOWNLOADGROUP_')";
if (isset($machines_group))
	$sqlStats.= " AND hardware_id".$machines_group;
if (isset($mesmachines))				
	$sqlStats.= " AND hardware_id".$mesmachines;	
$sqlStats.= " GROUP BY tvalue ORDER BY nb DESC";
$resStats = mysql_query($sqlStats, $_SESSION["readServer"]);
 	$tot = 0;
	$quartiers = array();
	$coul = array( 0x0091C3, 0xFFCB03  ,0x33CCCC, 0xFF9900,  0x969696,  0x339966, 0xFF99CC, 0x99CC00);
	$coulHtml = array( "0091C3", "FFCB03"  ,"33CCCC", "FF9900",  "969696",  "339966", "FF99CC", "99CC00");
	$i = 0;
	while( $valStats = mysql_fetch_array( $resStats ) ) {
		$tot += $valStats["nb"];
		if( $valStats["txt"] =="" )
			$valStats["txt"] = $l->g(482);
		$quartiers[] = array( $valStats["nb"], $coul[ $i ], $valStats["txt"]." (".$valStats["nb"].")" );
		$legende[] = array( "color"=>$coulHtml[ $i ], "name"=>$valStats["txt"], "count"=>$valStats["nb"] );
		$i++;
		if( $i > sizeof( $coul ) )
			$i=0;
	}

	$sort = array();
	$index = 0;
	for( $count=0; $count < (sizeof( $quartiers )); $count++ ) {
		if( $count%2==0) {
			$sort[ $count ] = $quartiers[ $index ];
			//echo "sort[ $count ] = quartiers[ $index ];<br>";
			$index++;
		}
		else {
			$sort[ $count ] = $quartiers[ sizeof( $quartiers ) - $index ];			
		}		
	}

if( @mysql_num_rows( $resStats ) == 0 ) {
	echo "<center>".$l->g(526)."</center>";
	die();	
}

if( ! function_exists( "imagefontwidth") ) {
	echo "<br><center><font color=red><b>ERROR: GD for PHP is not properly installed.<br>Try uncommenting \";extension=php_gd2.dll\" (windows) by removing the semicolon in file php.ini, or try installing the php4-gd package.</b></font></center>";
	die();
}
else if( isset($_GET["generatePic"]) ) {	
	camembert($sort);
}
else {
	?>
		<html>
		<head>
		<TITLE>OCS Inventory Stats</TITLE>
		<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
		<META HTTP-EQUIV="Expires" CONTENT="-1">
		<META HTTP-EQUIV="Content-Type" CONTENT="text/html"; charset="UTF-8";>
		<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>
		</HEAD>
	<?php 
//	$ban_head='no';
//	require_once("header.php");
	$sqlStats="SELECT COUNT(DISTINCT HARDWARE_ID) as 'nb' FROM devices d, download_enable e WHERE e.fileid='".$_GET["stat"]."'
	AND e.id=d.ivalue AND name='DOWNLOAD' AND hardware_id NOT IN (SELECT id FROM hardware WHERE deviceid='_SYSTEMGROUP_' or deviceid='_DOWNLOADGROUP_') ";
	if (isset($mesmachines))				
	$sqlStats.= " AND hardware_id".$mesmachines;
	if (isset($machines_group))
	$sqlStats.= " AND hardware_id".$machines_group;
	
	$resStats = mysql_query($sqlStats, $_SESSION["readServer"]);
	
	$resName = mysql_query("SELECT name FROM download_available WHERE fileid='".$_GET["stat"]."'", $_SESSION["readServer"]);
	$valName = mysql_fetch_array( $resName );

	$valStats = mysql_fetch_array( $resStats );
	
	echo "<body OnLoad='document.title=\"".urlencode($valName["name"])."\"'>";
	printEnTete( $l->g(498)." <b>".$valName["name"]."</b> (".$l->g(296).": ".$_GET["stat"]." )");
	echo "<br><center><img src='tele_stats.php?generatePic=1&stat=".$_GET["stat"]."&group=".$_GET["group"]."&option=".$_POST['selOpt']."'></center>";
	if($_SESSION["lvluser"]==SADMIN){
		echo "<table class='Fenetre' align='center' border='1' cellpadding='5' width='50%'><tr BGCOLOR='#C7D9F5'>";
		echo "<td width='33%' align='center'><a href='tele_stats.php?delsucc=1&stat=".$_GET["stat"]."'><b>".$l->g(483)."</b></a></td>";	
		echo "<td width='33%' align='center'><a href='tele_stats.php?deltout=1&stat=".$_GET["stat"]."'><b>".$l->g(571)."</b></a></td>";	
		echo "<td width='33%' align='center'><a href='tele_stats.php?delnotif=1&stat=".$_GET["stat"]."'><b>".$l->g(575)."</b></a></td>";
		echo "</tr></table><br><br>";
	}
	if ($_GET['group']){
	echo "<form name='refresh' method=POST><div align=center>".$l->g(941)." <select name=selOpt OnChange='refresh.submit();'>
				<option value='ALL'";
	if ($_POST['selOpt'] == "ALL")
	echo " selected ";
	echo ">".$l->g(940)."</option>
				<option value='GROUP'";
	if ($_POST['selOpt'] == "GROUP")
	echo " selected ";
	echo ">".$l->g(939)."</option></select>
		</div></form>";
	}
	echo "<table class='Fenetre' align='center' border='1' cellpadding='5' width='50%'>
	<tr BGCOLOR='#C7D9F5'><td width='30px'>&nbsp;</td><td align='center'><b>".$l->g(81)."</b></td><td align='center'><b>".$l->g(55)."</b></td></tr>";
	foreach( $legende as $leg ) {
		echo "<tr><td bgcolor='#".$leg["color"]."'>&nbsp;</td><td>".$leg["name"]."</td><td>
				<a href='index.php?multi=41&prov=stat&id_pack=".$_GET["stat"]."&stat=".urlencode($leg["name"])."'>".$leg["count"]."</a>
				<a href='speed_stat.php?ta=".$leg["name"]."&stat=".$_GET["stat"]."'>&nbsp;stat</a>
			</td></tr>";
	}
	echo "<tr bgcolor='#C7D9F5'><td bgcolor='white'>&nbsp;</td><td><b>".$l->g(87)."</b></td><td><b>".$valStats["nb"]."</b></td></tr>";
	echo "</table><br><br>";
	require_once($_SESSION['FOOTER_HTML']);
}

 
  
?>
