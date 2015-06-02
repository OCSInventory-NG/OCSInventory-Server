<?php
/*
 * Created on 19 mars 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
//ADD new static group
if($_POST['Valid_modif_x']){
	//this group does exist?
	$sql_verif="select id from hardware
				where DEVICEID='_SYSTEMGROUP_' 
					and name='".$_POST['NAME']."' 
					and DESCRIPTION='".$_POST['DESCR']."'";
	$res_verif = mysql_query($sql_verif, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item_verif = mysql_fetch_object($res_verif);
	//exist
	if ($item_verif -> id != "")
		$error=addslashes($l->g(621));
	//name is null
	if (trim($_POST['NAME']) == "")
		$error=addslashes($l->g(638));
	//an error?
	if (isset($error)){
	echo "<script>alert('".$error."');</script>";
	$_POST['add_static_group']='KO';	
	}else{
		//else insert new group
		$sql_insert="insert into hardware (DEVICEID,NAME,LASTDATE,DESCRIPTION) 
					value ('_SYSTEMGROUP_','".$_POST['NAME']."',sysdate(),'".$_POST['DESCR']."')";
		mysql_query($sql_insert, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
		$sql_insert_groups=" insert into groups (hardware_id,request,create_time) value (".mysql_insert_id().",'', UNIX_TIMESTAMP())";
		mysql_query($sql_insert_groups, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	}
	
}

 
 
//if no SADMIN=> view only your computors
if ($_SESSION["lvluser"] == ADMIN){
	$sql_mesMachines="select hardware_id from accountinfo a where ".$_SESSION["mesmachines"];
	$res_mesMachines = mysql_query($sql_mesMachines, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$mesmachines="(";
	while ($item_mesMachines = mysql_fetch_object($res_mesMachines)){
		$mesmachines.= $item_mesMachines->hardware_id.",";	
	}
	$mesmachines=" IN ".substr($mesmachines,0,-1).")";
		
}

//View for all profils?
if (isset($_POST['check_group']) and  $_POST['check_group'] != "")
{
	$sql_verif="select WORKGROUP from hardware where id=".$_POST['check_group'];
	$res = mysql_query($sql_verif, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item = mysql_fetch_object($res);
	if ($item->WORKGROUP != "GROUP_4_ALL")	
	$sql_update="update hardware set workgroup= 'GROUP_4_ALL' where id=".$_POST['check_group'];
	else
	$sql_update="update hardware set workgroup= '' where id=".$_POST['check_group'];
	mysql_query($sql_update, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
}

//if delete group
if (isset($_POST['supp']) and  $_POST['supp'] != "" and is_numeric($_POST['supp']))
{
	$del_groups_TAG="DELETE FROM accountinfo where HARDWARE_ID=".$_POST['supp'];
	mysql_query($del_groups_TAG, $_SESSION["writeServer"]) or die(mysql_error());
	$del_groups_cache="DELETE FROM groups_cache WHERE group_id=".$_POST['supp'];
	mysql_query($del_groups_cache, $_SESSION["writeServer"]) or die(mysql_error());
	$del_groups="DELETE FROM groups WHERE hardware_id=".$_POST['supp'];
	mysql_query($del_groups, $_SESSION["writeServer"]) or die(mysql_error());
	$del_hardware="DELETE FROM hardware where id=".$_POST['supp'];
	mysql_query($del_hardware, $_SESSION["writeServer"]) or die(mysql_error());

}

$form_name='groups';
require_once('require/function_table_html.php');
//if SADMIN=> view all groups
if ($_SESSION["lvluser"]!= ADMIN){
	$def_onglets['DYNA']=$l->g(810); //Dynamic group
	$def_onglets['STAT']=$l->g(809); //Static group centraux
	$def_onglets['SERV']=strtoupper($l->g(651));
	if ($_POST['onglet'] == "")
	$_POST['onglet']="DYNA";
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	//show onglet
	onglet($def_onglets,$form_name,"onglet",0);
}else{
	
	$_POST['onglet']="STAT";
	
}
$limit=nb_page($form_name);

if ($_POST['tri2'] == "")
$_POST['tri2']=1;
if ($_POST['onglet'] == "STAT" or $_POST['onglet'] == "DYNA"){
	$sql="select h.id id,h.name name,h.DESCRIPTION description,h.lastdate creat, count(g_c.HARDWARE_ID) nbr ";
	if ($_POST['onglet'] == "STAT")
	$sql.=", h.workgroup";
	$sql.=" from hardware h left join groups_cache g_c on g_c.group_id=h.ID,groups g ";
//	if ($_POST['onglet'] == "STAT")
//		$sql.="left join accountinfo on accountinfo.hardware_id=g.hardware_id";
	$sql.="	where deviceid = '_SYSTEMGROUP_' 
				and g.HARDWARE_ID=h.ID
				and g.request ";
	
	if ($_POST['onglet'] == "DYNA")
		$sql.=" != ";
	else
		$sql.=" = ";
		$sql .= " '' ";
	if($_SESSION["lvluser"] == ADMIN)
	$sql.=" and workgroup='GROUP_4_ALL' ";
	$sql.=" group by h.name order by ".$_POST['tri2']." ".$_POST['sens'];		
	$reqCount="select count(*) nb from (".$sql.") toto";
	$sql.=" limit ".$limit["BEGIN"].",".$limit["END"];
}elseif ($_POST['onglet'] == "SERV"){
	
	$sql="select group_id id,h.name name,h.DESCRIPTION description, h.lastdate creat, count(hardware_id) nbr
			from download_servers d_s,hardware h
			where d_s.group_id=h.id
			group by group_id order by ".$_POST['tri2']." ".$_POST['sens'];
	$reqCount="select count(*) nb from (".$sql.") toto";
	$sql.=" limit ".$limit["BEGIN"].",".$limit["END"];
	
}

$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$valCount = mysql_fetch_array($resCount);
$result = mysql_query( $sql, $_SESSION["readServer"]);
	$i=0;
	while($colname = mysql_fetch_field($result)){

		if ($colname->name != "id" and $colname->name != "workgroup"){
			$col=$colname->name;
			if ($_POST['sens'] == "ASC")
			$sens="DESC";
			else
			$sens="ASC";
		$deb="<a OnClick='tri(\"".$col."\",\"".$sens."\",\"".$form_name."\")' >";
		$fin="</a>";
		$entete[$i++]=$deb.$col.$fin;
		}
	}
	if ($_SESSION["lvluser"] == SADMIN){
		$entete[$i++]="del";
		if ($_POST['onglet'] == "STAT")
		$entete[$i++]="Visible";
	}
	$i=0;
	while($item = mysql_fetch_object($result)){
		$deb="<a href='index.php?multi=29&popup=1&systemid=".$item ->id."' target='_blank'>";
		$fin="</a>";
		$data[$i][$entete[0]]=$deb.$item ->name.$fin;
		$data[$i][$entete[1]]=$item ->description;
		$data[$i][$entete[2]]=$item ->creat;
		if ($_SESSION["lvluser"] == ADMIN){
			$sql_count_my = "SELECT count(hardware_id) c FROM groups_cache WHERE group_id='".$item ->id."' and hardware_id ".$mesmachines; 
			$res_count_my = mysql_query($sql_count_my, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
			$item_count_my = mysql_fetch_object($res_count_my);
			if ($item_count_my -> c == "")
			$nbr='0';
			else
			$nbr=$item_count_my -> c;
		}else
		$nbr=$item ->nbr;
		$data[$i][$entete[3]]=$nbr;
		if ($_SESSION["lvluser"] == SADMIN){
			$data[$i][$entete[4]]="<img src='image/supp.png' OnClick='confirme(\"".str_replace("'", "", $item ->name)."\",".$item ->id.",\"".$form_name."\",\"supp\",\"".$l->g(640)." \")'>";
			if ($_POST['onglet'] == "STAT")
			$data[$i][$entete[5]]="<input type='checkbox' OnClick='confirme(\"".str_replace("'", "", $item ->name)."\",".$item ->id.",\"".$form_name."\",\"check_group\",\"".$l->g(811)." \")' ".($item -> workgroup ? " checked" : "").">";
			
			//OnClick='page(\"".$item ->id."\",\"check\",\"".$form_name."\")'
		}
		$i++;
	}
	$titre=$l->g(768)." ".$valCount['nb'];
	$width=90;
	$height=300;
	tab_entete_fixe($entete,$data,$titre,$width,$height);
	show_page($valCount['nb'],$form_name);

//if we are SADMIN, add button for add new static group
if ($_POST['onglet'] == "STAT" and $_SESSION["lvluser"]==SADMIN)
echo "<br><input type=submit value='".$l->g(587)."' name='add_static_group'>";

echo "</table>";
echo "<input type='hidden' id='tri2' name='tri2' value='".$_POST['tri2']."'>";
echo "<input type='hidden' id='sens' name='sens' value='".$_POST['sens']."'>";
echo "<input type='hidden' id='supp' name='supp' value=''>";
echo "<input type='hidden' id='check_group' name='check_group' value=''>";
echo "</form>";

//if user want add a new group
if (isset($_POST['add_static_group']) and $_SESSION["lvluser"]==SADMIN){
	$tdhdpb = "<td  align='left' width='20%'>";
$tdhfpb = "</td>";
$tdhd = "<td  align='left' width='20%'><b>";
$tdhf = ":</b></td>";
$img_modif="";
	//list of input we can modify
	$name=show_modif($_POST['NAME'],'NAME',0);
	$description=show_modif($_POST['DESCR'],'DESCR',1);
	//show new bottons
	$button_valid="<input title='".$l->g(625)."' type='image'  src='image/modif_valid_v2.png' name='Valid_modif'>";
	$button_reset="<input title='".$l->g(626)."' type='image'  src='image/modif_anul_v2.png' name='Reset_modif'>";
//form for modify values of group's
echo "<form name='add' action='' method='POST'>";
echo "<br><br><table align='center' width='65%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>".$tdhd.$l->g(577).$tdhf.$tdhdpb.$name.$tdhfpb;
echo "</tr>";
echo $tdhd."</b></td><td  align='left' width='20%' colspan='3'>";
echo "</tr><tr>".$tdhd.$l->g(53).$tdhf.$tdhdpb.$description.$tdhfpb;
echo "<tr><td align='left' colspan=4>".$button_valid."&nbsp&nbsp".$button_reset."&nbsp&nbsp".$img_modif."</td></tr>";
echo "$tdhfpb</table>";
echo "<input type='hidden' id='onglet' name='onglet' value='".$_POST['onglet']."'>";
echo "</form>";	
	
	
}

?>
