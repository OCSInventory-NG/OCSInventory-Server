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
//Modified on 11/25/2005

$_GET["sessid"] = isset( $_POST["sessid"] ) ? $_POST["sessid"] : $_GET["sessid"];
if( isset($_GET["sessid"])){
	session_id($_GET["sessid"]);
	session_start();
	
	if( !isset($_SESSION["loggeduser"]) ) {
		die("FORBIDDEN");
	}
}
else
	die("FORBIDDEN");
	
require ('preferences.php');

if (isset($_GET['action']))
{
	$action = $_GET['action'];
}

if (isset($_POST['action_form']))
{
	$action_form = $_POST['action_form'];
}

if (isset($_GET['systemid']))
{
	$systemid = $_GET['systemid'];
	if ($systemid == "")
	{
		echo "Please Supply A System ID";
	}
}
elseif (isset($_POST['systemid']))
{
	$systemid = $_POST['systemid'];
}

function MAJ_Inventory($systemid)
{
	$queryAcc = "SELECT * FROM accountinfo WHERE deviceid='$systemid' LIMIT 0,1";
	$resultAcc = mysql_query($queryAcc, $_SESSION["readServer"])  or die(mysql_error($_SESSION["readServer"]));
	$item=mysql_fetch_array($resultAcc,MYSQL_ASSOC);
	foreach ($item as $k => $v) {
		$lesCol[] = $k;
	}
	
	$requeteSQL = "UPDATE accountinfo SET ";
	$indexType=0;
	foreach ($_POST as $kp => $vp) {		
		if(!in_array($kp,$lesCol))
			continue;
		$indexType++;	
		
		if(mysql_field_type($resultAcc,$indexType)=="date") {
			if($vp)
				$vp = dateToMysql($vp);			
		}
		
		$requeteSQL.="$kp='$vp',";		
	}
	
	$requeteSQL = substr($requeteSQL,0,strlen($requeteSQL)-1);
	$requeteSQL.=" WHERE deviceid='$systemid'";
	// Exécution de la requête SQL
	
	$resultat = mysql_query( $requeteSQL, $_SESSION["writeServer"] ) or die(mysql_error($_SESSION["writeServer"]));
	
	return;
}

if ($action_form == 'modifier')
{	
	$systemid = $_POST["systemid"];
	MAJ_Inventory($systemid);	
	echo "<script language='javascript'>\n";
	echo "\twindow.open(\"./machine.php?sessid=".session_id()."&systemid=$systemid&state='MAJ'\", \"_self\");\n";
	echo "</script>\n";
}


$queryHw = "SELECT * FROM hardware WHERE (DEVICEID='$systemid')";
$resultHw = mysql_query( $queryHw, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$item = mysql_fetch_object($resultHw);

echo "<html>\n";
echo "<head>\n";
echo "<TITLE>".$l->g(48)." -$systemid</TITLE>\n";
echo "<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>\n<script language=\"javascript\" type=\"text/javascript\" src=\"datetimepicker.js\"></script>";
echo "</head>\n";
echo "<body>\n";


// COMPUTER SUMMARY
$tdhdpb = "<td valign='top' align='left' width='15%'>";
$tdhfpb = "</font></td>";
$tdhd = "<td valign='top' align='left' width='15%'><b>";
$tdhf = ":</b></font></td>";

echo "<table width='100%' border='0' bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'><tr><td width='50%'>";

echo "<table width='70%' align='center' border='0' bgcolor='#C7D9F5'>";
echo "<tr>".$tdhd.$l->g(49).$tdhf.$tdhdpb.utf8_decode($item->NAME).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(33).$tdhf.$tdhdpb.utf8_decode($item->WORKGROUP).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(46).$tdhf.$tdhdpb.utf8_decode($item->LASTDATE).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(34).$tdhf.$tdhdpb.utf8_decode($item->IPADDR).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(24).$tdhf.$tdhdpb.utf8_decode($item->USERID).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(26).$tdhf.$tdhdpb.utf8_decode($item->MEMORY).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(50).$tdhf.$tdhdpb.utf8_decode($item->SWAP).$tdhfpb."</tr>";

echo "</table></td><td>";

echo "<table width='70%' align='center' border='0' bgcolor='#C7D9F5'>";
echo "<tr>".$tdhd.$l->g(274).$tdhf.$tdhdpb.utf8_decode($item->OSNAME).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(275).$tdhf.$tdhdpb.utf8_decode($item->OSVERSION).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(286).$tdhf.$tdhdpb.utf8_decode($item->OSCOMMENTS).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(51).$tdhf.$tdhdpb.utf8_decode($item->WINCOMPANY).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(24).$tdhf.$tdhdpb.utf8_decode($item->WINOWNER).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(111).$tdhf.$tdhdpb.utf8_decode($item->WINPRODID).$tdhfpb."</tr>";

echo "</table></td></tr></table>";
//*/// END COMPUTER SUMMARY

echo "<br><table width='100%' border='0' bgcolor='#C7D9F5' cellpadding='4' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>";
echo "<td valign='top' align='right' width='15%'><b>".$l->g(56).":</td>";
echo "<td valign='top' width='85%'><font face='Tahoma'size='2'>".utf8_decode($item->DESCRIPTION)."</td>";
echo "</tr>";
echo "</table><br>";

echo "<table class='Items' width='100%' border='0' cellpadding='4'>";
echo "<tr>";
echo "<td valign='center' align='left' width='100%'><b>".$l->g(56)."</b></font></td>";
echo "</tr>";
echo "</table>";

$queryAcInf = "SELECT * FROM accountinfo WHERE deviceid='$systemid' LIMIT 0,1";
$resultAcInf = mysql_query($queryAcInf, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
$item=mysql_fetch_array($resultAcInf,MYSQL_ASSOC);

//********************************************************************
//*                       FORMULAIRE DE SAISIE						 *
//********************************************************************
echo "<form method='POST' name='Ajout_MAJ' action='./ajout_maj.php'>\n";
echo "<table width='100%' border='0' bgcolor='#C7D9F5' cellpadding='4' style='border: solid thin; border-color:#A1B1F9'>\n";
$indexType = -1;
foreach ($item as $k=>$v) {
	$indexType++;
	$kaff = $k;
	if($k == "DEVICEID" || $k == "UNITID")
		continue;
	if($k == "TAG")
		$kaff = TAG_LBL;
	
	if(mysql_field_type($resultAcInf,$indexType)=="date")
		echo "<tr bgcolor='#FFFFFF'><td align='right'><b>$kaff:</b></font></td><td>
		<input READONLY ".dateOnClick($k)." type='text' tabindex='5' name='$k' id='$k' value='".dateFromMysql($v)."'>".datePick($k).
		"&nbsp;&nbsp;</td></tr>\n";
	else
		echo "<tr bgcolor='#F2F2F2'><td align='right'><b>$kaff:</b></font></td><td><input type='text' tabindex='1' name='$k' value='$v'></td></tr>\n";
}

// les dates
echo "</table><br>\n";
echo "<table class='Items' width='100%' border='0' cellpadding='4'>";
echo "<tr><td align='center' colspan='2'>\n";
echo "<input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" class='bouton' type='submit' value='".$l->g(114)."' $event_mouse>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
echo "<input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" class='bouton' type='button' value='   ".$l->g(113)."   ' onClick='window.open(\"./machine.php?sessid=".session_id()."&systemid=$systemid\", \"_self\");' $event_mouse>\n";
echo "<input type='hidden' name='sessid' value='".session_id()."'>";
echo "</td></tr>\n";
echo "</table>\n";

if ($action == "ajouter_donnees")
{
	echo "<input type='hidden' name='action_form' value='ajouter'>\n";
}
elseif ($action == "MAJ_donnees")
{
	echo "<input type='hidden' name='action_form' value='modifier'>\n";
}

echo "<input type='hidden' name='systemid' value='$systemid'>\n";
echo "</form>\n";
echo "</body>";
echo "</html>";
?>