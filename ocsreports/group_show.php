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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.4 $)



require('fichierConf.class.php');
require('req.class.php');
require('require/function_opt_param.php');
if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
	die("FORBIDDEN");
//$_GET["multi"] = 24; // To avoid storing systemid in querystring
require_once("preferences.php");
if (isset($_GET['systemid'])) {
	$systemid = $_GET['systemid'];
	if ($systemid == "")
	{
		echo "Please Supply A System ID";
		die();
	}
}
elseif (isset($_POST['systemid'])) {
	$systemid = $_POST['systemid'];
}

if (isset($_GET['state']))
{
	$state = $_GET['state'];
	if ($state == "MAJ")
		echo "<script language='javascript'>window.location.reload();</script>\n";		
}// fin if

if( isset( $_GET["suppack"] ) && $_SESSION["lvluser"] == SADMIN ) {
	if( $_SESSION["justAdded"] == false )
		@mysql_query("DELETE FROM devices WHERE ivalue=".$_GET["suppack"]." AND hardware_id='$systemid' AND name='DOWNLOAD'", $_SESSION["writeServer"]);
	else $_SESSION["justAdded"] = false;
}
else 
	$_SESSION["justAdded"] = false;

if( isset($_POST["actshowgroup"]) && $_SESSION["lvluser"] == SADMIN ) {
	foreach( $_POST as $key=>$val ) {
		if( strpos ( $key, "checkmass" ) !== false ) {
			
			$resDelete = "DELETE FROM groups_cache WHERE hardware_id=$val AND group_id=".$systemid;
			@mysql_query( $resDelete, $_SESSION["writeServer"] );
			
			if( $_POST["actshowgroup"] != 0 ) {
				$reqInsert = "INSERT INTO groups_cache(hardware_id, group_id, static) VALUES ($val, ".$systemid.", ".$_POST["actshowgroup"].")";
				$resInsert = mysql_query( $reqInsert, $_SESSION["writeServer"] );
			}
		}
	}	
}
//update values if user want modify groups' values
if ($_POST['Valid_modif_x'])
{
	if (trim($_POST['NAME'])!= '' and trim($_POST['DESCR'])!=''){
		$req = "UPDATE hardware SET ".	
			"NAME='".$_POST['NAME']."',".
			"DESCRIPTION='".$_POST['DESCR']."' ".
			"where ID='".$systemid."' and deviceid = '_SYSTEMGROUP_'";
		$result = mysql_query($req, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}
	else{
		
		echo "<script>alert('".$l->g(627)."')</script>";
	}
}
$queryMachine   = "SELECT * FROM hardware, groups WHERE ID=$systemid AND hardware_id=$systemid";
$result   = mysql_query( $queryMachine, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
$item     = mysql_fetch_object($result);

if( ! $item ) {
	echo "<script language='javascript'>wait(0);</script>";
	echo "<center><font class='warn'>".$l->g(623)."</font></center>";
	flush();
	die();
}

if( ! empty($item->REQUEST) )
	$pureStat = false;
else {
	$pureStat = true;
}

echo "<html>\n";
echo "<head>\n";
echo "<TITLE>".$item->NAME."</TITLE>\n";
echo "<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>\n";

incPicker();

echo "<script language='javascript'>\n";
echo "\tfunction Ajouter_donnees(systemid)\n";
echo "\t{\n";
echo "\t\twindow.open(\"./index.php?multi=29&popup=1&action=ajouter_donnees&systemid=\" + systemid, \"_self\");\n";
echo "\t}\n\n";
echo "\tfunction MAJ_donnees(systemid)\n";
echo "\t{\n";
echo "\t\twindow.open(\"./index.php?multi=29&popup=1&action=MAJ_donnees&systemid=\" + systemid, \"_self\");\n";
echo "\t}\n";
echo "</script>\n";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html";
if($l->g(0)) 
	echo ";charset=".$l->g(0).";";
else
	echo ";charset=ISO-8859-1;";

echo "\"></head>\n";
echo "<body style='font: Tahoma' alink='#000000' vlink='#000000' link='#000000' bgcolor='#ffffff' text='#000000'>\n";
/*
printEnTete("Détails du groupe <b><font color='red'>".$item->NAME."</font></b>");
if( ! empty($item->REQUEST) )
	echo "<br><i>".$item->REQUEST."</i><br>";*/
// COMPUTER SUMMARY
$tdhdpb = "<td  align='left' width='20%'>";
$tdhfpb = "</td>";
$tdhd = "<td  align='left' width='20%'><b>";
$tdhf = ":</b></td>";
$tdpopup = "<td align='left' width='20%' onclick=\"javascript: OuvrirPopup('group_chang_value.php', '', 'resizable=no, location=no, width=400, height=200, menubar=no, status=no, scrollbars=no, menubar=no')\">";

//if user clic on modify
if($_POST['MODIF_x']){
	//don't show the botton modify
	$img_modif="";
	//list of input we can modify
	$name=show_modif($item->NAME,'NAME',0);
	$description=show_modif($item->DESCRIPTION,'DESCR',1);
	//show new bottons
	$button_valid="<input title='".$l->g(625)."' type='image'  src='image/modif_valid_v2.png' name='Valid_modif'>";
	$button_reset="<input title='".$l->g(626)."' type='image'  src='image/modif_anul_v2.png' name='Reset_modif'>";
}
else{ //only show the botton for modify
	$img_modif="<input title='".$l->g(115)."' type='image' src='image/modif.png' name='MODIF'>";
	$name=textDecode($item->NAME);
	$description=textDecode($item->DESCRIPTION);
	$button_valid="";
	$button_reset="";
}
//form for modify values of group's
echo "<form name='CHANGE' action='' method='POST'>";
echo "<br><br><table align='center' width='65%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>".$tdhd.$l->g(577).$tdhf.$tdhdpb.$name.$tdhfpb;
echo $tdhd.$l->g(593).$tdhf.$tdhdpb.dateTimeFromMysql(textDecode($item->LASTDATE)).$tdhfpb;
if( ! $pureStat  )
	echo "</tr><tr>".$tdhd.$l->g(594).$tdhf.$tdhdpb.date("F j, Y, g:i a",$item->CREATE_TIME ).$tdhfpb;
echo "</tr><tr><td>&nbsp;</td></tr>";
echo $tdhd.$l->g(615).$tdhf."<td  align='left' width='20%' colspan='3'>";
if( ! $pureStat  )
	echo textDecode($item->REQUEST);
else {
	echo $l->g(595);
}

echo "</tr><tr>".$tdhd.$l->g(53).$tdhf.$tdhdpb.$description.$tdhfpb;
echo "<tr><td align='left' colspan=4>".$button_valid."&nbsp&nbsp".$button_reset."&nbsp&nbsp".$img_modif."</td></tr>";
echo "$tdhfpb</table>";
echo "</form>";

//*/// END COMPUTER SUMMARY

if( isset($_GET["action"]) || isset($_POST["action_form"]) ) {
	require("ajout_maj.php");
	die();
}

if( ! isset($_GET["option"]) ) {
	$opt = $l->g(500);
}
else {
	$opt = stripslashes(urldecode($_GET["option"]));
}

$td1	  = "<td height=20px id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>";
$td2      = "<td height=20px bgcolor='white' align='center'>";
$td3      = $td2;
$td4      = "<td height=20px bgcolor='#F0F0F0' align='center'>";
$lblAdm = Array($l->g(500));
$imgAdm = Array("spec");
$lblHdw = Array($l->g(580), $l->g(581));
$imgHdw = Array("ttmachinesred", "ttmachines",);

echo "<br><br>";

echo "<table width='20%' border=0 align='center' cellpadding='0' cellspacing='0'>
		<tr>";
echo img($imgAdm[0],$lblAdm[0], 1, $opt);

if( ! $pureStat )
	echo img($imgHdw[0],$lblHdw[0], 1, $opt);
	
echo img($imgHdw[1],$lblHdw[1], 1, $opt);
echo "</tr></table>";

echo"<br><br><br>";

switch ($opt) :
	case $l->g(500): print_perso($systemid);
						break;
	case $l->g(581):
			  print_computers_cached($systemid);
						break;
	case $l->g(580):
			  print_computers_real($systemid);
						break;
	default : print_perso($systemid);
						break;
	endswitch;					
echo "<script language='javascript'>wait(0);</script>";
flush();
echo "<br></body>";
echo "</html>";

function print_computers_real($systemid) {

	global $l;
	$reqGroup = "SELECT request FROM groups WHERE hardware_id='$systemid'";
	$resGroup = mysql_query( $reqGroup, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
	$valGroup = mysql_fetch_array($resGroup);
	
	if( ! $valGroup["request"] )
		$valGroup["request"] = "''";

	$lbl=$l->g(2);		//Nom de la requete	
	$sql = " deviceid<>'_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_' AND h.id IN (".$valGroup["request"].")";
	$whereId = "h.id";
	$linkId = "h.id";
	$select = array_merge( array("h.id"=>"h.id" ,"deviceid"=>"deviceid"), $_SESSION["currentFieldList"] );	
	$selectPrelim = array( "h.id"=>"h.id" );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "h.id";
	$pics = array("ttmachines.png", "ttmachines_a.png");
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,$pics,true); // Instanciation du nouvel objet de type "Req"
	
	$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,NULL,true);
	flush();
	ShowResults($requete,true,false,false,true,false,false,false,false);
}

function print_computers_cached($systemid) {

	global $l;
	
	//Need all hardware ids in cache table
	$reqIds = "SELECT DISTINCT hardware_id FROM groups_cache WHERE group_id='$systemid'"; 
	$sql = " deviceid<>'_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_' AND h.id IN ('".getGluedIds($reqIds)."')";

	$lbl=$l->g(2);		//Nom de la requete
	$whereId = "h.id";
	$linkId = "h.id";
	$select = array_merge( array("h.id"=>"h.id" ,"deviceid"=>"deviceid"), $_SESSION["currentFieldList"] );	
	$selectPrelim = array( "h.id"=>"h.id" );
	$from = "hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "h.id";
	$pics = array("ttmachines.png", "ttmachines_a.png");
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,$pics,true); // Instanciation du nouvel objet de type "Req"
	
	$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,NULL,true);
	flush();
	ShowResults($requete,true,false,false,true,false,false,false,false,true);
}

function print_perso($systemid) {
	global $l, $td1, $td2, $td3, $td4;
	$i=0;
	$queryDetails = "SELECT * FROM devices WHERE hardware_id=$systemid";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
					
		echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	
	//echo "<tr><td>&nbsp;&nbsp;</td> $td1 "."Libellé"." </td> $td1 "."Valeur"." </td><td>&nbsp;</td></tr>";		
	while($item=mysql_fetch_array($resultDetails,MYSQL_ASSOC)) {
		$optPerso[ $item["NAME"] ][ "IVALUE" ] = $item["IVALUE"];
		$optPerso[ $item["NAME"] ][ "TVALUE" ] = $item["TVALUE"];
	}	
	
	$ii++; $td3 = $ii%2==0?$td2:$td4;
	//IPDISCOVER
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["IPDISCOVER"])&&$optPerso["IPDISCOVER"]["IVALUE"]!=1?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>&nbsp;</td>";
	echo $td3.$l->g(489)."</td>";	
	if( isset( $optPerso["IPDISCOVER"] )) {		
		if( $optPerso["IPDISCOVER"]["IVALUE"]==0 ) echo $td3.$l->g(490)."</td>";	
		else if( $optPerso["IPDISCOVER"]["IVALUE"]==2 ) echo $td3.$l->g(491)." ".$optPerso["IPDISCOVER"]["TVALUE"]."</td>";
		else if( $optPerso["IPDISCOVER"]["IVALUE"]==1 ) echo $td3.$l->g(492)." ".$optPerso["IPDISCOVER"]["TVALUE"]."</td>";
	}
	else {
		echo $td3.$l->g(493)."</td>";
	}
	if( $_SESSION["lvluser"]==SADMIN ){
	echo "<form name='modif_param' id='modif_param' method='POST' action='index.php?multi=22'>";
	echo "<td align=center rowspan=8><a OnClick='recharge(\"$systemid\",\"group\")'><img src='image/modif_a.png' title='".$l->g(285)."'></a></td>";
	echo "</tr>";
	echo "<input type='hidden' id='systemid' name='systemid' value=''>";
	echo "<input type='hidden' id='origine' name='origine' value=''>"; 
	echo "</form>";
	}
	
	$ii++; $td3 = $ii%2==0?$td2:$td4;
	$sql_default_value="select NAME,IVALUE from config where NAME	in ('DOWNLOAD',
															'DOWNLOAD_CYCLE_LATENCY',
															'DOWNLOAD_PERIOD_LENGTH',
															'DOWNLOAD_FRAG_LATENCY',
															'DOWNLOAD_PERIOD_LATENCY',	
															'DOWNLOAD_TIMEOUT',
															'PROLOG_FREQ')";
	$result_default_value = mysql_query($sql_default_value, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($default=mysql_fetch_array($result_default_value)) {
		$optdefault[$default["NAME"] ] = $default["IVALUE"];
	}	
	//FREQUENCY
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["FREQUENCY"])?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>";
	echo $td3.$l->g(494)."</td>";
	if( isset( $optPerso["FREQUENCY"] )) {
		if( $optPerso["FREQUENCY"]["IVALUE"]==0 ) echo $td3.$l->g(485)."</td>";
		else if( $optPerso["FREQUENCY"]["IVALUE"]==-1 ) echo $td3.$l->g(486)."</td>";
		else echo $td3.$l->g(495)." ".$optPerso["FREQUENCY"]["IVALUE"]." ".$l->g(496)."</td>";
	}
	else {
		echo $td3.$l->g(497)."</td>";
	}
	
	echo "</tr>";
	
	//DOWNLOAD_SWITCH
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["DOWNLOAD_SWITCH"])?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>";
	echo $td3.$l->g(417)." <font color=green size=1><i>DOWNLOAD</i></font> </td>";
	if( isset( $optPerso["DOWNLOAD_SWITCH"] )) {
		if( $optPerso["DOWNLOAD_SWITCH"]["IVALUE"]==0 ) echo $td3.$l->g(733)."</td>";
		else if( $optPerso["DOWNLOAD_SWITCH"]["IVALUE"]==1 ) echo $td3.$l->g(205)."</td>";
		else echo $td3."</td>";
	}
	else {
		echo $td3.$l->g(488)."(";
		if ($optdefault["DOWNLOAD"] == 1) echo $l->g(205); else echo $l->g(733);
		echo ")</td>";
	}
		
	echo "</tr>";
	
	//DOWNLOAD_CYCLE_LATENCY
	optperso("DOWNLOAD_CYCLE_LATENCY",$l->g(720)." <font color=green size=1><i>DOWNLOAD_CYCLE_LATENCY</i></font>",$optPerso,1,$optdefault["DOWNLOAD_CYCLE_LATENCY"],$l->g(511));
	
	//DOWNLOAD_FRAG_LATENCY
	optperso("DOWNLOAD_FRAG_LATENCY",$l->g(721)." <font color=green size=1><i>DOWNLOAD_FRAG_LATENCY</i></font>",$optPerso,1,$optdefault["DOWNLOAD_FRAG_LATENCY"],$l->g(511));

	
	//DOWNLOAD_PERIOD_LATENCY
	optperso("DOWNLOAD_PERIOD_LATENCY",$l->g(722)." <font color=green size=1><i>DOWNLOAD_PERIOD_LATENCY</i></font>",$optPerso,1,$optdefault["DOWNLOAD_PERIOD_LATENCY"],$l->g(511));
	
	//DOWNLOAD_PERIOD_LENGTH
	optperso("DOWNLOAD_PERIOD_LENGTH",$l->g(723)." <font color=green size=1><i>DOWNLOAD_PERIOD_LENGTH</i></font>",$optPerso,1,$optdefault["DOWNLOAD_PERIOD_LENGTH"]);

	//PROLOG_FREQ
	optperso("PROLOG_FREQ",$l->g(724)." <font color=green size=1><i>PROLOG_FREQ</i></font>",$optPerso,1,$optdefault["PROLOG_FREQ"],$l->g(730));

	//TELEDEPLOY
	$resDeploy = @mysql_query("SELECT a.name, d.tvalue,d.ivalue, e.pack_loc  FROM devices d, download_enable e LEFT JOIN download_available a 
	ON e.fileid=a.fileid WHERE d.name='DOWNLOAD' AND e.id=d.ivalue AND d.hardware_id=$systemid"); 
	 
	if( mysql_num_rows( $resDeploy )>0 ) {
			
		while( $valDeploy = mysql_fetch_array( $resDeploy ) ) {
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			echo "<tr>";
			echo "<td bgcolor='white' align='center' valign='center'><img width='15px' src='image/red.png'></td>";
			echo $td3.$l->g(498)." <b>".$valDeploy["name"]."</b> </td>";			
			echo $td3.$l->g(499).": ".$valDeploy["pack_loc"]."</td>";//$l->g(81)."cac: ".($valDeploy["tvalue"]!=""?$valDeploy["tvalue"]:$l->g(482))."</td>";
			if( $_SESSION["lvluser"]==SADMIN )	
				echo "$td3 <a href='index.php?multi=29&popup=1&suppack=".$valDeploy["ivalue"]."&systemid=".
				urlencode($systemid)."&option=".urlencode($l->g(500))."'>".$l->g(122)."</a></td>";
			echo "</tr>";
		}
	}
	if( $_SESSION["lvluser"]==SADMIN )
		echo "<tr><td colspan='10' align='right'><a href='index.php?multi=24&systemid=$systemid&isgroup=1'>".$l->g(501)."</a></td></tr>";	
	echo "</table><br>";
}

function print_item_header($text)
{
	echo "<br><br><table align=\"center\"  width='100%'  cellpadding='4'>";
	echo "<tr>";
	echo "<td align='center' width='100%'><b><font color='blue'>".strtoupper($text)."</font></b></td>";
	echo "</tr>";
	echo "</table><br>";	
}

function img($i,$a,$avail,$opt) {
	global $systemid;

	if( $opt == $a ) {
		$suff = "_a";
	}
	
	if( $avail ) {
		$href = "<a href='index.php?multi=29&popup=1&systemid=".urlencode($systemid)."&option=".urlencode($a)."'>";
		$fhref = "</a>";
		$img = "<img title=\"".htmlspecialchars($a)."\" src='image/{$i}{$suff}.png' />";
	}
	else {
		$href = "";
		$fhref = "";
		$img = "<img title=\"".htmlspecialchars($a)."\" src='image/{$i}_d.png' />";
	}
	
	return "<td width='80px' align='center'>".$href.$img.$fhref."</td>";

}

function isAvail($lbl) {
	global $systemid,$l;

	switch (stripslashes(urldecode($lbl))) {
		case $l->g(56) : $tble = "accountinfo";
							break;
		case $l->g(26)  : $tble = "memories";
							break;
		case $l->g(63)  : $tble = "storages";
							break;
		case $l->g(92)  : $tble = "drives";
							break;
		case $l->g(273)  : $tble = "bios";
							break;
		case $l->g(96)  : $tble = "sounds";
							break;
		case $l->g(61)  : $tble = "videos";
							break;
		case $l->g(91)  : $tble = "inputs";
							break;
		case $l->g(97)  : $tble = "monitors";
							break;
		case $l->g(82) : $tble = "networks";
							break;
		case $l->g(272) : $tble = "ports";
							break;
		case $l->g(79) : $tble = "printers";
							break;
		case $l->g(93) : $tble = "controllers";
							break;
		case $l->g(271) : $tble = "slots";
							break;
		case $l->g(20) : $tble = "softwares";
							break;
		case $l->g(270) : $tble = "modems";	
							break;
		case $l->g(211) : $tble = "registry";
							break;
		case $l->g(512) : $tble = "download_history";
							break;					
		case $l->g(54):	return true;
							break;
	}
	$resAv = mysql_query("SELECT hardware_id FROM $tble WHERE hardware_id=$systemid", $_SESSION["readServer"] );
	//echo "SELECT hardware_id FROM $tble WHERE hardware_id=$systemid";
	$valAvail = mysql_num_rows( $resAv );
	return ($valAvail>0);
}
function show_modif($name,$input_name,$input_type)
{
	if ($input_type == 1)
	return "<textarea name='".$input_name."' cols='30' rows='5' onFocus=\"this.style.backgroundColor='white'\" onBlur=\"this.style.backgroundColor='#C7D9F5'\"\>".textDecode($name)."</textarea>";
	else
	return "<input type='text' name='".$input_name."' value=\"".textDecode($name)."\" onFocus=\"this.style.backgroundColor='white'\" onBlur=\"this.style.backgroundColor='#C7D9F5'\">";
}


?>