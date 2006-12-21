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
//Modified on $Date: 2006-12-21 18:13:46 $$Author: plemmet $($Revision: 1.9 $)

error_reporting(E_ALL & ~E_NOTICE);
include_once('req.class.php');
@session_start();
// First installation checking
if( (!$fconf=@fopen("dbconfig.inc.php","r")) || (!function_exists('session_start')) || (!function_exists('mysql_connect'))) {
	include('install.php');
	die();
}
else
	fclose($fconf);
//

$showingReq = false;
foreach( $_GET as $key=>$val) {
	$_GET["key"] = urldecode($val);
	if( $key == "lareq" ) {
		$showingReq = true;
	}
}

if( isset($_GET["pcparpage"]) ) {
	$_SESSION["pcparpage"] = $_GET["pcparpage"];
	setcookie( "PcParPage", $_GET["pcparpage"], time() + 3600 * 24 * 15 );
}

include ('header.php');
include ('donnees.php');

$pcparpage = $_SESSION["pcparpage"];

if( ! $_SESSION["pcparpage"] ) {
	if( isset($_COOKIE["PcParPage"]) )
		$pcparpage = $_COOKIE["PcParPage"];
	else	
		$pcparpage = PC_PAR_PAGE;
		
	$_SESSION["pcparpage"] = $pcparpage;	
}

if(isset($_GET["lareq"]))  {
	//unset( $_SESSION["c"] );
	$_SESSION["lareqpages"] = stripslashes($_GET["lareq"]);
}
else if(isset($_SESSION["lareqpages"]))
	$_GET["lareq"] = $_SESSION["lareqpages"] ;
	
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
if( $_GET["suppnet"] ) {
	deleteNet($_GET["suppnet"]);
}

if( isset($_GET["cuaff"])) {
		$_GET["lareq"]=$l->g(182);
		$_POST["option0"]=$_GET["cuaff"];
		$_POST["sub"]=true;
		$_SESSION["lareq"]=$_GET["lareq"];
}

if( !isset($_GET["popup"] )) {

echo "<table width='100%' border=0><tr><td>";
if(! isset($_SESSION["first"])||!$_GET["lareq"]) {
	$_GET["lareq"] = $l->g(2);
	$_SESSION["first"] = true;
}
	$i=0;
	$selectionne=0;
	foreach($requetes as $lareq)
	{							
		$selected=""; 

		if($lareq->label == $l->g(182)) 
			continue;
		$countHl++;
		echo "<a href=index.php?lareq=".urlencode($lareq->label)."><img title=\"".htmlspecialchars($lareq->label)."\" src='image/".$lareq->pics[(!isset($_GET["multi"])&&$_GET["lareq"]==$lareq->label?1:0)]."'></a>";
	}	
	
	$countHl++;
	//multicrit
	echo "<a href=index.php?multi=1><img title=\"".htmlspecialchars($l->g(9))."\" src='image/".($_GET["multi"]==1||(!isset($_GET["multi"])&&$_GET["lareq"]==$l->g(9))?"recherche_a.png":"recherche.png")."'></a>";
	$countHl++;
	/*if(!isset($_GET["lareq"]) || ( isset( $_GET["multi"] ) && $_GET["multi"]!=1 && $_GET["multi"]!=2&& $_GET["multi"]!=15) || isset($_GET["cuaff"]) || isset($_GET["lang"]) )
		echo "<option".($countHl%2==1?" class='hi'":""). " selected>".$l->g(32)."</option>\n";*/
	/*if( @stat("composants.php"))
		echo "<option".(!isset($_GET["multi"])&&$_GET["lareq"]==$l->g(371)?" selected":"").">".$l->g(371)."</option>\n";*/

	
	echo "</td><td>";	

	echo "<table BORDER='0' ALIGN = 'right' CELLPADDING='0' BGCOLOR='#FFFFFF' BORDERCOLOR='#9894B5'>";
	echo "<tr height=20px  bgcolor='white'>";
	
	flush();
	if($_SESSION["lvluser"]==SADMIN) {
?>
<script language='javascript'>
	
	function montre(id) {
		
		var d = document.getElementById(id);
			for (var i = 1; i<=10; i++) {
				if (document.getElementById('smenu'+i)) {document.getElementById('smenu'+i).style.display='none';}
			}
		if (d) {d.style.display='block';}
		}
</script>

	<td onmouseover="javascript:montre('smenu1');">
	<dl id="menu">
		<dt onmouseover="javascript:montre('smenu1');">
		<a href='javascript:void(0);'>
	<img src='image/pack<?php 
	$packAct = array(22,23,24,27,20,21,26);
	if( in_array($_GET["multi"],$packAct) )
		echo "_a";?>.png'></a></dt>
			<dd id="smenu1" onmouseover="javascript:montre('smenu1');" onmouseout="javascript:montre();">
				<ul>
					<li><b><?php echo $l->g(512); ?></b></li>
					
					<li><a href="index.php?multi=20"><?php echo $l->g(513); ?></a></li>
					<li><a href="index.php?multi=21"><?php echo $l->g(514); ?></a></li>
					<li><a href="index.php?multi=26"><?php echo $l->g(515); ?></a></li>
				</ul>
			</dd>
	</dl>
	</td>
<script language='javascript'>montre();</script>	
<?php 
	
			if( @stat("ipdiscover.php"))
				tab($l->g(174), 3);
 
			if( @stat("dico.php"))
				tab($l->g(380),14);
			
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
	flush();
	
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
		
	echo "<center><span id='wait'><h3><font color=red>".$l->g(332)."</font></h3></span></center>";
	flush();
	
	if( ! isset( $_SESSION["mac"] ) ) {
		loadMac();
	}
	
	if( $_GET["multi"] != 3 )
		unset( $_SESSION["forcedRequest"] );
		
	switch($_GET["multi"]) {
		case 1: include ('multicritere.php');	break;
		case 3: include ('ipdiscover.php');	break;
		case 4:	include ('confiGale.php');	break;
		case 5:	include ('reqRegistre.php');	break;
		case 6:	include ('doublons.php');	break;
		case 8:	include ('uploadfile.php');	break;
		case 9:	include ('donAdmini.php');	break;
		case 10: include ('users.php');	break;
		case 11: include ('pass.php');	break;
		case 12: include ('label.php');	break;
		case 13: include ('local.php');	break;
		case 14: include ('dico.php');	break;
		case 15: include ('composants.php'); break;
		case 20: include ('tele_package.php'); break; 
		case 21: include ('tele_activate.php'); break; 
		case 22: include ('opt_frequency.php'); break; 
		case 23: include ('opt_ipdiscover.php'); break; 
		case 24: include ('tele_affect.php'); break; 
		case 25: include ('tele_stats.php'); break;
		case 26: include ('tele_actives.php'); break;
		case 27: include ('opt_suppr.php'); break; 
 		default: include ('resultats.php');		
 	}

if( !isset($_GET["popup"] ))
	include ('footer.php');
	
echo "<script language='javascript'>wait(0);</script>";
	
function tab( $label, $multi, $lien=null ) {
	global $l, $_GET, $showingReq;
	if( isset($lien))
		$llink = $lien;
	else
		$llink = "?multi=$multi";
	
	switch($multi) {
		case 10: $img = "utilisateurs"; break;
		case 2: $img = "codes";	break;
 		case 3: $img = "securite";	break;
 		case 4:	$img = "configuration";	break;
 		case 5:	$img = "regconfig";	break;
 		case 6:	$img = "doublons";	break;
 		case 8:	$img = "agent";	break;
 		case 9:	$img = "administration"; break;
		case 13: $img = "local";	break;		
		case 12: $img = "label";	break;	
		default: if( $label==$l->g(15) )
					$img = "utilisateurs";
				else if( $label==$l->g(16) )
					$img = "codes";
				else
					$img = "dictionnaire";
	}
	if( !$showingReq && ($_GET["multi"] == $multi && $multi != "") ) {
		$img .= "_a";
	}
				
	echo "<td onmouseover=\"javascript:montre();\"><a href='$llink'><img title=\"".htmlspecialchars($label)."\" src='image/$img.png'></a></td>";	
}

?>
