<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2006
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2007-07-23 10:30:25 $$Author: plemmet $($Revision: 1.7 $)

if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");

if( isset($_POST["systemid"]) )
	$_GET["systemid"] = $_POST["systemid"];

if( isset($_POST["frequency"]) ) {
	
	$ok = true;
	if( $_POST["frequency"] == "SERVER DEFAULT" ) {
		$ok = resetFrequency();
	}
	else {
		$toBeSet = 0;
		switch($_POST["frequency"]) {
			case "NEVER" : $toBeSet = -1; break;
			case "ALWAYS": $toBeSet = 0; break;
			default: $toBeSet = $_POST["frequency_edit"];
		}
		resetFrequency();
		$ok = setFrequency( $toBeSet );		
	}
	if( $ok ) {
		if( ! isset( $_GET["systemid"] ) )
			echo "<script language='javascript'>window.location='index.php?redo=1".$_SESSION["queryString"]."';</script>";
		else
			echo "<script language='javascript'>history.go(-2);</script>";	

		die();
	}
}

$nbMach = 0;
if( isset($_GET["systemid"]))
	$nbMach = 1;
else if( isset( $_POST["maxcheck"] ) ) {
	foreach( $_POST as $key=>$val ) {
		if( strpos ( $key, "checkmass" ) !== false ) {
			$tbd[] = $val;
			$nbMach++;
		}		
	}	
}
if( empty( $tbd ) )
	$nbMach = getCount($_SESSION["storedRequest"]);

PrintEnTete( $l->g(484)." <font class='warn'>($nbMach ".$l->g(478).")</font>");

if( ! isset( $_GET["systemid"] ) )
	echo "<br><center><a href='#' OnClick=\"window.location='index.php?redo=1".$_SESSION["queryString"]."';\"><= ".$l->g(188)."</a></center>";
else
	echo "<br><center><a href='#' OnClick='history.go(-1);'><= ".$l->g(188)."</a></center>";
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
		if ( isNaN(document.getElementById('frequency_edit').value) ) {
			alert("<?php echo $l->g(411); ?>");
		}
		else {
			document.getElementById('formopt').submit();
		}
	}
</script>
<br>
<form action='index.php?multi=22' method='post'>
<table align='center' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5' width='20%'>
	<tr bgcolor='#F2F2F2'>
		<td width='90%'><input type='radio' name='frequency' value='ALWAYS' OnClick="active('frequency_div',0);"><?php echo $l->g(485); ?></td>
		<td rowspan='4' width='0%'>
			<div id='frequency_div' style='display:none'>
				<table>
					<tr>
						<td rowspan='4'><input type='text' size='3' maxlength='2' id='frequency_edit' name='frequency_edit' value='1'></td>
						<td align='center'><a href='javascript:void(0);' Onclick="modif('frequency_edit','1');">
						<b><font size='3'>+</font></b></a></td>
					</tr>
						<td align='center'><a href='javascript:void(0);' Onclick="modif('frequency_edit','-1');"><b>
						<font size='3'>-</font></b></a></td>
					</tr>
				</table>
			</div>
		</td>
	</tr>
	<tr bgcolor='#FFFFFF'>
		<td><input type='radio' name='frequency' value='NEVER' OnClick="active('frequency_div',0);"><?php echo $l->g(486); ?></td>
	</tr>
	<tr bgcolor='#F2F2F2'>
		<td><input type='radio' name='frequency' value='CUSTOM' OnClick="active('frequency_div',1);"><?php echo $l->g(487); ?></td>
	</tr>
	<tr bgcolor='#FFFFFF'>
		<td><input checked type='radio' name='frequency' value='SERVER DEFAULT' OnClick="active('frequency_div',0);"><?php echo $l->g(488); ?></td>
	</tr>
	<tr bgcolor='#FFFFFF'>
		<td>&nbsp;</td>
		<?php 
		
		if( isset($_GET["systemid"]) ) {
			echo "<input type='hidden' value='".$_GET["systemid"]."' name='systemid'>";
		}
		else if( isset( $tbd ) ) {
			$indexSys = 1;
			//reposting computerS for confirm
			foreach( $tbd as $sys ) {
				echo "<input type='hidden' value='".$sys."' name='sysfreq$indexSys'>";
				$indexSys++;
			}
		}
		
		 ?>
		<td><input type='Submit' value='<?php echo $l->g(433); ?>'></td>
	</tr>
</table>
</form>

<?php 
	function setFrequency( $freq ) {
		global $_GET, $_POST;
		if( isset($_GET["systemid"])) {
			$val["h.id"] = $_GET["systemid"];
			if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'FREQUENCY', $freq)", $_SESSION["writeServer"] )) {
				echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
				return false;
			}
		}
		else if( isset( $_POST["sysfreq1"] ) ) {		
			foreach( $_POST as $key=>$val ) {
				if( strpos ( $key, "sysfreq" ) !== false ) {
					if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val."', 'FREQUENCY', $freq)", $_SESSION["writeServer"] )) {
						echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
						return false;
					}
				}
			}
		}
		else {
			$lareq = getPrelim( $_SESSION["storedRequest"] );
			if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
				return false;
			while( $val = @mysql_fetch_array($res)) {
				if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'FREQUENCY', $freq)", $_SESSION["writeServer"] )) {
					echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
					return false;
				}
			}
		}
		return true;	
	}
	
	function resetFrequency( ) {
		
		global $_GET;
		if( isset($_GET["systemid"])) {
			$val["h.id"] = $_GET["systemid"];
			if( ! @mysql_query( "DELETE FROM devices WHERE name='FREQUENCY' AND hardware_id='".$val["h.id"]."'", $_SESSION["writeServer"] )) {
				echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
				return false;
			}
		}
		else if( isset( $_POST["sysfreq1"] ) ) {		
			foreach( $_POST as $key=>$val ) {
				if( strpos ( $key, "sysfreq" ) !== false ) {
					if( ! @mysql_query( "DELETE FROM devices WHERE name='FREQUENCY' AND hardware_id='".$val."'", $_SESSION["writeServer"] )) {
						echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
						return false;
					}
				}
			}
		}
		else {
			$lareq = getPrelim( $_SESSION["storedRequest"] );
			if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
				return false;
			while( $val = @mysql_fetch_array($res)) {
			
				if( ! @mysql_query( "DELETE FROM devices WHERE name='FREQUENCY' AND hardware_id='".$val["h.id"]."'", $_SESSION["writeServer"] )) {
					echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
					return false;
				}
			}
		}

		return true;		
		//comprends pas: echo "DELETE FROM devices WHERE name='FREQUENCY' AND hardware_id IN ($lareq)";flush();		
	}
?>

