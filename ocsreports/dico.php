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
//Modified on $Date: 2006-12-12 10:43:47 $ by $Author: plemmet $ (version: $Revision: 1.4 $)

$pgSize = $_SESSION["pcparpage"];
$rg = isset($_GET["rg"])?$_GET["rg"]:0;	
set_time_limit(0);
/*
foreach($_POST as $key=>$val) {	
	if( ($fin = stristr ($key, "inputncat_")) && $val != "") {
		$valName = substr( fromName(stripslashes($fin)), strlen("inputncat_"));
		addSoft( $val, $valName);
	}}
	
*/

$_GET["cat"] = isset($_GET["cat"]) ? $_GET["cat"] : (isset($_POST["alleracat"]) ? $_POST["alleracat"] : null ) ;

if( isset($_GET["cat"]) ) {
	
	if( isset($_GET["search"]) && $_GET["search"] != "" ) {
		$condG = " name LIKE '%".$_GET["search"]."%' AND";
		$condO = " extracted LIKE '%".$_GET["search"]."%' AND";
	}
	
	$order = isset($_GET["order"])&&$_GET["order"]!="" ? $_GET["order"] : 1 ;
	
	$laCat = stripslashes(fromName($_GET["cat"]));	
	if($laCat == "NEW")
		$sens = $order != 1 ? "ASC" : "DESC";
	else
		$sens = "ASC";
	
	if($laCat == "NEW") {		
		if(! isset($_GET["order"])) $sens = "DESC";
		$reqLog = "SELECT COUNT(hardware_id) as 'nbdef',name as 'extracted' FROM softwares WHERE{$condG} name NOT IN 
		(SELECT DISTINCT(extracted) FROM dico_soft) AND name NOT IN 
		(SELECT DISTINCT(extracted) FROM dico_ignored) GROUP BY name ORDER BY $order $sens";
		$reqCount = "SELECT COUNT(DISTINCT(name)) as 'nb' FROM softwares WHERE{$condG} name NOT IN 
		(SELECT DISTINCT(extracted) FROM dico_soft) AND name NOT IN 
		(SELECT DISTINCT(extracted) FROM dico_ignored)";
	}
	else if($laCat == "IGNORED") {
		$reqLog = "SELECT s.extracted FROM dico_ignored s WHERE{$condO} 1=1 ORDER BY $order $sens ";	
		$reqCount = "SELECT COUNT(s.extracted) as 'nb' FROM dico_ignored s WHERE{$condO} 1=1";
	}
	else if($laCat == "UNCHANGED") {
		$reqLog = "SELECT s.extracted FROM dico_soft s WHERE{$condO} extracted=formatted ORDER BY $order $sens";	
		$reqCount = "SELECT COUNT(s.extracted) as 'nb' FROM dico_soft s WHERE{$condO} extracted=formatted";
	}
	else {
		$reqLog = "SELECT s.extracted FROM dico_soft s WHERE{$condO} s.formatted='$laCat' ORDER BY $order $sens";	
		$reqCount = "SELECT COUNT(s.extracted) as 'nb' FROM dico_soft s WHERE{$condO} s.formatted='$laCat'";	
	}
	//echo $reqLog;

}
else if( isset($_GET["all"]) && $_GET["search"]!="" ) {
	$reqLog = "SELECT distinct(name) as 'extracted' FROM softwares WHERE name LIKE '%".$_GET["search"]."%' order by name asc";
	$reqCount = "SELECT count( distinct name) as 'nb' FROM softwares WHERE name LIKE '%".$_GET["search"]."%' order by name asc";
}

$lastCat = "";	
if( isset($_POST["combocat"]) ) {
	if(isset($_POST["all"])) {
		$resAll = mysql_query($reqLog, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		while($valAll = mysql_fetch_array($resAll)) {
			$_POST[ urlencode($valAll["extracted"]) ] = "on";
		}		
		unset($_POST["all"]);
	}	
	
	foreach($_POST as $key=>$val) {
		if($val == "on") {
			$key = addslashes(fromName($key));
			if( isset($_POST["inputcat"]) && $_POST["inputcat"] != "" ) {
				addSoft( fromName($_POST["inputcat"]), $key);
				$lastCat = fromName($_POST["inputcat"]);
			}
			else {
				addSoft( fromName($_POST["combocat"]), $key);
				$lastCat = fromName($_POST["combocat"]);
			}
		}		
	}
}

if( isset($reqLog) ) {
	$reqLog .= " LIMIT $rg,$pgSize";
}

if( isset($_GET["delcat"]) ) {
	delCat( fromName($_GET["delcat"]) ); 	
}

?><script language='javascript'>
		function actForm( field, value ) {
			for (var i = 0; i < document.reass.elements.length; i++) {
				elm = document.reass.elements[i];
				reg = new RegExp("checkbox_");
				if (elm.type == 'checkbox' && reg.test(elm.id)) {
					eval("elm."+field+" = "+value+";");
					
				}
			}
		}
</script><?

//les GET globaux a rajouter
$hiddens ="";
foreach ($_GET as $gk=>$gv) {
	if( $gk=="rev"|| $gk=="suppCol" || $gk == "logout" || $gk=="newcol" || $gk=="order") continue;
	
	if( $gk =="page" && ($gv==-1 || $gv==-2)) {
		$gv = $_SESSION["pageCur"];
	}	
	$hiddens .= "<input type='hidden' name='$gk' value='$gv'>\n";
}

$machNmb = array(5,10,15,20,50,100);
$pcParPageHtml = "<form name='pcp' method='GET' action='index.php'>$hiddens".$l->g(340).": 
<select name='pcparpage' OnChange='pcp.submit();'>";
foreach( $machNmb as $nbm ) {
	$pcParPageHtml .=  "<option".($_SESSION["pcparpage"] == $nbm ? " selected" : "").($countHl%2==1?" class='hi'":"").">$nbm</option>";
	$countHl++;
}
$pcParPageHtml .=  "</select></form>";

if( isset($_GET["cat"]) ) {	
	
	$link = "multi=14&cat=".urlencode($_GET["cat"])."&search=".urlencode($_GET["search"]);	
	
	$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$valCount = mysql_fetch_array($resCount);
	
	$printNbr = $valCount["nb"] == 0 ? "<font color='red'>VIDE</font>" : $valCount["nb"];
	printEnTete("Catégorie $laCat ( $printNbr )");
	echo "<br><center>$pcParPageHtml&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='index.php?multi=14&oldv=1'><= ".$l->g(398)."</a></center><br>";	
	
	if( $valCount["nb"] > 0 ) {
		$resLog = mysql_query($reqLog, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		echo "<form name='reass' method='POST' action='?$link'>";
		echo "<table width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		echo "<tr height='20px'>";
		
		if( $laCat == "NEW" ) echo "<td align='center' width='10%'><b><a href='?$link&order=1'>".$l->g(381)."</a></b></font></td>";
		echo "<td align='center' width='70%'><b>".( $laCat=="NEW" ? "<a href='?$link&order=2'>" : "" ).$l->g(382).( $laCat=="NEW" ? "</a>" : "")."</b></font></td><td align='center' width='10%'>
		<b><a href=# OnClick='actForm(\"checked\",\"true\")'>".$l->g(383)."</a>/<a href=# OnClick='actForm(\"checked\",\"false\")'>".$l->g(389)."</a></b></font></td></tr>";

		$ligne = 0;
		$optList = comboCat($laCat, $lastCat);				
		while($log = mysql_fetch_array($resLog)) {
		
			$laLigne = array(utf8_decode($log["extracted"]),"<input type='checkbox' name='".toName($log["extracted"])."' id='".toName($log["extracted"])."'>"
			/*,"<input name='inputncat_".toName($log["extracted"])."'>"*/ );
			if( $laCat == "NEW" ) $laLigne = array_merge( array($log["nbdef"]), $laLigne );
			printLigne($laLigne, ($ligne%2 == 1 ? "#FFFFFF" : "#F2F2F2"));			
			$ligne++;		
		}
		
		echo "<tr height='40px' bgcolor='".($ligne%2 == 1 ? "#FFFFFF" : "#F2F2F2")."'>";
		//if( $laCat == "NEW" ) echo "<td>&nbsp;</td>";		
		echo "<td align='right' colspan='4'>
		<input name='all' id='all' type='checkbox' OnClick='actForm(\"disabled\",document.getElementById(\"all\").checked)'>
		".$l->g(384)."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$l->g(385).":</font>&nbsp;
		<input name='inputcat'>&nbsp;<b>".$l->g(386)."</b> ".$l->g(387).":</font>&nbsp;$optList
		<input type='Submit'></td></tr></table></form>";
	}
	
	echo "<br><table width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0'>";
	echo "<tr height='30px'><td align='left'>
	<form action='' method='get' name='formrech'>
	<input type='hidden' name='cat' value='".$_GET["cat"]."'>
	<input type='hidden' name='order' value='".$_GET["order"]."'>
	<input type='hidden' name='multi' value='14'>
	<input name='search' id='search' value='".$_GET["search"]."'>
	<input type='Submit' value='".$l->g(393)."'><input type='Submit' OnClick='document.getElementById(\"search\").value=\"\"' value='".$l->g(396)."'></form></td>";
		
	$maxPgeNumber = ceil($valCount["nb"]/$pgSize);	
	if( $maxPgeNumber > 1 ){
		echo "<td align='center'>"."<form name='allera' method='post' action='index.php?multi=14'>".$l->g(397).":</font>"
		        .comboCat( isset($_GET["cat"])?$_GET["cat"]:"","","alleracat")."<input type='submit'></form></td>";
		$link = "<a href=\"?$link&order=".$_GET["order"]."&rg=";
		$min = 0;
		$prev = $rg - $pgSize < 0 ? 0 : $rg - $pgSize ;
		$next = $rg + $pgSize > $valCount["nb"] ? $valCount["nb"] - $pgSize : $rg + $pgSize ;
		$last = $valCount["nb"] - $pgSize > 0 ? $valCount["nb"] - $pgSize : 0 ;
		
		$linkMin  = $rg == 0 ? "1..</font>" : $link.$min."\">1..</a>";
		$linkPrev = $rg == 0 ? "<img src='image/prec24.png'></font>" : $link.$prev."\"><img src='image/prec24.png'></a>";
		$linkNext =  $rg >= $valCount["nb"] - $pgSize ? "<img src='image/proch24.png'></font>" : $link.$next."\"><img src='image/proch24.png'></a>";
		$linkLast  = $rg >= $valCount["nb"] - $pgSize ? "..$maxPgeNumber</font>" : $link.$last."\">..$maxPgeNumber</a>";
		$current = ceil( $rg / $pgSize )+1;
		
		echo "<td align='left'>{$linkPrev}</td><td align='right'>{$linkMin}</td>";
		if( $rg > 0 && $rg < $valCount["nb"] - $pgSize ) echo "<td align='center' width='0%'>$current</font></td>";
		echo "<td align='left'>{$linkLast}</td><td align='left'>{$linkNext}</td>";
	}
	else
		echo "<td align='right'>"."<form name='allera' method='post' action='index.php?multi=14'>".$l->g(397).":</font>"
		        .comboCat( isset($_GET["cat"])?$_GET["cat"]:"","","alleracat")."<input type='submit'></form></td>";
	echo "</tr></table>";
}
else {
	if( isset($_GET["oldv"]) ) {
		$_GET["search"] = $_SESSION["search"] ;
		$_GET["all"] = $_SESSION["all"] ;
		$rg = isset($_SESSION["rg"])?$_SESSION["rg"]:0 ;
	}
	else {
		$_SESSION["search"] = $_GET["search"] ;
		$_SESSION["all"] = $_GET["all"] ;
		$_SESSION["rg"] = $rg ;
	}
	
	$link = "multi=14&search=".urlencode($_GET["search"])."&order=$order";
	
	// multi category search
	if( isset($_GET["all"]) && $_GET["search"]!="" ) {
		$link .= "&all=1";
		echo "<br><form name='reass' method='POST' action='?$link'><br><center><a href='index.php?multi=14'><= ".$l->g(398)."</a></center><br><table width='90%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		$li = 0;
		$resLog = mysql_query($reqLog, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));		
		$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$valCount = mysql_fetch_array($resCount);
		
		printLigne(array("<b>".$l->g(382)."</b>","<b>".$l->g(388)."</b>","<b><a href=# OnClick='actForm(\"checked\",\"true\")'>".$l->g(383)."</a>/<a href=# OnClick='actForm(\"checked\",\"false\")'>".$l->g(389)."</a></b>"));
		$nb = 0;
		while( $leLog = mysql_fetch_array($resLog) ) {
			$extracted = $leLog["extracted"];
			$reqIsIgnored = "SELECT extracted FROM dico_ignored WHERE extracted='$extracted'";
			$resIsIgnored = mysql_query($reqIsIgnored, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			if( $softIgnored = mysql_fetch_array($resIsIgnored)) {
				$cat = "IGNORED";
			}
			else {
				$reqIsDico = "SELECT extracted,formatted FROM dico_soft WHERE extracted='$extracted'";
				$resIsDico = mysql_query($reqIsDico, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
				if( $softDico = mysql_fetch_array($resIsDico)) {
					if( $softDico["extracted"] == $softDico["formatted"] ) {
						$cat = "UNCHANGED";
					}
					else
						$cat = $softDico["formatted"];
				}
				else {
					$cat = 'NEW';
				}
			}
			$ligne = array(utf8_decode($leLog["extracted"]), "<a href='?multi=14&cat=".toName($cat)."'>$cat</a>","<input type='checkbox' name='".toName($leLog["extracted"])."' id='".toName($leLog["extracted"])."'>");
			printLigne( $ligne, ($li%2 == 1 ? "#FFFFFF" : "#F2F2F2"));
			$li++;
			$nb++;
		}
		
		if($nb>0) {
			$optList = comboCat($laCat, $lastCat);
			echo "<tr height='30px' bgcolor='#FFFFFF'><td align='right' colspan='4'>
			<input name='all' id='all' type='checkbox' OnClick='actForm(\"disabled\",document.getElementById(\"all\").checked)'>
			".$l->g(384)."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$l->g(385).":</font>&nbsp;
			<input name='inputcat'>&nbsp;<b>".$l->g(386)."</b> ".$l->g(387).":</font>&nbsp;$optList
			<input type='Submit'></td></tr></table></form>";
		}
	}
	else {		
		printEnTete($l->g(390));
		
		if( isset($_GET["search"]) && $_GET["search"] != "" ) {
			$cond = "AND formatted LIKE '%".$_GET["search"]."%'";
		}
		
		$reqCat = "SELECT formatted as 'name', COUNT(extracted) AS nbSoft FROM dico_soft WHERE extracted<>formatted $cond 
		GROUP BY formatted ORDER BY formatted ASC LIMIT $rg,$pgSize";
		echo "<br><center>$pcParPageHtml</center>";
		//echo $reqCat;
		$resCat = mysql_query($reqCat, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		echo "<table width='60%' border='0' align='center'><td align='center' width='33%'><a href='?multi=14&cat=NEW'><b><u>NEW</u></b></a></td><td align='center' width='33%'><a href='?multi=14&cat=IGNORED'><b><u>IGNORED</u></b></a></td><td align='center' width='33%'><a href='?multi=14&cat=UNCHANGED'><b><u>UNCHANGED</u></b></a></td></table>";
		echo "<br><table width='60%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		echo "<tr height='20px'><td align='center' width='70%'><b>".$l->g(391)."</b></font></td><td align='center' width='15%'><b>".$l->g(381)."</b></font></td><td align='center' width='15%'><b>".$l->g(392)."</b></font></td></tr>";
		$ligne = 0;
		$reqNew = "SELECT COUNT(DISTINCT(name)) as nbNew FROM softwares WHERE name NOT IN (SELECT DISTINCT(extracted) FROM dico_soft)";
		
		while($cat = mysql_fetch_array($resCat)) {
		
			$laLigne = array("<a href='?multi=14&cat=".toName($cat["name"])."'>".$cat["name"]."</a>", $cat["nbSoft"] );
			$laLigne = array_merge($laLigne, array("<a href='?$link&rg=$rg&delcat=".toName($cat["name"])."'><img src='image/supp.png'></a>"));
			
			printLigne($laLigne, ($ligne%2 == 1 ? "#FFFFFF" : "#F2F2F2"));	
			$ligne++;
			
		}
		echo "</table>";
	}
	echo "<br><table width='60%' BORDER='0' ALIGN = 'Center' CELLPADDING='0'>";
	echo "<tr height='30px'><td align='left'>
	<form action='' method='get' name='formrech'>
	<input type='hidden' name='multi' value='14'>
	<input name='search' id='search' value='".$_GET["search"]."'>
	<input type='Submit' value='".$l->g(394)."'><input type='Submit' name='all' value='".$l->g(395)."'><input type='Submit' OnClick='document.getElementById(\"search\").value=\"\"' value='".$l->g(396)."'></form></td>";
	
	$maxPgeNumber = ceil($valCount["nb"]/$pgSize);	
	if( $maxPgeNumber > 1 ){
		$link = "<a href=\"?$link&rg=";
		$min = 0;
		$prev = $rg - $pgSize < 0 ? 0 : $rg - $pgSize ;
		$next = $rg + $pgSize > $valCount["nb"] ? $valCount["nb"] - $pgSize : $rg + $pgSize ;
		$last = $valCount["nb"] - $pgSize > 0 ? $valCount["nb"] - $pgSize : 0 ;
		
		$linkMin  = $rg == 0 ? "1..</font>" : $link.$min."\">1..</a>";
		$linkPrev = $rg == 0 ? "<img src='image/prec24.png'></font>" : $link.$prev."\"><img src='image/prec24.png'></a>";
		$linkNext =  $rg >= $valCount["nb"] - $pgSize ? "<img src='image/proch24.png'></font>" : $link.$next."\"><img src='image/proch24.png'></a>";
		$linkLast  = $rg >= $valCount["nb"] - $pgSize ? "..$maxPgeNumber</font>" : $link.$last."\">..$maxPgeNumber</a>";
		$current = ceil( $rg / $pgSize )+1;
		
		echo "<td align='left'>{$linkPrev}</td><td align='right'>{$linkMin}</td>";
		if( $rg > 0 && $rg < $valCount["nb"] - $pgSize ) echo "<td align='center' width='0%'>$current</font></td>";
		echo "<td align='left'>{$linkLast}</td><td align='left'>{$linkNext}</td>";
	}
	echo "</tr></table>";
}

if( isset( $_SESSION["toBeMod"] ) ) {
	//var_dump( $_SESSION["toBeMod"]);
	computeChecksums();
	unset( 	$_SESSION["toBeMod"] );
}

/*function allerA() {
	return "<form name='allera' method='post' action='index.php?multi=14'>".$l->g(397).":</font>"
	.comboCat( isset($_GET["cat"])?$_GET["cat"]:"","","alleracat")."<input type='submit'></form>";
}*/

function comboCat( $saufCat="", $lastCat="",$name='combocat' ) {
	
	$reqCom = "SELECT formatted as 'name', COUNT(extracted) AS nbSoft FROM dico_soft WHERE extracted<>formatted AND formatted<>'$saufCat' 
		GROUP BY formatted ORDER BY formatted ASC";
	//echo $reqCom;
	$resCom = mysql_query($reqCom, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$ret = "<select name='$name'>";
	$countHl = 0;
	$ret .= "<option".( $lastCat == "IGNORED" ? " selected" : "" )." ".($countHl%2==1?"class='hi'":"").">IGNORED</option>";
	$countHl++;
	$ret .= "<option".( $lastCat == "UNCHANGED" ? " selected" : "" )." ".($countHl%2==1?"class='hi'":"").">UNCHANGED</option>";
	
	while($cat = mysql_fetch_array($resCom)) {
		$countHl++;
		$ret .= "<option".( $lastCat==$cat["name"]?" selected":"" ).($countHl%2==1?" class='hi'":"").">".$cat["name"]."</option>";
	}
	$ret .= "</select>";
	return $ret;
}

function printLigne($ligne, $bgcol=false) {
	if( $bgcol ) 
		$affBg =  "bgcolor='$bgcol'";
	echo "<tr height='20px'$affBg>";
	
	foreach($ligne as $l) {
		echo "<td align='center'>$l</font></td>";
	}
	echo "</tr>";
}

function toName($nm) {
	$ret = str_replace(".","%point%",$nm);
	$ret = "checkbox_".$ret;
	return urlencode($ret);	
}

function fromName($nm) {
	$ret = urldecode($nm);
	$ret = str_replace("checkbox_","",$ret);
	return str_replace("%point%",".",$ret);
}

function addSoft( $cat, $def) {
	
	if( $cat == "UNCHANGED") {
		$cat = $def;
	}
	
	if($cat == "IGNORED") {
		delSoft($def);
		$reqIgn = "INSERT INTO dico_ignored VALUES('$def')";
		@mysql_query($reqIgn, $_SESSION["writeServer"]);
	}
	else {
		delIgnored($def);
		$reqUpd = "UPDATE dico_soft SET formatted='$cat' WHERE extracted='$def'";//GLPI
		//echo $reqUpd."<br>";	
		mysql_query($reqUpd, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		if( mysql_affected_rows() <= 0) {
			$reqUpd = "INSERT INTO dico_soft(formatted,extracted) VALUES('$cat', '$def')";//GLPI
			//echo $reqUpd."<br>";
			@mysql_query($reqUpd, $_SESSION["writeServer"]);
			
		}
		alterChecksum($def);			
	}
}

function delSoft($def) {
	$reqDcat = "DELETE FROM dico_soft WHERE extracted='$def'";//GLPI
	mysql_query($reqDcat, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	alterChecksum($def);
}

function delIgnored($def) {
	$reqDcat = "DELETE FROM dico_ignored WHERE extracted='$def'";
	mysql_query($reqDcat, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
}

function delCat($cat) {
	alterChecksum(false,$cat);
	$reqDcat = "DELETE FROM dico_soft WHERE formatted='$cat'";//GLPI
	mysql_query($reqDcat, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
}

function alterChecksum($ext, $form=false) {

	if($ext) {
		$_SESSION["toBeMod"][]=$ext;
		//$reqCheck = "UPDATE hardware SET checksum=checksum|$softMod WHERE id IN( SELECT hardware_id FROM softwares WHERE name='$ext')";
		//mysql_query($reqCheck, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}
	else {		
		$reqSofts = "SELECT DISTINCT(extracted) FROM dico_soft WHERE formatted='$form'";
		
		$resSofts = mysql_query($reqSofts, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		while( $valSofts = mysql_fetch_array($resSofts) ) {
			alterChecksum($valSofts["extracted"]);
		}
	}
}

function computeChecksums() {
	echo "COMPUTING: ".sizeof($_SESSION["toBeMod"])."<br>";
	flush();
	
	$softMod = "65536";
	$reqCheck = "UPDATE hardware SET checksum=checksum|$softMod WHERE id IN (SELECT DISTINCT(hardware_id) FROM softwares WHERE name IN(";
	$first = true;
	foreach( $_SESSION["toBeMod"] as $soft ) {
		if( !$first )
			$reqCheck .= ",";
		$reqCheck .= "'$soft'";
		if( $first ) $first = false;	
	}
	$reqCheck .= "));";
	//echo $reqCheck;
	mysql_query($reqCheck, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
}

?>
