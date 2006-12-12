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
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.4 $)

include ('fichierConf.class.php');

include('req.class.php');
if($_GET["suppAcc"]) {
	dbconnect();
	@mysql_query("DELETE FROM operators WHERE id='".$_GET["suppAcc"]."'");	
	echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>". $_GET["suppAcc"] ."</b> ".$l->g(245)." </font></center><br>";
}

if($_POST["nom"])
{
	switch($_POST["type"]) {
		case $l->g(242): $suff = "1"; break;
		case $l->g(243): $suff = "2"; break;
	}
	
	$query = "INSERT INTO operators(id,passwd,accesslvl) VALUES('".$_POST["nom"]."','".md5( $_POST["pass"])."', '$suff')";
	@mysql_query($query);
	echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>". $_POST["nom"] ."</b> ".$l->g(234)." </font></center><br>";
}//fin if	
?>
			<script language=javascript>
				function confirme(did)
				{
					if(confirm("<?echo $l->g(246)?> "+did+" ?"))
						window.location="index.php?multi=<?=$_GET["multi"]?>&c=<?=($_SESSION["c"]?$_GET["c"]:2)?>&a=<?=$_GET["a"]?>&page=<?=$_GET["page"]?>&suppAcc="+did;
				}
			</script>
<?
printEntete($l->g(244));		
echo "<br>
		 <form name='ajouter_reg' method='POST' action='index.php?multi=10'>
	<center>
	<table width='60%'>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(49)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 name='nom'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(236)." :&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td width='50%' align='left'><input size=40 type='password' name='pass'>
		</td>
	</tr>
	<tr>
		<td align='right' width='50%'>
			<font face='Verdana' size='-1'>".$l->g(66).":&nbsp;&nbsp;&nbsp;&nbsp;</font>
		</td>
		<td>
			<select name='type'>
				<option>".$l->g(242)."</option>
				<option>".$l->g(243)."</option>				
			</select>
		</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
		<tr>
		<td colspan='2' align='center'>
			<input class='bouton' name='enre' type='submit' value=".$l->g(114)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</td>
	</tr>
	
	</table></center></form><br>";
		
	printEntete($l->g(233));
	echo "<br>";
	
	$reqAc = mysql_query("SELECT id,accesslvl FROM operators ORDER BY accesslvl,id ASC") or die(mysql_error());
	echo "<table BORDER='0' WIDTH = '50%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr><td align='center'><FONT FACE='tahoma' SIZE=2><b>".$l->g(49)."</b></font></td><td align='center'><FONT FACE='tahoma' SIZE=2><b>".$l->g(66)."</b></font></td></tr>";		
	while($row=mysql_fetch_array($reqAc)) {			
		$x++;
		echo "<TR height=20px bgcolor='". ($x%2==0 ? "#FFFFFF" : "#F2F2F2") ."'>";	// on alterne les couleurs de ligne			
		echo "<td align=center><FONT FACE='tahoma' SIZE=2>".$row["id"]."</font></td><td align=center><FONT FACE='tahoma' SIZE=2>".($row["accesslvl"]==1?$l->g(242):$l->g(243))."</font></td><td align=center>
		<a href=# OnClick='confirme(\"".$row["id"]."\");'><img src=image/supp.png></a></td></tr>";
		
	}
	echo "</table><br>";

?>

