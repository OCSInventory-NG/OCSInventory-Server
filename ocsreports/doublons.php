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
//Modified on 11/25/2005

$select = "h.deviceid, h.name AS \"".$l->g(23)."\", b1.ssn AS \"".$l->g(36)."\", n1.macaddr AS \"".$l->g(95)."\",h.lastdate AS \"".$l->g(46)."\",h.userid AS \"".$l->g(24)."\", a.".TAG_NAME." AS '".TAG_LBL."', h.OSNAME AS \"".$l->g(25)."\", 
h.memory AS \"Ram (MO)\", h.processors AS \"CPU (MHz)\", h.winprodid";

$filtreSSN="
AND b1.ssn <> 'N/A'
AND b1.ssn <> '(null string)'
AND b1.ssn <> ''
AND b1.ssn <> 'INVALID'
AND b1.ssn <> 'SYS-1234567890'
AND b1.ssn <> 'SYS-9876543210'
AND b1.ssn <> 'SN-12345'
AND b1.ssn <> 'SN-1234567890'
AND b1.ssn <> '1111111111'
AND b1.ssn <> '1111111'
AND b1.ssn <> '1'
AND b1.ssn <> '0123456789'
AND b1.ssn <> '12345'
AND b1.ssn <> '123456'
AND b1.ssn <> '1234567'
AND b1.ssn <> '12345678'
AND b1.ssn <> '123456789'
AND b1.ssn <> '1234567890'
AND b1.ssn <> '123456789000'
AND b1.ssn <> '12345678901234567'
AND b1.ssn <> '0000000000'
AND b1.ssn <> '000000000'
AND b1.ssn <> '00000000'
AND b1.ssn <> '0000000'
AND b1.ssn <> '000000'
AND b1.ssn <> 'NNNNNNN'
AND b1.ssn <> 'xxxxxxxxxxx' 
AND b1.ssn <> 'EVAL'
AND b1.ssn <> 'IATPASS'
AND b1.ssn <> 'none'
AND b1.ssn <> 'To Be Filled By O.E.M.'
AND b1.ssn <> 'Tulip Computers'
AND b1.ssn <> 'Serial Number xxxxxx'
AND b1.ssn <> 'SN-123456fvgv3i0b8o5n6n7k'";

$filtreMAC="
AND n1.macaddr <> '44:45:53:54:00:00'
AND n1.macaddr <> '44:45:53:54:00:01'
AND n1.macaddr <> '00:00:00:00:00:00'";

$from="FROM accountinfo a,hardware h, bios b1 LEFT OUTER JOIN networks n1 on b1.deviceid=n1.deviceid,";

$where="WHERE a.deviceid = h.deviceid
AND b1.deviceid = h.deviceid
AND n1.deviceid = h.deviceid";

// hostname seul
$req4 = "hardware h2 $where
AND h.name = h2.name
AND h.deviceid <> h2.deviceid";

$req4F = " GROUP BY h.deviceid ORDER BY h.name";

// ssn seul
$req5 = "hardware h2, bios b2 $where
AND b2.deviceid = h2.deviceid
AND b2.ssn = b1.ssn
AND h.deviceid <> h2.deviceid $filtreSSN";
 
$req5F = " GROUP BY h.deviceid ORDER BY b1.ssn";

//mac seule
$req6="hardware h2, networks n2 $where
AND n2.deviceid = h2.deviceid
AND n2.macaddr = n1.macaddr
AND h.deviceid <> h2.deviceid
$filtreMAC";
 
$req6F = " GROUP BY h.deviceid ORDER BY n1.macaddr";

// hostname + ssn
$req1 = "hardware h2, bios b2 $where
AND b2.deviceid = h2.deviceid
AND h.name = h2.name
AND b2.ssn = b1.ssn
AND h.deviceid <> h2.deviceid
$filtreSSN";
 
$req1F =  " GROUP BY h.deviceid ORDER BY h.name,b1.ssn";

// hostname + mac
$req2 = "hardware h2, networks n2 $where
AND n2.deviceid = h2.deviceid
AND h.name = h2.name
AND n2.macaddr = n1.macaddr
AND h.deviceid <> h2.deviceid
$filtreMAC";

$req2F =  " GROUP BY h.deviceid ORDER BY h.name,b1.ssn";

// mac + ssn
$req3 = "networks n2, bios b2 $where
AND b2.deviceid = n2.deviceid
AND b2.deviceid <> b1.deviceid
AND n1.macaddr = n2.macaddr
AND b2.ssn = b1.ssn
$filtreSSN
$filtreMAC";
 
$req3F = " GROUP BY h.deviceid ORDER BY n1.macaddr,b1.ssn";

if(isset($_POST["subredon"])) {
	for( $i = 1 ; $i <= $_POST["maxredon"] ; $i++) {
		if(! isset($_POST["ch".$i]))
			continue;
		
		$res = mysql_query("SELECT deviceid,lastcome FROM hardware WHERE deviceid='".$_POST["ch".$i]."'", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));		
		$afus[] = mysql_fetch_array($res,MYSQL_ASSOC);			
	}
	
	if(sizeof($afus)<2) {
		echo "<center><font color=red>".$l->g(189)."</font></center>";
	}
	else {
		fusionne($afus);
	}
}



if($_SESSION["typ"]&&!isset($_POST["typ"])) {
	$_POST["typ"] = $_SESSION["typ"];
}

if( !isset($_POST["typ"])) {
	$_POST["typ"] = $l->g(192) ;
}	

if(isset($_POST["typ"]) && $_POST["typ"]!=$l->g(192)) {

	$_SESSION["typ"] = $_POST["typ"];
	
	switch($_POST["typ"]) {
		case $l->g(194): $laReqt = $req2; $laF = $req2F ; break ;
		case $l->g(195): $laReqt = $req3; $laF = $req3F ; break ;
		case $l->g(196): $laReqt = $req4; $laF = $req4F ; break;
		case $l->g(197):  $laReqt = $req5; $laF = $req5F; break ;
		case $l->g(198):  $laReqt = $req6; $laF = $req6F; break ;
		/*case $l->g(193)*/
		default :$laReqt = $req1.$req1F; break ;
	}

	$laReq = "SELECT $select $from $laReqt $mesMachines $laF";
	$laReqC = "SELECT COUNT(DISTINCT h.deviceid) $from $laReqt $mesMachines";
	
	//echo $laReq;
}

echo "<table width=100%><tr align=right><td><form name='formtyp' action='index.php?multi=6' method='POST'>
	<select name='typ' OnChange='formtyp.submit();'>";	
echo "	<option".($_POST["typ"]==$l->g(32)?" selected":"").">".$l->g(32).":</option>
	<option".($_POST["typ"]==$l->g(192)?" selected":"").">".$l->g(192)."</option>
	<option".($_POST["typ"]==$l->g(193)?" selected":"").">".$l->g(193)."</option>
	<option".($_POST["typ"]==$l->g(194)?" selected":"").">".$l->g(194)."</option>
	<option".($_POST["typ"]==$l->g(195)?" selected":"").">".$l->g(195)."</option>
	<option".($_POST["typ"]==$l->g(196)?" selected":"").">".$l->g(196)."</option>
	<option".($_POST["typ"]==$l->g(197)?" selected":"").">".$l->g(197)."</option>
	<option".($_POST["typ"]==$l->g(198)?" selected":"").">".$l->g(198)."</option>	
	</select>
	</form></td></tr></table>";
	
printEnTete($l->g(199));

if($_POST["typ"]!=$l->g(192)) {
	$requete = new Req("Doublons",$laReq,$laReqC,"","","");
	ShowResults($requete,true,false,true);
}
else {
	echo "<br><table BORDER='0' WIDTH = '25%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	for($j=1;$j<=6;$j++) {
		$nameReq = "req".$j;
		
		$rres = mysql_query( "SELECT COUNT(DISTINCT h.deviceid) $from ".$$nameReq ." ".$mesMachines, $_SESSION["readServer"]);
		
		$valr = mysql_fetch_row($rres);
		echo "<tr><td align='center'>";
		//if ( $valr[0] > 0 ) {
			switch($j) {
				case 1: echo $l->g(193); break ;
				case 2: echo $l->g(194); break ;
				case 3: echo $l->g(195); break ;
				case 4: echo $l->g(196); break ;
				case 5: echo $l->g(197); break ;
				case 6: echo $l->g(198); break ;
			}
			echo  ":&nbsp;<b>".$valr[0]."</b></td></tr>";
		//}
	}
	echo "</table><br>";
}

function fusionne($afus) {
	global $l;
	$i=0;
	$maxStamp = 0;
	$minStamp = mktime(0,0,0,date("m"),date("d") + 1,date("Y")); //demain
	foreach($afus as $a) {
		$d = $a["lastcome"];
		$a["stamp"] = mktime($d[11].$d[12],$d[14].$d[15],$d[17].$d[18],$d[5].$d[6],$d[8].$d[9],$d[0].$d[1].$d[2].$d[3]);
		//echo "stamp:".$a["stamp"]."== mktime($d[11]$d[12],$d[14]$d[15],$d[17]$d[18],$d[5]$d[6],$d[8]$d[9],$d[0]$d[1]$d[2]$d[3]);<br>";
		if($maxStamp<$a["stamp"]) {
			$maxStamp = $a["stamp"];
			$maxInd = $i;
		}
		if($minStamp>$a["stamp"]) {
			$minStamp = $a["stamp"];
			$minInd = $i;
		}		
		$i++;
	}
	if($afus[$minInd]["deviceid"]!="") {
		$okLock = true;
		foreach($afus as $a) {
			if( ! $okLock = ($okLock && lock($a["deviceid"])) )
				break;
			else
				$locked[] = $a["deviceid"];
		}
		
		if( $okLock ) {
			//TRACE_DELETED
			if(mysql_num_rows(mysql_query("SELECT * FROM config WHERE IVALUE>0 AND NAME='TRACE_DELETED'", $_SESSION["writeServer"]))){
				foreach($afus as $a) {	
					if($afus[$minInd]["deviceid"]==$a["deviceid"]){continue;}
					mysql_query("insert into deleted_equiv(DELETED,EQUIVALENT) values('".$a["deviceid"]."','".$afus[$minInd]["deviceid"]."')", $_SESSION["writeServer"]);
				}
			}
			
			$reqDelAccount = "DELETE FROM accountinfo WHERE deviceid='".$afus[$maxInd]["deviceid"]."'";
			mysql_query($reqDelAccount, $_SESSION["writeServer"]);
			echo "<center><font color=green>".$l->g(190)." ".$afus[$maxInd]["deviceid"]." ".$l->g(191)."</font></center>";
			$reqRecupAccount = "UPDATE accountinfo SET deviceid='".$afus[$maxInd]["deviceid"]."' WHERE deviceid='".$afus[$minInd]["deviceid"]."'";
			
			mysql_query($reqRecupAccount, $_SESSION["writeServer"]);
			//echo $reqRecupAccount;
			echo "<center><font color=green>".$l->g(190)." ".$afus[$minInd]["deviceid"]." ".$l->g(206)." ".$afus[$maxInd]["deviceid"]."</font></center><br>";
			$i=0;
			foreach($afus as $a) {
				if($i != $maxInd) {
					deleteDid($a["deviceid"], false);
				}			
				$i++;
			}
		}
		else
			errlock();
		
		foreach($locked as $a) {
			unlock($a);	
		}		
	}
}


?>
