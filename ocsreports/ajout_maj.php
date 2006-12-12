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
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.5 $)
	
if (isset($_GET['action'])) {
	$action = $_GET['action'];
}

if (isset($_POST['action_form'])) {
	$action_form = $_POST['action_form'];
}

function MAJ_Inventory($systemid)
{
	$queryAcc = "SELECT * FROM accountinfo WHERE hardware_id=$systemid";
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
	$requeteSQL.=" WHERE hardware_id=$systemid";
	// Exécution de la requête SQL
	if( lock($systemid) ) {
		$resultat = mysql_query( $requeteSQL, $_SESSION["writeServer"] );
		unlock($systemid);
	}
	else
		errlock();
		
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

echo "<table class='Items' width='100%' border='0' cellpadding='4'>";
echo "<tr>";
echo "<td valign='center' align='left' width='100%'><b>".$l->g(56)."</b></font></td>";
echo "</tr>";
echo "</table>";

$queryAcInf = "SELECT * FROM accountinfo WHERE hardware_id=$systemid";
$resultAcInf = mysql_query($queryAcInf, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
$item=mysql_fetch_array($resultAcInf,MYSQL_ASSOC);

//********************************************************************
//*                       FORMULAIRE DE SAISIE						 *
//********************************************************************
echo "<form method='POST' name='Ajout_MAJ' action='machine.php'>\n";
echo "<table width='100%' border='0' bgcolor='#C7D9F5' cellpadding='4' style='border: solid thin; border-color:#A1B1F9'>\n";
$indexType = -1;
foreach ($item as $k=>$v) {
	$indexType++;
	$kaff = $k;
	if($k == "DEVICEID" || $k == "UNITID" || $k == "HARDWARE_ID")
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
echo "<input type='hidden' name='systemid' value='".$systemid."'>";
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