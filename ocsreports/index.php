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
//Modified on 10/21/2005
error_reporting(E_ALL & ~E_NOTICE);

// First installation checking
if( (!$fconf=@fopen("dbconfig.inc.php","r")) || (!function_exists('session_start')) || (!function_exists('mysql_connect'))) {
	include('install.php');
	die();
}
else
	fclose($fconf);
//
@session_start();
	
include ('header.php');
include ('donnees.php');

if( isset($_GET["pcparpage"]) ) {
	$_SESSION["pcparpage"] = $_GET["pcparpage"];
}
$pcparpage = $_SESSION["pcparpage"];
if( ! $_SESSION["pcparpage"] ) {
	$pcparpage = PC_PAR_PAGE;
	$_SESSION["pcparpage"] = $pcparpage;
}

if(isset($_POST["lareq"])) 
	$_SESSION["lareqpages"] = stripslashes($_POST["lareq"]);
else if(isset($_SESSION["lareqpages"]))
	$_POST["lareq"] = $_SESSION["lareqpages"] ;
	
unset($_SESSION["user"],$_SESSION["add"],$_SESSION["suppr"]);

if(!isset($_SESSION["pageCur"]))
	$_SESSION["pageCur"] = 1;
	
if($_GET["page"])
{
	if($_GET["page"]==-1)
		$_SESSION["pageCur"] -= 1;
	else if($_GET["page"]==-2)
		$_SESSION["pageCur"] += 1;
	else 
		$_SESSION["pageCur"] = $_GET["page"];		
		
	if($_SESSION["pageCur"]<=0)
		$_SESSION["pageCur"]=1;
}
else if($_GET["multi"]!=6)
	$_SESSION["pageCur"] = 1;

if($_GET["supp"] && $_GET["multi"] != 8) {
		deleteDid($_GET["supp"]);
}
if( isset($_GET["cuaff"])) {
		$_POST["lareq"]=$l->g(182);
		$_POST["option0"]=$_GET["cuaff"];
		$_POST["sub"]=true;
		$_SESSION["lareq"]=$_POST["lareq"];
}

if( !isset($_GET["popup"] )) {

echo "<table width='100%' border=0><tr><td><form name='req' method='POST' action='index.php'><b><select name=lareq OnChange='req.submit();'>";	

	$i=0;
	$selectionne=0;
	foreach($requetes as $lareq)
	{							
		$selected=""; 

		if($lareq->label == $l->g(182)) 
			continue;
		$countHl++;
		echo "<option".($countHl%2==1?" class='hi'":"").($_POST["lareq"]==$lareq->label?" selected":"").">".$lareq->label."</option>\n";
	}
	$countHl++;
	echo "<option".($countHl%2==1?" class='hi'":"").($_POST["lareq"]==$l->g(9)?" selected":"").">".$l->g(9)."</option>\n";
	$countHl++;
	if(!isset($_POST["lareq"]) || ( isset( $_GET["multi"] ) && $_GET["multi"]!=1 && $_GET["multi"]!=2&& $_GET["multi"]!=15) || isset($_GET["cuaff"]) || isset($_GET["lang"]) )
		echo "<option".($countHl%2==1?" class='hi'":""). " selected>".$l->g(32)."</option>\n";
	/*if( @stat("composants.php"))
		echo "<option".($_POST["lareq"]==$l->g(371)?" selected":"").">".$l->g(371)."</option>\n";*/

	
echo "</select></b></form></td><td>";	

if(!$_SESSION["first"]||!$_POST["lareq"]) {
	$_POST["lareq"] = $l->g(2);
	$_SESSION["first"] = true;
}

	echo "<table BORDER='0' ALIGN = 'right' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr height=20px  bgcolor='white'>";
	
	if($_SESSION["lvluser"]==SADMIN) {
	
			if( @stat("ipdiscover.php"))
				tab($l->g(174), 3);
 
			if( @stat("dico.php"))
				tab("Dictionnaire",14);
			
			tab($l->g(17) , 8);
			tab($l->g(107), 4);
			tab($l->g(211), 5);

		tab($l->g(225), 9);
		tab($l->g(175), 6);
		tab("Label", 12);
		tab($l->g(235), 10);
		tab($l->g(287), 13);
	}	
}	

	echo "</tr></table>";
	echo "</td></tr></table>";
	
	if(isset($_POST["subPass"])) {
		if(!$_POST["pass1"] || !$_POST["pass2"]) {
			echo "<br><center><font color=red><b>".$l->g(239)."</b></font></center>";
		}
		else if($_POST["pass1"] != $_POST["pass2"]) {
			echo "<br><center><font color=red><b>".$l->g(240)."</b></font></center>";
		}
		else {
			echo "<br><center><font color=red><b>".$l->g(241)."</b></font></center>";
			// DL 25/08/2005
			// When changing password, always use MD5 encrypted password
			mysql_query("UPDATE operators SET passwd='".md5( $_POST["pass1"])."' WHERE ID='".$_SESSION["loggeduser"]."'",$_SESSION["writeServer"]);
		}
	}
	
	if( $_POST["lareq"] == $l->g(9) && !$_GET["multi"])
		$_GET["multi"] = 1;
	
	if($_POST["lareq"] == $l->g(371) && !$_GET["multi"])
		$_GET["multi"] = 15;
	switch($_GET["multi"]) {
		case 1: include ('multicritere.php');	break;
		case 3: include ('ipdiscover.php');	break;
		case 4:	include ('confiGale.php');	break;
		case 5:	include ('reqRegistre.php');	break;
		case 6:	include ('doublons.php');	break;
		case 8:	include ('uploadfile.php');	break;
		case 9:	include ('donAdmini.php');	break;
		case 10:	include ('users.php');	break;
		case 11:	include ('pass.php');	break;
		case 12:	include ('label.php');	break;
		case 13:	include ('local.php');	break;
		case 14:	include ('dico.php');	break;
		case 15: include ('composants.php'); break; 
		default:include ('resultats.php');		
	}

if( !isset($_GET["popup"] ))
	include ('footer.php');
	
function tab( $label, $multi, $lien=null ) {
	
	if( isset($lien))
		$llink = $lien;
	else
		$llink = "?multi=$multi";
		
	echo "<td><a href='$llink'>
	<b>&nbsp;&nbsp;{$label}&nbsp;&nbsp;</b></a></td>";
}

?>