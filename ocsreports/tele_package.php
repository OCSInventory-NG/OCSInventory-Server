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
//Modified on $Date: 2008-02-22 16:39:02 $$Author: hunal $($Revision: 1.14 $)
?>
<script language='javascript'>

	function active(id, sens) {
		//document.write( id );
		var mstyle = document.getElementById(id).style.display	= (sens!=0?"block" :"none");
		if( id == 'EXECUTE_div' && sens ) {
			document.getElementById("filetext").innerHTML = "<?php echo htmlentities($l->g(550)); ?>";
		}
		else if( ! sens ) {
			document.getElementById("filetext").innerHTML = "<?php echo htmlentities($l->g(549)); ?>";
		}
	}
	function checkAll() {
		if ( document.getElementById('nom').value =="" || ( document.getElementById('command').value =="" && document.getElementById('path').value =="" && document.getElementById('nme').value =="" )
		|| ( document.getElementById('NOTIFY_USER').value =="1" && (document.getElementById('NOTIFY_TEXT').value =="" || document.getElementById('NOTIFY_COUNTDOWN').value =="") )
		)
					alert("<?php echo $l->g(239); ?>");
		else if( isNaN(document.getElementById('NOTIFY_COUNTDOWN').value) )  {
				alert("<?php echo $l->g(459); ?>");
		}
		else document.getElementById('pack').submit();
	}

</script>

<?php 
	//looking for the directory for pack
	$sql_document_root="select tvalue from config where NAME='DOWNLOAD_PACK_DIR'";
	$res_document_root = mysql_query( $sql_document_root, $_SESSION["readServer"] );
	while( $val_document_root = mysql_fetch_array( $res_document_root ) ) {
		$document_root = $val_document_root["tvalue"];
	}
	//if no directory in base, take $_SERVER["DOCUMENT_ROOT"]
	if (!isset($document_root))
	$document_root = $_SERVER["DOCUMENT_ROOT"];
	else
	@mkdir($document_root."/download/");
	
	@set_time_limit(0);
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
		
				if( $_POST["digest_algo"] == "SHA1" )
					$digest = sha1_file($_FILES["fichier"]["tmp_name"],true);
				else
					$digest = md5_file($_FILES["fichier"]["tmp_name"]);
					
				if( $_POST["digest_encod"] == "Base64" )
					$digest = base64_encode( $digest );
					
				$digName = $_POST["digest_algo"]. " / ".$_POST["digest_encod"];
				
				if((! @mkdir( $document_root."/download/".$id)) ||
				   (! copy( $_FILES["fichier"]["tmp_name"], $document_root."/download/".$id."/tmp" ))) {
					echo "<br><center><font color='red'><b>ERROR: can't create or write in ".$document_root."/download/".$id." folder, please refresh when fixed. <br>
					<br>(or try disabling php safe mode)</b></font></center>";
					die("<script language='javascript'>wait(0);</script>");
				}			
						
				?>
				<script type="text/javascript" src="js/range.js"></script>
				<script type="text/javascript" src="js/timer.js"></script>
				<script type="text/javascript" src="js/slider.js"></script>
				<link type="text/css" rel="StyleSheet" href="css/winclassic.css" />
		
				<br>
				<form name='frag' action='index.php?multi=20' method='post'>
				<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
				<tr height='30px'><td align='center' colspan='10'><b><?php echo $l->g(435); ?> [<?php echo $_POST["nom"]; ?>]</b></td></tr>
				<tr height='30px' bgcolor='white'><td><?php echo $l->g(446); ?>:</td><td><?php echo $_FILES["fichier"]["name"]; ?></td></tr> 
				<tr height='30px' bgcolor='white'><td><?php echo $l->g(460); ?>:</td><td><?php echo $id; ?></td></tr>
				<tr height='30px' bgcolor='white'><td><?php echo $l->g(461); ?> <b><?php echo $digName; ?></b>:</td><td><?php echo $digest; ?></td></tr>
				<tr height='30px' bgcolor='white'><td><?php echo $l->g(462); ?>:</td><td><?php echo round($size/1024); ?> <?php echo $l->g(516); ?></td></tr>
				<tr height='30px' bgcolor='white'><td><?php echo $l->g(463); ?>:</td><td>
				<table><tr><td width='30%'>	
				<span id='tailleFrag' name='tailleFrag'><?php echo round($size/1024); ?></span> <?php echo $l->g(516); ?>
				</td>
				<?php if( round($size) > 1024 ) { ?>
						<td>
						<div class="slider" id="slider-1" tabIndex="1">
						<input class="slider-input" id="slider-input-1" name="slider-input-1"/>
						</div>
						</td>
				<?php }?>
				</tr></table></td></tr>
				<tr height='30px' bgcolor='white'><td><?php echo $l->g(464); ?>:</td><td>
					<input id='nbfrags' name='nbfrags' value='1' size='5' readonly></td></tr>
				<tr height='30px' bgcolor='white'><td align='right' colspan='10'><input type='submit'>
				<input type='hidden' name='id' value='<?php echo $id; ?>'>
				<input type='hidden' name='digest' value='<?php echo $digest; ?>'>
				</td></tr>
				</table>		
				</form>
				<?php if( round($size) > 1024 ) { ?>
					<script type="text/javascript">
					
					var s = new Slider(document.getElementById("slider-1"),
					                   document.getElementById("slider-input-1"));
					var siz = <?php echo round($size); ?>;
					var vmin = 1024;
					
					s.setMaximum( siz );				
					s.setValue( siz );
					
					s.setMinimum(vmin);						
					s.onchange = function () {
						document.getElementById('tailleFrag').innerHTML = Math.ceil((s.getValue())/1024);
						document.getElementById('nbfrags').value = Math.ceil( siz / (Math.ceil(s.getValue())) );				
					}	
					</script>
					<?php 
				}
				die("<script language='javascript'>wait(0);</script>");			
				}
				else {
					$id = time();
					if( ! @mkdir( $document_root."/download/".$id)) {
						echo "<center><font color='red'><b>ERROR: can't write in ".$document_root."/download/ folder, please refresh when corrected</b></font></center>";
						die("<script language='javascript'>wait(0);</script>");
					}
					?>
					<form name='frag' id='frag' action='index.php?multi=20' method='post'>
						<input type='hidden' id='nbfrags' name='nbfrags' value='0'>			
						<input type='hidden' name='id' value='<?php echo $id; ?>'>
					</form>
					<script language='javascript'>document.getElementById("frag").submit();</script>
					<?php 
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
		$fname = $document_root."/download/".$_POST["id"]."/tmp";
		if( $size = @filesize( $fname )) {
			$handle = fopen ( $fname, "rb");
			
			$read = 0;
			for( $i=1; $i<$_POST["nbfrags"]; $i++ ) {
				$contents = fread ($handle, $size / $_POST["nbfrags"] );
				$read += strlen( $contents );
				$handfrag = fopen( $document_root."/download/".$_POST["id"]."/".$_POST["id"]."-".$i, "w+b" );
				fwrite( $handfrag, $contents );
				fclose( $handfrag );
				//echo "FRAG ".$i." lu ".strlen( $contents ). " (en tout " .$read.")<br>";
			}	
			
			$contents = fread ($handle, $size - $read);
			$read += strlen( $contents );
			$handfrag = fopen( $document_root."/download/".$_POST["id"]."/".$_POST["id"]."-".$i, "w+b" );
			fwrite( $handfrag, $contents );
			fclose( $handfrag );
			fclose ($handle);
	
			unlink( $document_root."/download/".$_POST["id"]."/tmp" );
		}
		
		//creation info
		$info = "<DOWNLOAD ID=\"".clean($_POST["id"])."\" ".
		"PRI=\"".clean($_SESSION["down_priority"])."\" ".
		"ACT=\"".clean($_SESSION["down_action"])."\" ".
		"DIGEST=\"".clean($_POST["digest"])."\" ".		
		"PROTO=\"".	clean($_SESSION["down_proto"])."\" ".
		"FRAGS=\"".clean($_POST["nbfrags"])."\" ".
		"DIGEST_ALGO=\"".clean($_SESSION["down_digest_algo"])."\" ".
		"DIGEST_ENCODE=\"".clean($_SESSION["down_digest_encod"])."\" ".
		"PATH=\"".clean($_SESSION["down_path"])."\" ".
		"NAME=\"".clean($_SESSION["down_nme"])."\" ".
		"COMMAND=\"".clean($_SESSION["down_command"])."\" ".
		"NOTIFY_USER=\"".clean($_SESSION["down_NOTIFY_USER"])."\" ".
		"NOTIFY_TEXT=\"".clean($_SESSION["down_NOTIFY_TEXT"])."\" ".
		"NOTIFY_COUNTDOWN=\"".clean($_SESSION["down_NOTIFY_COUNTDOWN"])."\" ".
		"NOTIFY_CAN_ABORT=\"".clean($_SESSION["down_NOTIFY_CAN_ABORT"])."\" ".
		"NOTIFY_CAN_DELAY=\"".clean($_SESSION["down_NOTIFY_CAN_DELAY"])."\" ".
		"NEED_DONE_ACTION=\"".clean($_SESSION["down_NEED_DONE_ACTION"])."\" ".		
		"NEED_DONE_ACTION_TEXT=\"".clean($_SESSION["down_NEED_DONE_ACTION_TEXT"])."\" ".		
		"GARDEFOU=\"rien\" />\n";
		
		$handinfo = fopen( $document_root."/download/".$_POST["id"]."/info", "w+" );
		fwrite( $handinfo, $info );
		fclose( $handinfo );
		
		mysql_query( "DELETE FROM download_available WHERE FILEID='".$_POST["id"]."'", $_SESSION["writeServer"]);
		$req = "INSERT INTO download_available(FILEID, NAME, PRIORITY, FRAGMENTS, SIZE, OSNAME, COMMENT) VALUES
		( '".$_POST["id"]."', '".addslashes($_SESSION["down_nom"])."','".$_SESSION["down_priority"]."', '".$_POST["nbfrags"]."',
		'".$size."', '".$_SESSION["down_os"]."', '' )";
		
		mysql_query( $req, $_SESSION["writeServer"] );
		echo mysql_error();
		echo "<br><center><b><font color='green'>".$l->g(437)." ".$document_root."/download/".$_POST["id"]."</font></b></center><br>";

		unset( $_POST["nbfrags"] );	
		//vider session
		//die();
	}
	
	function clean( $txt ) {
		$cherche = array( "<",   ">",   "&",     "\"",     "'");
		$replace = array( "&lt;","&gt;","&amp;", "&quot;", "&apos;");
		return str_replace($cherche, $replace, $txt);
	}
?>

<link type="text/css" rel="StyleSheet" href="css/winclassic.css" />
<br><p class='text'>
<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
<form id='pack' name='pack' action='index.php?multi=20' method='post' enctype='multipart/form-data'>
	<tr height='30px'><td colspan='10' align='center'><b><?php echo $l->g(438); ?></b></td></tr>
	<tr height='30px' bgcolor='white'><td><?php echo $l->g(49); ?>:</td><td colspan='2'><input id='nom' name='nom'></td></tr>
	<tr height='30px' bgcolor='white'><td><?php echo $l->g(25); ?>:</td><td colspan='2'><select id='os' name='os' OnChange='active("divNotif", this.value=="WINDOWS");'><option>WINDOWS</option><option>LINUX</option></select></td></tr>
	<tr height='30px' bgcolor='white'><td><?php echo $l->g(439); ?>:</td><td colspan='2'><select id='proto' name='proto'><option>HTTP</option></select></td></tr>
	<tr height='30px' bgcolor='white'><td><?php echo $l->g(440); ?>:</td><td colspan='2'><select  id='priority' name='priority'>
	<option>0</option><option>1</option><option>2</option><option>3</option><option>4</option><option selected>5</option><option>6</option><option>7</option><option>8</option><option>9</option></select></td></tr>
	
	
	<tr height='30px' bgcolor='white'><td><span id='filetext'><?php echo $l->g(549); ?></span>:</td><td colspan='2'><input id='fichier' name='fichier' type='file' accept='archive/zip'></td></tr>
	<tr height='30px' bgcolor='white'><td><?php echo $l->g(443); ?>:</td><td><select id='action' name='action' OnChange='active("EXECUTE_div", false);active("STORE_div", false);active("LAUNCH_div", false);active(this.value + "_div", true);'>
	<option value='STORE' selected><?php echo $l->g(457); ?></option>
	<option value='EXECUTE'><?php echo $l->g(456); ?></option>
	<option value='LAUNCH'><?php echo $l->g(458); ?></option>
	</select></td>
		<td width='43%' align='right'>
		<div id='EXECUTE_div' style='display:none'><?php echo $l->g(444); ?>: <input id='command' name='command'></div>
		<div id='STORE_div' style='display:block'><?php echo $l->g(445); ?>: <input id='path' name='path'></div>
		<div id='LAUNCH_div' style='display:none'><?php echo $l->g(446); ?>: <input id='nme' name='nme'></div></td>
	</tr>
	<tr><td colspan='3'>
		<div id='divNotif' style='display:block'>
		<table BGCOLOR='#C7D9F5' BORDER='0' WIDTH = '600px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
		<tr height='30px' BGCOLOR='#C7D9F5'><td align='center' colspan='10'><b><?php echo $l->g(447); ?></b></td></tr>
		<tr height='30px' bgcolor='white'><td><?php echo $l->g(448); ?>:</td><td colspan='2'><select id='NOTIFY_USER' name='NOTIFY_USER' OnChange='active("d1", this.value);'><option value='0'><?php echo $l->g(454); ?></option><option value='1'><?php echo $l->g(455); ?></option></select></td></tr>
		<tr><td colspan='10' align='right'>
		<span id='d1' style='display:none'>
			<table width='80%'>
			<tr height='30px' bgcolor='white'><td><?php echo $l->g(449); ?>:</span></td><td colspan='2'><input id='NOTIFY_TEXT' name='NOTIFY_TEXT'></div></td></tr>
			<tr height='30px' bgcolor='white'><td><?php echo $l->g(450); ?>:</td><td colspan='2'><input id='NOTIFY_COUNTDOWN' name='NOTIFY_COUNTDOWN' size='4'>&nbsp;&nbsp;&nbsp;<?php echo $l->g(511); ?></td></tr>
			<tr height='30px' bgcolor='white'><td><?php echo $l->g(451); ?>:</td><td colspan='2'><select id='NOTIFY_CAN_ABORT' name='NOTIFY_CAN_ABORT'><option value='0'><?php echo $l->g(454); ?></option><option value='1'><?php echo $l->g(455); ?></option></td></tr>
			<tr height='30px' bgcolor='white'><td><?php echo $l->g(452); ?>:</td><td colspan='2'><select id='NOTIFY_CAN_DELAY' name='NOTIFY_CAN_DELAY'><option value='0'><?php echo $l->g(454); ?></option><option value='1'><?php echo $l->g(455); ?></option></td></tr>
			</table>
			<br>
		</span>
		</td></tr>
		<tr height='30px' bgcolor='white'><td><?php echo $l->g(453); ?>:</td><td colspan='2'><select id='NEED_DONE_ACTION' name='NEED_DONE_ACTION' OnChange='active("divNDA", this.value);'><option value='0'><?php echo $l->g(454); ?></option><option value='1'><?php echo $l->g(455); ?></option></td></tr>
		<tr><td colspan='10' align='right'>
		<span id='divNDA' style='display:none'>
			<table width='80%'>
			<tr height='30px' bgcolor='white'><td><?php echo $l->g(449); ?>:</span></td><td colspan='2'><input id='NEED_DONE_ACTION_TEXT' name='NEED_DONE_ACTION_TEXT'></div></td></tr>
			</table>
			<br>
		</span>
		</td></tr>		
		</table>		
		</div>
	</td></tr>
	<tr height='30px' bgcolor='white'><td align='right' colspan='10'>			
		<input type='hidden' id='digest_algo' name='digest_algo' value='MD5'>
		<input type='hidden' id='digest_encod' name='digest_encod' value='Hexa'>
		<input type='button' name='send' OnClick='checkAll()' value='<?php echo $l->g(13); ?>'></td></tr>
</form>
</table></p>


