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
//Modified on $Date: 2007-01-26 17:05:42 $$Author: plemmet $($Revision: 1.10 $)

$filtreSSN="
AND b.ssn <> 'N/A'
AND b.ssn <> '(null string)'
AND b.ssn <> ''
AND b.ssn <> 'INVALID'
AND b.ssn <> 'SYS-1234567890'
AND b.ssn <> 'SYS-9876543210'
AND b.ssn <> 'SN-12345'
AND b.ssn <> 'SN-1234567890'
AND b.ssn <> '1111111111'
AND b.ssn <> '1111111'
AND b.ssn <> '1'
AND b.ssn <> '0123456789'
AND b.ssn <> '12345'
AND b.ssn <> '123456'
AND b.ssn <> '1234567'
AND b.ssn <> '12345678'
AND b.ssn <> '123456789'
AND b.ssn <> '1234567890'
AND b.ssn <> '123456789000'
AND b.ssn <> '12345678901234567'
AND b.ssn <> '0000000000'
AND b.ssn <> '000000000'
AND b.ssn <> '00000000'
AND b.ssn <> '0000000'
AND b.ssn <> '000000'
AND b.ssn <> 'NNNNNNN'
AND b.ssn <> 'xxxxxxxxxxx' 
AND b.ssn <> 'EVAL'
AND b.ssn <> 'IATPASS'
AND b.ssn <> 'none'
AND b.ssn <> 'To Be Filled By O.E.M.'
AND b.ssn <> 'Tulip Computers'
AND b.ssn <> 'Serial Number xxxxxx'
AND b.ssn <> 'SN-123456fvgv3i0b8o5n6n7k'";

$filtreMAC="
AND n1.macaddr <> '44:45:53:54:00:00'
AND n1.macaddr <> '44:45:53:54:00:01'
AND n1.macaddr <> '00:00:00:00:00:00'";

$fromBase="hardware h LEFT JOIN accountinfo a ON a.hardware_id = h.id LEFT JOIN bios b ON b.hardware_id = h.id LEFT OUTER JOIN networks n1 on b.hardware_id=n1.hardware_id";
$whereBase="n1.hardware_id = h.id ";
if( $mesMachines ) {
	$whereBase .= "AND $mesMachines";
}
$from = array();
$where = array();
$group = array();
$order = array();
// hostname seul
$from[4] = "hardware h2";
$where[4] = " $whereBase AND h.name = h2.name AND h.id <> h2.id";
$group[4] = "h.id";
$order[4] = "h.name";

// ssn seul
$from[5] = "hardware h2, bios b2";
$where[5] = "$whereBase AND b2.hardware_id = h2.id AND b2.ssn = b.ssn AND h.id <> h2.id $filtreSSN";
$group[5] = "h.id";
$order[5] = "b.ssn";

//mac seule
$from[6] = "hardware h2, networks n2";
$where[6] = " $whereBase AND n2.hardware_id = h2.id AND n2.macaddr = n1.macaddr AND h.id <> h2.id $filtreMAC"; 
$group[6] = "h.id";
$order[6] = "n1.macaddr";

// hostname + ssn
$from[1] = "hardware h2, bios b2";
$where[1] = " $whereBase AND b2.hardware_id = h2.id AND h.name=h2.name AND b2.ssn = b.ssn AND h.id <> h2.id $filtreSSN";
$group[1] = "h.id";
$order[1] = "h.name,b.ssn";

// hostname + mac
$from[2]  = "hardware h2, networks n2";
$where[2] = " $whereBase AND n2.hardware_id = h2.id AND h.name = h2.name AND n2.macaddr = n1.macaddr AND h.id <> h2.id $filtreMAC";
$group[2] = "h.id";
$order[2] = "h.name,b.ssn";

// mac + ssn
$from[3] = "networks n2, bios b2";
$where[3] = " $whereBase AND b2.hardware_id = n2.hardware_id AND b2.hardware_id <> b.hardware_id AND n1.macaddr = n2.macaddr AND b2.ssn = b.ssn $filtreSSN $filtreMAC";
$group[3] = "h.id";
$order[3] = "n1.macaddr,b.ssn";

if(isset($_POST["subredon"])) {
	for( $i = 1 ; $i <= $_POST["maxredon"] ; $i++) {
		if(! isset($_POST["ch".$i]))
			continue;
		
		$res = mysql_query("SELECT deviceid,id,lastcome FROM hardware WHERE id=".$_POST["ch".$i], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));		
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
		case $l->g(194): $ind = 2 ; break ;
		case $l->g(195): $ind = 3 ; break ;
		case $l->g(196): $ind = 4 ; break;
		case $l->g(197): $ind = 5 ; break ;
		case $l->g(198): $ind = 6 ; break ;
		/*case $l->g(193)*/
		default : $ind = 1 ; break ;
	}

	$rq_sql = $where[$ind];
	$rq_whereId = "h.id";
	$rq_linkId = "h.id";
	$rq_select = array_merge( array("h.id"=>"h.id", "deviceid"=>"deviceid","n1.macaddr"=>$l->g(95),"b.ssn"=>$l->g(36)),
	$_SESSION["currentFieldList"] );

	//$select = array_merge( array("h.id"=>"h.id" ,"deviceid"=>"deviceid", "a.".TAG_NAME=>TAG_LBL), $_SESSION["currentFieldList"] );	
	$rq_selectPrelim = array( "h.id"=>"h.id" );
	$rq_from = $fromBase;
	$rq_fromPrelim = $from[$ind];
	$rq_group = $group[$ind];
	$rq_order = $order[$ind];
	$rq_countId = "h.id";
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
	$req=new Req("Doubles",$rq_whereId,$rq_linkId,$rq_sql,$rq_select,$rq_selectPrelim,$rq_from,$rq_fromPrelim,$rq_group,$rq_order,$rq_countId,true);	
	ShowResults($req,true,false,true,true,false);
}
else {
	echo "<br><table BORDER='0' WIDTH = '25%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	for($j=1;$j<=6;$j++) {
		
		$rrqs = "SELECT COUNT(DISTINCT h.id) FROM ".$fromBase.",".$from[$j]." WHERE ".$where[$j];		
		$rres = mysql_query( $rrqs, $_SESSION["readServer"]);
	
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
			if( ! $okLock = ($okLock && lock($a["id"])) )
				break;
			else
				$locked[] = $a["id"];
		}
		
		if( $okLock ) {
			//TRACE_DELETED
			if(mysql_num_rows(mysql_query("SELECT * FROM config WHERE IVALUE>0 AND NAME='TRACE_DELETED'", $_SESSION["readServer"]))){
				foreach($afus as $a) {	
					if($afus[$maxInd]["deviceid"]==$a["deviceid"]){continue;}
					mysql_query("insert into deleted_equiv(DELETED,EQUIVALENT) values('".$a["deviceid"]."','".$afus[$maxInd]["deviceid"]."')", $_SESSION["writeServer"]) ;
				}
			}
			
			//KEEP OLD QUALITY,FIDELITY AND CHECKSUM
			$persistent_req = mysql_query("SELECT CHECKSUM,QUALITY,FIDELITY FROM hardware WHERE ID=".$afus[$minInd]["id"]) ;
					
			$reqDelAccount = "DELETE FROM accountinfo WHERE hardware_id=".$afus[$maxInd]["id"];
			mysql_query($reqDelAccount, $_SESSION["writeServer"]) ;
			echo "<center><font color=green>".$l->g(190)." ".$afus[$maxInd]["deviceid"]." ".$l->g(191)."</font></center>";
			// Keep old accountinfo
			$reqRecupAccount = "UPDATE accountinfo SET hardware_id=".$afus[$maxInd]["id"]." WHERE hardware_id=".$afus[$minInd]["id"];			
			mysql_query($reqRecupAccount, $_SESSION["writeServer"]) ;
			// Keep old download_history
			$reqRecupAccount = "UPDATE download_history SET hardware_id=".$afus[$maxInd]["id"]." WHERE hardware_id=".$afus[$minInd]["id"];			
			mysql_query($reqRecupAccount, $_SESSION["writeServer"]) ;
			//echo $reqRecupAccount;
			echo "<center><font color=green>".$l->g(190)." ".$afus[$minInd]["deviceid"]." ".$l->g(206)." ".$afus[$maxInd]["deviceid"]."</font></center><br>";
			$i=0;
			foreach($afus as $a) {
				if($i != $maxInd) {
					deleteDid($a["id"], false, false);
				}			
				$i++;
			}
			
			//RESTORE PERSISTENT VALUES
			$persistent_values = mysql_fetch_row($persistent_req);
			mysql_query("UPDATE hardware SET QUALITY=".$persistent_values[1].",FIDELITY=".$persistent_values[2].",CHECKSUM=CHECKSUM|".$persistent_values[0]." WHERE id=".$afus[$maxInd]["id"]) ;
			
		}
		else
			errlock();
		
		foreach($locked as $a) {
			unlock($a);	
		}		
	}
}


?>
