<?php 
error_reporting(E_ALL & ~E_NOTICE);
@session_start();
require_once('require/aide_developpement.php');
require_once('require/function_table_html.php');
require_once('fichierConf.class.php');
//print_r($_SESSION);
include('dbconfig.inc.php');
//echo "toto";
require_once('var.php');
//print_r($_SESSION);
//$_SESSION["SERVER_READ"]=$_SESSION["SERVEUR_SQL"];
//$_SESSION["SERVER_WRITE"]=$_SESSION["SERVER_READ"];

if( ! isset($_SESSION["debug"]) ) {
	$_SESSION["debug"] = 0 ;
}

if( isset( $_GET["cache"] ) ) {
	$_SESSION["usecache"] = $_GET["cache"];
}
else if( ! isset($_SESSION["usecache"]) ) {
	$_SESSION["usecache"] = USE_CACHE ;
}


//GESTION LOGS
if ($_SESSION['LOG_GUI'] == 1){
	define("DB_LOG_NAME", DB_NAME);
//	if ($_SESSION['LOG_DIR'] == "")
//		define("LOG_FILE", $_SERVER["DOCUMENT_ROOT"]."/oscreport/log.csv");
//	else
		define("LOG_FILE", $_SESSION['LOG_DIR']."/log.csv");
	$logHandler = @fopen( LOG_FILE, "a");
}
//END GESTION LOGS

if( ! function_exists ( "utf8_decode" )) {
	function utf8_decode($st) {
		return $st;
	}
}

dbconnect();

if(!isset($_SESSION["rangCookie"])) $_SESSION["rangCookie"] = 0;

function addComputersToGroup( $gName, $ids ) {
	
	$reqIdGroup = "SELECT id FROM hardware WHERE name='$gName'";
	$resIdGroup = mysql_query( $reqIdGroup, $_SESSION["readServer"] );
	$valIdGroup = mysql_fetch_array( $resIdGroup );
	if( lock( $valIdGroup["id"] ) ) {
		$nb_res=0;
		foreach( $ids as $key=>$val ) {
			if( strpos ( $key, "checkmass" ) !== false ) {
				$idsList[] = $val;
				$resDelete = "DELETE FROM groups_cache WHERE hardware_id=$val AND group_id=".$valIdGroup["id"];
				@mysql_query( $resDelete, $_SESSION["writeServer"] );
				
				$reqInsert = "INSERT INTO groups_cache(hardware_id, group_id, static) VALUES ($val, ".$valIdGroup["id"].", 1)";
				$resInsert = mysql_query( $reqInsert, $_SESSION["writeServer"] );
				$nb_res++;
			}
		}
		unlock( $valIdGroup["id"] );
		return $nb_res;
	}
	else
		errlock();
}

/** 
* Group creating function
*/
function createGroup( $name,$description="", $staticOnly=false, $alreadyExists = false ) {
	global $_GET, $l;	
	//Creating hardware
	$deviceid = "_SYSTEMGROUP_";
	
	//does $name group already exists
	$reqGetId = "SELECT id FROM hardware WHERE name='".$name."'";
	$resGetId = mysql_query( $reqGetId, $_SESSION["readServer"]);
	if( $valGetId = mysql_fetch_array( $resGetId ) )
		$groupAlreadyInDb = true;
	else
		$groupAlreadyInDb = false;
		
	if( $alreadyExists && $groupAlreadyInDb ) {		
		$name_id_supp=deleteDid( $valGetId["id"], true, true, true );
		addLog("DELETE",$valGetId["id"].' => '.$name_id_supp);
	}
	else if( $groupAlreadyInDb ) {
		echo "<center><font class='warn'>".$l->g(621)."</font></center>";
		return false;
	}  
	
	if( ! $staticOnly )
		$request = "SELECT DISTINCT h.id " . addslashes( $_SESSION["groupReq"] );
	else
		$request = "";
		
	mysql_query( "INSERT INTO hardware(deviceid,name,description,lastdate) VALUES( '$deviceid' , '".$name."', '".$description."', NOW() )", $_SESSION["writeServer"] ) 
	or die( mysql_error($_SESSION["writeServer"]));
	 
	//Getting hardware id
	$insertId = mysql_insert_id( $_SESSION["writeServer"] );
	
	//Creating group
	mysql_query( "INSERT INTO groups(hardware_id, request, create_time) VALUES ( $insertId, '$request', UNIX_TIMESTAMP() )", $_SESSION["writeServer"] ) 
	or die( mysql_error($_SESSION["writeServer"]) );

	//Generating cache
	if( ! $staticOnly && lock($insertId) ) {	
		$reqCache = "INSERT IGNORE INTO groups_cache(hardware_id, group_id, static) SELECT DISTINCT h.id, $insertId, 0 ".$_SESSION["groupReq"];
		$cachedRes = mysql_query( $reqCache , $_SESSION["writeServer"] )
		or die( mysql_error($_SESSION["writeServer"]) );
		$cached = mysql_affected_rows($_SESSION["writeServer"]);	
		unlock($insertId);
	}
	else if( ! $staticOnly ) {
		return false;
	}

	echo "<br><center>".$l->g(607)." <b>".stripslashes($name)."</b> ".(!$alreadyExists?$l->g(608):$l->g(609))." ".(isset($cached)?$l->g(622).":".$cached:"")."<br>";
	return true;
}

function dbconnect() {
	$db = DB_NAME;
	//echo $db;
	//echo $_SESSION["SERVER_READ"];
	$link=@mysql_connect($_SESSION["SERVER_READ"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	if(!$link) {
		echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error()."</b></font></center>";
		die();
	}
	if( ! mysql_select_db($db,$link)) {
		require('install.php');
		die();
	}
		
	$link2=@mysql_connect($_SESSION["SERVER_WRITE"],$_SESSION["COMPTE_BASE"],$_SESSION["PSWD_BASE"]);
	if(!$link2) {
		echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($link2)."</b></font></center>";
		die();
	}

	if( ! @mysql_select_db($db,$link2)) {
		require('install.php');
		die();
	}
	
	$_SESSION["writeServer"] = $link2;	
	$_SESSION["readServer"] = $link;
	return $link2;
}



function getCount( $req ) {
	$ech = $_SESSION["debug"];
	//IF nor accountinfo and bios are needed, don't join them in count query.
	if( strpos(" ".$req->where , " a.")===FALSE && strpos(" ".$req->where , " b.")===FALSE&&
		strpos(" ".$req->where , " A.")===FALSE && strpos(" ".$req->where , " B.")===FALSE ) {
		$newFrom = str_replace("hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id LEFT JOIN bios b ON b.hardware_id=h.id", "hardware h ",$req->from );
	}
	else
		$newFrom = $req->from;

	$reqCount = "SELECT count(distinct ".$req->countId.") AS cpt FROM ".$newFrom.($req->fromPrelim?",":"").$req->fromPrelim;
	if( $req->where )
		$reqCount .= " WHERE ".$req->where;
		
	if($ech) echo "<br><font color='red'><b>$reqCount</b></font><br><br>";
	$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$valCount = mysql_fetch_array($resCount);
	
	return $valCount["cpt"];
}

function getPrelim(  $req, $limit=NULL ) {
	$ech = $_SESSION["debug"];
	$rac = "LEFT JOIN accountinfo a ON a.hardware_id=h.id";
	$selectReg = "";
	//	$selectFin = $req->getSelect();
	//$fromFin = $req->from;
	$cpt = 1;
	/*if( is_array($_SESSION["currentRegistry"]) )
		foreach( $_SESSION["currentRegistry"] as $regist ) {
			$selectReg .= ", regAff{$cpt}.regvalue AS \"$regist\"";
			$fromReg.= "LEFT JOIN registry regAff{$cpt} ON regAff{$cpt}.hardware_id=h.id";
			if( $cpt > 1 )
				$whereReg .= " AND ";
			$whereReg .= "regAff{$cpt}.name='".$regist;		
			$cpt ++;
		}*/	
	
	$selPrelim = $req->getSelectPrelim();
	$fromPrelim = $req->from;	
	
	$reqPrelim = "SELECT $selPrelim FROM ".$fromPrelim.($req->fromPrelim?",":"").$req->fromPrelim;
	if( $req->where ) $reqPrelim .= " WHERE ".$req->where; 
	if( $req->group ) $reqPrelim .= " GROUP BY ".$req->group;
	
	// bidouille
	if( strstr( $req->order, "ipaddr" ) ) {
		if( strstr( $req->order, "DESC" ) ) {
			$order = "inet_aton(h.ipaddr) DESC";
		}
		else {
			$order = "inet_aton(h.ipaddr) ASC";
		}
	}
	else
		$order = $req->order;
		
	if( $req->order ) $reqPrelim .= " ORDER BY ".$order;
	
	
	if( $limit ) $reqPrelim .= " LIMIT ".$limit;
	
	if($ech) echo "<br><font color='green'><b>$reqPrelim</b></font><br><br>";
	flush();
	return $reqPrelim;
}

function getQuery( $req, $limit ) {
	
	$ech = $_SESSION["debug"];
	$resPrelim = mysql_query( getPrelim( $req, $limit ) , $_SESSION["readServer"]);
	
	$selFin = $req->getSelect();
	$fromFin = $req->from ;	
	
	$toExec = "SELECT ".$selFin." FROM ".$fromFin;
	$prem = true;
	
	while( $valPrelim = mysql_fetch_array($resPrelim) ) {
		if( !$prem) $lesIn .= ",";

		$lesIn .= "'".addslashes($valPrelim[$req->linkId])."'";
		$prem = false;
	}
	
	if( !$prem ) {
		$toExec .= " WHERE ".$req->whereId." IN($lesIn) ";	
		if( $req->selFinal )
			$toExec .= $req->selFinal;
	}
	else
		$toExec .= " WHERE 1=0";
	
	if( $req->group ) $toExec .= " GROUP BY ".$req->group;
	// bidouille
	if( strstr( $req->order, "ipaddr" ) ) {
		if( strstr( $req->order, "DESC" ) ) {
			$order = "inet_aton(h.ipaddr) DESC";
		}
		else {
			$order = "inet_aton(h.ipaddr) ASC";
		}
	}
	else
		$order = $req->order;
		
	if( $req->order ) $toExec .= " ORDER BY ".$order;
	
	if($ech) echo "<br><font color='blue'><b>$toExec</b></font><br><br>";
	flush();
	return $toExec;
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

function addLog( $type, $value="" ) {
	global $logHandler;
	if ($_SESSION['LOG_GUI'] == 1){
		$dte = getDate();
		$date = sprintf("%02d/%02d/%04d %02d:%02d:%02d", $dte["mday"], $dte["mon"], $dte["year"], $dte["hours"], $dte["minutes"], $dte["seconds"]); 
		@fwrite($logHandler, $_SESSION["loggeduser"].";$date;".DB_LOG_NAME.";$type;$value;\n");
	}
}


function getBrowser() {
	$bro = $_SERVER['HTTP_USER_AGENT'];
	if( strpos ( $bro, "MSIE") === false ) {
		return "MOZ";
	}
	return "IE";
}

function getBrowserLang() {
	$bro = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
	if (strpos( $bro,"de") === false) {
           // Not german
           if (strpos( $bro,"es") === false) {
               // Not spanish
               if (strpos( $bro,"fr") === false) {
                   // Not french
                   if (strpos( $bro,"it") === false) {
                       // Not italian
                       if (strpos( $bro,"pt-br") === false) {
                           // Not brazilian portugueuse
                           if (strpos( $bro,"pt") === false) {
                               // Not portugueuse
                               if (strpos( $bro,"pl") === false) {
                                  // Not polish
                                  // Use english default language
	                           return "english";
                               }
                               else
                                  // Polish
                                  return "polish";
                           }
                           else
                               // Portuguese
		                 return "portuguese";
                       }
                       else
                           // Brazilian portuguese
		             return "brazilian_portuguese";
                   }
                   else
                       // Italian
		         return "italian";
               }
               else
                   // French
		     return "french";
           }
           else
               // Spanish
               return "spanish";
       }
       else
           // German
	    return "german";
}



function printNavigation( $lesGets, $numPages) {
				
		$prefG = "<a href=index.php?".stripslashes($lesGets)."&page=";
		echo "<p align='center'>";
		if( $numPages > 1 ) {			
			if( $_SESSION["pageCur"] == 1) {				
				echo "&nbsp;&nbsp;";//voir gris�
				echo "&nbsp;&nbsp;1&nbsp;..";							
			} else {
				echo "&nbsp;&nbsp;{$prefG}-1><img src='image/prec24.png'></a>";
				echo "&nbsp;{$prefG}1>1</a>&nbsp;..";			
			}
			
			if( $_SESSION["pageCur"] && $_SESSION["pageCur"]>1 && $_SESSION["pageCur"]!=$numPages ) {
				echo  "&nbsp;".$_SESSION["pageCur"]."&nbsp;";
			}
			
			if( $_SESSION["pageCur"] >= $numPages) {
				echo "..&nbsp;&nbsp;$numPages&nbsp;";
				//echo "<img src='image/proch24.png'>&nbsp;&nbsp;"; voir gris�
			} else {
				echo "..&nbsp;{$prefG}$numPages>$numPages</a>&nbsp;";
				echo "{$prefG}-2><img src='image/proch24.png'></a>&nbsp;&nbsp;";
			}
		}
		echo "</p><br>";
}

function deleteNet($id) {
	mysql_query("DELETE FROM network_devices WHERE macaddr='$id';", $_SESSION["writeServer"]);
}

/**
  * Deleting function
  * @param id Hardware identifier to be deleted
  * @param checkLock Tells wether or not the locking system must be used (default true)
  * @param traceDel Tells wether or not the deleted entities must be inserted in deleted_equiv for tracking purpose (default true)
  */
function deleteDid($id, $checkLock = true, $traceDel = true, $silent=false
) {
	global $l;
	//If lock is not user OR it is used and available
	if( ! $checkLock || lock($id) ) {	
		$resId = mysql_query("SELECT deviceid,name,IPADDR,OSNAME FROM hardware WHERE id='$id'",$_SESSION["readServer"]) or die(mysql_error());
		$valId = mysql_fetch_array($resId);
		$idHard = $id;
		$did = $valId["deviceid"];
		if( $did ) {
					
			//Deleting a network device
			if( strpos ( $did, "NETWORK_DEVICE-" ) === false ) {
				$resNetm = @mysql_query("SELECT macaddr FROM networks WHERE hardware_id=$idHard", $_SESSION["readServer"]) or die(mysql_error());
				while( $valNetm = mysql_fetch_array($resNetm)) {
					@mysql_query("DELETE FROM netmap WHERE mac='".$valNetm["macaddr"]."';", $_SESSION["writeServer"]) or die(mysql_error());
				}		
			}
			//deleting a regular computer
			if( $did != "_SYSTEMGROUP_" and $did != '_DOWNLOADGROUP_') {
				$tables=Array("accesslog","accountinfo","bios","controllers","drives",
				"inputs","memories","modems","monitors","networks","ports","printers","registry",
				"slots","softwares","sounds","storages","videos","devices","download_history","download_servers");	
			}
			elseif($did == "_SYSTEMGROUP_"){//Deleting a group
				$tables=Array("devices");
				mysql_query("DELETE FROM groups WHERE hardware_id=$idHard", $_SESSION["writeServer"]) or die(mysql_error());
				$resDelete = mysql_query("DELETE FROM groups_cache WHERE group_id=$idHard", $_SESSION["writeServer"]) or die(mysql_error());
				$affectedComputers = mysql_affected_rows( $_SESSION["writeServer"] );
			}
			
			if( !$silent )
				echo "<center><font color=red><b>".$valId["name"]." ".$l->g(220)."</b></font></center>";
			
			foreach ($tables as $table) {
				mysql_query("DELETE FROM $table WHERE hardware_id=$idHard;", $_SESSION["writeServer"]) or die(mysql_error());		
			}
			mysql_query("delete from download_enable where SERVER_ID=".$idHard, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			
			mysql_query("DELETE FROM hardware WHERE id=$idHard;", $_SESSION["writeServer"]) or die(mysql_error());
			//Deleted computers tracking
			if($traceDel && mysql_num_rows(mysql_query("SELECT IVALUE FROM config WHERE IVALUE>0 AND NAME='TRACE_DELETED'", $_SESSION["readServer"]))){
				mysql_query("insert into deleted_equiv(DELETED,EQUIVALENT) values('$did',NULL)", $_SESSION["writeServer"]) or die(mysql_error());
			}
		}
		//Using lock ? Unlock
		if( $checkLock ) 
			unlock($id);
		return $valId["name"];
	}
	else
		errlock();
		
}

/**
  * Hardware locking function. Prevents the hardware to be altered by either the server or another administrator using the GUI
  * @param id Hardware identifier to be locked
  */
function lock($id) {
	//echo "<br><font color='red'><b>LOCK $id</b></font><br>";
	$reqClean = "DELETE FROM locks WHERE unix_timestamp(since)<(unix_timestamp(NOW())-3600)";
	$resClean = mysql_query($reqClean, $_SESSION["writeServer"]) or die(mysql_error());
	
	$reqLock = "INSERT INTO locks(hardware_id) VALUES ('$id')";
	if( $resLock = mysql_query($reqLock, $_SESSION["writeServer"]) or die(mysql_error()))
		return( mysql_affected_rows ( $_SESSION["writeServer"] ) == 1 );
	else return false;
}

/**
  * Hardware unlocking function
  * @param id Hardware identifier to be unlocked
  */
function unlock($id) {
	//echo "<br><font color='green'><b>UNLOCK $id</b></font><br>";
	$reqLock = "DELETE FROM locks WHERE hardware_id='$id'";
	$resLock = mysql_query($reqLock, $_SESSION["writeServer"]) or die(mysql_error());
	return( mysql_affected_rows ( $_SESSION["writeServer"] ) == 1 );
}

/**
  * Show an error message if the locking failed
  */
function errlock() {
	global $l;
	echo "<br><center><font color=red><b>".$l->g(376)."</b></font></center><br>";
}

/**
  * Includes the javascript datetime picker
  */
function incPicker() {

	global $l;
	echo "<script language=\"javascript\">
	var MonthName=[";
	
	for( $mois=527; $mois<538; $mois++ )
		echo "\"".$l->g($mois)."\",";
	echo "\"".$l->g(538)."\"";
	
	echo "];
	var WeekDayName=[";
	
	for( $jour=539; $jour<545; $jour++ )
		echo "\"".$l->g($jour)."\",";
	echo "\"".$l->g(545)."\"";	
	
	echo "];
	</script>	
		<script language=\"javascript\" type=\"text/javascript\" src=\"js/datetimepicker.js\">
	</script>";
}

/**
  * Loads the whole mac file in memory
  */
function loadMac() {
	if( $file=@fopen(MAC_FILE,"r") ) {			
		while (!feof($file)) {				 
			$line  = fgets($file, 4096);
			if( preg_match("/^((?:[a-fA-F0-9]{2}-){2}[a-fA-F0-9]{2})\s+\(.+\)\s+(.+)\s*$/", $line, $result ) ) {
				$_SESSION["mac"][strtoupper(str_replace("-",":",$result[1]))] = $result[2];
			}				
		}
		fclose($file);			
	}
}

/**
  * Gets the manufacturer of a given network card
  * @param mac A mac adress
  * @return The manufacturer of the given mac
  */	
function getConstructor( $mac ) {	
	$beg = strtoupper(substr( $mac, 0, 8 ));
	return ( ucwords(strtolower( $_SESSION["mac"][ $beg ])) );
}

/**
  * Decodes all the text from utf8
  * @param txt Text to be decoded
  * @return Text decoded from UTF8 according to UTF8_DEGREE
  */



function getGluedIds( $reqSid ) {
	$idNotIn = getIds($reqSid);
	$idNotIn = @array_unique( $idNotIn );
	$gluedId = @implode( "','", $idNotIn );
	
	return $gluedId;
}

function getIds($reqSid) {
	$resSid = mysql_query( $reqSid, $_SESSION["readServer"] );
	while( $valSid = mysql_fetch_array($resSid) ) {
		$idNotIn[] = $valSid["hardware_id"];
	}
	return $idNotIn;
}
?>