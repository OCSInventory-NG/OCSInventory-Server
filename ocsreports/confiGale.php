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
//Modified on 9/30/2005
require ('fichierConf.class.php');

if( ! $_GET["multi"] )
{
	include('req.class.php');
	include('preferences.php');
}

if($_GET["modifi"])
	modifier();
	
elseif($_POST["enre"])
	enregistrer();
	
else 
{   
	$requete = new Req($l->g(107),"select * from config","select count(*) from config","","","");
	printEnTete($l->g(107));
	echo "<p align='center'><input type=button OnClick=\"window.location='index.php?multi=4&modifi=1'\" value='".$l->g(103)."'>";
	
	ShowResults($requete);
}// fin else

function formulaire($name,$ivalue,$tvalue,$comments)
{	
	global $l;
	$readonly = "";
	$label    = $modconf;

	if($_GET["modifi"]) // c'est une modification
	{
		$label = $name;
		$readonly = "readonly";
	}
	
	$tr =  " <tr><td align='right'><font face=Verdana size=-1>";
	echo   "<center><br><br><font size='+1'><b>&nbsp;&nbsp; $label &nbsp;&nbsp;</b></font>
			 <form name='ajouter_reg' method='POST' action='index.php?multi=4'>
			 <table>
			 <tr><td align='right'><font face=Verdana size=-1>".$l->g(49)."   :&nbsp;&nbsp;&nbsp;&nbsp;</font> </td>
			 <td> <input name='NAME' ".$readonly." value='".urldecode($name)."'></td></tr><br>
			 <tr><td align='right'><font face=Verdana size=-1> IVALUE :&nbsp;&nbsp;&nbsp;&nbsp;</font></td>
			 <td> <input name='IVALUE' value='".$ivalue."'> </td></tr><br>
			 <tr><td align='right'><font face=Verdana size=-1>TVALUE : &nbsp;&nbsp;&nbsp;&nbsp; </font> </td>
			 <td> <input name='TVALUE' value='".urldecode($tvalue)."'></td></tr><br>
			 <tr><td align='right'><font face=Verdana size=-1>".$l->g(51)."   : &nbsp;&nbsp;&nbsp;&nbsp;</font>  </td>
			 <td><textarea cols=15 name='COMMENTS'>".urldecode($comments)."</textarea></td></tr>
			 </table>
 			 <br><br>
			 <table>
			 <tr><td>
			 <input class='bouton' name='enre'    type='submit' value=".$l->g(114)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			 <input class='bouton' name='annuler' type='submit' value=".$l->g(113)."></td></tr>
			 </table></form>";
}
function modifier()
{
	global $l;
	$tr =  "<tr><td align='right'><font face=Verdana size=-1>";

	if ($_GET["modifi"]==2) // on a récupéré le NAME de la ligne que l'on veut modifier
	{
		$requete = "select NAME,IVALUE,TVALUE,COMMENTS from config where NAME='".$_GET["name"]."';";
		$result  = mysql_query($requete, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$item    = mysql_fetch_object($result);
		formulaire($item->NAME,$item->IVALUE,$item->TVALUE,$item->COMMENTS);
	}
	else // on demande quelle ligne on veut modifier
	{	
		echo   "<center>".$l->g(285)." :<BR><BR><BR>";
	
		$requete = "select NAME from config";
		$result  = mysql_query($requete, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		while($item = mysql_fetch_object($result))
			echo "<a href='index.php?multi=4&modifi=2&name=$item->NAME'>$item->NAME</a><br>";
			
		echo "<br><br><r><a align='right' href='index.php?multi=4'>".$l->g(113)."</a>";
	}// fin else
}// fin function

function enregistrer()
{
		$req = "UPDATE config SET ".	
				"IVALUE='".$_POST["IVALUE"]."',".
				"TVALUE='".$_POST["TVALUE"]."',".
				"COMMENTS='".$_POST["COMMENTS"]."' ".
				"where NAME='".$_POST["NAME"]."'";
		$result = mysql_query($req, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		?>
		<script language="javascript">
			window.location="index.php?multi=4";
		</script>
	 <?php
	 	return;
}
?>