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
//Modified on $Date: 2007-07-23 10:30:26 $$Author: plemmet $($Revision: 1.8 $)

require ('fichierConf.class.php');

require('req.class.php');
if($_GET["suppAcc"]) {
	dbconnect();
	@mysql_query("DELETE FROM operators WHERE id='".$_GET["suppAcc"]."'", $_SESSION["writeServer"] );	
	echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>". htmlentities(stripslashes($_GET["suppAcc"])) ."</b> ".$l->g(245)." </font></center><br>";
}

if($_POST["nom"])
{
	$suff = $_POST["type"];
	$query = "INSERT INTO operators(id,passwd,accesslvl) VALUES('".$_POST["nom"]."','".md5( $_POST["pass"])."', '$suff')";
	@mysql_query($query, $_SESSION["writeServer"] );
	echo "<br><br><center><font face='Verdana' size=-1 color='red'><b>". $_POST["nom"] ."</b> ".$l->g(234)." </font></center><br>";
}//fin if	
?>
			<script language=javascript>
				function confirme(did)
				{
					if(confirm("<?php echo $l->g(246)?> "+" ?"))
						window.location="index.php?multi=<?php echo $_GET["multi"]?>&c=<?php echo ($_SESSION["c"]?$_GET["c"]:2)?>&a=<?php echo $_GET["a"]?>&page=<?php echo $_GET["page"]?>&suppAcc="+did;
				}
			</script>
<?php 
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
				<option value=1>".$l->g(242)."</option>
				<option value=2>".$l->g(243)."</option>	
				<option value=3>".$l->g(619)."</option>				
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
	
	$reqAc = mysql_query("SELECT id,accesslvl FROM operators ORDER BY accesslvl,id ASC", $_SESSION["readServer"] ) or die(mysql_error());
	echo "<table BORDER='0' WIDTH = '50%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr><td align='center'><FONT FACE='tahoma' SIZE=2><b>".$l->g(49)."</b></font></td><td align='center'><FONT FACE='tahoma' SIZE=2><b>".$l->g(66)."</b></font></td></tr>";		
	while($row=mysql_fetch_array($reqAc)) {			
		$x++;
		echo "<TR height=20px bgcolor='". ($x%2==0 ? "#FFFFFF" : "#F2F2F2") ."'>";	// on alterne les couleurs de ligne			
		echo "<td align=center><FONT FACE='tahoma' SIZE=2>".$row["id"]."</font></td><td align=center><FONT FACE='tahoma' SIZE=2>";
		switch ($row["accesslvl"]) {
			case 1: echo $l->g(242); break;
			case 2: echo $l->g(243); break;
			case 3: echo $l->g(619)." <a href='index.php?multi=31&user=".$row["id"]."'>(".$l->g(618).")</a>"; break;
		}
		echo "</font></td><td align=center>
		<a href=# OnClick='confirme(\"".urlencode($row["id"])."\");'><img src=image/supp.png></a></td></tr>";
		
	}
	echo "</table><br>";

?>

