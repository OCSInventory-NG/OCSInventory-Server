<?php
/*
 * For redistribution's server
 */

require ('fichierConf.class.php');
require('req.class.php');
require_once('require/function_table_html.php');
require_once('require/function_server.php');
if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
	die("FORBIDDEN");

//modif for server's group
if (isset($_POST['Valid_modif_x']) and $_POST['WHERE'] == "MODIFGROUPSERVER"){
	//looking for server's group whith same name
	$reqGetId = "SELECT id FROM hardware WHERE name='".$_POST['NAME']."' and id !=".$_POST["ID"];
	$resGetId = mysql_query( $reqGetId, $_SESSION["readServer"]);
	if( $valGetId = mysql_fetch_array( $resGetId ) )
		$idGroupServer = $valGetId['id'];
	//if group's name is not null
	if (trim($_POST['NAME']) != ""){
		if (!isset($idGroupServer))//update server's group
			mysql_query("update hardware set name= '".$_POST['NAME']."', description='".$_POST['DESCRIPTION']."' where id=".$_POST["ID"], $_SESSION["writeServer"]);
		else //error
		echo "<script>alert('".$l->g(621)."');</script>";
	}
	else //error
	echo "<script>alert('".$l->g(638)."');</script>";


}
//Modif server's machine
if (isset($_POST['Valid_modif_x']) and $_POST['WHERE'] == "MODIFMACHGROUPSERVER"){
	$default_values=look_default_values();
	if (trim($_POST['URL']) == "")
	$_POST['URL']=$default_values['tvalue']['DOWNLOAD_SERVER_URI'];
	if (trim($_POST['REP_STORE']) == "")
	$_POST['REP_STORE']=$default_values['tvalue']['DOWNLOAD_SERVER_DOCROOT'];
	
	if ($_POST['ID'] != "ALL")
	{
		$sql= "update download_servers set URL='".$_POST['URL']."' ,ADD_REP='".$_POST['REP_STORE']."' where hardware_id=".$_POST['ID'];
		mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		$sql= "update download_enable set pack_loc='".$_POST['URL']."' where SERVER_ID=".$_POST['ID'];
		mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
	}else
	{
		$sql="update download_servers set URL='".$_POST['URL']."' ,ADD_REP='".$_POST['REP_STORE']."' where GROUP_ID=".$_POST['ID_GROUP'];
		
		mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		$sql= "update download_enable set pack_loc='".$_POST['URL']."' where GROUP_ID=".$_POST['ID_GROUP'];
		mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));

	}
//	print_r($_POST);
}


//cas of delete group
if($_GET["suppAcc"]){
	if ($_GET['orig'] == "group"){
		$verif[0]['sql']="select fileid from download_enable,devices
				where download_enable.id=devices.ivalue
				and GROUP_ID=".$_GET["suppAcc"];
		$verif[0]['condition']='EXIST';
		$verif[0]['MSG_ERROR']=$l->g(688)." ".$l->g(687);
		$verif[1]['sql']="select ID,NAME from hardware where deviceid='_DOWNLOADGROUP_' and id=".$_GET["suppAcc"];
		$verif[1]['condition']='NOT EXIST';
		$verif[1]['MSG_ERROR']=$l->g(639);
		$ok=verification($verif);
		if (isset($ok)){
            mysql_query("delete from download_enable where GROUP_ID=".$_GET["suppAcc"], $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			$sql="delete from download_servers where GROUP_ID = ".$_GET["suppAcc"];
			mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			mysql_query("delete from hardware where id=".$_GET["suppAcc"], $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		}
	}
	elseif ($_GET['orig'] == "mach"){
		$verif[0]['sql']="select fileid from download_enable,devices
				where download_enable.id=devices.ivalue
				and download_enable.SERVER_ID=".$_GET["suppAcc"];
		$verif[0]['condition']='EXIST';
		$verif[0]['MSG_ERROR']=$l->g(689)." ".$l->g(687);
		$ok=verification($verif);
		if (isset($ok)){
            mysql_query("delete from download_enable where SERVER_ID=".$_GET["suppAcc"], $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			mysql_query("delete from download_servers where hardware_id=".$_GET["suppAcc"], $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		}
	}
	elseif ($_GET['orig'] == "all_mach"){
		$verif[0]['sql']="select fileid from download_enable,devices
				where download_enable.id=devices.ivalue
				and GROUP_ID=".$_GET["suppAcc"];
		$verif[0]['condition']='EXIST';
		$verif[0]['MSG_ERROR']=$l->g(688)." ".$l->g(690);
		$ok=verification($verif);
		if (isset($ok)){
			mysql_query("delete from download_enable where GROUP_ID=".$_GET["suppAcc"], $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
			$sql="delete from download_servers where GROUP_ID = ".$_GET["suppAcc"];
			mysql_query($sql, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		}
	}
}
?>
	<script language=javascript>
		function confirme(did,lbl,orig,get,get_value){
			if(confirm("<?echo $l->g(640)?> "+lbl+" "+did+" ?"))
				window.location="index.php?multi=<?php echo $_GET["multi"]?>&suppAcc="+did+"&orig="+orig+"&"+get+"="+get_value;
		}
	</script>
<?php

//view of all group's servers
	$result = mysql_query("select ID,NAME,LASTDATE,DESCRIPTION from hardware where deviceid='_DOWNLOADGROUP_'", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$i=0;
	while($colname = mysql_fetch_field($result))
		$entete[$i++]=$colname->name;
		$entete[$i++]="Sup";
		$entete[$i++]="Mod";
		$entete[$i]="Visu";
	$i=0;
	while($item = mysql_fetch_object($result)){
			$data[$i]['ID']=$item ->ID;
			$data[$i]['NAME']=$item ->NAME;
			$data[$i]['DATE_CREA']=$item ->LASTDATE;
			$data[$i]['DESCRIPTION']=$item ->DESCRIPTION;
			$data[$i]['SUP']="<a href=# OnClick='confirme(\"".$item ->ID."\",\"".$l->g(642)."\",\"group\",\"server\",\"\");'><img src=image/supp.png></a>";
			$data[$i]['MODIF']="<a href='index.php?multi=33&modifgroupserver=".$i."'><img src=image/modif_tab.png ></a>";
			$data[$i]['VISU']="<a href='index.php?multi=33&viewmach=".$i."'><img src=image/oeil.png  ></a>";
			$i++;
	}

tab_entete_fixe($entete,$data,$l->g(641),"60","300");

//modif of group's server data
if (isset($_GET['modifgroupserver']) and !isset($_POST['Valid_modif_x']) and !isset($_POST['Reset_modif_x']))
{
	$tab_name[0]="NAME";
	$tab_name[1]="DESCRIPTION";
	$tab_typ_champ[0]['DEFAULT_VALUE']=$data[$_GET['modifgroupserver']]['NAME'];
	$tab_typ_champ[0]['INPUT_NAME']="NAME";
	$tab_typ_champ[0]['INPUT_TYPE']=0;
	$tab_typ_champ[1]['DEFAULT_VALUE']=$data[$_GET['modifgroupserver']]['DESCRIPTION'];
	$tab_typ_champ[1]['INPUT_NAME']="DESCRIPTION";
	$tab_typ_champ[1]['INPUT_TYPE']=1;
	$tab_hidden['ID']=$data[$_GET['modifgroupserver']]['ID'];
	$tab_hidden['WHERE']="MODIFGROUPSERVER";
	$title= $l->g(695)." ".$data[$_GET['modifgroupserver']]['ID']."(".$data[$_GET['modifgroupserver']]['NAME'].")";

	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title);

}

//view of all group's machin
if (isset($_GET['viewmach']))
{
		$result = mysql_query("select hardware.ID,
									  hardware.NAME,
									  hardware.IPADDR,
									  hardware.DESCRIPTION,
									  download_servers.URL,
				 					  download_servers.ADD_REP
								from hardware left join download_servers on hardware.id=download_servers.hardware_id
								where download_servers.GROUP_ID=".$data[$_GET['viewmach']]['ID'], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		$i=0;
	while($colname = mysql_fetch_field($result))
		$entete2[$i++]=$colname->name;
		$entete2[$i++]="SUP <br><a href=# OnClick='confirme(\"".$data[$_GET['viewmach']]['ID']."\",\"".$l->g(643)."\",\"all_mach\",\"viewmach\",\"".$_GET['viewmach']."\");'><img src=image/delete_all.png></a>";
		$entete2[$i]="MODIF <a href='index.php?multi=33&viewmach=".$_GET['viewmach']."&modifmachgroupserver=ALL'><img src=image/modif_all.png ></a>";

	$i=0;
	//" du groupe ".$data[$_GET['viewmach']]['ID'].
	while($item = mysql_fetch_object($result)){
			$data2[$i]['ID']=$item ->ID;
			$data2[$i]['NAME']=$item ->NAME;
			$data2[$i]['IP_ADDR']=$item ->IPADDR;
			$data2[$i]['DESCRIPTION']=$item ->DESCRIPTION;
			$data2[$i]['URL']="http://".$item ->URL;
			$data2[$i]['REP_STORE']=$item ->ADD_REP;
			$data2[$i]['SUP']="<a href=# OnClick='confirme(\"".$item ->ID."\",\"".$l->g(644)."\",\"mach\",\"viewmach\",\"".$_GET['viewmach']."\");'><img src=image/supp.png></a>";
			$data2[$i]['MODIF']="<a href='index.php?multi=33&viewmach=".$_GET['viewmach']."&modifmachgroupserver=".$i."'><img src=image/modif_tab.png ></a>";
			$i++;
	}
	 $total="<font color=red> (<b>".$i." ".$l->g(652)."</b>)</font>";
	tab_entete_fixe($entete2,$data2,$l->g(645).$total,"95","300");

}
//detail of group's machin
if (isset($_GET['modifmachgroupserver']) and !isset($_POST['Valid_modif_x']) and !isset($_POST['Reset_modif_x']))
{
	$tab_name[1]=$l->g(646).": ";
	$tab_name[2]=$l->g(648).": ";
	$tab_typ_champ[1]['DEFAULT_VALUE']=substr($data2[$_GET['modifmachgroupserver']]['URL'],7);
	$tab_typ_champ[1]['COMMENT_BEFORE']="<b>http://</b>";
	$tab_typ_champ[1]['COMMENT_BEHING']="<small>".$l->g(691)."</small>";
	$tab_typ_champ[1]['INPUT_NAME']="URL";
	$tab_typ_champ[1]['INPUT_TYPE']=0;
	$tab_typ_champ[2]['DEFAULT_VALUE']=$data2[$_GET['modifmachgroupserver']]['REP_STORE'];
	$tab_typ_champ[2]['INPUT_NAME']="REP_STORE";
	$tab_typ_champ[2]['INPUT_TYPE']=0;
	if ($_GET['modifmachgroupserver'] != "ALL")
	$tab_hidden['ID']=$data2[$_GET['modifmachgroupserver']]['ID'];
	else
	{
		$tab_hidden['ID']=$_GET['modifmachgroupserver'];
		$tab_hidden['ID_GROUP']=$data[$_GET['viewmach']]['ID'];
	}
	$tab_hidden['WHERE']="MODIFMACHGROUPSERVER";
	if ($_GET['modifmachgroupserver'] == "ALL")
		$title= $l->g(692);
	else
		$title= $l->g(693).": ".$data2[$_GET['modifmachgroupserver']]['NAME'];
        $comment=$l->g(694);
        tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment);

}

?>
