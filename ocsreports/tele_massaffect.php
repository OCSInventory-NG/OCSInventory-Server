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

if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
	
printEnTete($l->g(601));

if( $_POST["sub"] ) {
	
	if( ! $_FILES["fichier"]["name"] ) {
		echo "<br><center><font color=red><b>".$l->g(602)."</b></font></center><br>";
	}
	else {
		$fSize = @filesize( $_FILES["fichier"]["tmp_name"] );
		if( $fSize <= 0 ) {
			echo "<br><center><font color=red><b>".$l->g(436)."</b></font></center><br>";
		}
		else {
			$filename = $_FILES['fichier']['tmp_name'];
			if( $fd = fopen($filename, "r") ) {
				$okComputers = 0;
				$koComputers = array();
				while( !feof($fd) ) {				
					$line = trim( fgets( $fd, 256 ) );
					if( affectPackage( $line, $_POST["id"] ) ) {
						$okComputers++;						
					}
					else if( ! empty($line) ){
						$koComputers[] = $line;
					}
					flush();					
				}				
				fclose( $fd );
				
				if( $okComputers == 0  ) {
					echo "<br><center><font color=red><b>".$l->g(603)."</b></font></center><br>";
				}
				else {
					echo "<br><center><font color=green><b>".$okComputers." ".$l->g(604)."."."</b></font></center><br>";
					
					if( ! empty( $koComputers ) ) {
						echo "<br><center><font color=red><b>".sizeof($koComputers)." ".$l->g(605).": "."</b></font></center><center><font color=red><b>";
						foreach( $koComputers as $koComputer )
							echo "<br>".$koComputer;
						echo "</b></font></center>";
					}
				}
			}
			else {
				echo "<br><center><font color=red><b>".$l->g(436)."</b></font></center><br>";
			}			
		}
	}
}

function affectPackage( $computer, $packageId ) {
		
	//Getting hardware_id from name
	$reqName = "SELECT id FROM hardware WHERE name='$computer'";
	$resName = @mysql_query( $reqName , $_SESSION["readServer"] );
	$valName = @mysql_fetch_array( $resName );
	
	if( ! $valName ) {
		return false;
	}
	$computerId = $valName["id"];
	
	//Removing packages already affected
	@mysql_query( "DELETE FROM devices WHERE name='DOWNLOAD' AND IVALUE=$packageId AND hardware_id='".$computerId."'", $_SESSION["writeServer"] );
	
	if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('$computerId', 'DOWNLOAD', $packageId )", $_SESSION["writeServer"] )) {
		return false;
	}	

	return true;				
}

?>
<br><br>
<form id='mass' name='mass' action='index.php?multi=30' method='post' enctype='multipart/form-data'>
<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
	<tr height='30px' bgcolor='white'>
		<td><span id='filetext'><?php echo $l->g(606); ?>:</td>
		<td colspan='2'><input id='fichier' name='fichier' type='file' accept='archive/zip'></td>
	</tr>
	<tr height='20px'><td colspan='2' align='right'><input type='submit' name='sub'></td></tr>
</table>
<input type='hidden' name='id' value='<? echo $_POST["id"]?$_POST["id"]:$_GET["id"]; ?>'>
</form>
