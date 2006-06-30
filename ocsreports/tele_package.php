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
//Modified on 06/23/2006
?>
<script language='javascript'>

	function active(id, sens) {
		//document.write( id );
		var mstyle = document.getElementById(id).style.display	= (sens!=0?"block" :"none");
		if( id == 'EXECUTE_div' && sens ) {
			document.getElementById("filetext").innerHTML = "<? echo htmlentities($l->g(550)); ?>";
		}
		else if( ! sens ) {
			document.getElementById("filetext").innerHTML = "<? echo htmlentities($l->g(549)); ?>";
		}
	}
	function checkAll() {
		if ( document.getElementById('nom').value =="" || ( document.getElementById('command').value =="" && document.getElementById('path').value =="" && document.getElementById('nme').value =="" )
		|| ( document.getElementById('NOTIFY_USER').value =="1" && (document.getElementById('NOTIFY_TEXT').value =="" || document.getElementById('NOTIFY_COUNTDOWN').value =="") )
		)
					alert("<? echo $l->g(239); ?>");
		else if( isNaN(document.getElementById('NOTIFY_COUNTDOWN').value) )  {
				alert("<? echo $l->g(459); ?>");
		}
		else document.getElementById('pack').submit();
	}

</script>

<?
	set_time_limit(0);
	printEnTete($l->g(434));

	if( isset($_POST["nom"]) ) {
	
		$verifN = "SELECT fileid FROM download_available WHERE name='".$_POST["nom"]."'";
		$resN = mysql_query( $verifN, $_SESSION["readServer"] ) or die(mysql_error());
		
		if( mysql_num_rows( $resN ) == 0 ) {
					
			$fSize = @filesize( $_FILES["fichier"]["tmp_name"]);
	
			if( $fSize <= 0 && ! $_POST["command"]) {
				echo "<script language='javascript'>alert(\"".$l->g(436)." ".$_FILES["fichier"]["name"]."\");history.go(-1);</script>";
				die("<script language='javascript'>wait(0);</script>");	
			}
			
			foreach( $_POST as $key=>$val ) {
				$_POST[ $key ] = stripslashes( $val );
				$_SESSION[ "down_" . $key ] = stripslashes( $val );
			}
				
			foreach( $_FILES["fichier"] as $key=>$val )
				$_SESSION[ "down_" . $key ] = $val;
			
			if( $fSize ) {
				$size = $_FILES["fichier"]["size"];
				$id = time();
				
				$raw = false;
				if( $_POST["digest_encod"] == "Base64"  )
					$raw = true;
		
				if( $_POST["digest_algo"] == "SHA1" )
					$digest = sha1_file($_FILES["fichier"]["tmp_name"],$raw);
				else
					$digest = md5_file($_FILES["fichier"]["tmp_name"],$raw);
					
				if( $_POST["digest_encod"] == "Base64" )
					$digest = base64_encode( $digest );
					
				$digName = $_POST["digest_algo"]. " / ".$_POST["digest_encod"];
				
				if( ! @mkdir( $_SERVER["DOCUMENT_ROOT"]."/download/".$id)) {
					echo "<center><font color='red'><b>ERROR: can't write in ".$_SERVER["DOCUMENT_ROOT"]."/download/ folder, please refresh when corrected</b></font></center>";
					die("<script language='javascript'>wait(0);</script>");
				}
				//TODO: catcher
				copy( $_FILES["fichier"]["tmp_name"], $_SERVER["DOCUMENT_ROOT"]."/download/".$id."/tmp" );
			
				?>
				<script type="text/javascript" src="js/range.js"></script>
				<script type="text/javascript" src="js/timer.js"></script>
				<script type="text/javascript" src="js/slider.js"></script>
				<link type="text/css" rel="StyleSheet" href="css/winclassic.css" />
		
				<br>
				<form name='frag' action='index.php?multi=20' method='post'>
				<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
				<tr height='30px'><td align='center' colspan='10'><b><? echo $l->g(435); ?> [<? echo $_POST["nom"]; ?>]</b></td></tr>
				<tr height='30px' bgcolor='white'><td><? echo $l->g(446); ?>:</td><td><? echo $_FILES["fichier"]["name"]; ?></td></tr> 
				<tr height='30px' bgcolor='white'><td><? echo $l->g(460); ?>:</td><td><? echo $id; ?></td></tr>
				<tr height='30px' bgcolor='white'><td><? echo $l->g(461); ?> <b><? echo $digName; ?></b>:</td><td><? echo $digest; ?></td></tr>
				<tr height='30px' bgcolor='white'><td><? echo $l->g(462); ?>:</td><td><? echo round($size/1024); ?> <? echo $l->g(516); ?></td></tr>
				<tr height='30px' bgcolor='white'><td><? echo $l->g(463); ?>:</td><td>
				<table><tr><td width='30%'>	
				<span id='tailleFrag' name='tailleFrag'><? echo round($size/1024); ?></span> <? echo $l->g(516); ?>
				</td>
				<? if( round($size) > 1024 ) { ?>
						<td>
						<div class="slider" id="slider-1" tabIndex="1">
						<input class="slider-input" id="slider-input-1" name="slider-input-1"/>
						</div>
						</td>
				<?}?>
				</tr></table></td></tr>
				<tr height='30px' bgcolor='white'><td><? echo $l->g(464); ?>:</td><td>
					<input id='nbfrags' name='nbfrags' value='1' size='5' readonly></td></tr>
				<tr height='30px' bgcolor='white'><td align='right' colspan='10'><input type='submit'>
				<input type='hidden' name='id' value='<? echo $id; ?>'>
				<input type='hidden' name='digest' value='<? echo $digest; ?>'>
				</td></tr>
				</table>		
				</form>
				<? if( round($size) > 1024 ) { ?>
					<script type="text/javascript">
					
					var s = new Slider(document.getElementById("slider-1"),
					                   document.getElementById("slider-input-1"));
					var siz = <? echo round($size); ?>;
					var vmin = 1024;
					
					s.setMaximum( siz );				
					s.setValue( siz );
					
					s.setMinimum(vmin);						
					s.onchange = function () {
						document.getElementById('tailleFrag').innerHTML = Math.ceil((s.getValue())/1024);
						document.getElementById('nbfrags').value = Math.ceil( siz / (Math.ceil(s.getValue())) );				
					}	
					</script>
					<?
				}
				die("<script language='javascript'>wait(0);</script>");			
				}
				else {
					$id = time();
					if( ! @mkdir( $_SERVER["DOCUMENT_ROOT"]."/download/".$id)) {
						echo "<center><font color='red'><b>ERROR: can't write in ".$_SERVER["DOCUMENT_ROOT"]."/download/ folder, please refresh when corrected</b></font></center>";
						die("<script language='javascript'>wait(0);</script>");
					}
					?>
					<form name='frag' id='frag' action='index.php?multi=20' method='post'>
						<input type='hidden' id='nbfrags' name='nbfrags' value='0'>			
						<input type='hidden' name='id' value='<? echo $id; ?>'>
					</form>
					<script language='javascript'>document.getElementById("frag").submit();</script>
					<?
					flush();
					die("<script language='javascript'>wait(0);</script>");
				}
			}
			else {
				echo "<br><center><font color='red'><b>".$l->g(551)."</b></font></center>";
			}
	}
	else if( isset( $_POST["nbfrags"] ) ) {
		
		//fragmenter
		$fname = $_SERVER["DOCUMENT_ROOT"]."/download/".$_POST["id"]."/tmp";
		if( $size = @filesize( $fname )) {
			$handle = fopen ( $fname, "rb");
			
			$read = 0;
			for( $i=1; $i<$_POST["nbfrags"]; $i++ ) {
				$contents = fread ($handle, $size / $_POST["nbfrags"] );
				$read += strlen( $contents );
				$handfrag = fopen( $_SERVER["DOCUMENT_ROOT"]."/download/".$_POST["id"]."/".$_POST["id"]."-".$i, "w+b" );
				fwrite( $handfrag, $contents );
				fclose( $handfrag );
				//echo "FRAG ".$i." lu ".strlen( $contents ). " (en tout " .$read.")<br>";
			}	
			
			$contents = fread ($handle, $size - $read);
			$read += strlen( $contents );
			$handfrag = fopen( $_SERVER["DOCUMENT_ROOT"]."/download/".$_POST["id"]."/".$_POST["id"]."-".$i, "w+b" );
			fwrite( $handfrag, $contents );
			fclose( $handfrag );
			fclose ($handle);
	
			unlink( $_SERVER["DOCUMENT_ROOT"]."/download/".$_POST["id"]."/tmp" );
		}
		
		//creation info
		$info = "<DOWNLOAD ID=\"".htmlentities($_POST["id"])."\" ".
		"PRI=\"".htmlentities($_SESSION["down_priority"])."\" ".
		"ACT=\"".htmlentities($_SESSION["down_action"])."\" ".
		"DIGEST=\"".htmlentities($_POST["digest"])."\" ".		
		"PROTO=\"".	htmlentities($_SESSION["down_proto"])."\" ".
		"FRAGS=\"".htmlentities($_POST["nbfrags"])."\" ".
		"DIGEST_ALGO=\"".htmlentities($_SESSION["down_digest_algo"])."\" ".
		"DIGEST_ENCODE=\"".htmlentities($_SESSION["down_digest_encod"])."\" ".
		"PATH=\"".htmlentities($_SESSION["down_path"])."\" ".
		"NAME=\"".htmlentities($_SESSION["down_nme"])."\" ".
		"COMMAND=\"".htmlentities($_SESSION["down_command"])."\" ".
		"NOTIFY_USER=\"".htmlentities($_SESSION["down_NOTIFY_USER"])."\" ".
		"NOTIFY_TEXT=\"".htmlentities($_SESSION["down_NOTIFY_TEXT"])."\" ".
		"NOTIFY_COUNTDOWN=\"".htmlentities($_SESSION["down_NOTIFY_COUNTDOWN"])."\" ".
		"NOTIFY_CAN_ABORT=\"".htmlentities($_SESSION["down_NOTIFY_CAN_ABORT"])."\" ".
		"NOTIFY_CAN_DELAY=\"".htmlentities($_SESSION["down_NOTIFY_CAN_DELAY"])."\" ".
		"NEED_DONE_ACTION=\"".htmlentities($_SESSION["down_NEED_DONE_ACTION"])."\" ".		
		"GARDEFOU=\"rien\" />\n";
		
		$handinfo = fopen( $_SERVER["DOCUMENT_ROOT"]."/download/".$_POST["id"]."/info", "w+" );
		fwrite( $handinfo, $info );
		fclose( $handinfo );
		
		mysql_query( "DELETE FROM download_available WHERE FILEID='".$_POST["id"]."'", $_SESSION["writeServer"]);
		$req = "INSERT INTO download_available(FILEID, NAME, PRIORITY, FRAGMENTS, SIZE, OSNAME, COMMENT) VALUES
		( '".$_POST["id"]."', '".addslashes($_SESSION["down_nom"])."','".$_SESSION["down_priority"]."', '".$_POST["nbfrags"]."',
		'".$size."', '".$_SESSION["down_os"]."', '' )";
		
		mysql_query( $req, $_SESSION["writeServer"] );
		echo mysql_error();
		echo "<br><center><b><font color='green'>".$l->g(437)." ".$_SERVER["DOCUMENT_ROOT"]."/download/".$_POST["id"]."</font></b></center><br>";

		unset( $_POST["nbfrags"] );	
		//vider session
		//die();
	}
?>

<link type="text/css" rel="StyleSheet" href="css/winclassic.css" />
<br><p class='text'>
<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
<form id='pack' name='pack' action='index.php?multi=20' method='post' enctype='multipart/form-data'>
	<tr height='30px'><td colspan='10' align='center'><b><? echo $l->g(438); ?></b></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(49); ?>:</td><td colspan='2'><input id='nom' name='nom'></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(25); ?>:</td><td colspan='2'><select id='os' name='os'><option>WINDOWS</option><option>LINUX</option></select></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(439); ?>:</td><td colspan='2'><select id='proto' name='proto'><option>HTTP</option></select></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(440); ?>:</td><td colspan='2'><select  id='priority' name='priority'>
	<option>0</option><option>1</option><option>2</option><option>3</option><option>4</option><option selected>5</option><option>6</option><option>7</option><option>8</option><option>9</option></select></td></tr>
	
	
	<tr height='30px' bgcolor='white'><td><span id='filetext'><? echo $l->g(549); ?></span>:</td><td colspan='2'><input id='fichier' name='fichier' type='file' accept='archive/zip'></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(443); ?>:</td><td><select id='action' name='action' OnChange='active("EXECUTE_div", false);active("STORE_div", false);active("LAUNCH_div", false);active(this.value + "_div", true);'>
	<option value='STORE'><? echo $l->g(457); ?></option><option value='EXECUTE'><? echo $l->g(456); ?></option><option value='LAUNCH'><? echo $l->g(458); ?>
	</option></select></td>
		<td width='43%' align='right'><div id='EXECUTE_div' style='display:block'><? echo $l->g(444); ?>: <input id='command' name='command'></div>
		<div id='STORE_div' style='display:none'><? echo $l->g(445); ?>: <input id='path' name='path'></div>
		<div id='LAUNCH_div' style='display:none'><? echo $l->g(446); ?>: <input id='nme' name='nme'></div></td>
	</tr>
	<tr height='30px' BGCOLOR='#C7D9F5'><td align='center' colspan='10'><b><? echo $l->g(447); ?></b></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(448); ?>:</td><td colspan='2'><select id='NOTIFY_USER' name='NOTIFY_USER' OnChange='active("d1", this.value);'><option value='0'><? echo $l->g(454); ?></option><option value='1'><? echo $l->g(455); ?></option></select></td></tr>
	<tr><td colspan='10' align='right'>
	<span id='d1' style='display:none'>
	<table width='80%'>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(449); ?>:</span></td><td colspan='2'><input id='NOTIFY_TEXT' name='NOTIFY_TEXT'></div></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(450); ?>:</td><td colspan='2'><input id='NOTIFY_COUNTDOWN' name='NOTIFY_COUNTDOWN' size='4'>&nbsp;&nbsp;&nbsp;<? echo $l->g(511); ?></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(451); ?>:</td><td colspan='2'><select id='NOTIFY_CAN_ABORT' name='NOTIFY_CAN_ABORT'><option value='0'><? echo $l->g(454); ?></option><option value='1'><? echo $l->g(455); ?></option></td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(452); ?>:</td><td colspan='2'><select id='NOTIFY_CAN_DELAY' name='NOTIFY_CAN_DELAY'><option value='0'><? echo $l->g(454); ?></option><option value='1'><? echo $l->g(455); ?></option></td></tr>
	</table>
	<br>
	</span>
	</td></tr>
	<tr height='30px' bgcolor='white'><td><? echo $l->g(453); ?>:</td><td colspan='2'><select name='NOTIFY_CAN_DELAY' name='NEED_DONE_ACTION'><option value='0'><? echo $l->g(454); ?></option><option value='1'><? echo $l->g(455); ?></option></td></tr>
	<tr height='30px' bgcolor='white'><td align='right' colspan='10'>				
	<input type='hidden' id='digest_algo' name='digest_algo' value='MD5'>
	<input type='hidden' id='digest_encod' name='digest_encod' value='Base64'>
	<input type='button' name='send' OnClick='checkAll()' value='<? echo $l->g(13); ?>'></td></tr>
</form>
</table></p>


