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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.12 $)
require_once('require/function_table_html.php');
require_once('require/function_server.php');

//TODO: A REPRENDRE COMPLETEMENT => NE MARCHE PLUS

if (isset($_POST['RULE_AFFECT'])){
	//recupération des id des machines a affecter le paquet
	
	
	$lareq = getPrelim( $_SESSION["saveRequest"] );
	$res_toute_machines = mysql_query( $lareq, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));

	while( $val_toute_machines = mysql_fetch_array($res_toute_machines)) {
			$ID_HARDWARE[$val_toute_machines['h.id']]=$val_toute_machines['h.id'];
	}
	
	//recuperation des conditions de la règle
	$sql="select PRIORITY,CFIELD,OP,COMPTO,SERV_VALUE from download_affect_rules where rule=".$_POST['RULE_AFFECT']." order by PRIORITY";
	$res_rules = mysql_query( $sql, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
	
	while( $val_rules = mysql_fetch_array($res_rules)) {
	$cfield[$val_rules['PRIORITY']]=$val_rules['CFIELD'];
	$op[$val_rules['PRIORITY']]=$val_rules['OP'];
	$compto[$val_rules['PRIORITY']]=$val_rules['COMPTO'];
	$serv_value[$val_rules['PRIORITY']]=$val_rules['SERV_VALUE'];
	}
	
	$i=1;
	$nb_exist=0;
	foreach ($cfield as $key=>$value)
	{
		$i++;
		//$result=insert_with_rules($ID_HARDWARE,$cfield[$key],$op[$key],$compto[$key],$serv_value[$key]);
		$result=insert_with_rules_opt($ID_HARDWARE,$cfield[$key],$op[$key],$compto[$key],$serv_value[$key]);
		$m=0;
		while ($result['exist'][$m]){
			$exist[]=$result['exist'][$m];
			$m++;
		}
		$nb_exist += $result['nb_exist'];
	
		if ($result['not_match'] == "")
		break;
		else
		$ID_HARDWARE=$result['not_match'];

	}

	if (isset($result['not_match']))
	{
		tab_list_error($result['not_match'],$result['nb_not_match']." ".$l->g(658));
		$error='YES';
	}
	
	if (isset($exist))
	{
			tab_list_error($exist,$nb_exist." ".$l->g(659)." ".$l->g(482));
			//$error='YES';
	}
	if (!isset($error))
	echo "<script> alert('".$l->g(558)."');</script>";
		
		
}


if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
	
if( isset( $_GET["isgroup"] ) )
	$_SESSION["isgroup"] = $_GET["isgroup"];
	
if( isset($_GET["frompref"]) && $_GET["frompref"] == 1 ) {
	unset( $_SESSION["saveId"] );
}
else if( isset($_GET["systemid"]) ) {
	$_SESSION["saveId"] = $_GET["systemid"];
}

if( ! isset($_SESSION["saveRequest"])) {
	$_SESSION["saveRequest"] = $_SESSION["storedRequest"];
}

if( isset($_GET["affpack"])) {
	$ok = resetPack( $_GET["affpack"] );
	$ok = $ok && setPack( $_GET["affpack"] );
}

if( $_GET["retour"] == 1 || (isset($_GET["affpack"]) && $ok) ) {
	$_SESSION["storedRequest"] = $_SESSION["saveRequest"];
	unset( $_SESSION["saveRequest"] );
	if( ! isset( $_SESSION["saveId"] ) )
		echo "<script language='javascript'>window.location='index.php?redo=1".$_SESSION["queryString"]."';</script>";
		//TODO MARCHE PÄS
	else if( isset( $_SESSION["isgroup"] ) && $_SESSION["isgroup"]== "1" )
		echo "<script language='javascript'>window.location='index.php?multi=29&popup=1&systemid=".$_SESSION["saveId"]."&option=".$l->g(500)."';</script>";
	else
		echo "<script language='javascript'>window.location='machine.php?systemid=".$_SESSION["saveId"]."&option=".$l->g(500)."';</script>";
	die();
}

$nbMach = 0;
if( isset($_GET["systemid"]))
	$nbMach = 1;
else if( isset( $_POST["maxcheck"] ) ) {
	foreach( $_POST as $key=>$val ) {
		if( strpos ( $key, "checkmass" ) !== false ) {
			$tbd[] = $val;
			$nbMach++;
		}		
	}	
}

if( empty( $tbd ) and !isset($_GET["systemid"]))
	$nbMach = getCount($_SESSION["saveRequest"]);

if( $nbMach > 0 ) {
	$canAc = 1;
	$strHead = $l->g(477);
	if( ! isset($_SESSION["isgroup"]) || $_SESSION["isgroup"] == 0 ) 
		$strHead .= " <font class='warn'>( $nbMach ".$l->g(478).")</font>";
	PrintEnTete( $strHead );
}
else {
	die($l->g(478));	
}

echo "<br><center><a href='#' OnClick=\"window.location='index.php?multi=24&retour=1'\"><= ".$l->g(188)."</a></center>";
if (isset($_GET['systemid'])){
	$visu='block';	
}else
{
	$visu='none';
if ($_GET['affect']=="mach"){
$selected="selected";
$visu='block';
}
echo "<table align='center'><tr><td>".$l->g(696)." <select id='action_affect' name='action_affect'>
              <option value='' onclick='document.getElementById(\"groups\").style.display=\"none\"; document.getElementById(\"server\").style.display=\"none\";'></option>
              <option value='1' onclick='document.getElementById(\"groups\").style.display=\"block\"; document.getElementById(\"server\").style.display=\"none\";' ".$selected.">".$l->g(697)."</option>
              <option value='2' onclick='document.getElementById(\"groups\").style.display=\"none\";document.getElementById(\"server\").style.display=\"block\";'>".$l->g(698)."</option>
     </select></td></tr></table>";
}
//echo "<table><tr><td>choix:</td><td><input type='text' name='".$input_name."' value=\"".textDecode($name)."\" onFocus=\"this.style.backgroundColor='white'\" onBlur=\"this.style.backgroundColor='#C7D9F5'\">";
if( isset($_GET["systemid"]))
	$canAc = 3; //preferences.php must set systemid in query string
$lbl = "pack";
$sql = "";
$whereId = "e.ID";
$linkId = "e.ID";
$select = array("ID"=>$l->g(460), "e.FILEID"=>$l->g(475), "NAME"=>$l->g(49),
"PRIORITY"=>$l->g(440),"INFO_LOC"=>$l->g(470), "PACK_LOC"=>$l->g(471),
"FRAGMENTS"=>$l->g(480), "SIZE"=>$l->g(462), "OSNAME"=>$l->g(25));
$selectPrelim = array("e.ID"=>"e.ID");
$from = "download_enable e RIGHT JOIN download_available d ON d.fileid = e.fileid and e.SERVER_ID is null";
$fromPrelim = "";
$group = "";
$order = "e.FILEID DESC";
$countId = "e.ID";

$requete = new Req($lbl,$whereId,$linkId,$sql,$select,$selectPrelim,$from,$fromPrelim,$group,$order,$countId,true);
echo "<div id='groups' style='display:".$visu."'>";
ShowResults($requete,true,false,false,false,false,false,$canAc);
echo "</div>";
//javascript for confirmation delete
?>
	<script language=javascript>
		function confirme(did,lbl,orig){
			if(confirm("<?php $l->g(699) ?>"))
				window.location=document.location.href+"&acc="+did;
		}

		function open_popup(pag,name) {
       window.open(pag,name,"menubar=no, status=no, scrollbars=no, menubar=no, width=500, height=200, modal=yes'");
 		  }


	</script>
<?php

$sql="select hardware.NAME as GROUP_SERVER,
			count(d.FILEID) as 'NOMBRE',
			d.FILEID as TIMESTAMP ,
			d.NAME as NOM,
			PRIORITY as PRIORITE,
			FRAGMENTS,
			SIZE as TAILLE,
			d.OSNAME as SYSTEME,
			e.GROUP_ID
			from hardware RIGHT join
			download_enable e
			RIGHT JOIN download_available d
			ON d.fileid = e.fileid
			ON hardware.ID=e.GROUP_ID
			where e.SERVER_ID is not null and hardware.NAME is not null
			group by d.FILEID,d.NAME,
			PRIORITY,
			FRAGMENTS,
			SIZE,
			d.OSNAME,
			e.GROUP_ID,
			hardware.NAME
union
select '<font color=red><b>".$l->g(660)."</b></font>' as GROUP_SERVER,
			count(d.FILEID) as 'NOMBRE',
			d.FILEID as TIMESTAMP ,
			d.NAME as NOM,
			PRIORITY as PRIORITE,
			FRAGMENTS,
			SIZE as TAILLE,
			d.OSNAME as SYSTEME,
			e.GROUP_ID
			from hardware RIGHT join
			download_enable e
			RIGHT JOIN download_available d
			ON d.fileid = e.fileid
			ON hardware.ID=e.GROUP_ID
			where e.SERVER_ID is not null and hardware.NAME is null
			group by d.FILEID,d.NAME,
			PRIORITY,
			FRAGMENTS,
			SIZE,
			d.OSNAME,
			e.GROUP_ID,
			hardware.NAME";
$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$i=0;
while($colname = mysql_fetch_field($result))
		$entete[$i++]=$colname->name;
		$entete[$i++]=$l->g(433);

unset($entete[$i-2]);
unset($entete[1]);
$i=0;
while($item = mysql_fetch_object($result)){
	$data[$i]["GROUP_SERVER"]=$item ->GROUP_SERVER;
	//$data[$i]["NOMBRE"]=$item ->NOMBRE;
	$data[$i]["TIMESTAMP"]=$item ->TIMESTAMP;
	$data[$i]["NOM"]=$item ->NOM;
	$data[$i]["PRIORITE"]=$item ->PRIORITE;
	$data[$i]["FRAGMENTS"]=$item ->FRAGMENTS;
	$data[$i]["TAILLE"]=$item ->TAILLE;
	$data[$i]["SYSTEME"]=$item ->SYSTEME;
	if (substr($item ->GROUP_SERVER, 0, 5) !=  "<font")
	$data[$i]["AFFECTER"]="<a href=# OnClick='confirme(\"".$item ->GROUP_ID."\",\"\",\"\");'><img src=image/Gest_admin1.png></a>";
	else
	$data[$i]["AFFECTER"]=" ";
	$data[$i]["AFFECTER"]="<a href=# OnClick='open_popup(\"popup_rules_redistribution.php?lvluser=".$_SESSION["lvluser"]."&GROUP_ID=".$item ->GROUP_ID."&paq_name=".$item ->NOM."&timestamp=".$item ->TIMESTAMP."\",\"CECI EST UNE POPUP\")'><img src=image/Gest_admin1.png></a>";
	$i++;
}
echo "<div id='server' style='display:none'>";
tab_entete_fixe($entete,$data,"","90","300");
echo "</div>";
echo "<form name='TELE_AFFECT_RULE' method='POST'>";
echo "<input type='hidden' name='RULE_AFFECT' value=''>";
echo "<input type='hidden' name='GROUP_ID' value=''>";
echo "<input type='hidden' name='TIMESTAMP' value=''>";
echo "</form>";

function setPack( $packid ) {
		global $_GET;

		if( isset($_GET["systemid"])) {
			$val["h.id"] = $_GET["systemid"];
			if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'DOWNLOAD', $packid )", $_SESSION["writeServer"] )) {
				echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
				return false;
			}
			addLog("TELEDEPLOIEMENT", "Affectation simple ".$packid." sur ".$_GET["systemid"] );
			$_SESSION["justAdded"] = true;
		}
		else if( isset( $_GET["compAffect1"] ) ) {		
			foreach( $_GET as $key=>$val ) {
				if( strpos ( $key, "compAffect" ) !== false ) {
					if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val."', 'DOWNLOAD', $packid)", $_SESSION["writeServer"] )) {
						echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
						return false;
					}
				}
			}
			addLog("TELEDEPLOIEMENT", "Affectation simple ".$packid." sur ".$_GET["systemid"] );
		}
		else {
			$lareq = getPrelim( $_SESSION["saveRequest"] );
			if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
				return false;
			while( $val = @mysql_fetch_array($res)) {
				if( ! @mysql_query( "INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'DOWNLOAD', $packid)", $_SESSION["writeServer"] )) {
					echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
					return false;
				}
			}
			addLog("TELEDEPLOIEMENT", "Affectation de masse ".$packid." sur ".$lareq );
		}
		return true;	
	}
	
	function resetPack( $packid ) {
		global $_GET;
		if( isset($_GET["systemid"])) {
			$val["h.id"] = $_GET["systemid"];
			if( ! @mysql_query( "DELETE FROM devices WHERE name='DOWNLOAD' AND IVALUE=$packid AND hardware_id='".$val["h.id"]."'", $_SESSION["writeServer"] )) {
				echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
				return false;
			}
		}
		else if( isset( $_GET["compAffect1"] ) ) {		
			foreach( $_GET as $key=>$val ) {
				if( strpos ( $key, "compAffect" ) !== false ) {
					if( ! @mysql_query( "DELETE FROM devices WHERE name='DOWNLOAD' AND IVALUE=$packid AND hardware_id='".$val."'", $_SESSION["writeServer"] )) {
						echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
						return false;
					}
				}
			}
			addLog("TELEDEPLOIEMENT", "DESaffectation simple ".$packid." sur ".$_GET["systemid"] );
		}
		else {
			$lareq = getPrelim( $_SESSION["saveRequest"] );
			if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
				return false;
			while( $val = @mysql_fetch_array($res)) {
			
				if( ! @mysql_query( "DELETE FROM devices WHERE name='DOWNLOAD' AND IVALUE=$packid AND hardware_id='".$val["h.id"]."'", $_SESSION["writeServer"] )) {
					echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
					return false;
				}
			}
			addLog("TELEDEPLOIEMENT", "DESaffectation de masse ".$packid." sur ".$lareq );

		}

		return true;		
		// comprends pas: echo "DELETE FROM devices WHERE name='FREQUENCY' AND hardware_id IN ($lareq)";flush();		
	}
?>