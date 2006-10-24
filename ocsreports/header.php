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
//Modified on 10/17/2006

error_reporting(E_ALL & ~E_NOTICE);
set_time_limit(0);
@session_start();

include("preferences.php");

// update checking
$resUpd = @mysql_query("SELECT tvalue FROM config WHERE name='GUI_VERSION'", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$valUpd = @mysql_fetch_array($resUpd);
if( !$valUpd || $valUpd["tvalue"]<GUI_VER ) {
	$fromAuto = true;
	include('install.php');
	die();
}//

if(isset($_GET["logout"])) {
	foreach( $_SESSION as $key=>$val) {		
		unset($_SESSION[$key]);
	}
}

if( isset($_GET["first"] )) {
	unset( $_SESSION["lareq"] );
	unset( $_SESSION["lareqpages"] );
}

?>
<html>
<head>
<TITLE>OCS Inventory</TITLE>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Expires" CONTENT="-1">
<META HTTP-EQUIV="Content-Type" CONTENT="text/html<? 
	if($l->g(0)) 
		echo "; charset=".$l->g(0).";";
	else
		echo "; charset=ISO-8859-1;";	
?>">
<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>
<? incPicker(); ?>
<script language='javascript'>
<?if($_GET["multi"] == 3 && $_GET["mode"] == 1) {?>
	function scrollHeaders() {
		var monSpan = document.getElementById("headers");
		if( document.body.scrollTop > 200) {
			monSpan.style.top = (( Math.ceil(document.body.scrollTop / 27)) * 27) + 3<?
	if( getBrowser() == "MOZ" )
		echo " - 17 + 27;\n";
?>			
			monSpan.style.visibility = 'visible';
			// 15 Netsc 8ie
		}
		else
			monSpan.style.visibility = 'hidden';
	}
<?}?>
	
	function wait( sens ) {	
		var mstyle = document.getElementById('wait').style.display	= (sens!=0?"block" :"none");	
	}

</script>
</head> 
<?
echo "<body bottommargin='0' leftmargin='0' topmargin='0' rightmargin='0' marginheight='0' marginwidth='0'";
if( $_GET["multi"] ==3 && $_GET["mode"] == 1) {
	echo " OnScroll='javascript:scrollHeaders()'";
	if( getBrowser()=="MOZ")
		echo " OnMouseMove='javascript:scrollHeaders()'";
}
echo ">";

if( !isset($_GET["popup"] )) {
?>
<table class='headfoot' border='0'>
<tr height=25px>
	<td><a href='index.php?first'><img src='image/logo OCS-ng-48.png'></a></td>
	<td align='center' width='33%'><a href='index.php?first'><img src=image/banner-ocs.png></a></td><td width='33%' align='right'>
	<b>Ver. <?=GUI_VER?>&nbsp&nbsp&nbsp;</b>	
<?
	}

	if(isset($_POST["subLogin"])) {				
		$req="SELECT id, accesslvl, passwd FROM operators WHERE id='".$_POST["login"]."'";
		
		$res=mysql_query($req,$_SESSION["readServer"]) or die(mysql_error());
		
		if($row=@mysql_fetch_object($res))
		{
                     // DL 25/08/2005
			// Support new MD5 encrypted password or old clear password for login only						
			if (($row->passwd != md5( $_POST["pass"])) and
			    ($row->passwd != $_POST["pass"])) {
				$err = "</tr></table><br><center><font color=red><b>".$l->g(216)."</b></font></center>";
				unset($_SESSION["loggeduser"],$_SESSION["lvluser"]);				
			}
			else {
				$_SESSION["loggeduser"]=$row->id;
				$_SESSION["lvluser"]=$row->accesslvl;	
			}
		}
		else
		{
			$err = "</tr></table><br><center><font color=red><b>".$l->g(180)."</b></font></center>";
			unset($_SESSION["loggeduser"],$_SESSION["lvluser"]);			
		}				
	}	
	
	if ( !isset($_SESSION["loggeduser"]) && $dir = @opendir("languages")) {
		while($filename = readdir($dir)) {
			if( strstr ( $filename, ".txt") === false)
				continue;
			$langue = basename ( $filename,".txt");
			echo "<a title='$langue' href=\"index.php?av=1&multi=".$_GET["multi"]."&c=".$_GET["c"]."&a=".$_GET["a"]."&lang=$langue\"><img src =\"languages/$langue.png\" width=\"20\" height=\"15\"></a>&nbsp;";
		}
		closedir($dir);
	}
	
	if( isset($err) )
		echo $err;
	
	if(isset($_SESSION["loggeduser"]) && !isset($_GET["popup"]))
		echo "</td></tr><tr align=center><td align='center' colspan='3'>&nbsp;&nbsp;&nbsp;<a href=?logout><font color=black><u>".$l->g(251)."</u></font></a>&nbsp;&nbsp;&nbsp;<a href=index.php?multi=11><font color=black><u>".$l->g(236)."</u></font></a></td>";

	echo "</tr></table>";

	if(!isset($_SESSION["loggeduser"]))
	{			
		echo "<br><form name=log action=index.php method=post>
		<table BORDER='0' WIDTH = '35%' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
			<tr>
				<td><b>".$l->g(24).":</b></td>
				<td><input name=login type=input size=15></td>
			</tr>
			<tr>
				<td><b>".$l->g(217).":</b></td>
				<td><input name=pass type=password size=15></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td><input name=subLogin type=submit value=".$l->g(13)."></td>
			</tr>
		</table>
		</form>		
		";
		include ("footer.php");
		die();
	}
	
	$limitedAccess = array(2,3,4,5,6,7,8,9,14,13,22,23,24,27,20,21,26);
	if( in_array($_GET["multi"],$limitedAccess) && $_SESSION["lvluser"]!=1) {
		echo "<br><br><center><b><font color=red>ACCESS DENIED</font></b></center><br>";
		unset($_GET["multi"]);
		die();
	}

	
?>