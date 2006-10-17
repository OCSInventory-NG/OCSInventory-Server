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
//Modified on 10/17/2006
require ('fichierConf.class.php');
printEnTete($l->g(107));

$lesEdits = array("IPDISCOVER_MAX_ALIVE","DOWNLOAD_CYCLE_LATENCY","DOWNLOAD_FRAG_LATENCY","DOWNLOAD_PERIOD_LATENCY",
"DOWNLOAD_PERIOD_LENGTH","DOWNLOAD_TIMEOUT","PROLOG_FREQ");

if( isset($_POST["FREQUENCY"] ) ) {
	switch($_POST["FREQUENCY"]) {
		case "NEVER" : setOpt("FREQUENCY", -1); break;
		case "ALWAYS": setOpt("FREQUENCY", 0); break;
		default: setOpt("FREQUENCY", $_POST["FREQUENCY_edit"]);
	}	

	switch($_POST["IPDISCOVER"]) {
		case "OFF": setOpt("IPDISCOVER", 0); break;
		default: setOpt("IPDISCOVER", $_POST["IPDISCOVER_edit"]);
	}
	
	$autoDupLvl =  isset($_POST["AUTO_DUPLICATE_LVL_serial"])?$_POST["AUTO_DUPLICATE_LVL_serial"]:0;
	$autoDupLvl += isset($_POST["AUTO_DUPLICATE_LVL_host"])?$_POST["AUTO_DUPLICATE_LVL_host"]:0;
	$autoDupLvl += isset($_POST["AUTO_DUPLICATE_LVL_mac"])?$_POST["AUTO_DUPLICATE_LVL_mac"]:0;
	setOpt("AUTO_DUPLICATE_LVL", $autoDupLvl);
	
	$lesOnOff = array("REGISTRY","UPDATE","DEPLOY","TRACE_DELETED","LOGLEVEL","DOWNLOAD","INVENTORY_DIFF","INVENTORY_TRANSACTION");

	
	foreach($lesOnOff as $oo) 
		setOpt($oo, ($_POST[$oo]=="ON"?1:0));
		
	foreach($lesEdits as $ee)
		setOpt($ee, $_POST[$ee."_edit"]);
}

?>
<script language='javascript'>
	function modif(id, val) {
		if( document.getElementById(id).disabled == true )
			return;		
		var curVal = parseFloat(document.getElementById(id).value);
		curVal = curVal + parseFloat(val);
		if( curVal < 1 || isNaN(curVal) || curVal > 99 )
			curVal = 1;			
		document.getElementById(id).value = curVal;		
	}
	
	function active(id, sens) {
		var mstyle = document.getElementById(id).style.display	= (sens!=0?"block" :"none");
	}
	
	function checkNumbers() {
		if ( 0 <? foreach($lesEdits as $ee) echo "|| isNaN(document.getElementById('{$ee}_edit').value)"; ?> ) {
			alert("<?echo $l->g(411);?>");
		}
		else {
			document.getElementById('formopt').submit();
		}
	}
</script>
<br><form name='formopt' id='formopt' action='index.php?multi=4' method='POST'><table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
<tr height=25px>
<td width='27%' align='center'><b><? echo $l->g(49); ?></b></td><td  width='22%' align='center'><b><? echo $l->g(224); ?></b></td><td width='2%'>&nbsp;&nbsp;&nbsp;</td><td width='27%' align='center'><b><? echo $l->g(49); ?></b></td><td  width='22%' align='center'><b><? echo $l->g(224); ?></b></td>
<?
//><td  width='*' align='center'><b> echo $l->g(51); </b></td>
$nbr = 0;
$trad = array("REGISTRY"=>412,"UPDATE"=>413,"DEPLOY"=>414,"TRACE_DELETED"=>415,"LOGLEVEL"=>416,"DOWNLOAD"=>417,"INVENTORY_DIFF"=>418,
"IPDISCOVER_MAX_ALIVE"=>419,"DOWNLOAD_CYCLE_LATENCY"=>420,"DOWNLOAD_FRAG_LATENCY"=>421,"DOWNLOAD_PERIOD_LATENCY"=>422,
"DOWNLOAD_PERIOD_LENGTH"=>423,"DOWNLOAD_TIMEOUT"=>424,"IPDISCOVER"=>425,"FREQUENCY"=>426,"AUTO_DUPLICATE_LVL"=>427);

$resConf = mysql_query("SELECT NAME,IVALUE FROM config WHERE NAME<>'GUI_VERSION' ORDER BY NAME", $_SESSION["readServer"]) or die(mysql_error());
$decal = "&nbsp;&nbsp;&nbsp;";
$ligne = 0;
while( $conf = mysql_fetch_array($resConf) ) {

	$title = " title=\"".htmlspecialchars($l->g($trad[$conf["NAME"]]))."\" ";
	echo "<TR height=65px bgcolor='".($ligne%2==0 ? "#FFFFFF" : "#F2F2F2")."'>";
	//case 1
	echo "<td$title>$decal".$conf["NAME"]."</td>";
	
	//case 2
	echo "<td$title align='left' width='150px'>";
	showOption( $conf["NAME"], $conf["IVALUE"] );
	echo "</td>";

	//separator
	echo "<td bgcolor='#C7D9F5'>&nbsp;</td>";

	if( ! $conf = mysql_fetch_array($resConf) )
		break;
	
	$title = " title=\"".strtr($l->g($trad[$conf["NAME"]]), "\"","'")."\" ";
	//case 3
	echo "<td$title>$decal".$conf["NAME"]."</td>";
	
	//case 4
	echo "<td$title align='left' width='150px'>";
	showOption( $conf["NAME"], $conf["IVALUE"] );
	echo "</td>";
	$ligne++;
}
		
echo "</tr></table>";
echo "<p align='center'><input type='button' height='60px' value='".$l->g(103)."' OnClick='checkNumbers();'></p></form>";		

function showOption($nme, $val) {
	switch($nme) {
		case "REGISTRY":
		case "UPDATE":
		case "DEPLOY":
		case "TRACE_DELETED":
		case "LOGLEVEL":
		case "DOWNLOAD":
		case "INVENTORY_DIFF":
		case "INVENTORY_TRANSACTION":
		case "WEB_SERVICE_ENABLED":
			echo "<table><tr><td align='left'><input type='radio' name='$nme' value='ON' ".($val?"checked":"").">ON</td></tr>
			<tr><td align='left'><input type='radio' name='$nme' value='OFF' ".($val?"":"checked").">OFF</td></tr></table>";
			break;
		case "AUTO_DUPLICATE_LVL":
			echo "<table><tr><td align='left'><input type='checkbox' name='{$nme}_serial' value='2' ".(in_array($val,array(2,3,6,7))?"checked":"").">Serial</td></tr>
			<tr><td align='left'><input type='checkbox' name='{$nme}_host' value='1' ".(in_array($val,array(1,3,5,7))?"checked":"").">hostname</td></tr>
			<tr><td align='left'><input type='checkbox' name='{$nme}_mac' value='4' ".(in_array($val,array(4,5,6,7))?"checked":"").">macaddress</td></tr>
					</table>";
			break;
		case "IPDISCOVER":
			echo "<table><tr height='25px'><td width='90px' align='left'><input type='radio' name='$nme' value='ON' ".($val?"checked":"")." OnClick=\"active('{$nme}_div',1);\">ON</td></td><td rowspan='2'>".edit($nme,$val)."</td></tr>
			<tr height='25px'><td align='left'><input type='radio' name='$nme' value='OFF' ".($val?"":"checked")." OnClick=\"active('{$nme}_div',0);\">OFF</td></tr></table>";
			break;
		case "FREQUENCY":
			echo "<table><tr><td width='90px'><input ".($val==0?"checked":"")." type='radio' name='$nme' value='ALWAYS' OnClick=\"active('{$nme}_div',0);\">ALWAYS</td><td rowspan='3'>".edit($nme,$val)."</td></tr>
			<tr><td><input ".($val==-1?"checked":"")." type='radio' name='$nme' value='NEVER' OnClick=\"active('{$nme}_div',0);\">NEVER</td></tr>
			<tr><td><input ".($val>0?"checked":"")." type='radio' name='$nme' value='CUSTOM' OnClick=\"active('{$nme}_div',1);\">CUSTOM</td></tr></table>";			
			break;
		default:
			echo edit($nme,$val);
			break;
	}
}

function edit($nme, $val) {
	$enabled = true;
	$valInit = $val;
	if( $nme == "FREQUENCY" && $val < 1 ) {
		$enabled = false;
		$valInit = 1;
	}
	else if( $nme == "IPDISCOVER" && $val <= 0 )
		$enabled = false;
		
	return "<div id='{$nme}_div' style='display:".($enabled?"block":"none")."'><table><tr><td rowspan='2'><input type='text' size='3' maxlength='2' id='{$nme}_edit' name='{$nme}_edit' value='$valInit'></td><td align='center'>
	<a href='javascript:void(0);' Onclick=\"modif('{$nme}_edit','1');\"><b><font size='3'>+</font></b></a></td></tr><td align='center'>
	<a href='javascript:void(0);' Onclick=\"modif('{$nme}_edit','-1');\"><b><font size='3'>-</font></b></a></td></tr></table></div>";
}

function setOpt($nme, $ivalue) {
	mysql_query("UPDATE config SET IVALUE=$ivalue WHERE name='$nme'", $_SESSION["writeServer"]) or die(mysql_error());
}