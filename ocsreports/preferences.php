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
//Modified on 12/13/2005

error_reporting(E_ALL & ~E_NOTICE);
@session_start();

if( ! function_exists ( "utf8_decode" )) {
	function utf8_decode($st) {
		return $st;
	}
}
require('dbconfig.inc.php');
include("fichierConf.class.php");

if(isset($_GET["lang"])) {
	$_SESSION["langueFich"] = "languages/".$_GET["lang"].".txt";
	unset($_SESSION["availFieldList"], $_SESSION["currentFieldList"]);
}

define("GUI_VER", "4012");
define("SADMIN", 1);
define("LADMIN", 2);   
define("ADMIN", 3);
define("PC_PAR_PAGE", 15); // default computer / page value
define("TAG_NAME", "TAG"); // do NOT change
define("LOCAL_SERVER", $_SESSION["SERVEUR_SQL"]); // adress of the server handler used for local import
$_SESSION["SERVER_READ"] = $_SESSION["SERVEUR_SQL"];
$_SESSION["SERVER_WRITE"] = $_SESSION["SERVEUR_SQL"];

// DB NAME 
define("DB_NAME", "ocsweb");
//////////

define("TAG_LBL", "Tag");
define("DEFAULT_LANGUAGE", "" );

$l = new FichierConf(DEFAULT_LANGUAGE?DEFAULT_LANGUAGE:getBrowserLang());
dbconnect();

// choix des colonnes
if(!isset($_SESSION["availFieldList"])) {
	$_SESSION["availFieldList"] = array(
	"h.lastdate"=>$l->g(46), 	"h.name"=>$l->g(23), 
	"h.userid"=>$l->g(24), 	"h.osname"=>$l->g(25), "h.memory"=>"Ram(MO)", "h.processors"=>"CPU(MHz)",
	"h.workgroup"=>$l->g(33), "h.osversion"=>$l->g(275), "h.oscomments"=>$l->g(286), "h.processort"=>$l->g(350), "h.processorn"=>$l->g(351),
	"h.swap"=>"Swap", "lastcome"=>$l->g(352), "h.quality"=>$l->g(353), "h.fidelity"=>$l->g(354),"h.description"=>$l->g(53), 
	"h.wincompany"=>$l->g(355), "h.winowner"=>$l->g(356), "h.useragent"=>$l->g(357), "b.smanufacturer"=>$l->g(64),
	"b.bmanufacturer"=>$l->g(284),"b.ssn"=>$l->g(36),"b.smodel"=>$l->g(65),"b.bversion"=>$l->g(209),"h.ipaddr"=>$l->g(34));
	
	$reqCol = "SELECT * FROM accountinfo LIMIT 0,1";
	$resCol = mysql_query($reqCol, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($colname=mysql_fetch_field($resCol)) {
		if($colname->name != TAG_NAME && $colname->name != "DEVICEID")
			$_SESSION["availFieldList"] = array_merge($_SESSION["availFieldList"],array("a.".$colname->name=>$colname->name));
	}
}
	
if(!isset($_SESSION["currentFieldList"])) {
	$_SESSION["currentFieldList"] = array_slice($_SESSION["availFieldList"], 0,5);
	array_multisort($_SESSION["availFieldList"]);
}

if(isset($_GET["suppCol"])) {
	$_SESSION["currentFieldList"] = array_flip($_SESSION["currentFieldList"]);
	
	// supprimer la colonne dans la requete en session
	$_SESSION["availFieldList"] = array_flip($_SESSION["availFieldList"]);	
	$fNam =  stripslashes(urldecode($_GET["suppCol"]));
	$fVal = $_SESSION["availFieldList"][$fNam]; 		
	$_SESSION["availFieldList"] = array_flip($_SESSION["availFieldList"]);		
	$_SESSION["query"] = str_replace(", ".$fVal." AS \"".$fNam."\"" , "", $_SESSION["query"] );	

	$_SESSION["currentFieldList"] = array_flip($_SESSION["currentFieldList"]);
	unset($_SESSION["currentFieldList"][ $fVal ]);	

	if( $_GET["c"]>sizeof($_SESSION["currentFieldList"])+2 ) {
		$_GET["c"] = sizeof($_SESSION["currentFieldList"])+2;
		if( $_GET["c"] == 0 ) $_GET["c"]++;
	}
}

if(isset($_GET["newcol"])) {
	$_SESSION["availFieldList"] = array_flip($_SESSION["availFieldList"]);
	$fNam =  stripslashes(urldecode($_GET["newcol"]));	
	$fVal = $_SESSION["availFieldList"][$fNam];
	if( !isset( $_SESSION["currentFieldList"][$fVal])) {		
		$_SESSION["currentFieldList"] = array_merge($_SESSION["currentFieldList"], array( $fVal=>$fNam));
		// ajouter la colonne dans la requete en session (si elle n'y est pas)		
		$_SESSION["query"] = str_replace("FROM hardware h", ", ".$fVal." AS \"".$fNam."\" FROM hardware h" , $_SESSION["query"] );
	}	
	$_SESSION["availFieldList"] = array_flip($_SESSION["availFieldList"]);		
}
	
$selectH = "h.deviceid";
foreach($_SESSION["currentFieldList"] as $nomField=>$valField) {
	$selectH .= ", ".$nomField." AS \"".$valField."\"";
}
// fin choix des colonnes

$boutOver="onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\"";

function dbconnect() {
	$db = DB_NAME;
	
	$link=@mysql_connect($_SESSION["SERVER_READ"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	if(!$link) {
		echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error()."</b></font></center>";
		die();
	}
	if( ! @mysql_select_db($db,$link)) {
		include('install.php');
		die();
	}
		
	$link2=@mysql_connect($_SESSION["SERVER_WRITE"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	if(!$link2) {
		echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($link2)."</b></font></center>";
		die();
	}

	if( ! @mysql_select_db($db,$link2)) {
		include('install.php');
		die();
	}
	
	$_SESSION["writeServer"] = $link2;	
	$_SESSION["readServer"] = $link;
	return $link2;
}

function ShowResults($req,$sortable=true,$modeCu=false,$modeRedon=false,$deletableP=true,$registrable=false)
{
		global $l;				
		$deletable = ($_SESSION["lvluser"]==SADMIN) && $_GET["multi"]!=2 && $deletableP;
		

		global $pcparpage;
		
		if(!$req->sql) return 0;	

		$columneditable = $req->columnEdit;		
		
		$ind=0;
		$var="option".$ind;		


		while(isset($_POST[$var]))
		{					
			if($req->isNumber[$ind]) 	
				$_POST[$var]=0+$_POST[$var];// si un nombre est attendu, on transforme en nombre
			
			$req->sql=str_replace("option$ind",$_POST[$var],$req->sql); 
			$req->sqlCount=str_replace("option$ind",$_POST[$var],$req->sqlCount); 
			// on remplace les strings "optionX" de la requete par leurs valeurs présentes dans les variables en POST
			$ind++;
			$var="option".$ind;			
		}			
			
		if(    isset($_SESSION["query"])   && (  (isset($_GET["c"])&&$_GET["c"]) || $_GET["av"] == 1 || $_GET["suppCol"] == 1 || isset($_GET["newCol"]))   ) 
		{
			$suffixe = $_GET["a"] ? " ASC" : " DESC";			
			if($_SESSION["c"]==$_GET["c"])
			{
				if( $_GET["rev"] == 1 ) {
					$_GET["a"]= $_GET["a"] ? 0 : 1 ;
				}
				$suffixe= $_GET["a"] ? " ASC" : " DESC";
			}
			else
				$_GET["a"]= isset($_GET["a"])? $_GET["a"]: 0;			
			
			$resR = mysql_query($_SESSION["queryC"], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			$resD = mysql_fetch_array($resR);
						
			$pcParPage = $modeCu ? $resD[0] : $pcparpage ;
			$pcParPage = $pcParPage>0 ? $pcParPage : PC_PAR_PAGE;
			$numPages = ceil($resD[0]/$pcParPage);	

			if( $numPages == 0 )
				$numPages++;
			
			if( $_SESSION["pageCur"] > $numPages ){
				$_SESSION["pageCur"] = $numPages ;
			}
			
			$beg = ($_SESSION["pageCur"]-1) * $pcParPage;				
		
			if ( $_GET["c"] ) {
				
				$quer = substr ( $_SESSION["query"], 0 ,  strpos($_SESSION["query"]," ORDER BY") );
				$_SESSION["query"] = $quer ? $quer : $_SESSION["query"] ;
				$toExec = $_SESSION["query"]." ORDER BY ".$_GET["c"].$suffixe." LIMIT {$beg},".$pcParPage;			
			}
			else {
				$toExec = $_SESSION["query"]." LIMIT {$beg},".$pcParPage;			
			}
		}
		else
		{
			$resR = mysql_query($req->sqlCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			$resD = mysql_fetch_array($resR);
			$pcParPage = $modeCu ? $resD[0] : $pcparpage ;	
			$pcParPage = $pcParPage>0 ? $pcParPage : PC_PAR_PAGE;			
			$numPages = ceil($resD[0]/$pcParPage);	

			if( $numPages <= 0 )
				$numPages++;
			
			if( $_SESSION["pageCur"] > $numPages || ! $_SESSION["pageCur"]){
				$_SESSION["pageCur"] = $numPages ;
			}

			if( $columneditable && !strpos($req->sql, " ORDER BY"))
				$orderDefault = isset($_SESSION["currentFieldList"]["h.lastdate"]) ? "ORDER BY h.lastdate DESC" : "ORDER BY 1 ASC";
			
			$beg = ($_SESSION["pageCur"]-1) * $pcParPage;
			$toExec = $req->sql. " $orderDefault LIMIT {$beg},".$pcParPage;			
		}
		//echo($toExec);
		$result = mysql_query( $toExec, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));	

		//les GET a rajouter
		$pref="";
		foreach ($_GET as $gk=>$gv) {
			if($gk=="page" || $gk=="rev" || $gk == "logout" || $gk=="c"|| $gk=="direct"|| $gk=="supp" || $gk=="a"|| $gk=="suppCol" || $gk=="newcol") continue;
			$pref .= "&{$gk}=".urlencode($gv);
		}
		
		//les GET globaux a rajouter
		$prefG="";
		$hiddens ="";
		foreach ($_GET as $gk=>$gv) {
			if( $gk=="rev"|| $gk=="suppCol" || $gk == "logout" || $gk=="newcol") continue;
			
			if( $gk =="page" && ($gv==-1 || $gv==-2)) {
				$gv = $_SESSION["pageCur"];
			}
			
			$prefG .= "&{$gk}=".urlencode($gv);
			$hiddens .= "<input type='hidden' name='$gk' value='$gv'>\n";
		}		
		
		if( !$modeCu && $resD[0] > 0) {
			echo "<br><center><table width='60%'><tr><td align='center'><b>".$resD[0]." ".$l->g(90)."</b>";		
					
			echo "<br>&nbsp;&nbsp;<a href=ipcsv.php target=_blank>(".$l->g(183).")</a></td>";
				
			$machNmb = array(5,10,15,20,50,100);
			
			echo "<td align='center'><form name='pcp' method='GET' action='index.php'>$hiddens".$l->g(340).
			":&nbsp;<select name='pcparpage' OnChange='pcp.submit();'>";
			
			foreach( $machNmb as $nbm ) {
				$countHl++;
				echo "<option".($countHl%2==1?" class='hi'":"").($_SESSION["pcparpage"] == $nbm ? " selected" : "").">$nbm</option>";
			}
			
			echo "</select></form></font></td>";
			
			if( $columneditable) {
				echo "<td align='center'><form name='addCol' method='GET' action='index.php'>";
				echo $hiddens;							
				echo "<select name='newcol' OnChange='addCol.submit();'>";			
				echo "<option>".$l->g(349)."</option>";
				
				foreach( $_SESSION["availFieldList"] as $nomField=>$valField ) {
					if( ! in_array($valField,$_SESSION["currentFieldList"])	) {
						$countHl++;
						echo "<option".($countHl%2==1?" class='hi'":"").">$valField</option>";						
					}
				}
				echo "</select></form></td>";
			}
			
			echo "</tr></table></center><br>";
		}
		
		if($modeRedon) {
			echo "<form id='idredon' name='redon' action='index.php?multi=6&c=".$_GET["c"]."&a=".$_GET["a"]."' method='POST'>
				<p align='center'><input name='subredon' value='".$l->g(177)."' type='submit'></p>";
		}
				
		$cpt=1;
		printNavigation( $prefG, $numPages);	
		echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
		<tr>";		
		if($modeRedon)
			echo "<td>&nbsp;</td>";
		
		if(!isset($_GET["c"]))
		{
			$_SESSION["query"]=$req->sql;
			$_SESSION["queryC"]=$req->sqlCount;
			$_SESSION["lareq"]=$req->label;				
		}
		else
			$_SESSION["c"]=$_GET["c"];		
			
		if($deletable)
		{?>
			<script language=javascript>
				function confirme(did)
				{
					if(confirm("<?echo $l->g(119)?>"+did+" ?"))
						window.location="index.php?<?=$pref?>&c=<?=(isset($_SESSION["c"])?$_GET["c"]:3)?>&a=<?=(isset($_GET["a"])?$_GET["a"]:0); ?>&page=<?=$_GET["page"]?>&supp="+did;
				}
			</script>
		<?
		flush();
		}
				
		$did=0;		
		
		while($colname=mysql_fetch_field($result)) // On récupère le nom de toutes les colonnes		
		{
			if($colname->name!="deviceid")
			{							
				$a = ( isset($_GET["a"]) ? $_GET["a"] : 0 ) ;
				$isDate[$colname->name] = ($colname->type == "date" ? 1 : 0);
				$isDateTime[$colname->name] =($colname->type == "datetime" || $colname->type == "timestamp" ? 1 : 0);

				if($sortable)
				{	
					echo "<td><CENTER><B>
					<a href=index.php?$pref&c=$cpt&a=$a&rev=1&page=1>$colname->name</a>";
					
					if( $columneditable && in_array( $colname->name , $_SESSION["currentFieldList"]))						
						echo "<a href=index.php?page=1&$prefG&suppCol=".urlencode($colname->name).">&nbsp;<img src=image/supp.png></a>";
					echo "</CENTER></td>"; // Affichage en tete colonne*/
					
				}
				else
				   echo "<td><CENTER><B>$colname->name</CENTER></td>"; // Affichage en tete colonne
				
				$tabChamps[$cpt-$did]=$colname->name;
			}
			else $did=1;			
			$cpt++;
	
		}
		
		if($deletable||$modeRedon)
		{
			echo "<td>&nbsp;</td>";
		}
		echo "</tr>";
		$x=-1; $nb=0;
		$uneMachine=false;
		while($item = mysql_fetch_object($result)) // Parcour de toutes les lignes résultat
		{	
			echo "<TR height=20px bgcolor='". ($x == 1 ? "#FFFFFF" : "#F2F2F2") ."'>";	// on alterne les couleurs de ligne
			$x = ($x == 1 ? 0 : 1) ;	
			$nb++;
			if($modeRedon) {
				echo "<td align=center><input type=checkbox name='ch$nb' value='".urlencode($item->deviceid)."'></td>";
			}			
			foreach($tabChamps as $chmp) // Affichage de toutes les valeurs résultats
			{
				echo "<td align='center'>";								
				if($chmp==TAG_LBL)
				{
					$leCuPrec=$item->$chmp;					
				}
				else if($chmp==$l->g(23)&&isset($item->deviceid))
				{					
					echo "<a href=\"machine.php?sessid=".session_id()."&systemid=".urlencode($item->deviceid)."\" target=\"_new\" onmouseout=\"this.style.color = 'blue';\" onmouseover=\"this.style.color = '#ff0000';\">";
					$uneMachine=true;
				}
				else if($chmp==$l->g(28))
				{
					echo "<a href=?cuaff=$leCuPrec>";
				}
				
				if( $isDate[$chmp] )
					echo dateFromMysql($item->$chmp)."</span></a></font></td>\n";
				else if( $isDateTime[$chmp] )
					echo dateTimeFromMysql($item->$chmp)."</span></a></td>\n";				

				else if(!$toutAffiche)
					echo $item->$chmp."</span></a></font></td>\n";
				
			}
			
			if( $deletable && isset($item->deviceid) )
			{
				echo "<td align=center><a href='#' OnClick='confirme(\"$item->deviceid\");'><img src=image/supp.png></a></td>";
			}
			if( $registrable &&  isset($item->mac) )
				echo "<td align=center><a href=index.php?multi=3&mode=8&mac=".$item->mac."><img src='image/Gest_admin1.png'></a></td>";
			echo "</tr>";
		}	
		
		echo"</td></tr></table>";
		if($modeRedon) {
			echo "<input name='maxredon' type='hidden' value='$nb'></form>";
		}
		
		if($x==-1)
		{
			 echo "<div align=center><b>".$l->g(42)."</b></div>";
		}			
		
		echo"<table width='100%' border='0'>";
		echo"<tr><td align='center'>";
		echo "</table>";
		
		printNavigation( $prefG, $numPages);	

		return $nb;
}

function printEnTete($ent) {
	echo "<br><table border=1 class= \"Fenetre\" WIDTH = '62%' ALIGN = 'Center' CELLPADDING='5'>
	<th height=40px class=\"Fenetre\" colspan=2><b>".$ent."</b></th></table>";
}

function dateOnClick($input, $checkOnClick=false) {
	global $l;
	$dateForm = $l->g(269) == "%m/%d/%Y" ? "MMDDYYYY" : "DDMMYYYY" ;
	if( $checkOnClick ) $cOn = ",'$checkOnClick'";
	$ret = "OnClick=\"javascript:NewCal('$input','$dateForm',false,24{$cOn});\"";
	return $ret;
}

function datePick($input, $checkOnClick=false) {
	global $l;
	$dateForm = $l->g(269) == "%m/%d/%Y" ? "MMDDYYYY" : "DDMMYYYY" ;
	if( $checkOnClick ) $cOn = ",'$checkOnClick'";
	$ret = "<a href=\"javascript:NewCal('$input','$dateForm',false,24{$cOn});\">";
	$ret .= "<img src=\"image/cal.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Pick a date\"></a>";
	return $ret;
}

function dateFromMysql($v) {
	global $l;
	
	if( $l->g(269) == "%m/%d/%Y" )
		$ret = sprintf("%02d/%02d/%04d", $v[5].$v[6], $v[8].$v[9], $v);
	else	
		$ret = sprintf("%02d/%02d/%04d", $v[8].$v[9], $v[5].$v[6], $v);
	return $ret;
}

function dateTimeFromMysql($v) {
	global $l;
	
	if( $l->g(269) == "%m/%d/%Y" )
		$ret = sprintf("%02d/%02d/%04d %02d:%02d:%02d", $v[5].$v[6], $v[8].$v[9], $v, $v[11].$v[12],$v[14].$v[15],$v[17].$v[18]);
	else	
		$ret = sprintf("%02d/%02d/%04d %02d:%02d:%02d", $v[8].$v[9], $v[5].$v[6], $v, $v[11].$v[12],$v[14].$v[15],$v[17].$v[18]);
	return $ret;
}

function dateToMysql($date_cible) {

	global $l;
	if(!isset($date_cible)) return "";
	
	$dateAr = explode("/", $date_cible);
	
	if( $l->g(269) == "%m/%d/%Y" ) {
		$jour  = $dateAr[1];
		$mois  = $dateAr[0];
	}
	else {
		$jour  = $dateAr[0];
		$mois  = $dateAr[1];
	}

	$annee = $dateAr[2];
	return sprintf("%04d-%02d-%02d", $annee, $mois, $jour);	
}

function getBrowser() {
	$bro = $_SERVER['HTTP_USER_AGENT'];
	if( strpos ( $bro, "MSIE") === false ) {
		return "MOZ";
	}
	return "IE";
}

function getBrowserLang() {
	$bro = $_SERVER['HTTP_USER_AGENT'];
	if( strpos ( $bro, "; fr-") > 0 ) {
		return "french";
	}
	else if( strpos ( $bro, "; es-") > 0 ) {
		return "spanish";
	}
	else if( strpos ( $bro, "; pt-") > 0 ) {
		return "brazilian_portuguese";
	}
	return "english";
}

function printNavigation( $lesGets, $numPages) {
				
		$prefG = "<a href=index.php?".$lesGets."&page=";
		echo "<center>";
		if( $numPages > 1 ) {			
			if( $_SESSION["pageCur"] == 1) {				
				echo "&nbsp;&nbsp;<<&nbsp;&nbsp;";
				echo "&nbsp;&nbsp;1&nbsp;..";							
			} else {
				echo "&nbsp;&nbsp;{$prefG}-1><<</a>&nbsp;&nbsp;";
				echo "&nbsp;&nbsp;{$prefG}1>1</a>&nbsp;..";			
			}
			
			if( $_SESSION["pageCur"] && $_SESSION["pageCur"]>1 && $_SESSION["pageCur"]!=$numPages ) {
				echo  "&nbsp;".$_SESSION["pageCur"]."&nbsp;";
			}
			
			if( $_SESSION["pageCur"] >= $numPages) {
				echo "..&nbsp;&nbsp;$numPages&nbsp;";
				echo "&nbsp;&nbsp;>>&nbsp;&nbsp;";
			} else {
				echo "..&nbsp;{$prefG}$numPages>$numPages</a>&nbsp;";
				echo "&nbsp;&nbsp;{$prefG}-2>>></a>&nbsp;&nbsp;";
			}
		}
		echo "</center><br>";
}

function deleteDid($did, $checkLock = true) {
	global $l;
	
	if( ! $checkLock || lock($did) ) {
		if( strpos ( $did, "NETWORK_DEVICE-" ) === false ) {
			$resNetm = @mysql_query("SELECT macaddr FROM networks WHERE deviceid='$did'", $_SESSION["writeServer"]);
			while( $valNetm = mysql_fetch_array($resNetm)) {
				@mysql_query("DELETE FROM netmap WHERE mac='".$valNetm["macaddr"]."';");
			}		
		}
		
		$tables=Array("accesslog","accountinfo","bios","controllers","drives","hardware",
		"inputs","memories","modems","monitors","networks","ports","printers","registry",
		"slots","softwares","sounds","storages","videos","devices");	
		
		echo "<center><font color=red><b>$did ".$l->g(220)."</b></font></center>";
		
		foreach ($tables as $table) {
			mysql_query("DELETE FROM $table WHERE deviceid='$did';", $_SESSION["writeServer"]);		
		}
		if( $checkLock ) 
			unlock($did);
	}
	else
		errlock();
}

function lock($did) {
	//echo "<br><font color='red'><b>LOCK $did</b></font><br>";
	$reqClean = "DELETE FROM locks WHERE unix_timestamp(since)<(unix_timestamp(NOW())-60)";
	$resClean = mysql_query($reqClean, $_SESSION["writeServer"]);
	
	$reqLock = "INSERT INTO locks(deviceid) VALUES ('$did')";
	if( $resLock = mysql_query($reqLock, $_SESSION["writeServer"]))
		return( mysql_affected_rows ( $_SESSION["writeServer"] ) == 1 );
	else return false;
}

function unlock($did) {
	//echo "<br><font color='green'><b>UNLOCK $did</b></font><br>";
	$reqLock = "DELETE FROM locks WHERE deviceid='$did'";
	$resLock = mysql_query($reqLock, $_SESSION["writeServer"]);
	return( mysql_affected_rows ( $_SESSION["writeServer"] ) == 1 );
}

function errlock() {
	echo "<br><center><font color=red><b>".$l->g(371)."</b></font></center><br>";
}
?>
