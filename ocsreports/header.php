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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.16 $)

error_reporting(E_ALL & ~E_NOTICE);
@set_time_limit(0);
@session_start();

require_once("preferences.php");

// update checking
$resUpd = @mysql_query("SELECT tvalue FROM config WHERE name='GUI_VERSION'", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$valUpd = @mysql_fetch_array($resUpd);
if( !$valUpd || $valUpd["tvalue"]<GUI_VER ) {
	$fromAuto = true;
	require('install.php');
	die();
}

if(isset($_GET["logout"])) {
        $_SESSION = array();
        session_destroy();
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
<META HTTP-EQUIV="Content-Type" CONTENT="text/html<?php 
	if($l->g(0)) 
		echo "; charset=".$l->g(0).";";
	else
		echo "; charset=ISO-8859-1;";	
?>">
<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>
<?php incPicker(); ?>
<script language='javascript'>
<?php if($_GET["multi"] == 3 && $_GET["mode"] == 1) {?>
	function scrollHeaders() {
		var monSpan = document.getElementById("headers");
		if( monSpan ) {
			if( document.body.scrollTop > 200) {
				monSpan.style.top = (( Math.ceil(document.body.scrollTop / 27)) * 27) + 3;		
				monSpan.style.visibility = 'visible';
				// 15 Netsc 8ie
			}
			else
				monSpan.style.visibility = 'hidden';
		}
	}
<?php }?>
	
	function wait( sens ) {	
		var mstyle = document.getElementById('wait').style.display	= (sens!=0?"block" :"none");	
	}
	
	function ruSure( pageDest ) {
		if( confirm("<? echo $l->g(525); ?>") )
			window.location = pageDest;
	}

</script>
</head> 
<?php 
echo "<body bottommargin='0' leftmargin='0' topmargin='0' rightmargin='0' marginheight='0' marginwidth='0'";
if( $_GET["multi"] ==3 && $_GET["mode"] == 1) {
	echo " OnScroll='javascript:scrollHeaders()'";
	if( getBrowser()=="MOZ")
		echo " OnMouseMove='javascript:scrollHeaders()'";
}
echo ">";
if( !isset($_GET["popup"] )) {
?>
<table class='headfoot' border='0' <?if ($ban_head=='no') echo "style='display:none;'"?>>
<tr height=25px>
	<td><a href='index.php?first'><img src='image/logo OCS-ng-48.png'></a></td>
	<td align='center' width='33%'><a href='index.php?first'><img src=image/banner-ocs.png></a></td><td width='33%' align='right'>
	<b>Ver. 1.02 RC3&nbsp&nbsp&nbsp;</b>	
<?php 

if($_SESSION["debug"]==1)
	echo "<br><font color='black'><b>CACHE:&nbsp;<font color='".($_SESSION["usecache"]?"green'><b>ON</b>":"red'><b>OFF</b>")."</font><div id='tps'>Calcul...</div>";
}

	if(isset($_POST["login"])) {				
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
		echo "<br><br>";
		while($filename = readdir($dir)) {
			if( strstr ( $filename, ".txt") === false)
				continue;
			$langue = basename ( $filename,".txt");
			echo "<a title='$langue' href=\"index.php?av=1&multi=".$_GET["multi"]."&c=".$_GET["c"]."&a=".$_GET["a"]."&lang=$langue\"><img src =\"languages/$langue.png\" width=\"20\" height=\"15\"></a>&nbsp;";
		}
		closedir($dir);
	}
	
	if(isset($_SESSION["loggeduser"])&&!isset($_GET["popup"] )) {
		echo "<br><br><a href=?logout>";
		echo "<img src='image/deconnexion.png' title='".$l->g(251)."' alt='".$l->g(251)."'>";
		echo "</a>&nbsp;&nbsp;&nbsp;<a href=index.php?multi=11>";
		echo "<img src='image/pass";
		if( $_GET["multi"] == 11 )
			echo "_a";
		echo ".png' title='".$l->g(236)."' alt='".$l->g(236)."' width=40px>";
		echo "</a>";
	}
	echo "</td></tr></table>";
	
	if( isset($err) )
		echo $err;
		
	if(!isset($_SESSION["loggeduser"]))
	{			
		echo "<br><form name='log' id='log' action='index.php' method='post'>
		<table BORDER='0' WIDTH = 250px' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
			<tr>
				<td><b>".$l->g(24).":</b></td>
				<td width='1%'><input name=login type=input size=15></td>
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
		require ("footer.php");
		die();
	}

	$limitedAccess = array(2,3,4,5,6,7,8,9,10,12,13,14,20,21,22,23,24,25,26,27,28,30,31,32,33,34,35);
	if( in_array($_GET["multi"],$limitedAccess) && $_SESSION["lvluser"]!=1) {
		echo "<br><br><center><b><font color=red>ACCESS DENIED</font></b></center><br>";
		unset($_GET["multi"]);
		die();
	}

	
?>
