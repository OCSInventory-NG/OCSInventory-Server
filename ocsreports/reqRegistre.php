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
//Modified on $Date: 2006-12-12 10:43:49 $ by $Author: plemmet $ (version: $Revision: 1.3 $)

require ('fichierConf.class.php');

if( ! $_GET["multi"] )
{
	include('req.class.php');
	include('preferences.php');	
}

switch ($_GET["typeDemande"]) :
	
	case "ajout" : formulaire("","","","","");
						break;
	case "modif" : modifier();
						break;
	case "suppr" : supprimer();
						break;
	case "enreModif"  : enregistrer();	
						break;
	case "enreAjout"  : enregistrer();
						break;					
	default :
		
		if($_GET["id"])
			mysql_query("delete from regconfig where id='".$_GET["id"]."'", $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			
		echo "&nbsp;";
		$lbl=$l->g(2);		//Nom de la requete	
	
	$sql = "";
	$whereId = "id";
	$linkId = "id";
	$select = array("name"=>"name" ,"regtree"=>"regtree", "regkey"=>"regkey", "regvalue"=>"regvalue");	
	$selectPrelim = array( "id"=>"id" );
	$from = "regconfig";
	$fromPrelim = "";
	$group = "";
	$order = "";
	$countId = "id";
	
	$req=new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId);
	
	printEnTete($requete->label);
	ShowResults($req,true);
		
		echo "<br><br><table align='right'><tr><td>";
		echo "<input  class='bouton' name='ajout' type='submit' value='".$l->g(116)."' onClick='window.location=\"index.php?multi=5&typeDemande=ajout\"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input  class='bouton' name='modif' type='submit' value='".$l->g(115)."' onClick='window.location=\"index.php?multi=5&typeDemande=modif\"'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input  class='bouton' name='suppr' type='submit' value='".$l->g(122)."' onClick='window.location=\"index.php?multi=5&typeDemande=suppr\"'>";
		echo "</td></tr></table><br>&nbsp;";
			break;
	endswitch;

function formulaire($id,$name,$regtree,$regkey,$regvalue)
{	
	global $l;
	$readonly = "";
	$modif    = "";
	$label    = $ajreq;
	$demande  = "enreAjout";

	if($_GET["typeDemande"]== "modif") // c'est une modification
	{
		$label 	  = $name;
		$modif    = 1;
		$readonly = "readonly"; // pour qu'on ne puisse pas modifier le ID
		$demande  = "enreModif";
	}
	
	$tr =  " <tr><td align='right'><font face=Verdana size=-1>";
	//echo   " <center><font face='Arial' color='#330033' size=4><b><center>OCS INVENTORY</center></b></font><hr>";
	printEnTete($l->g(108));
	echo   "<center><br><form name='ajouter_reg' method='POST' action='index.php?multi=5&typeDemande=$demande'>
		 <input type='hidden' name='ID' value='".urldecode($id)."'>
		 <table>
		 <tr><td align='left'><font face=Verdana size=-1>".$l->g(252)." :</font> </td>
		 <td><input size=40 name='NAME' value='".urldecode($name)."'></td></tr>
		 <tr><td align='left'><font face=Verdana size=-1>".$l->g(253)." :</font></td>
		     <td align='left'><select size='1' name='REGTREE' id='REGTREE'>";
	if (urldecode($regtree) == 0)
		echo "               <option value='0' selected>HKEY_CLASSES_ROOT</option>";
	else
		echo "               <option value='0' selected>HKEY_CLASSES_ROOT</option>";
	if (urldecode($regtree) == 1)
		echo "               <option value='1' selected>HKEY_CURRENT_USER</option>";
	else
		echo "               <option value='1'>HKEY_CURRENT_USER</option>";
	if (urldecode($regtree) == 2)
		echo "               <option value='2' selected>HKEY_LOCAL_MACHINE</option>";
	else
		echo "               <option value='2'>HKEY_LOCAL_MACHINE</option>";
	if (urldecode($regtree) == 3)
		echo "               <option value='3' selected>HKEY_USERS</option>";
	else
		echo "               <option value='3'>HKEY_USERS</option>";
	if (urldecode($regtree) == 4)
		echo "               <option value='4' selected>HKEY_CURRENT_CONFIG</option>";
	else
		echo "               <option value='4'>HKEY_CURRENT_CONFIG</option>";
	if (urldecode($regtree) == 5)
		echo "               <option value='5' selected>HKEY_DYN_DATA (Windows 9X only)</option>";
	else
		echo "               <option value='5'>HKEY_DYN_DATA (Windows 9X only)</option>";
	echo	 "			</select></td></tr>
		 <tr><td align='left'><font face=Verdana size=-1>".$l->g(254)." :</font></td>
		 <td><input size=40 name='REGKEY' value='".urldecode($regkey)."'> </td></tr>			 
		 <tr><td align='left'><font face=Verdana size=-1>".$l->g(255)." :</font>  </td>
		 <td><input size= 40 name='REGVALUE' value=".urldecode($regvalue)."></td></tr>
		 </table><br><br>
		 <table><tr><td>
		 <input class='bouton' name='enre'    type='submit' value=".$l->g(13)."> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		 </form>
		 <input class='bouton' name='annuler' type='submit' value=".$l->g(113)." onClick='window.location=\"index.php?multi=5\"'></td></tr>
		 <input type='hidden' size=40 name='ID' value='$id'>
		 </table>";
}
function modifier()
{
	global $l;
	$tr =  "<tr><td align='right'><font face=Verdana size=-1>";

	if ($_GET["id"]) // on a récupéré le ID de la ligne que l'on veut modifier 
	{
		$requete = "select ID,NAME,REGTREE,REGKEY,REGVALUE from regconfig where ID='".$_GET["id"]."';";
		$result  = mysql_query($requete, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$item    = mysql_fetch_object($result);
		formulaire($item->ID,$item->NAME,$item->REGTREE,$item->REGKEY,$item->REGVALUE);
	} 
	else // on demande quelle ligne on veut modifier
	{	
		//echo   "<center><font face='Arial' color='#330033' size=4><b><center>OCS INVENTORY</center></b></font><hr>";
		echo   "<center>".$l->g(256)." :<BR><BR><BR>";
	
		$requete = "SELECT ID,NAME,REGVALUE FROM regconfig";
		$result  = mysql_query($requete, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		echo "<table BORDER='0' WIDTH = '55%' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
		<tr BGCOLOR='#C7D9F5'>";	
		while($colname = mysql_fetch_field($result))
				echo"<td align='center'><B>$colname->name</td></b>";
	
		echo "</tr>";
			while($item = mysql_fetch_object($result))
			{
				echo "<tr><td align='center'><FONT FACE='Verdana' size=2><a href='index.php?multi=5&typeDemande=modif&id=$item->ID'>$item->ID&nbsp;&nbsp;&nbsp;</a></td> 
						  <td align='center'>$item->NAME</td></font></tr>";
			}
			
		echo"</table>";
		echo "<br><br><r><a align='right' href='index.php?multi=5'>".$l->g(113)."</a>";

}// fin else
}// fin function

function enregistrer()
{
	if($_GET["typeDemande"]== "enreModif") // enregistrer une modififcation
	{
		$req = "UPDATE regconfig SET ".	
			"NAME='".$_POST["NAME"]."',".
			"REGTREE='".$_POST["REGTREE"]."',".
			"REGKEY='".$_POST["REGKEY"]."',".
			"REGVALUE='".$_POST["REGVALUE"]."' ".
			"where ID='".$_POST["ID"]."'";

		$result = mysql_query($req, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}	
	else // enregistrer un ajout
	{	
		$req    = "INSERT INTO regconfig VALUES(\"\",\"".$_POST["NAME"]."\",\"".$_POST["REGTREE"]."\",\"".$_POST["REGKEY"]."\",\"".$_POST["REGVALUE"]."\")";
		
		$result = mysql_query($req, $_SESSION["writeServer"]);
	}//fin else
	?>
		<script language="javascript">
			window.location="index.php?multi=5";
		</script>
	<?php
		return;
}//fin function

function supprimer()
{
	global $l;
	
	if ($_GET["id"])
		{ ?>
			<script language="javascript">
				if(confirm ("<?echo trim($l->g(119))?><?=$_GET["id"]?> ?")) 
					window.location="index.php?multi=5&id=<?=$_GET["id"]?>";
				else	
					window.location="index.php?multi=5";
			</script>
			<?php
		}
	else
	{	
		//echo   "<center><font face='Arial' color='#330033' size=4><b><center>OCS INVENTORY</center></b></font><hr>";
		echo   "<center>sélectionner la requête registre que vous voulez supprimer :<BR><BR><BR>";
	
		$requete = "SELECT ID,NAME,REGVALUE FROM regconfig";
		$result  = mysql_query($requete, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		echo "<table BORDER='0' WIDTH = '55%' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
		<tr BGCOLOR='#C7D9F5'>";	
	
		while($colname = mysql_fetch_field($result))
				echo"<td align='center'><B>$colname->name</td></b>";
	
		echo "</tr>";
		while($item = mysql_fetch_object($result))
		{
			echo "<tr><td align='center'><FONT FACE='Verdana' size=2><a href='index.php?multi=5&typeDemande=suppr&id=$item->ID'>$item->ID&nbsp;&nbsp;&nbsp;</a></td> 
					  <td align='center'>$item->NAME</td></font></tr>";
		}
		echo"</table>";
		echo "<br><br><r><a align='right' href='index.php?multi=5'>".$l->g(113)."</a>";
		
	}// fin else	
}// fin supprimer
?>