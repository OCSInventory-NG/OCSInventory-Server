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
//Modified on $Date: 2007/07/23 10:30:25 $$Author: plemmet $($Revision: 1.2 $)


require_once('require/function_opt_param.php');

require_once("preferences.php");
if (isset($_GET['systemid'])) {
	$systemid = $_GET['systemid'];
	if ($systemid == "")
	{
		echo "Please Supply A System ID";
		die();
	}
}
elseif (isset($_POST['systemid'])) {
	$systemid = $_POST['systemid'];
}

if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  ){
	$sql_verif="select workgroup from hardware where workgroup='GROUP_4_ALL' and ID='".$systemid."'";
	$res_verif = mysql_query($sql_verif, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item_verif = mysql_fetch_object($res_verif);
	if ($item_verif == "")
	die("FORBIDDEN");
}


if (isset($_GET['state']))
{
	$state = $_GET['state'];
	if ($state == "MAJ")
		echo "<script language='javascript'>window.location.reload();</script>\n";		
}// fin if

if( isset( $_GET["suppack"] ) ) {
	if( $_SESSION["justAdded"] == false )
		@mysql_query("DELETE FROM devices WHERE ivalue=".$_GET["suppack"]." AND hardware_id='$systemid' AND name='DOWNLOAD'", $_SESSION["writeServer"]);
	else $_SESSION["justAdded"] = false;
}
else 
	$_SESSION["justAdded"] = false;


//update values if user want modify groups' values
if ($_POST['Valid_modif_x'] and !isset($_POST['modif']))
{
	if (trim($_POST['NAME'])!= '' and trim($_POST['DESCR'])!=''){
		$req = "UPDATE hardware SET ".	
			"NAME='".$_POST['NAME']."',".
			"DESCRIPTION='".$_POST['DESCR']."' ".
			"where ID='".$systemid."' and (deviceid = '_SYSTEMGROUP_' or deviceid ='_DOWNLOADGROUP_')";
		$result = mysql_query($req, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));		
	}
	else{
		
		echo "<script>alert('".$l->g(627)."')</script>";
	}
}
$queryMachine   = "SELECT REQUEST,
						  CREATE_TIME,
						  NAME,
						  XMLDEF,
						  DESCRIPTION,LASTDATE,OSCOMMENTS,DEVICEID FROM hardware h left join groups g on g.hardware_id=h.id 
				  WHERE ID=$systemid AND (deviceid ='_SYSTEMGROUP_' or deviceid='_DOWNLOADGROUP_')";
$result   = mysql_query( $queryMachine, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
$item     = mysql_fetch_object($result);

if( ! $item ) {
	echo "<script language='javascript'>wait(0);</script>";
	echo "<center><font class='warn'>".$l->g(623)."</font></center>";
	flush();
	die();
}

if( $item->REQUEST !="" or $item->XMLDEF != "")
	$pureStat = false;
else{
	$pureStat = true;
}

if ( $item->CREATE_TIME == "")
	$server_group=true;
else
	$server_group=false;
incPicker();
$tdhdpb = "<td  align='left' width='20%'>";
$tdhfpb = "</td>";
$tdhd = "<td  align='left' width='20%'><b>";
$tdhf = ":</b></td>";
$tdpopup = "<td align='left' width='20%' onclick=\"javascript: OuvrirPopup('group_chang_value.php', '', 'resizable=no, location=no, width=400, height=200, menubar=no, status=no, scrollbars=no, menubar=no')\">";

//if user clic on modify
if($_POST['MODIF_x']){
	//don't show the botton modify
	$img_modif="";
	//list of input we can modify
	$name=show_modif($item->NAME,'NAME',0);
	$description=show_modif($item->DESCRIPTION,'DESCR',1);
	//show new bottons
	$button_valid="<input title='".$l->g(625)."' type='image'  src='image/modif_valid_v2.png' name='Valid_modif'>";
	$button_reset="<input title='".$l->g(626)."' type='image'  src='image/modif_anul_v2.png' name='Reset_modif'>";
	
}
else{ //only show the botton for modify
	$img_modif="<input title='".$l->g(115)."' type='image' src='image/modif.png' name='MODIF'>";
	$name=$item->NAME;
	$description=$item->DESCRIPTION;
	$button_valid="";
	$button_reset="";
}
//form for modify values of group's
echo "<form name='CHANGE' action='' method='POST'>";
echo "<br><br><table align='center' width='65%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>".$tdhd.$l->g(577).$tdhf.$tdhdpb.$name.$tdhfpb;
echo $tdhd.$l->g(593).$tdhf.$tdhdpb.dateTimeFromMysql($item->LASTDATE).$tdhfpb;
if( ! $pureStat  )
	echo "</tr><tr>".$tdhd.$l->g(594).$tdhf.$tdhdpb.date("F j, Y, g:i a",$item->CREATE_TIME ).$tdhfpb;
echo "</tr><tr><td>&nbsp;</td></tr>";
echo $tdhd.$l->g(615).$tdhf."<td  align='left' width='20%' colspan='3'>";
if( ! $pureStat  ){
	echo $item->REQUEST;

	//affichage des requetes qui ont form� ce groupe
	if ($item->XMLDEF != ""){
		$tab_list_sql=regeneration_sql($item->XMLDEF);
		$i=1;
		while ($tab_list_sql[$i]){
			echo $i.") => ".$tab_list_sql[$i]."<br>";
			$i++;
		}
	}
}else {
	echo $l->g(595);
}

echo "</tr><tr>".$tdhd.$l->g(53).$tdhf.$tdhdpb.$description.$tdhfpb;


if ($_SESSION["lvluser"]!=ADMIN)
echo "<tr><td align='left' colspan=4>".$button_valid."&nbsp&nbsp".$button_reset."&nbsp&nbsp".$img_modif."</td></tr>";
echo "$tdhfpb</table>";
echo "</form>";
$td1	  = "<td height=20px id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>";
	$td2      = "<td height=20px bgcolor='white' align='center'>";
	$td3      = $td2;
	$td4      = "<td height=20px bgcolor='#F0F0F0' align='center'>";
//*/// END COMPUTER SUMMARY
if ($server_group){
	$sql_affect_pack="select da.NAME, da.PRIORITY,da.FRAGMENTS,da.SIZE,da.OSNAME,de.INFO_LOC,de.CERT_FILE,de.CERT_PATH,de.PACK_LOC
			from download_enable de,download_available da 
			where de.GROUP_ID =$systemid 
			and da.FILEID=de.FILEID
			group by de.fileid;";
	$res_affect_pack = mysql_query($sql_affect_pack, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
	$i=0;
	while( $val_affect_pack = mysql_fetch_array($res_affect_pack)) {
		$PACK_LIST[$i]['NAME'] = $val_affect_pack['NAME'];
		$PACK_LIST[$i]['PRIORITY'] = $val_affect_pack['PRIORITY'];
		$PACK_LIST[$i]['FRAGMENTS'] = $val_affect_pack['FRAGMENTS'];
		$PACK_LIST[$i]['SIZE'] = $val_affect_pack['SIZE'];
		$PACK_LIST[$i]['OSNAME'] = $val_affect_pack['OSNAME'];
		$PACK_LIST[$i]['INFO_LOC'] = $val_affect_pack['INFO_LOC'];
		$PACK_LIST[$i]['CERT_FILE'] = $val_affect_pack['CERT_FILE'];
		$PACK_LIST[$i]['CERT_PATH'] = $val_affect_pack['CERT_PATH'];
		$PACK_LIST[$i]['PACK_LOC'] = $val_affect_pack['PACK_LOC'];	
		$i++;	
	}

	if (isset($PACK_LIST)){
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr><td height=20px colspan=10 align='center'>".$l->g(481)."</td></tr>";
	echo "<tr><td></td>";
	foreach ($PACK_LIST[0] as $key=>$value){
		echo $td2."<i><b>".$key."</b></i></td>";
	}
	echo "</tr>";
	$i=0;
	while ($PACK_LIST[$i]){
		echo "<tr>";
		echo "<td bgcolor='white' align='center' valign='center'><img width='15px' src='image/red.png'></td>";
		$ii++; $td3 = $ii%2==0?$td2:$td4;
			foreach ($PACK_LIST[$i] as $key=>$value){
				echo $td3.$value."</td>";							
			}
			echo "</tr>";
		$i++;
			//print_r($valDeploy);
		}
	echo "</table><br>";
	}
	require("server_redistrib.php");
}else{
	
	
	
//	if( isset($_GET["action"]) || isset($_POST["action_form"]) ) {
//		require("ajout_maj.php");
//		die();
//	}
	
	if( ! isset($_GET["option"]) ) {
		$opt = $l->g(500);
	}
	else {
		$opt = stripslashes(urldecode($_GET["option"]));
	}
	
	
	$lblAdm = Array($l->g(500));
	$imgAdm = Array("spec");
	$lblHdw = Array($l->g(580), $l->g(581));
	$imgHdw = Array("ttmachinesred", "ttmachines",);
	
	echo "<br><br>";
	
	echo "<table width='20%' border=0 align='center' cellpadding='0' cellspacing='0'>
			<tr>";
	echo img($imgAdm[0],$lblAdm[0], 1, $opt);
	
	if( ! $pureStat )
		echo img($imgHdw[0],$lblHdw[0], 1, $opt);
		
	echo img($imgHdw[1],$lblHdw[1], 1, $opt);
	echo "</tr></table>";
	
	echo"<br><br><br>";
	
	switch ($opt) :
		case $l->g(500): print_perso($systemid);
							break;
		case $l->g(581):
				  print_computers_cached($systemid);
							break;
		case $l->g(580):
				  print_computers_real($systemid);
							break;
		default : print_perso($systemid);
							break;
		endswitch;		
}			
echo "<script language='javascript'>wait(0);</script>";
flush();
echo "<br></body>";
echo "</html>";
function regeneration_sql($valGroup){
	
	$tab=xml_decode($valGroup);
	$cherche = array( "<xmldef>","</REQUEST>","</xmldef>");
	$replace = array("","","");
	$tab= str_replace($cherche, $replace, $tab);	
//	echo "<br>".$tab."<br>";
	$tab_list_sql=explode("<REQUEST>",trim($tab));
	unset($tab_list_sql[0]);
	return($tab_list_sql);
	
}


function print_computers_real($systemid) {

	global $l;
	
	//groupe nouvelle version
	$sql_group="SELECT xmldef FROM groups WHERE hardware_id='$systemid'";
	$resGroup = mysql_query( $sql_group, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
	$valGroup = mysql_fetch_array($resGroup);//groupe d'ancienne version
	if( ! $valGroup["xmldef"] ){
		$sql_group="SELECT request FROM groups WHERE hardware_id='$systemid'";
		$resGroup = mysql_query( $sql_group, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
		$valGroup = mysql_fetch_array($resGroup);
		$request=$valGroup["request"];
	}else{
		$tab_list_sql=regeneration_sql($valGroup["xmldef"]);
		$i=1;
		//print_r($tab_list_sql);
		$tab_id= array();
		while ($tab_list_sql[$i]){
			if ($tab_id != array()){
				if (strtolower(substr($tab_list_sql[$i],0,19)) == "select distinct id ")
				$tab_list_sql[$i] .= " and id in (".implode(",",$tab_id).")";
				else
				$tab_list_sql[$i] .= " and hardware_id in (".implode(",",$tab_id).")";
				unset($tab_id);
			}
//		echo $tab_list_sql[$i];
			$result_value = mysql_query(xml_decode($tab_list_sql[$i]), $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			while($value=mysql_fetch_array($result_value)) {
				$tab_id[] = $value["HARDWARE_ID"];
			}	
			$i++;
		}
	}
	$form_name="calcul_computor_groupcache";
	$table_name=$form_name;
	echo "<font color=red><b>".$l->g(927)."</b></font>";
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array(TAG_LBL => 'a.TAG',
					   'ID MACHINE' => 'h.ID',
					   'DEVICEID' => 'h.DEVICEID',
					   'NAME' => 'h.NAME',
					   'OS'=> 'h.OSNAME',
					   'VERSION OS' => 'h.OSVERSION',
					   'COMMENTAIRE OS' => 'h.OSCOMMENTS',
					   'TYPE PROC' => 'h.PROCESSORT',
					   'VITESSE PROC'=>'h.PROCESSORS',
					   'NBRE PROC'=>'h.PROCESSORN',
					   'RAM'=>'h.MEMORY',					   
					   'SWAP' => 'h.SWAP',
					   'DERNIER INV' => 'h.LASTDATE',
					   'DERNIER CONTACT' => 'h.LASTCOME',
					   'QUALITY'=> 'h.QUALITY',
					   'FIDELITY' => 'h.FIDELITY',
					   'DESCRIPTION' => 'h.DESCRIPTION',
					   '@ IP'=>'h.IPADDR');
	$list_col_cant_del=array('NAME'=>'NAME');
	$default_fields= array('NAME'=>'NAME',TAG_LBL=>TAG_LBL,'DERNIER INV'=>'DERNIER INV','DERNIER CONTACT'=>'DERNIER CONTACT','@ IP'=>'@ IP');
	$queryDetails  = "SELECT ";
	foreach ($list_fields as $lbl=>$value){
			$queryDetails .= $value.",";		
	}
	$queryDetails  = substr($queryDetails,0,-1)." FROM  hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id
						where h.id in (".implode(",",$tab_id).") ";
	if(isset($mesmachines) and $mesmachines != '')
	$queryDetails  .= $mesmachines;
	$tab_options['FILTRE']=array('h.NAME'=>'Nom');
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,90,$tab_options);
	//echo "<br><input type=submit name=GENERATE value='APPLIQUER EN CACHE'>"; 
	echo "</form>";
}

function print_computers_cached($systemid) {

	global $l,$server_group;
	//print_r($_POST);
	//traitement des machines du groupe
	if( isset($_POST["actshowgroup"])) {
		foreach( $_POST as $key=>$val ) {//check65422
			if( substr($key,0,5) == "check") {
				//echo substr($key,5);
				$resDelete = "DELETE FROM groups_cache WHERE hardware_id=".substr($key,5)." AND group_id=".$systemid;
				//echo $resDelete;
				@mysql_query( $resDelete, $_SESSION["writeServer"] );
				if( $_POST["actshowgroup"] != 0 ) {
					$reqInsert = "INSERT INTO groups_cache(hardware_id, group_id, static) VALUES (".substr($key,5).", ".$systemid.", ".$_POST["actshowgroup"].")";
					$resInsert = mysql_query( $reqInsert, $_SESSION["writeServer"] );
				}
			}
		}	
		$tab_options['CACHE']='RESET';
	}
	if ($_SESSION["lvluser"]==ADMIN){
		$sql_mesMachines="select hardware_id from accountinfo a where ".$_SESSION["mesmachines"];
		$res_mesMachines = mysql_query($sql_mesMachines, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$mesmachines="(";
		while ($item_mesMachines = mysql_fetch_object($res_mesMachines)){
			$mesmachines.= $item_mesMachines->hardware_id.",";	
		}
		$mesmachines="and e.hardware_id IN ".substr($mesmachines,0,-1).")";
			
	}

	$form_name="list_computor_groupcache";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array(TAG_LBL => 'a.TAG',
					   'ID MACHINE' => 'h.ID',
					   'DEVICEID' => 'h.DEVICEID',
					   'NAME' => 'h.NAME',
					   'OS'=> 'h.OSNAME',
					   'VERSION OS' => 'h.OSVERSION',
					   'COMMENTAIRE OS' => 'h.OSCOMMENTS',
					   'TYPE PROC' => 'h.PROCESSORT',
					   'VITESSE PROC'=>'h.PROCESSORS',
					   'NBRE PROC'=>'h.PROCESSORN',
					   'RAM'=>'h.MEMORY',					   
					   'SWAP' => 'h.SWAP',
					   'DERNIER INV' => 'h.LASTDATE',
					   'DERNIER CONTACT' => 'h.LASTCOME',
					   'QUALITY'=> 'h.QUALITY',
					   'FIDELITY' => 'h.FIDELITY',
					   'DESCRIPTION' => 'h.DESCRIPTION',
					   '@ IP'=>'h.IPADDR',
					   'CHECK'=>'h.ID');
	$list_col_cant_del=array('NAME'=>'NAME','CHECK'=>'CHECK');
	$default_fields= array('NAME'=>'NAME',TAG_LBL=>TAG_LBL,'DERNIER INV'=>'DERNIER INV','DERNIER CONTACT'=>'DERNIER CONTACT','@ IP'=>'@ IP');
	$queryDetails  = "SELECT ";
	foreach ($list_fields as $lbl=>$value){
			$queryDetails .= $value.",";		
	}
	$queryDetails  = substr($queryDetails,0,-1)." FROM  hardware h LEFT JOIN accountinfo a ON a.hardware_id=h.id
						,groups_cache e
						where group_id='".$systemid."' and h.id=e.HARDWARE_ID ";
	if(isset($mesmachines) and $mesmachines != '')
	$queryDetails  .= $mesmachines;
	$tab_options['FILTRE']=array('h.NAME'=>'Nom');
	$statut=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	if ($statut){
		$reqGrpStat = "SELECT REQUEST,XMLDEF FROM groups WHERE hardware_id=".$systemid;
		$resGrpStat = @mysql_query($reqGrpStat, $_SESSION["readServer"]);
		$valGrpStat = @mysql_fetch_array($resGrpStat);
		echo "<center>".$l->g(585).": <select name='actshowgroup' id='actshowgroup'>";
						if (($valGrpStat['REQUEST'] == "" or $valGrpStat['REQUEST'] == null) and ($valGrpStat['XMLDEF'] == "" or $valGrpStat['XMLDEF'] == null))
						echo "<option value='0'>".$l->g(818)."</option></select>";
						else
						echo "<option value='0'>".$l->g(590)."</option><option value='1'>".$l->g(591)."</option><option value='2'>".$l->g(592)."</option></select>";
						echo "<input type='submit'></center>";
	}
	echo "</form>";
}

function print_perso($systemid) {
	global $l, $td1, $td2, $td3, $td4,$pages_refs;
	$i=0;
	$queryDetails = "SELECT * FROM devices WHERE hardware_id=$systemid";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
					
		echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	
	//echo "<tr><td>&nbsp;&nbsp;</td> $td1 "."Libell�"." </td> $td1 "."Valeur"." </td><td>&nbsp;</td></tr>";		
	while($item=mysql_fetch_array($resultDetails,MYSQL_ASSOC)) {
		$optPerso[ $item["NAME"] ][ "IVALUE" ] = $item["IVALUE"];
		$optPerso[ $item["NAME"] ][ "TVALUE" ] = $item["TVALUE"];
	}	
	
	$ii++; $td3 = $ii%2==0?$td2:$td4;
	//IPDISCOVER
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["IPDISCOVER"])&&$optPerso["IPDISCOVER"]["IVALUE"]!=1?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>&nbsp;</td>";
	echo $td3.$l->g(489)."</td>";	
	if( isset( $optPerso["IPDISCOVER"] )) {		
		if( $optPerso["IPDISCOVER"]["IVALUE"]==0 ) echo $td3.$l->g(490)."</td>";	
		else if( $optPerso["IPDISCOVER"]["IVALUE"]==2 ) echo $td3.$l->g(491)." ".$optPerso["IPDISCOVER"]["TVALUE"]."</td>";
		else if( $optPerso["IPDISCOVER"]["IVALUE"]==1 ) echo $td3.$l->g(492)." ".$optPerso["IPDISCOVER"]["TVALUE"]."</td>";
	}
	else {
		echo $td3.$l->g(493)."</td>";
	}
	if( $_SESSION["lvluser"]==SADMIN ){
	echo "<form name='modif_param' id='modif_param' method='POST' action='index.php?".PAG_INDEX."=".$pages_refs['opt_param']."'>";
	echo "<td align=center rowspan=8><a OnClick='recharge(\"$systemid\",\"group\")'><img src='image/modif_a.png' title='".$l->g(285)."'></a></td>";
	echo "</tr>";
	echo "<input type='hidden' id='systemid' name='systemid' value=''>";
	echo "<input type='hidden' id='origine' name='origine' value=''>"; 
	echo "</form>";
	}
	
	$ii++; $td3 = $ii%2==0?$td2:$td4;
	$sql_default_value="select NAME,IVALUE from config where NAME	in ('DOWNLOAD',
															'DOWNLOAD_CYCLE_LATENCY',
															'DOWNLOAD_PERIOD_LENGTH',
															'DOWNLOAD_FRAG_LATENCY',
															'DOWNLOAD_PERIOD_LATENCY',	
															'DOWNLOAD_TIMEOUT',
															'PROLOG_FREQ')";
	$result_default_value = mysql_query($sql_default_value, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($default=mysql_fetch_array($result_default_value)) {
		$optdefault[$default["NAME"] ] = $default["IVALUE"];
	}	
	//FREQUENCY
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["FREQUENCY"])?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>";
	echo $td3.$l->g(494)."</td>";
	if( isset( $optPerso["FREQUENCY"] )) {
		if( $optPerso["FREQUENCY"]["IVALUE"]==0 ) echo $td3.$l->g(485)."</td>";
		else if( $optPerso["FREQUENCY"]["IVALUE"]==-1 ) echo $td3.$l->g(486)."</td>";
		else echo $td3.$l->g(495)." ".$optPerso["FREQUENCY"]["IVALUE"]." ".$l->g(496)."</td>";
	}
	else {
		echo $td3.$l->g(497)."</td>";
	}
	
	echo "</tr>";
	
	//DOWNLOAD_SWITCH
	echo "<tr><td bgcolor='white' align='center' valign='center'>".(isset($optPerso["DOWNLOAD_SWITCH"])?"<img width='15px' src='image/red.png'>":"&nbsp;")."</td>";
	echo $td3.$l->g(417)." <font color=green size=1><i>DOWNLOAD</i></font> </td>";
	if( isset( $optPerso["DOWNLOAD_SWITCH"] )) {
		if( $optPerso["DOWNLOAD_SWITCH"]["IVALUE"]==0 ) echo $td3.$l->g(733)."</td>";
		else if( $optPerso["DOWNLOAD_SWITCH"]["IVALUE"]==1 ) echo $td3.$l->g(205)."</td>";
		else echo $td3."</td>";
	}
	else {
		echo $td3.$l->g(488)."(";
		if ($optdefault["DOWNLOAD"] == 1) echo $l->g(205); else echo $l->g(733);
		echo ")</td>";
	}
		
	echo "</tr>";
	
	//DOWNLOAD_CYCLE_LATENCY
	optperso("DOWNLOAD_CYCLE_LATENCY",$l->g(720)." <font color=green size=1><i>DOWNLOAD_CYCLE_LATENCY</i></font>",$optPerso,1,$optdefault["DOWNLOAD_CYCLE_LATENCY"],$l->g(511));
	
	//DOWNLOAD_FRAG_LATENCY
	optperso("DOWNLOAD_FRAG_LATENCY",$l->g(721)." <font color=green size=1><i>DOWNLOAD_FRAG_LATENCY</i></font>",$optPerso,1,$optdefault["DOWNLOAD_FRAG_LATENCY"],$l->g(511));

	
	//DOWNLOAD_PERIOD_LATENCY
	optperso("DOWNLOAD_PERIOD_LATENCY",$l->g(722)." <font color=green size=1><i>DOWNLOAD_PERIOD_LATENCY</i></font>",$optPerso,1,$optdefault["DOWNLOAD_PERIOD_LATENCY"],$l->g(511));
	
	//DOWNLOAD_PERIOD_LENGTH
	optperso("DOWNLOAD_PERIOD_LENGTH",$l->g(723)." <font color=green size=1><i>DOWNLOAD_PERIOD_LENGTH</i></font>",$optPerso,1,$optdefault["DOWNLOAD_PERIOD_LENGTH"]);

	//PROLOG_FREQ
	optperso("PROLOG_FREQ",$l->g(724)." <font color=green size=1><i>PROLOG_FREQ</i></font>",$optPerso,1,$optdefault["PROLOG_FREQ"],$l->g(730));

	//TELEDEPLOY
	$resDeploy = @mysql_query("SELECT a.name, d.tvalue,d.ivalue, e.pack_loc,e.fileid  FROM devices d, download_enable e LEFT JOIN download_available a 
	ON e.fileid=a.fileid WHERE d.name='DOWNLOAD' AND e.id=d.ivalue AND d.hardware_id=$systemid"); 
	if( mysql_num_rows( $resDeploy )>0 ) {
			
		while( $valDeploy = mysql_fetch_array( $resDeploy ) ) {
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			echo "<tr>";
			echo "<td bgcolor='white' align='center' valign='center'><img width='15px' src='image/red.png'></td>";
			echo $td3.$l->g(498)." <b>".$valDeploy["name"]."</b>";
			if (isset($valDeploy["fileid"]))
			echo "(<small>".$valDeploy["fileid"]."</small>)";	
			echo "</td>".$td3.$l->g(499).": ".$valDeploy["pack_loc"]."</td>";//$l->g(81)."cac: ".($valDeploy["tvalue"]!=""?$valDeploy["tvalue"]:$l->g(482))."</td>";
			if( $_SESSION["lvluser"]==SADMIN )	
				echo "$td3 <a href='index.php?".PAG_INDEX."=".$_GET[PAG_INDEX]."&popup=1&suppack=".$valDeploy["ivalue"]."&systemid=".
				urlencode($systemid)."&option=".urlencode($l->g(500))."'>".$l->g(122)."</a></td>";
			show_stat($valDeploy["fileid"]);
			echo "</tr>";
			//print_r($valDeploy);
		}
	}
	if( $_SESSION["lvluser"]==SADMIN ){
	echo "<tr>
		<td colspan='10' align='right'>
		<a href=# Onclick=window.open(\"multi_lot.php?img=image/tele_search.png&idchecked=".$systemid."&origine=group\",\"rollo\",\"location=0,status=0,scrollbars=1,menubar=0,resizable=0,width=800,height=500\");>".$l->g(501)."
		</a>
		</td></tr>";
	}
	echo "</table><br>";
}

function print_item_header($text)
{
	echo "<br><br><table align=\"center\"  width='100%'  cellpadding='4'>";
	echo "<tr>";
	echo "<td align='center' width='100%'><b><font color='blue'>".strtoupper($text)."</font></b></td>";
	echo "</tr>";
	echo "</table><br>";	
}

function img($i,$a,$avail,$opt) {
	global $systemid;

	if( $opt == $a ) {
		$suff = "_a";
	}
	
	if( $avail ) {
		$href = "<a href='index.php?".PAG_INDEX."=".$_GET[PAG_INDEX]."&popup=1&systemid=".urlencode($systemid)."&option=".urlencode($a)."'>";
		$fhref = "</a>";
		$img = "<img title=\"".htmlspecialchars($a)."\" src='image/{$i}{$suff}.png' />";
	}
	else {
		$href = "";
		$fhref = "";
		$img = "<img title=\"".htmlspecialchars($a)."\" src='image/{$i}_d.png' />";
	}
	
	return "<td width='80px' align='center'>".$href.$img.$fhref."</td>";

}

function show_stat($fileId){
	global $td3;
	
	echo $td3."<a href=\"tele_stats.php?stat=".$fileId."&group=".$_GET['systemid']."\" target=_blank><img src='image/stat.png'></a></td>";
}


?>