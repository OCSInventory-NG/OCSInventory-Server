<?php
/*
 * Created on 25 janv. 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 require_once('require/function_table_html.php');
require_once('require/function_opt_param.php');
require_once('require/function_config_generale.php');
if( $_SESSION["lvluser"] != SADMIN )
	die("FORBIDDEN");
	
if (isset($_SESSION['ID_REQ']) and $_POST['origine'] != "machine" and $_POST['origine'] != "group")
$_GET["listid"]=implode(',',$_SESSION['ID_REQ']);


if ($_POST['onglet'] == "" or !isset($_POST['onglet']))
$_POST['onglet'] = $l->g(499);

$def_onglets[$l->g(499)]=$l->g(499); //Serveur
$def_onglets[$l->g(728)]=$l->g(728); //Inventaire
$def_onglets[$l->g(512)]=$l->g(512); //Télédéploiement
$def_onglets[$l->g(312)]=$l->g(312); //ipdiscover
if (!isset($form_name))
$form_name='admin_param';
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//print_r($_GET);
//update values	
if ($_POST['Valid']==$l->g(103)){
	//echo "totot";
	if (!isset($_POST['origine']) and !isset($_GET["listid"])){
		$list_hardware_id="";
		$lareq = getPrelim( $_SESSION["storedRequest"] );
		if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
			return false;
		while( $val = @mysql_fetch_array($res)) {
		$list_hardware_id.=$val["h.id"].",";
		$tab_hadware_id[]=$val["h.id"];
		}
		$list_hardware_id = substr($list_hardware_id,0,-1);
		$nbMach = getCount($_SESSION["storedRequest"]);
		$add_lbl=" (".$nbMach." ".$l->g(652).")";
	}elseif(isset($_GET["listid"])){
		if ($_POST['CHOISE'] == "REQ"){
			$list_hardware_id=$_GET["listid"];
		}elseif ($_POST['CHOISE'] == "SEL"){
			$list_hardware_id=$_GET["idchecked"];
		}
		$tab_hadware_id=explode(",",$list_hardware_id);
		$add_lbl=" (".count($tab_hadware_id)." ".$l->g(652).")";	
	}elseif(($_POST['origine'] == "machine" or $_POST['origine'] == "group") and $_POST['systemid'] != ""){
		
		$list_hardware_id=$_POST['systemid'];
	}
	if (isset($list_hardware_id)){
		 foreach ($_POST as $key => $value){
		 	if ($key != "systemid" and $key != "origine"){
			 	if ($value == "SERVER DEFAULT" or $value == "des")
			 		erase($key);
			 	elseif ($value == "CUSTOM"){
			 		insert($key,$_POST[$key.'_edit']);	 	
			 	}
			 	elseif ($value == "ALWAYS"){
			 		insert($key,0);	 
			 	}
				elseif ($value == "NEVER"){
			 		insert($key,-1);	 
			 	} 
			 	elseif ($value == "ON"){
			 		insert($key,1);	 
			 	} 
			 	elseif ($value == "OFF"){
			 		insert($key,0);	 
			 	}elseif ($key == "IPDISCOVER" and $value != "des" and $value != "OFF"){
			 		insert($key,2,$value);	
			 	}
			 	
		 	}
	 	}
	 	$MAJ=$l->g(711);
	 	echo "<font color=green><center><b>".$MAJ.$add_lbl."</b></center></font>";
	}else
	echo "<script>alert('".$l->g(983)."')</script>";
 }
if ($_POST['origine'] == "machine"){
$direction=	"machine.php?option=cd_configuration&systemid=".$_POST["systemid"];	
}elseif ($_POST['origine'] == "group")
$direction=	"index.php?multi=29&popup=1&systemid=".$_POST["systemid"];
else
$direction="index.php?redo=1".$_SESSION["queryString"];	

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

//not a sql query
if (isset($_POST['origine'])){
	//looking for value of systemid
	$sql_value_idhardware="select * from devices where name != 'DOWNLOAD' and hardware_id=".$_POST["systemid"];
	$result_value = mysql_query($sql_value_idhardware, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($value=mysql_fetch_array($result_value)) {
		$optvalue[$value["NAME"] ] = $value["IVALUE"];
		$optvalueTvalue[$value["NAME"]]=$value["TVALUE"];
	}
	$champ_ignored=0;
}elseif(!isset($_GET["listid"])){
	$list_hardware_id="";
	$lareq = getPrelim( $_SESSION["storedRequest"] );
	if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
		return false;
	while( $val = @mysql_fetch_array($res)) {
	$list_hardware_id.=$val["h.id"].",";
	$tab_hadware_id[]=$val["h.id"];
	}
	$list_hardware_id = substr($list_hardware_id,0,-1);
	$champ_ignored=1;
}elseif(isset($_GET["listid"])){
	$list_hardware_id=$_GET["listid"];
	$tab_hadware_id=explode(",",$list_hardware_id);
	$champ_ignored=1;
	if (isset($select_choise))
	echo "<div align=center>".$l->g(984)." ".$select_choise."</div>";		
	else{
		$_POST['CHOISE']='REQ';
		echo "<input type='hidden' name='CHOISE' value='".$_POST['CHOISE']."'>";	
	}
	
	//else
	
//	$choise_req_selection['REQ']=$l->g(584);
//	if ($_GET['idchecked'] != "")
//	$choise_req_selection['SEL']=$l->g(585);
//	$select_choise=show_modif($choise_req_selection,'CHOISE',2,$form_name);
//	echo "<div align=center>Modiciations ".$select_choise."</div>";
}


if(!isset($_GET["listid"])){
//link for return 
	echo "<br><center><a href='#' OnClick=\"window.location='".$direction."';\"><= ".$l->g(188)."</a></center>";
	
}

onglet($def_onglets,$form_name,'onglet',7);
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td>";
if ($_POST['onglet'] == $l->g(728)){
	include ('opt_frequency.php');
}
if ($_POST['onglet'] == $l->g(499)){
		include ('opt_prolog.php');
}
if ($_POST['onglet'] == $l->g(512)){
	include ('opt_download.php');

}
if ($_POST['onglet'] == $l->g(312)){
	include ('opt_ipdiscover.php');

}
if (isset($_POST['origine'])){
echo "<input type='hidden' id='systemid' name='systemid' value='".$_POST['systemid']."'>";
	echo "<input type='hidden' id='origine' name='origine' value='".$_POST['origine']."'>";
} 
echo "</td></tr></table>";
 echo "</form>";
?>
