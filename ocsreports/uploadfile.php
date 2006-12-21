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
//Modified on $Date: 2006-12-21 18:13:47 $$Author: plemmet $($Revision: 1.7 $)

if( ! function_exists ( "zip_open" )) {
	function zip_open($st) {
		echo "<br><center><font color=red><b>ERROR: Zip for PHP is not properly installed.<br>Try uncommenting \";extension=php_zip.dll\" (windows) by removing the semicolon in file php.ini, or try installing the php4-zip package.</b></font></center>";
		die();
	}
}

if(is_uploaded_file($HTTP_POST_FILES['userfile']['tmp_name']))
{	
	$nomFich=$HTTP_POST_FILES['userfile']['name'];
	
	ereg( "(.*)\.(.*)$" , $HTTP_POST_FILES['userfile']['name'] , $results );

	$ext = $results[2];
	$cp = $results[1];
	$ok=true;

	if($ext=="zip")
	{
		$fname="agent";
		$platform="windows";
	}
	else if($ext=="pl")
	{
		$fname="agent";
		$platform="linux";		
	}
	else if((strcasecmp($ext,"exe")==0)&&((strcasecmp($cp,"ocsagent")==0) ||(strcasecmp($cp,"ocspackage")==0))) {
		$fname = strtolower($cp."."."exe");
		$platform="windows";
	}
	else if(is_numeric($ext))
	{
		$fname=$cp;
		$platform="linux";
	}
	else
	{
		echo "<br><b><center><font color=red>".$l->g(168)."</font></center></b><br>";
		$ok=false;
	}
	
	if($ok)
	{
		$filename = $HTTP_POST_FILES['userfile']['tmp_name'];
		$fd = fopen($filename, "r");

		$contents = fread($fd, filesize ($filename));
		fclose($fd);
		
		$binary = addslashes($contents);
		if($ext=="zip")
		{
			$version=getVersionFromZip($filename);	
		}
		else if($ext=="pl")
		{
			$version=getVersionFromLinuxAgent($binary);		
		}
		else
		{
			$version=$ext;
		}
		
		if($version!=-1&&$version!="")
		{	
			$table = "files";
			$val = "('$fname','$version','$platform','$binary')";
			if( $fname == "ocsagent.exe" || $fname == "ocspackage.exe" ) {
				@mysql_query("DELETE FROM deploy WHERE name='$fname'");
				$table = "deploy";
				$val = "('$fname','$binary')";
			}
			
			$query = "INSERT INTO $table VALUES$val;";
			if(mysql_query($query, $_SESSION["writeServer"]))
			{
				echo "<br><b><center><font color='green'>".$l->g(137)." ".$HTTP_POST_FILES['userfile']['name']." ".$l->g(234)."</font></center></b><br>";
			}
			else if(mysql_errno()==1062)
			{
				echo "<br><b><center><font color=red>".$l->g(170)."</font></center></b><br>";
			}
			else
			{
				echo "<br><b><center><font color=red>".$l->g(172)." ".$HTTP_POST_FILES['userfile']['name']." ".$l->g(173)."<br>";
				echo mysql_error($_SESSION["writeServer"])."</font></center></b>";
			}
			sleep(2);
		}
	}	
}

if($_GET["o"]&&$_GET["n"]&&$_GET["v"]&&$_GET["supp"]==1)
{
	if( strtolower($_GET["n"]) == "ocsagent.exe" ) {
		@mysql_query("DELETE FROM deploy WHERE name='ocsagent.exe'");
	}
	else if( strtolower($_GET["n"]) == "ocspackage.exe" ) {
		@mysql_query("DELETE FROM deploy WHERE name='ocspackage.exe'");
	}
	else
	{	
		$suppQuery="DELETE FROM files WHERE name='".$_GET["n"]."' AND os='".$_GET["o"]."' AND version='".$_GET["v"]."'";
		@mysql_query($suppQuery, $_SESSION["writeServer"]);
	}
	echo "<br><b><center>".$l->g(171)."</center></b><br>";
	sleep(2);
}
?>
<script language=javascript>
				function confirme(v,o,n)
				{
					if(confirm("<?php echo $l->g(135);?>"))
						window.location="index.php?multi=8&supp=1&v="+v+"&o="+o+"&n="+n;
				}
</script>
<br>
<FORM ENCTYPE="multipart/form-data" ACTION="index.php?multi=8" METHOD="POST">
<br>
<table border=1 class= "Fenetre" WIDTH = '52%' ALIGN = 'Center' CELLPADDING='5'>
<th height=30px class="Fenetre" colspan=2>
	<b><?php echo $l->g(134);?></b>
</th>
	<tr bgcolor='#F2F2F2'><td><?php echo $l->g(137);?></td>
	    <td><INPUT NAME="userfile" TYPE="file"></td></tr>	
	<tr bgcolor='white'>
	    <td colspan=2 align=right><INPUT TYPE="submit" VALUE="<?php echo $l->g(13);?>"></td>
	</tr>
</table>
</FORM>
<br><br>

<table BORDER='0' WIDTH = '65%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
<tr>		
		<td align=center><B><?php echo $l->g(283);?></B></td>
		<td align=center><B><?php echo $l->g(19);?></B></td>
		<td align=center><B><?php echo $l->g(25);?></B></td>
		<td align=center><B><?php echo $l->g(49);?></B></td>
		<td align=center width=20px><B><?php echo $l->g(136);?></B></td>
		<td align=center width=20px><B><?php echo $l->g(122);?></B></td>
</tr>
<?php 
	$queryD = "SELECT '-' as version, 'windows' as os,name FROM deploy WHERE name<>'label'";
	if(!$resultD=mysql_query($queryD, $_SESSION["readServer"])) 
		echo mysql_error($_SESSION["readServer"]);
	
	$query="SELECT version, os,name FROM files ORDER BY version DESC,os ASC,name ASC";
	
	if(!$result=mysql_query($query, $_SESSION["readServer"])) 
		echo mysql_error($_SESSION["readServer"]);
	$x=0;
	
	$item = mysql_fetch_object($resultD);
	if( $item->name =="")
		$item=mysql_fetch_object($result);
		
	if( $item->name !="")
	do
	{
		echo "<TR height=20px bgcolor='". ($x == 1 ? "#FFFFFF" : "#F2F2F2") ."'>";	// on alterne les couleurs de ligne
		$x = ($x == 1 ? 0 : 1) ;
		
		if(strcasecmp($item->name,"ocsagent.exe") ==0 || strcasecmp($item->name,"ocspackage.exe") ==0 )
			echo "<td align=center><b><font color='blue'>".$l->g(370)."</font></b></td>";
		else
			echo "<td align=center><b><font color='green'>".$l->g(103)."</font></b></td>";
	?>		
		<td align=center><?php echo $item->version?></td>
		<td align=center><?php echo $item->os?></td>
		<td align=center><?php echo $item->name?></td>
		<td align=center><a href=javascript:void(0); OnClick='window.open("download.php?dl=1&v=<?php echo $item->version?>&o=<?php echo $item->os?>&n=<?php echo $item->name?>","fen","location=0,status=0,scrollbars=0,menubar=0,resizable=0,width=1,height=1")'><img src=image/Gest_admin1.png></a></FONT></td>
		<td align=center><a href=# OnClick='confirme("<?php echo $item->version?>","<?php echo $item->os?>","<?php echo $item->name?>");'><img src=image/supp.png></a></FONT></td>
		</tr>
	<?php }
	while($item=mysql_fetch_object($resultD));
?>
<table><br>
	

<?php 

function getVersionFromLinuxAgent($content)
{
	global $l;
	$res=Array();
	ereg("use constant VERSION =>([^;]*);", $content , $res);	
	
	if($res[1]=="")
	{
		echo "<br><b><center><font color=red>".$l->g(184)."</font></center></b><br>";
		return -1;
	}
	return str_replace ( " ", "", $res[1]);	
}

function getVersionFromZip($zipFile)
{
	global $l;
	if ($zip = @zip_open($zipFile)) 
	{		
		$trouve=false;
		while ($zip_entry = zip_read($zip)) 
		{
			if(zip_entry_name ($zip_entry) == "ver")
				if (zip_entry_open($zip, $zip_entry, "r")) 
				{
					$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					$trouve=true;
					zip_close($zip);
					if( $buf == "0") {
						echo "<br><b><center><font color=red>".$l->g(185)."</font></center></b><br>";
						return -1;
					}
					return $buf;
				}
		}
		
		if(!$trouve)
		{
			echo "<br><b><center><font color=red>".$l->g(186)."</font></center></b><br>";
			zip_close($zip);
			return -1;
		}
	}
	else
	{
		echo "<br><b><center><font color=red>".$l->g(187)."</font></center></b><br>";
		return -1;
	}
}
