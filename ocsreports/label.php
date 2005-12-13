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
//Modified on 7/7/2005
printEntete($l->g(263));

if($_POST["newlabel"]!="") {
	@mysql_query("DELETE FROM deploy WHERE name='label'");
	$queryL = "INSERT INTO deploy VALUES('label','".$_POST["newlabel"]."');";
	mysql_query($queryL) or die(mysql_error());
	echo "<br><center><font color=green><b>".$l->g(260)."</b></font></center>";
}
else if(isset($_POST["newlabel"])) {
	@mysql_query("DELETE FROM deploy WHERE name='label'");
	echo "<br><center><font color=green><b>".$l->g(261)."</b></font></center>";
}

$reqL="SELECT content FROM deploy WHERE name='label'";
$resL=mysql_query($reqL) or die(mysql_error());
$con = mysql_fetch_row($resL);

if($con[0]) {
	//echo "<br><center><FONT FACE='tahoma' SIZE=2 color='green'><b>Label actuel: \"".$con[0]."\"</b></font></center>";
}
else {
	if(!isset($_POST["newlabel"]))
		echo "<br><center><FONT FACE='tahoma' SIZE=2 color='green'><b>".$l->g(264)."</b></font></center>";
}
$con[0] = stripslashes($con[0]);
?><br>
<center><b><? echo $l->g(262);?>:</b>
<form name='lab' action='index.php?multi=12' method='post'>
	<textarea name='newlabel'><?=$con[0]?></textarea>
	<input name='sublabel' type='submit' value='envoyer'>
</form>
</center>