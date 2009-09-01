<?php
//suppression des paquets qui restent en notifi� et qui sont plus vieux de 3 mois
if (isset($_GET['reset_notified']) and is_numeric($_GET['reset_notified'])){
	$sql=" delete from devices where name='DOWNLOAD' and tvalue = 'NOTIFIED' and IVALUE='".$_GET['reset_notified']."' and hardware_id=".$systemid; 
	mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["readServer"]));	
}



//r�affectation de paquet
//traitement de la r�affectation du paquet
if ($_POST['Valid_modif_x']){
	if (trim($_POST['MOTIF'])){
		if ($_POST["ACTION"] == "again"){
			$sql=" update devices set TVALUE=null
					where name='DOWNLOAD' and tvalue like 'ERR_%' and IVALUE='".$_GET['affect_again']."' and hardware_id=".$systemid; 
		}elseif($_POST["ACTION"] == "reset"){
			$sql=" delete from devices where name='DOWNLOAD' and tvalue like 'ERR_%' and IVALUE='".$_GET['affect_reset']."' and hardware_id=".$systemid; 
		}
		mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["readServer"]));
		if (mysql_affected_rows() != 0){
			$txt_trait=xml_encode(stripslashes($_POST['MOTIF']));
			$sql="INSERT INTO itmgmt_comments (hardware_id,comments,user_insert,date_insert,action) 
					values ('".$systemid."','".$txt_trait."','".$_SESSION["loggeduser"]."',
							sysdate(),'".$_POST["ACTION"]." => ".$_POST['NAME_PACK']."')"; 
			mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["readServer"]));
		}
	}else
	echo "<script>alert(\"".$l->g(903)."\")</script>";	
}

if ($_POST['Reset_modif_x'])
unset($_GET['affect_again'],$_GET['affect_reset']);


if ($_GET['affect_again'] or $_GET['affect_reset']){
	if ($_GET['affect_again']){
		$id_pack_affect=$_GET['affect_again'];
		$hidden_action='again';
		$title_action=$l->g(904);		
		$lbl_action=$l->g(905);	
	}else{
		$id_pack_affect=$_GET['affect_reset'];
		$hidden_action='reset';
		$title_action=$l->g(906);
		$lbl_action=$l->g(907);
	}
	$sql="select da.name from devices d, 
						  download_enable de,
							download_available da
          where de.id='".$id_pack_affect."' and de.FILEID=da.FILEID
			and d.IVALUE=de.ID
			AND d.hardware_id='".$_GET['systemid']."' AND d.name='DOWNLOAD'
			and tvalue like 'ERR_%'";
			//echo $sql;
	$res = mysql_query( $sql, $_SESSION["readServer"] );
		$val = mysql_fetch_array( $res ); 
	if (isset($val['name'])){		
		$tab_typ_champ[0]['INPUT_NAME']="MOTIF";
		$tab_typ_champ[0]['INPUT_TYPE']=1;
		$data_form[0]="<center>".$lbl_action."</center>";
		tab_modif_values($data_form,$tab_typ_champ,array('NAME_PACK'=>$val['name'],'ACTION'=>$hidden_action),$title_action.$val['name'],"");

	}
}
if( isset( $_GET["suppack"] ) &  $_SESSION["lvluser"]==SADMIN  ) {
	if( $_SESSION["justAdded"] == false )
		@mysql_query("DELETE FROM devices WHERE ivalue=".$_GET["suppack"]." AND hardware_id='$systemid' AND name='DOWNLOAD'", $_SESSION["writeServer"]);
	else $_SESSION["justAdded"] = false;
	addLog($l->g(512), $l->g(886)." ".$_GET["suppack"]." => ".$systemid );
}
else 
	$_SESSION["justAdded"] = false;
	//TODO: voir si on loggue les evenements de groupe
if( isset( $_GET["actgrp"] )) {	
		//v�rification si la valeur correspond � un groupe
		$reqGroups = "SELECT h.id id
					  FROM hardware h 
					  WHERE h.deviceid='_SYSTEMGROUP_' ";
		//pour les autres qu'SADMIN, ajout que pour les groupes d�clar�s visibles
		if ($_SESSION["lvluser"]!=SADMIN)
			$reqGroups .= " and h.workgroup = 'GROUP_4_ALL'";
		$resGroups = mysql_query( $reqGroups, $_SESSION["readServer"] );
		$valGroups = mysql_fetch_array( $resGroups ); 
		if (isset($valGroups['id'])){
			$reqDelete = "DELETE FROM groups_cache WHERE hardware_id=".$systemid." AND group_id=".$_GET["grp"];
			
			if( $_GET["actgrp"] == 0 ) 
				$reqDelete .= " AND static<>0";
			$reqInsert = "INSERT INTO groups_cache(hardware_id, group_id, static) VALUES (".$systemid.", ".$_GET["grp"].", ".$_GET["actgrp"].")";
			@mysql_query( $reqDelete, $_SESSION["writeServer"] );
			if( $_GET["actgrp"] != 0 )
				@mysql_query( $reqInsert, $_SESSION["writeServer"] );
		}
}

$td1	  = "<td height=20px id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>";
$td2      = "<td height=20px bgcolor='white' align='center'>";
$td3      = $td2;
$td4      = "<td height=20px bgcolor='#F0F0F0' align='center'>";
$i=0;
	$queryDetails = "SELECT * FROM devices WHERE hardware_id=$systemid";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	echo "<table BORDER='1' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	
	//echo "<tr><td>&nbsp;&nbsp;</td> $td1 "."Libell�"." </td> $td1 "."Valeur"." </td><td>&nbsp;</td></tr>";		
	while($item=mysql_fetch_array($resultDetails,MYSQL_ASSOC)) {
		$optPerso[ $item["NAME"] ][ "IVALUE" ] = $item["IVALUE"];
		$optPerso[ $item["NAME"] ][ "TVALUE" ] = $item["TVALUE"];
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
	echo "<td align=center rowspan=8><a OnClick='recharge(\"$systemid\",\"machine\")'><img src='image/modif_a.png' title='".$l->g(285)."'></a></td>";
	echo "</tr>";
	echo "<input type='hidden' id='systemid' name='systemid' value=''>";
	echo "<input type='hidden' id='origine' name='origine' value=''>"; 
	echo "</form>";
	}
	$ii++; $td3 = $ii%2==0?$td2:$td4;
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
	echo $td3.$l->g(417)." <font color=green size=1><i>DOWNLOAD</i></font></td>";
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
	optperso("DOWNLOAD_CYCLE_LATENCY",$l->g(720)." <font color=green size=1><i>DOWNLOAD_CYCLE_LATENCY</i></font>",$optPerso,0,$optdefault["DOWNLOAD_CYCLE_LATENCY"],$l->g(511));
	
	//DOWNLOAD_FRAG_LATENCY
	optperso("DOWNLOAD_FRAG_LATENCY",$l->g(721)." <font color=green size=1><i>DOWNLOAD_FRAG_LATENCY</i></font>",$optPerso,0,$optdefault["DOWNLOAD_FRAG_LATENCY"],$l->g(511));

	
	//DOWNLOAD_PERIOD_LATENCY
	optperso("DOWNLOAD_PERIOD_LATENCY",$l->g(722)." <font color=green size=1><i>DOWNLOAD_PERIOD_LATENCY</i></font>",$optPerso,0,$optdefault["DOWNLOAD_PERIOD_LATENCY"],$l->g(511));
	
	//DOWNLOAD_PERIOD_LENGTH
	optperso("DOWNLOAD_PERIOD_LENGTH",$l->g(723)." <font color=green size=1><i>DOWNLOAD_PERIOD_LENGTH</i></font>",$optPerso,0,$optdefault["DOWNLOAD_PERIOD_LENGTH"]);

	//PROLOG_FREQ
	optperso("PROLOG_FREQ",$l->g(724)." <font color=green size=1><i>PROLOG_FREQ</i></font>",$optPerso,0,$optdefault["PROLOG_FREQ"],$l->g(730));
	
	//PROLOG_FREQ
	optperso("DOWNLOAD_TIMEOUT",$l->g(424)." <font color=green size=1><i>DOWNLOAD_TIMEOUT</i></font>",$optPerso,0,$optdefault["DOWNLOAD_TIMEOUT"],$l->g(496));
	//GROUPS
	$sql_groups="SELECT static, name, group_id,workgroup  FROM groups_cache g, hardware h WHERE g.hardware_id=$systemid AND h.id=g.group_id";
	$resGroups = @mysql_query($sql_groups, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"])); 
	echo "<tr><td colspan=100></td></tr>";
	if( mysql_num_rows( $resGroups )>0 ) {
		while( $valGroups = mysql_fetch_array( $resGroups ) ) {
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			echo "<tr>";
			echo "<td bgcolor='white' align='center' valign='center'>&nbsp;</td>";
			echo $td3.$l->g(607)." ";		
			if( $_SESSION["lvluser"] == SADMIN || $_SESSION["lvluser"] == LADMIN || $valGroups["workgroup"]=="GROUP_4_ALL")
				echo "<a href='index.php?".PAG_INDEX."=29&popup=1&systemid=".$valGroups["group_id"]."' target='_blank'>".$valGroups["name"]."</td>";
			else
				echo "<b>".$valGroups["name"]."</b></td>";			
				
			echo $td3.$l->g(81).": ";
			switch( $valGroups["static"] ) {
				case 0: echo "<font color='green'>".$l->g(596)."</font></td>"; break;
				case 1: echo "<font color='blue'>".$l->g(610)."</font></td>"; break;
				case 2: echo "<font color='red'>".$l->g(597)."</font></td>"; break;
			}
			
			if( $_SESSION["lvluser"]==SADMIN || $valGroups["workgroup"]=="GROUP_4_ALL") {
				$hrefBase = "machine.php?systemid=".urlencode($systemid)."&option=cd_configuration&grp=".$valGroups["group_id"];
				switch( $valGroups["static"] ) {
					case 0: echo $td3."<a href='$hrefBase&actgrp=1'>".$l->g(598)."</a>&nbsp; &nbsp; &nbsp;<a href='$hrefBase&actgrp=2'>".$l->g(600)."</a></td>"; break;
					case 1: echo $td3."<a href='$hrefBase&actgrp=0'>".$l->g(818)."</a></td>"; break;
					case 2: echo $td3."<a href='$hrefBase&actgrp=1'>".$l->g(598)."</a>&nbsp; &nbsp; &nbsp;<a href='$hrefBase&actgrp=0'>".$l->g(599)."</a></td>"; break;
				}
			}			
			echo "</td>";
			echo "</tr>";			
		}
	}
	echo "<tr><td colspan=100></td></tr>";
	//TELEDEPLOY
	$query="SELECT a.name, d.tvalue,d.ivalue,d.comments,e.fileid, e.pack_loc,h.name as name_server,h.id
			FROM devices d left join download_enable e on e.id=d.ivalue
						LEFT JOIN download_available a ON e.fileid=a.fileid
						LEFT JOIN hardware h on h.id=e.server_id
			WHERE d.name='DOWNLOAD' and a.name != '' and pack_loc != ''   AND d.hardware_id=".$systemid."
			union
			SELECT 'PAQUET SUPPRIME', d.tvalue,d.ivalue,d.comments,e.fileid, 'PAQUET SUPPRIME',h.name,h.id 
			FROM devices d left join download_enable e on e.id=d.ivalue
						LEFT JOIN download_available a ON e.fileid=a.fileid
						LEFT JOIN hardware h on h.id=e.server_id
			WHERE d.name='DOWNLOAD' and a.name is null and pack_loc is null  AND d.hardware_id=".$systemid;
	$resDeploy = @mysql_query($query, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"])); 
	if( mysql_num_rows( $resDeploy )>0 ) {
			
		while( $valDeploy = mysql_fetch_array( $resDeploy ) ) {
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			echo "<tr>";
			echo "<td bgcolor='white' align='center' valign='center'><img width='15px' src='image/red.png'></td>";
			echo $td3.$l->g(498)." <b>".$valDeploy["name"]."</b>";
			if (isset($valDeploy["fileid"]))
			echo "(<small>".$valDeploy["fileid"]."</small>)";
			
			if ($valDeploy["name_server"]!="")
				echo " (".$l->g(499)." redistrib: <a href='machine.php?systemid=".$valDeploy["id"]."' target='_blank'><b>".$valDeploy["name_server"]."</b></a>";
			else
			echo " (".$l->g(499).": ".$valDeploy["pack_loc"]." ";
			//echo ($valDeploy["name_server"]!=""?"<a href='machine.php?systemid=".$valDeploy["id"]."' target='_blank'><b>".$valDeploy["name_server"]."</b></a>":"");
			echo ")</td>";			
			echo $td3.$l->g(81).": ".($valDeploy["tvalue"]!=""?$valDeploy["tvalue"]:$l->g(482));
			echo ($valDeploy["comments"]!=""?" (".$valDeploy["comments"].")":"");
			echo "</td>";
			
			if( $_SESSION["lvluser"]==SADMIN )	{
				echo "$td3 <a href='machine.php?suppack=".$valDeploy["ivalue"]."&systemid=".
				urlencode($systemid)."&option=cd_configuration'>".$l->g(122)."</a></td>";
			}elseif (strstr($valDeploy["tvalue"], 'ERR_')){
				echo $td3."<a href='machine.php?affect_reset=".$valDeploy["ivalue"]."&systemid=".
					urlencode($systemid)."&option=cd_configuration'>".$l->g(113)."</a>";
				if ($valDeploy["name"] != "PAQUET SUPPRIME")
				echo $td3."<a href='machine.php?affect_again=".$valDeploy["ivalue"]."&systemid=".
					urlencode($systemid)."&option=cd_configuration'>R�-".$l->g(433)."</a></td>";				
			}elseif (strstr($valDeploy["tvalue"], 'NOTIFIED')){	
					if (isset($valDeploy["comments"]) and strtotime ($valDeploy["comments"])<strtotime ("-12 week")){
						$possible_desafect='YES';
					//	echo $td3."<a href=# OnClick='confirme(\"\",\"".$value_of_field."\",\"".$form_name."\",\"SUP_PROF\",\"".$l->g(640)." ".$value_of_field."\");'><img src=image/supp.png></a>";
						echo $td3."<a href='machine.php?reset_notified=".$valDeploy["ivalue"]."&systemid=".
						urlencode($systemid)."&option=cd_configuration'><img src=image/supp.png></a>";
	//					$actuel_mount=date("M");
	//					$actuel_year=date("Y");
	//					echo "$td3 <a href='machine.php?suppack=".$valDeploy["ivalue"]."&systemid=".
	//				urlencode($systemid)."&option=cd_configuration'>".$year."   ".$mount."</a></td>";
					}
			
				
//			if (strstr($valDeploy["tvalue"], 'ERR_')){
//				echo "$td3<a href='machine.php?affect_again=".$valDeploy["ivalue"]."&systemid=".
//					urlencode($systemid)."&option=cd_configuration'>R�-".$l->g(433)."</a></td>";				
			}
			echo "</tr>";
		}
	}

		$hrefBase = "machine.php?systemid=".urlencode($systemid)."&option=cd_configuration";
		
		echo "<tr><td colspan='10' align='right'>";
		if( $_SESSION["lvluser"]==SADMIN ) 
			echo "<a href=# Onclick=window.open(\"multi_lot.php?img=image/tele_search.png&idchecked=".$systemid."&origine=mach\",\"rollo\",\"location=0,status=0,scrollbars=1,menubar=0,resizable=0,width=800,height=500\");>".$l->g(501)."</a>".$l->g(386);
		echo " <a href=# OnClick=window.location='$hrefBase&actgrp=1&grp='+document.getElementById(\"groupcombo\").options[document.getElementById(\"groupcombo\").selectedIndex].value>".
		$l->g(589)."</a>";
	
		$reqGroups = "SELECT h.name,h.id,h.workgroup 
					  FROM hardware h 
					  WHERE h.deviceid='_SYSTEMGROUP_'";
		if( $_SESSION["lvluser"]!=SADMIN )
			$reqGroups .= " and workgroup = 'GROUP_4_ALL'";
		$resGroups = mysql_query( $reqGroups, $_SESSION["readServer"] );
		$first = true;
		while( $valGroups = mysql_fetch_array( $resGroups ) ) {
			if( $first ) {
				echo " <select id='groupcombo'>";
				$first = false;
			}
			echo "<option value='".$valGroups["id"]."'>".$valGroups["name"]."</option>";
		}
		
		if( ! $first )
			echo "</select>";
			
		echo "</td></tr>";		
	//}
	echo "</table><br>";
	
?>