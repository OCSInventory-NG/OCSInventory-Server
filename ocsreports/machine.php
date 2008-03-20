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
//Modified on $Date: 2008-03-20 16:26:48 $$Author: airoine $($Revision: 1.21 $)

require('fichierConf.class.php');
@session_start();
require_once("preferences.php");
$ban_head='no';
require_once("header.php");
require('require/function_opt_param.php');
if( ! in_array($_SESSION["lvluser"], array(SADMIN,LADMIN,ADMIN) )) {
	die("FORBIDDEN");
}

unset( $_SESSION["isgroup"] );
//$_GET["multi"] = 24; // To avoid storing systemid in querystring

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
//for update blacklist serial or mac
update_blacklist();

if (isset($_GET['state']))
{
	$state = $_GET['state'];
	if ($state == "MAJ")
		echo "<script language='javascript'>window.location.reload();</script>\n";		
}// fin if

if( isset( $_GET["suppack"] ) &  $_SESSION["lvluser"]==SADMIN  ) {
	if( $_SESSION["justAdded"] == false )
		@mysql_query("DELETE FROM devices WHERE ivalue=".$_GET["suppack"]." AND hardware_id='$systemid' AND name='DOWNLOAD'", $_SESSION["writeServer"]);
	else $_SESSION["justAdded"] = false;
}
else 
	$_SESSION["justAdded"] = false;
	//TODO: voir si on loggue les evenements de groupe
if( isset( $_GET["actgrp"] )) {	
		//vérification si la valeur correspond à un groupe
		$reqGroups = "SELECT h.id id
					  FROM hardware h left join accountinfo a on h.id='".$_GET["grp"]."' 
					  WHERE h.deviceid='_SYSTEMGROUP_' ";
		//pour les autres qu'SADMIN, ajout que pour les groupes déclarés visibles
		if ($_SESSION["lvluser"]!=SADMIN)
			$reqGroups .= " and a.TAG = 'GROUP_4_ALL'";
		$resGroups = mysql_query( $reqGroups, $_SESSION["readServer"] );
		$valGroups = mysql_fetch_array( $resGroups ); 
		if (isset($valGroups->id)){
			$reqDelete = "DELETE FROM groups_cache WHERE hardware_id=".$systemid." AND group_id=".$_GET["grp"];
			
			if( $_GET["actgrp"] == 0 ) 
				$reqDelete .= " AND static<>0";
			$reqInsert = "INSERT INTO groups_cache(hardware_id, group_id, static) VALUES (".$systemid.", ".$_GET["grp"].", ".$_GET["actgrp"].")";
			@mysql_query( $reqDelete, $_SESSION["writeServer"] );
			if( $_GET["actgrp"] != 0 )
				@mysql_query( $reqInsert, $_SESSION["writeServer"] );
		}
}

$queryMachine    = "SELECT * FROM hardware WHERE (ID=$systemid)";
$result   = mysql_query( $queryMachine, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
$item     = mysql_fetch_object($result);

echo "<html>\n";
echo "<head>\n";
echo "<TITLE>".$item->NAME."</TITLE>\n";
echo "<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>\n";

incPicker();

echo "<script language='javascript'>\n";
echo "\tfunction Ajouter_donnees(systemid)\n";
echo "\t{\n";
echo "\t\twindow.open(\"./machine.php?action=ajouter_donnees&systemid=\" + systemid, \"_self\");\n";
echo "\t}\n\n";
echo "\tfunction MAJ_donnees(systemid)\n";
echo "\t{\n";
echo "\t\twindow.open(\"./machine.php?action=MAJ_donnees&systemid=\" + systemid, \"_self\");\n";
echo "\t}\n";
echo "</script>\n";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html";
if($l->g(0)) 
	echo ";charset=".$l->g(0).";";
else
	echo ";charset=ISO-8859-1;";

echo "\"></head>\n";
echo "<body alink='#000000' vlink='#000000' link='#000000' bgcolor='#ffffff' text='#000000'>\n";

if( ! $item ) {
	echo "<script language='javascript'>wait(0);</script>";
	flush();
	die();
}

// COMPUTER SUMMARY
$tdhdpb = "<td  align='left' width='20%'>";
$tdhfpb = "</td>";
$tdhd = "<td  align='left' width='20%'><b>";
$tdhf = ":</b></td>";

echo "<table width='100%' border='0' bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'><tr><td width='50%'>";

echo "<table width='80%' align='center' border='0' bgcolor='#C7D9F5'>";
echo "<tr>".$tdhd.$l->g(49).$tdhf.$tdhdpb.textDecode($item->NAME).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(33).$tdhf.$tdhdpb.textDecode($item->WORKGROUP).$tdhfpb."</tr>";
if( $item->USERDOMAIN ) echo "<tr>".$tdhd.$l->g(557).$tdhf.$tdhdpb.textDecode($item->USERDOMAIN).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(46).$tdhf.$tdhdpb.dateTimeFromMysql(textDecode($item->LASTDATE)).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(34).$tdhf.$tdhdpb.textDecode($item->IPADDR).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(24).$tdhf.$tdhdpb.textDecode($item->USERID).$tdhfpb."</tr>";

$sqlMem = "SELECT SUM(capacity) AS 'capa' FROM memories WHERE hardware_id=$systemid";
$resMem = mysql_query($sqlMem, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$valMem = mysql_fetch_array( $resMem );

if( $valMem["capa"] > 0 )
	$memory = $valMem["capa"];
else
	$memory = $item->MEMORY;

echo "<tr>".$tdhd.$l->g(26).$tdhf.$tdhdpb.$memory.$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(50).$tdhf.$tdhdpb.textDecode($item->SWAP).$tdhfpb."</tr>";

echo getNetName($systemid);

echo "</table></td><td>";

echo "<table width='90%' align='center' border='0' bgcolor='#C7D9F5'>";
echo "<tr>".$tdhd.$l->g(274).$tdhf.$tdhdpb.textDecode($item->OSNAME).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(275).$tdhf.$tdhdpb.textDecode($item->OSVERSION).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(286).$tdhf.$tdhdpb.textDecode($item->OSCOMMENTS).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(51).$tdhf.$tdhdpb.textDecode($item->WINCOMPANY).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(348).$tdhf.$tdhdpb.textDecode($item->WINOWNER).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(111).$tdhf.$tdhdpb.textDecode($item->WINPRODID).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(553).$tdhf.$tdhdpb.textDecode($item->WINPRODKEY).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(357).$tdhf.$tdhdpb.textDecode($item->USERAGENT).$tdhfpb."</tr>";
echo "</table></td></tr></table>";
//*/// END COMPUTER SUMMARY

echo "<br><table width='100%' border='1' bgcolor='#C7D9F5' cellpadding='4' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>";
echo "<td  align='right' width='15%'><b>Description:</td>";
echo "<td  width='85%'>".textDecode($item->DESCRIPTION)."</td>";
echo "</tr>";
echo "</table>";

if( isset($_GET["action"]) || isset($_POST["action_form"]) ) {
	require("ajout_maj.php");
	die();
}

if( ! isset($_GET["option"]) ) {
	$opt = $l->g(56);
}
else {
	$opt = stripslashes(urldecode($_GET["option"]));
}

$td1	  = "<td height=20px id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>";
$td2      = "<td height=20px bgcolor='white' align='center'>";
$td3      = $td2;
$td4      = "<td height=20px bgcolor='#F0F0F0' align='center'>";
$lblAdm = Array($l->g(56), $l->g(500));
$imgAdm = Array("adm", "spec");
$lblHdw = Array($l->g(54), $l->g(26), $l->g(63), $l->g(92), $l->g(61), $l->g(96), $l->g(82), $l->g(93), $l->g(271), $l->g(272));
$imgHdw = Array("processeur", "memoire","stockage","disque","video","son","reseau", "controleur", "slot","port" );
$lblSof = Array($l->g(273), $l->g(20),$l->g(512), $l->g(211));
$imgSof = Array("bios", "logiciels","paquets", "registre");
$lblOut = Array($l->g(97),$l->g(91),$l->g(79),$l->g(270));
$imgOut = Array("moniteur", "peripherique", "imprimante", "modem");
echo "<br><br>";

echo "<table width='80%' border=0 align='center' cellpadding='0' cellspacing='0'>
		<tr>
			<td align='left'>
				<table width='100%' align='left' border=0 cellpadding='0' cellspacing='0'>
					<tr>";
							//bleu
							$cpt = 0;
							foreach( $imgHdw as $im ) {
								echo img($im, $lblHdw[$cpt], isAvail($lblHdw[$cpt]), $opt );
								$cpt++;
							}
							echo "						
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td align='left'>
				<table width='100%' align='left' border=0 cellpadding='0' cellspacing='0'>					
					<tr>";
						//jaune
						echo img($imgAdm[0],$lblAdm[0], isAvail($lblAdm[0]), $opt);
						echo img($imgAdm[1],$lblAdm[1], true, $opt);
						
						//echo "<td width='80px'><img src='image/blanc.png'></td>";
						//rouge
						$cpt = 0;
						foreach( $imgSof as $im ) {
							echo img($im, $lblSof[$cpt], isAvail($lblSof[$cpt]), $opt );
							$cpt++;
						}
						
						//vert
						$cpt = 0;
						foreach( $imgOut as $im ) {
							echo img($im, $lblOut[$cpt], isAvail($lblOut[$cpt]), $opt );
							$cpt++;
						}
						echo "
					</tr>
				</table>
			</td>
		</tr>
	</table>";

echo"<br><br><br>";

if($_GET["tout"]==1)
{
	print_inventory($systemid);
	print_perso($systemid);
	print_proc($systemid);
	print_memories($systemid);
	print_storages($systemid);
	print_drives($systemid);
	print_bios($systemid);
	print_sounds($systemid);
	print_videos($systemid);
	print_inputs($systemid);
	print_monitors($systemid);
	print_networks($systemid);
	print_ports($systemid);
	print_printers($systemid);
	print_controllers($systemid);
	print_slots($systemid);
	print_softwares($systemid);
	print_packets($systemid);
	print_modems($systemid);
	print_registry($systemid);
}

switch ($opt) :
	case $l->g(56) : print_inventory($systemid);
						break;
	case $l->g(54) : print_proc($systemid);
						break;
	case $l->g(26)  : print_memories($systemid);
						break;
	case $l->g(63)  : print_storages($systemid);
						break;
	case $l->g(92)  : print_drives($systemid);
						break;
	case $l->g(273)  : print_bios($systemid);
						break;
	case $l->g(96)  : print_sounds($systemid);
						break;
	case $l->g(61)  : print_videos($systemid);
						break;
	case $l->g(91)  : print_inputs($systemid);
						break;
	case $l->g(97)  : print_monitors($systemid);
						break;
	case $l->g(82) : print_networks($systemid);
						break;
	case $l->g(272) : print_ports($systemid);
						break;
	case $l->g(79) : print_printers($systemid);
						break;
	case $l->g(93) : print_controllers($systemid);
						break;
	case $l->g(271) : print_slots($systemid);
						break;
	case $l->g(20) : print_softwares($systemid);
						break;
	case $l->g(270) : print_modems($systemid);	
						break;
	case $l->g(211) : print_registry($systemid);
						break;
	case $l->g(500) : print_perso($systemid);
						break;
	case $l->g(512) : print_packets($systemid);
						break;
	default: print_inventory($systemid);
						break;
	endswitch;					

echo "<br><table align='center'> <tr><td width =50%>";
echo "<a style=\"text-decoration:underline\" onClick=print()><img src='image/imprimer.png' title='".$l->g(214)."'></a></td>";


if(!isset($_GET["tout"]))
		echo"<td width=50%><a style=\"text-decoration:underline\" href=\"machine.php?systemid=".urlencode(stripslashes($systemid))."&tout=1\"><img width='60px' src='image/ttaff.png' title='".$l->g(215)."'></a></td>";
		
echo "</tr></table></body>";
echo "</html>";
exit;

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
	echo "<td align=center rowspan=8><a OnClick='recharge(\"$systemid\",\"machine\")'><img src='image/modif_a.png' title='".$l->g(285)."'></a></td>";
	echo "</tr>";
	echo "<input type='hidden' id='systemid' name='systemid' value=''>";
	echo "<input type='hidden' id='origine' name='origine' value=''>"; 
	echo "</form>";
	}
	$ii++; $td3 = $ii%2==0?$td2:$td4;
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
//	if( $_SESSION["lvluser"]==SADMIN )
//		echo "$td3<a href='index.php?multi=22&systemid=$systemid&update=FREQUENCY'>".$l->g(115)."</a></td>";		
	echo "</tr>";
	
	//DOWNLOAD_SWITCH
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["DOWNLOAD_SWITCH"])?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>";
	echo $td3.$l->g(417)." <font color=green size=1><i>DOWNLOAD</i></font></td>";
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
//	if( $_SESSION["lvluser"]==SADMIN )
//		echo "$td3<a href='index.php?multi=22&systemid=$systemid&update=DOWNLOAD_SWITCH''>".$l->g(115)."</a></td>";		
	echo "</tr>";
	
	//DOWNLOAD_CYCLE_LATENCY
	optperso("DOWNLOAD_CYCLE_LATENCY",$l->g(720)." <font color=green size=1><i>DOWNLOAD_CYCLE_LATENCY</i></font>",$optPerso,0,$optdefault["DOWNLOAD_CYCLE_LATENCY"],$l->g(511));
	
	//DOWNLOAD_FRAG_LATENCY
	optperso("DOWNLOAD_FRAG_LATENCY",$l->g(721)." <font color=green size=1><i>DOWNLOAD_FRAG_LATENCY</i></font>",$optPerso,0,$optdefault["DOWNLOAD_FRAG_LATENCY"],$l->g(511));

	
	//DOWNLOAD_PERIOD_LATENCY
	optperso("DOWNLOAD_PERIOD_LATENCY",$l->g(722)." <font color=green size=1><i>DOWNLOAD_PERIOD_LATENCY</i></font>",$optPerso,0,$optdefault["DOWNLOAD_PERIOD_LATENCY"],$l->g(511));
	
	//DOWNLOAD_PERIOD_LENGTH
	optperso("DOWNLOAD_PERIOD_LENGTH",$l->g(723)." <font color=green size=1><i>DOWNLOAD_PERIOD_LENGTH</i></font>",$optPerso,0,$optdefault["DOWNLOAD_PERIOD_LENGTH"]);

	//PROLOG_FREQ
	optperso("PROLOG_FREQ",$l->g(724)." <font color=green size=1><i>PROLOG_FREQ</i></font>",$optPerso,0,$optdefault["PROLOG_FREQ"],$l->g(730));
	//GROUPS
	$sql_groups="SELECT static, name, group_id,a.TAG  FROM groups_cache g, hardware h left join accountinfo a on h.id=a.hardware_id WHERE g.hardware_id=$systemid AND h.id=g.group_id";
	$resGroups = @mysql_query($sql_groups, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"])); 
	
	if( mysql_num_rows( $resGroups )>0 ) {
		while( $valGroups = mysql_fetch_array( $resGroups ) ) {
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			echo "<tr>";
			echo "<td bgcolor='white' align='center' valign='center'>&nbsp;</td>";
			echo $td3.$l->g(607)." ";		
			if( $_SESSION["lvluser"] == SADMIN || $_SESSION["lvluser"] == LADMIN || $valGroups["TAG"]=="GROUP_4_ALL")
				echo "<a href='index.php?multi=29&popup=1&systemid=".$valGroups["group_id"]."' target='_blank'>".$valGroups["name"]."</td>";
			else
				echo "<b>".$valGroups["name"]."</b></td>";			
				
			echo $td3.$l->g(81).": ";
			switch( $valGroups["static"] ) {
				case 0: echo "<font color='green'>".$l->g(596)."</font></td>"; break;
				case 1: echo "<font color='blue'>".$l->g(610)."</font></td>"; break;
				case 2: echo "<font color='red'>".$l->g(597)."</font></td>"; break;
			}
			
			if( $_SESSION["lvluser"]==SADMIN || $valGroups["TAG"]=="GROUP_4_ALL") {
				$hrefBase = "machine.php?systemid=".urlencode($systemid)."&option=".urlencode($l->g(500))."&grp=".$valGroups["group_id"];
				switch( $valGroups["static"] ) {
					case 0: echo $td3."<a href='$hrefBase&actgrp=1'>".$l->g(598)."</a>&nbsp; &nbsp; &nbsp;<a href='$hrefBase&actgrp=2'>".$l->g(600)."</a></td>"; break;
					case 1: echo $td3."<a href='$hrefBase&actgrp=0'>".$l->g(599)."</a>&nbsp; &nbsp; &nbsp;<a href='$hrefBase&actgrp=2'>".$l->g(600)."</a></td>"; break;
					case 2: echo $td3."<a href='$hrefBase&actgrp=1'>".$l->g(598)."</a>&nbsp; &nbsp; &nbsp;<a href='$hrefBase&actgrp=0'>".$l->g(599)."</a></td>"; break;
				}
			}			
			echo "</td>";
			echo "</tr>";			
		}
	}
	
	//TELEDEPLOY
	$resDeploy = @mysql_query("SELECT a.name, d.tvalue,d.ivalue,d.comments, e.pack_loc  FROM devices d, download_enable e LEFT JOIN download_available a 
	ON e.fileid=a.fileid WHERE d.name='DOWNLOAD' AND e.id=d.ivalue AND d.hardware_id=$systemid"); 
	 
	if( mysql_num_rows( $resDeploy )>0 ) {
			
		while( $valDeploy = mysql_fetch_array( $resDeploy ) ) {
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			echo "<tr>";
			echo "<td bgcolor='white' align='center' valign='center'><img width='15px' src='image/red.png'></td>";
			echo $td3.$l->g(498)." <b>".$valDeploy["name"]."</b> (".$l->g(499).": ".$valDeploy["pack_loc"]." )</td>";			
			echo $td3.$l->g(81).": ".($valDeploy["tvalue"]!=""?$valDeploy["tvalue"]:$l->g(482));
			echo ($valDeploy["comments"]!=""?" (".$valDeploy["comments"].")":"");
			echo "</td>";
			if( $_SESSION["lvluser"]==SADMIN )	
				echo "$td3 <a href='machine.php?suppack=".$valDeploy["ivalue"]."&systemid=".
				urlencode($systemid)."&option=".urlencode($l->g(500))."'>".$l->g(122)."</a></td>";
			echo "</tr>";
		}
	}
	
		$hrefBase = "machine.php?systemid=".urlencode($systemid)."&option=".urlencode($l->g(500));
		
		echo "<tr><td colspan='10' align='right'>";
		if( $_SESSION["lvluser"]==SADMIN ) 
			echo "<a href='index.php?multi=24&systemid=$systemid&isgroup=0'>".$l->g(501)."</a>".$l->g(386);
		echo " <a href=# OnClick=window.location='$hrefBase&actgrp=1&grp='+document.getElementById(\"groupcombo\").options[document.getElementById(\"groupcombo\").selectedIndex].value>".
		$l->g(589)."</a>";
	
		$reqGroups = "SELECT h.name,h.id 
					  FROM hardware h left join accountinfo a on h.id=a.hardware_id 
					  WHERE h.deviceid='_SYSTEMGROUP_'";
		if( $_SESSION["lvluser"]!=SADMIN )
			$reqGroups .= " and a.TAG = 'GROUP_4_ALL'";
		$resGroups = mysql_query( $reqGroups, $_SESSION["readServer"] );
		$first = true;
		while( $valGroups = mysql_fetch_array( $resGroups ) ) {
			if( $first ) {
				echo " <select id='groupcombo'>";
				$first = false;
			}
			echo "<option value='".$valGroups["id"]."'>".$valGroups["name"]."</option>";
		}
		
		if( ! $first )
			echo "</select>";
			
		echo "</td></tr>";		
	//}
	echo "</table><br>";
	
	if ($_POST['modification_param'])
	include ('opt_param.php');
	
	
	
	
}

function print_proc($systemid)
{
	global $l,$td1,$td3;
	print_item_header($l->g(54));
	$queryDetails = "SELECT * FROM hardware WHERE (id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
	$item = mysql_fetch_object($resultDetails);
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(66)." </td> $td1 ".$l->g(377)." </td> $td1 ".$l->g(55)."</td></tr>";
	echo "<tr>";
	echo "$td3".textDecode($item->PROCESSORT)."</td>
	      $td3".textDecode($item->PROCESSORS)."</td>
	      $td3".textDecode($item->PROCESSORN)."</td>";
	echo "</tr>";
	echo "</table>";
}

function print_videos($systemid)
{
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM videos WHERE (hardware_id = $systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
		
	if( mysql_num_rows($resultDetails) == 0 ) 		return;
	print_item_header($l->g(61));
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(49)." </td> $td1 ".$l->g(276)." </td>  $td1 ".$l->g(26)." (MB)</td> $td1 ".$l->g(62)."</td></tr>";	
	
	while($item = mysql_fetch_object($resultDetails))
	{
		$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		     $td3".textDecode($item->NAME)."      </td>
			 $td3".textDecode($item->CHIPSET)."   </td>
			 $td3".textDecode($item->MEMORY)."    </td>
			 $td3".textDecode($item->RESOLUTION)."</td>
			 </tr>";
	}
	echo "</table><br>";		
}

function print_storages($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM storages WHERE (hardware_id=$systemid)";
	
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	print_item_header($l->g(63));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>  $td1 ".$l->g(49)."   </td> $td1 ".$l->g(64)."   </td>   $td1 ".$l->g(65)."         </td>
		  		$td1 ".$l->g(53)."  </td>    $td1 ".$l->g(66)."         </td>
		  		$td1 ".$l->g(67)." (MB) </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".textDecode($item->NAME)."</td>
                    $td3".textDecode($item->MANUFACTURER)."</td>
			  $td3".textDecode($item->MODEL)."       </td>
	          $td3".textDecode($item->DESCRIPTION)." </td>
     		  $td3".textDecode($item->TYPE)."        </td>
 		      $td3".textDecode($item->DISKSIZE)."    </td>	";
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_sounds($systemid)
{	
	global $l,$td1,$td2,$td3,$td4;
	
	$queryDetails  = "SELECT * FROM sounds WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	print_item_header($l->g(96));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(64)." </td> $td1 ".$l->g(49)." </td> $td1 ".$l->g(53)." </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".textDecode($item->MANUFACTURER)."</td>
	          $td3".textDecode($item->NAME)."        </td>
		      $td3".textDecode($item->DESCRIPTION)." </td>";
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_softwares($systemid)
{	
	global	$l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM softwares WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	if ( mysql_num_rows($resultDetails) == 0 )		 return;	
	print_item_header($l->g(20));	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		
	//echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(69)."     </td> $td1 ".$l->g(49)."     </td>   $td1 ".$l->g(277)."  </td>   $td1 ".$l->g(51)."  </td>";
	          // $td1 $rep     </td> $td1 $com     </td>  </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;	
		echo "<tr>";
		echo "$td3".htmlentities(textDecode($item->PUBLISHER))."</td>
			  $td3".htmlentities(textDecode($item->NAME))."     </td>
		      $td3".textDecode($item->VERSION)."  </td>
			  $td3".(htmlentities(textDecode($item->COMMENTS))?htmlentities(textDecode($item->COMMENTS)):"N/A")."     </td>";
		/*      $td3".textDecode($item->FOLDER)."   </td>
		      $td3".textDecode($item->COMMENTS)." </td>";*/
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_packets($systemid)
{	
	global	$l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT h.pkg_id, a.name FROM download_history h LEFT JOIN download_available a ON h.pkg_id=a.fileid WHERE h.hardware_id=$systemid ORDER BY pkg_id DESC";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	if ( mysql_num_rows($resultDetails) == 0 )		 return;	
	print_item_header($l->g(512));	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		
	//echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(475)."     </td> $td1 ".$l->g(49)."     </td>";
	          // $td1 $rep     </td> $td1 $com     </td>  </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;	
		echo "<tr>";
		echo "$td3".htmlentities(textDecode($item->pkg_id))."</td>";
		if( $item->name ) 
			echo "$td3".htmlentities(textDecode($item->name))."</td>";
		else
			echo "$td3<font color='red'>".$l->g(561)."</font></td>";
		echo "</tr>";
	}
	echo "</table><br>";		
}


function print_slots($systemid)
{	
	global	$l, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM slots WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	if ( mysql_num_rows($resultDetails) == 0 )		return;
	print_item_header($l->g(271));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(49)."  </td> $td1 ".$l->g(53)."  </td>  $td1 ".$l->g(70)." </td>";
	echo "</tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".textDecode($item->NAME)."       </td>
		      $td3".textDecode($item->DESCRIPTION)."</td>
		      $td3".textDecode($item->DESIGNATION)."</td>";	
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_printers($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM printers WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	print_item_header($l->g(79));
		
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>  $td1 ".$l->g(49)."   </td>  $td1 ".$l->g(278)." </td>  $td1 ".$l->g(279)."   </td>  </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3".textDecode($item->NAME)."   </td>
  		      $td3".textDecode($item->DRIVER)." </td>
		      $td3".textDecode($item->PORT)."   </td>
			 </tr>";
	}
		echo "</table><br>";		
}

function print_registry($systemid)
{
	global $l, $td1, $td2, $td4, $td3;
	$queryDetails = "SELECT * FROM registry WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	if(mysql_num_rows($resultDetails)==0) return;	
	print_item_header($l->g(211));
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(212)."</td>
	$td1 ".$l->g(213)."</td>
	";
	echo "</tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3 ".textDecode($item->NAME)."</td>
		$td3 ".textDecode($item->REGVALUE)."</td>
		";
		echo "</tr>";
	}
	echo "</table><br>";		
}


function print_ports($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM ports WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	if ( mysql_num_rows($resultDetails) == 0 )		return;	
	print_item_header($l->g(272));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(66)."   </td>  $td1 ".$l->g(49)."   </td> $td1 ".$l->g(88)."   </td>  $td1 ".$l->g(53)."   </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		      $td3".textDecode($item->TYPE)."        </td>
 		      $td3".textDecode($item->NAME)."        </td>
		      $td3".textDecode($item->CAPTION)."     </td>
		      $td3".textDecode($item->DESCRIPTION)." </td>
			  </tr>";
	}
	echo "</table><br>";		
}

function print_networks($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	$queryDetails  = "SELECT * FROM networks WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	if ( mysql_num_rows($resultDetails) == 0 )	 return;
	print_item_header($l->g(82));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr><td width='20%' id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>".$l->g(53)."   </td>  $td1 ".$l->g(66)." </td>
	      $td1 ".$l->g(268)."        </td>  $td1 ".$l->g(95)." </td> $td1 ".$l->g(81)."     </td>
	      $td1 ".$l->g(34)."        </td>  $td1 ".$l->g(208)."</td>  $td1 ".$l->g(207)." </td>
	      $td1 ".$l->g(331)."     </td>$td1 ".$l->g(281)."     </td></tr>";

	while($item = mysql_fetch_object($resultDetails)) {	
		$ii++; $td3 = $ii%2==0?$td2:$td4;
		$const = getConstructor($item->MACADDR);
		echo "<tr>
		<td width='20%' align='center' bgcolor='".($ii%2?"#F0F0F0":"white")."'><FONT FACE='tahoma'>".textDecode($item->DESCRIPTION)."</td>
		$td3".textDecode($item->TYPE)."       </td>
		$td3".textDecode($item->SPEED)."      </td>
		$td3".textDecode($item->MACADDR).($const?"<br>($const)":"");
		blacklist("select ID from blacklist_macaddresses where macaddress='".textDecode($item->MACADDR)."'",textDecode($item->MACADDR),$l->g(704)." ".$l->g(708),$l->g(705)." ".$l->g(708),"Réseau(x)");
		echo "</td>";
		echo "$td3".textDecode($item->STATUS)."     </td>
		$td3".textDecode($item->IPADDRESS)."  </td>
		$td3".textDecode($item->IPMASK)."     </td>
		$td3".textDecode($item->IPGATEWAY)."  </td>
		$td3".textDecode($item->IPSUBNET)."   </td>
		$td3".textDecode($item->IPDHCP)."     </td></tr>";
	}
	echo "</table><br>";	
}

function print_monitors($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails = "SELECT * FROM monitors WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	
	if(mysql_num_rows($resultDetails)==0)	 	return;
	print_item_header($l->g(97));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>  $td1 ".$l->g(64)." </td>  $td1 ".$l->g(80)."  </td>   $td1 ".$l->g(360)." </td>  $td1 ".$l->g(66)." </td>$td1 ".$l->g(36)." </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			$td3".textDecode($item->MANUFACTURER)." </td>
			$td3".textDecode($item->CAPTION)."      </td>
			$td3".textDecode($item->DESCRIPTION)."  </td>
			$td3".textDecode($item->TYPE)."         </td>
			$td3".textDecode($item->SERIAL)."         </td>
		</tr>";
	}
	echo "</table><br>";		
}

function print_modems($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM modems WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	print_item_header($l->g(270));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(49)."  </td> $td1 ".$l->g(65)."  </td> $td1 ".$l->g(53)."  </td> $td1 ".$l->g(66)."  </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3".textDecode($item->NAME)."        </td>
 		      $td3".textDecode($item->MODEL)."       </td>
		      $td3".textDecode($item->DESCRIPTION)." </td>
		      $td3".textDecode($item->TYPE).        "</td>
		      </tr>";
	}
	echo "</table><br>";		
}

function print_memories($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM memories WHERE (hardware_id=$systemid) ORDER BY capacity ASC";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	print_item_header($l->g(26));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(80)."  </td>  $td1 ".$l->g(53)."  </td>  $td1 ".$l->g(83)." (MB)  </td> $td1 ".$l->g(283)."    </td>
	      $td1 ".$l->g(66)."  </td>  $td1 ".$l->g(268)."  </td>  $td1 ".$l->g(94)."      </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3 ".textDecode($item->CAPTION)."     </td>
		      $td3 ".textDecode($item->DESCRIPTION)." </td>
		      $td3 ".textDecode($item->CAPACITY)."    </td>
		      $td3 ".textDecode($item->PURPOSE)."     </td>
		      $td3 ".textDecode($item->TYPE)."        </td>
		      $td3 ".textDecode($item->SPEED)."       </td>
		      $td3 ".textDecode($item->NUMSLOTS)."    </td>
		      </tr>";
	}
	echo "</table><br>";		
}

function print_inputs($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails = "SELECT * FROM inputs WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	
	if ( mysql_num_rows($resultDetails) == 0 )	 	return;
	print_item_header($l->g(91));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(66)."   </td>   $td1 ".$l->g(64)."   </td>    $td1 ".$l->g(80)."   </td>
	      $td1 ".$l->g(53)."   </td>   $td1 ".$l->g(84)." </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3 ".textDecode($item->TYPE)."        </td>
		      $td3 ".textDecode($item->MANUFACTURER)."</td>
		      $td3 ".textDecode($item->CAPTION)."     </td>
		      $td3 ".textDecode($item->DESCRIPTION)." </td>
		      $td3 ".textDecode($item->INTERFACE)."   </td>
		     </tr>";
	}
	echo "</table><br>";		
}

function print_drives($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM drives WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	
	if ( mysql_num_rows($resultDetails) == 0 )	 	return;
	print_item_header($l->g(92));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(85)."     </td>  $td1 ".$l->g(66)."       </td> $td1 ".$l->g(86)."  </td>
		  $td1 ".$l->g(87)." (MB) </td> $td1 ".$l->g(88)." (MB)   </td> $td1 ".$l->g(70)."</td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		      $td3 ".textDecode($item->LETTER)."     </td>
		      $td3 ".textDecode($item->TYPE)."       </td>
		      $td3 ".textDecode($item->FILESYSTEM)." </td>
		      $td3 ".textDecode($item->TOTAL)."      </td>
		      $td3 ".textDecode($item->FREE)."       </td>
		      $td3 ".textDecode($item->VOLUMN)."       </td>
			  </tr>";
	}
	echo "</table><br>";		
}

function print_bios($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	$queryDetails  = "SELECT * FROM bios WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	print_item_header($l->g(273));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>$td1 ".$l->g(36)."  </td>	  $td1 ".$l->g(64)."  </td>	  $td1 ".$l->g(65)."   </td>	  $td1 ".$l->g(284)."  </td>
		  $td1 ".$l->g(209)."  </td> $td1 ".$l->g(210)."  </td> </tr>";
		  
	$item = mysql_fetch_object($resultDetails);	
	echo "<tr>";
	echo "$td3".textDecode($item->SSN);
	blacklist("select ID from blacklist_serials where SERIAL='".textDecode($item->SSN)."'",textDecode($item->SSN),$l->g(704)." ".$l->g(707),$l->g(705)." ".$l->g(707),"BIOS");
//	if ($_SESSION["lvluser"]==SADMIN){
//		$sql_verif_blacklist="select ID from blacklist_serials where SERIAL='".textDecode($item->SSN)."'";
//		$result_blacklist = mysql_query($sql_verif_blacklist, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
//		if ( mysql_num_rows($result_blacklist) == 0 )
//		//<a href='./machine.php?systemid=".$systemid."&option=BIOS&black=".textDecode($item->SSN)."'>
//		
//	   echo " <a href=# OnClick='confirme(\"".textDecode($item->SSN)."\",\"black\",\"".$l->g(704)."\");' title='".$l->g(703)."'><img height=10 src=image/interdit.jpg></a>";
//	   else{
//	   	$item_blacklist = mysql_fetch_object($result_blacklist);	
//	   	// <a href='./machine.php?systemid=".$systemid."&option=BIOS&noblack=".$item_blacklist->ID."'>   		
//	    echo "<a href=# OnClick='confirme(\"".$item_blacklist->ID."\",\"noblack\",\"".$l->g(705)."\");' title='".$l->g(706)."'><img height=12 src=image/suppv.png></a>";
//	    
//	   }
//	
//	}
	echo "</td>";
	echo "$td3".textDecode($item->SMANUFACTURER)." </td>
	      $td3".textDecode($item->SMODEL)."        </td>
		  $td3".textDecode($item->BMANUFACTURER)." </td>
		  $td3".textDecode($item->BVERSION)."      </td>
		  $td3".textDecode($item->BDATE)."         </td>";
	echo "</tr>";
	echo "</table><br>";
}

function print_comments($systemid)
{
	global $com, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM comments WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		
	if ( mysql_num_rows($resultDetails) == 0 )  	return;
	print_item_header($l->g(51));
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr >";
	echo "$td1 ".$l->g(51)."</td>";
	echo "</tr>";
	
	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".textDecode($item->COMMENTS)."</td>";
		echo "</tr>";
	}
	echo "</table><br>";
}

function print_controllers($systemid)
{
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM controllers WHERE (hardware_id=$systemid)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	if ( mysql_num_rows($resultDetails) == 0 )  	return;
	print_item_header($l->g(93));
		
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr > $td1 ".$l->g(64)." </td> $td1 ".$l->g(49)." </td> $td1 ".$l->g(66)." </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
				$td3 ".textDecode($item->MANUFACTURER)."</td>
		      	$td3 ".textDecode($item->NAME)."        </td>
		      	$td3 ".textDecode($item->TYPE)."        </td>
			</tr>";
	}
	echo "</table><br>";	
}

function print_inventory($systemid)
{
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails = "SELECT * FROM accountinfo WHERE hardware_id=$systemid";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item=mysql_fetch_array($resultDetails,MYSQL_ASSOC);
	if( $item ) {			
		$label_bouton = "<input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" class='bouton' type='button' value='".$l->g(103)."' onClick='MAJ_donnees(\"$systemid\",\"".session_id()."\");' $event_mouse>";
		echo "<td  align='middle' width='20%'>$label_bouton</td>";
			
		echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		echo "<tr > $td1 ".$l->g(223)." </td> $td1 ".$l->g(224)." </td></tr>";		
		
		$indType=-1;
		foreach ($item as $k=>$v) {
			$indType++;
			if( strcasecmp($k,"DEVICEID")==0 || strcasecmp($k,"UNITID")==0|| strcasecmp($k,"HARDWARE_ID")==0)
				continue;
			if(strcasecmp($k,TAG_NAME)==0)
				$k = TAG_LBL;
			
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			
			if(mysql_field_type($resultDetails,$indType)=="date")
				$v = dateFromMysql($v);
				
			echo "<tr>$td3 $k</td>$td3 $v</tr>";		
		}		
		
		echo "</table><br>";
	}	
}

function getNetName($did) {
	global $tdhd,$tdhf,$tdhdpb,$tdhfpb,$l;
	
	//echo "<tr>"$tdhd.$l->g(50).$tdhf.      $tdhdpb.VAL.$tdhfpb."</tr>";
			
	$reqSub = "SELECT name FROM subnet s,networks n WHERE s.netid=n.ipsubnet AND n.hardware_id=$did";
	$resSub = mysql_query($reqSub, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$indice = 1;
	$returnVal = "<tr>".$tdhd.$l->g(304)." ".$indice.$tdhf;	

	while($valSub = mysql_fetch_array( $resSub )){
		if($indice != 1) {
			$returnVal .= "</tr><tr>".$tdhd.$l->g(304)." ".$indice.$tdhf;
		}
		$indice++;
		$returnVal .= $tdhdpb.$valSub["name"].$tdhfpb;
	}	
	
	$queryDetails  = "SELECT ipsubnet FROM networks WHERE hardware_id=$did AND ipsubnet NOT IN(SELECT netid FROM subnet)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
	while($item = mysql_fetch_array($resultDetails)) {
		if($indice != 1) {
			$returnVal .= "</tr><tr>".$tdhfpb.$tdhd.$l->g(304)." ".$indice.$tdhf;
		}
		$returnVal .= $tdhdpb.$item["ipsubnet"].$tdhfpb;
		$indice++;
	}
	
	return 	$returnVal;
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
		$href = "<a href='machine.php?systemid=".urlencode($systemid)."&option=".urlencode($a)."'>";
		$fhref = "</a>";
		$img = "<img title=\"".htmlspecialchars($a)."\" src='image/{$i}{$suff}.png' />";
	}
	else {
		$href = "";
		$fhref = "";
		$img = "<img title=\"".htmlspecialchars($a)."\" src='image/{$i}_d.png' />";
	}
	
	return "<td width='80px'>".$href.$img.$fhref."</td>";

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


function blacklist($sql_verif_blacklist,$serial_mac,$lblpopup_blacklist,$lblpopup_unblacklist,$direct){
	global $l,$systemid;
	//javascript 
	?>
	<script language=javascript>
		function confirme(did,champ,lbl){
			if(confirm(lbl+" ?"))
				window.location="machine.php?systemid=<? echo $systemid ?>&option=<? echo $direct ?>&"+champ+"="+did;
		}
	</script>
	<?php
	if ($_SESSION["lvluser"]==SADMIN){
		$result_blacklist = mysql_query($sql_verif_blacklist, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		if ( mysql_num_rows($result_blacklist) == 0 )
		//<a href='./machine.php?systemid=".$systemid."&option=BIOS&black=".textDecode($item->SSN)."'>
		
	   echo " <a href=# OnClick='confirme(\"".$serial_mac."\",\"black\",\"".$lblpopup_blacklist."\");' title='".$l->g(703)."'><img height=10 src=image/interdit.jpg></a>";
	   else{
	   	$item_blacklist = mysql_fetch_object($result_blacklist);	
	   	// <a href='./machine.php?systemid=".$systemid."&option=BIOS&noblack=".$item_blacklist->ID."'>   		
	    echo "<a href=# OnClick='confirme(\"".$item_blacklist->ID."\",\"noblack\",\"".$lblpopup_unblacklist."\");' title='".$l->g(706)."'><img height=12 src=image/suppv.png></a>";
	    
	   }
	
	}
}

function update_blacklist(){
	// blacklist serial
	if (isset($_GET['black']) &  $_SESSION["lvluser"]==SADMIN & $_GET['option'] == "BIOS")
		@mysql_query("INSERT INTO blacklist_serials (SERIAL) value ('".$_GET['black']."')", $_SESSION["writeServer"]);

	//blacklist mac
	if (isset($_GET['black']) &  $_SESSION["lvluser"]==SADMIN & $_GET['option'] == "Réseau(x)")
		@mysql_query("INSERT INTO blacklist_macaddresses (MACADDRESS) value ('".$_GET['black']."')", $_SESSION["writeServer"]);
		
	// unblacklist serial
	if (isset($_GET['noblack']) &  $_SESSION["lvluser"]==SADMIN & $_GET['option'] == "BIOS")	
		@mysql_query("DELETE FROM blacklist_serials WHERE id=".$_GET['noblack'], $_SESSION["writeServer"]);
		
	// unblacklist mac 
	if (isset($_GET['noblack']) &  $_SESSION["lvluser"]==SADMIN & $_GET['option'] == "Réseau(x)")	
		@mysql_query("DELETE FROM blacklist_macaddresses WHERE id=".$_GET['noblack'], $_SESSION["writeServer"]);
	
}

?>