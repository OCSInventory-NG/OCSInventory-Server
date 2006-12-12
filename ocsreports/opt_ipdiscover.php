<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2006
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.3 $)

if( isset($_POST["systemid"]) )
	$_GET["systemid"] = $_POST["systemid"];

if( !isset($_GET["systemid"]) ) {
	$lareq = getPrelim( $_SESSION["storedRequest"] );
	$res = @mysql_query( $lareq, $_SESSION["readServer"] ) or die(mysql_error());
	$val = mysql_fetch_array( $res );
	$id = $val["h.id"];
}
else {
	$id = $_GET["systemid"];	
}

if( isset($_POST["ipdiscover"]) ){
	
	$ok = resetIpdiscover( $id );

	if( $_POST["ipdiscover"]=="never" ){
		$ok = makeIpdiscover( $id, 0 );
	}
	else if( $_POST["ipdiscover"]!="des" ) {
		$ok = makeIpdiscover( $id, 2, $_POST["ipdiscover"] );	
	}
	if( $ok ) {
		echo "<script language='javascript'>history.go(-2);</script>";
		die();
	}
}

$nbMach = getCount($_SESSION["storedRequest"]);
if( $nbMach != 1 && ! isset($_GET["systemid"])) {
	echo "<script language='javascript'>alert(\"".$l->g(517)."\");history.go(-1);</script>";
	die();
}

PrintEnTete( $l->g(518) );
echo "<br><center><a href='javascript:void(0);' OnClick='history.go(-1)'><= ".$l->g(188)."</a></center>";

$resIpd = mysql_query("SELECT tvalue, ivalue FROM devices WHERE name='IPDISCOVER' AND hardware_id=$id",$_SESSION["readServer"] );
$valIpd = mysql_fetch_array( $resIpd );

$resInt = mysql_query("SELECT ipaddress FROM networks WHERE hardware_id=$id",$_SESSION["readServer"] );
while( $valInt = mysql_fetch_array( $resInt ))
	$lesRez[] = $valInt["ipaddress"];

$mode = 0;
if( $valIpd["tvalue"] && $valIpd["ivalue"]==1 ) {
	echo "<br><center><b>".$l->g(519).": ".$valIpd["tvalue"]."</b></center>";
	$mode = 1;
}
else if( $valIpd["ivalue"]==2 ) {
	echo "<br><center><b>".$l->g(520).": ".$valIpd["tvalue"]."</b></center>";
	$mode = 3;
}
else if( $valIpd["ivalue"]==="0" ) {
	echo "<br><center><b>".$l->g(521)."</b></center>";
	$mode = 2;	
}
else {
	echo "<br><center><b>".$l->g(522)."</b></center>";		
}

?>
<br>
<form action='index.php?multi=23' method='post'>
<table align='center' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5' width='30%'>
	<tr bgcolor='#F2F2F2'>
		<td><? echo $l->g(518); ?>:</td><td><select name='ipdiscover'>
		<? foreach($lesRez as $leRez) echo "<option>$leRez</option>";
		if( $mode==3 || $mode==2) {?>
			<option value='des'><? echo $l->g(523); ?></option>
		<? } ?>
		<option value='never'><? echo $l->g(524); ?></option>
		</select></td>
	</tr>
	<tr bgcolor='#FFFFFF'>
	<td align='center'>

		&nbsp;</td>
		<td align='center'>
		<? if( isset($_GET["systemid"]) ) {
			echo "<input type='hidden' value='".$_GET["systemid"]."' name='systemid'>";
		 } ?>
		<input type='Submit' value='<? echo $l->g(433); ?>' name='sub'></td>
	</tr>
</table>
</form>
</form>

<?
function makeIpdiscover($id, $sens, $ip=NULL) {

	if( $ip != NULL )
		$ipsubnet = getSub( $id, $ip );
	else 
		$ipsubnet = NULL;
	
	if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE".($ipsubnet!=NULL?",TVALUE":"").") VALUES('$id', 'IPDISCOVER', '$sens'".($ipsubnet!=NULL?",'$ipsubnet'":"").")", $_SESSION["writeServer"] )) {
		echo "<br><center><font color=red><b>ERROR: MySql problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
		return false;
	}
	return true;	
}
	
function resetIpdiscover( $id ) {

	if( ! @mysql_query( "DELETE FROM devices WHERE name='IPDISCOVER' AND hardware_id='$id'", $_SESSION["writeServer"] )) {
		echo "<br><center><font color=red><b>ERROR: MySql problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
		return false;
	}	
	return true;		
}

function getSub( $id, $ip ) {
	if( ! ($res = @mysql_query( "SELECT ipsubnet FROM networks WHERE ipaddress='$ip' AND hardware_id='$id'", $_SESSION["readServer"] ))) {
		echo "<br><center><font color=red><b>ERROR: MySql problem<br>".mysql_error($_SESSION["readServer"])."</b></font></center>";
	}
	$val = mysql_fetch_array( $res );
	return $val["ipsubnet"];
}
?>

