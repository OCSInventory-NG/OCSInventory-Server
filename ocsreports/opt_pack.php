<?php
/*
 * Created on 4 juil. 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once('require/function_telediff.php');
if ($_POST['SELECT'] != ''){
	if ($_POST['onglet'] == 'MACH')
	$nb_affect=active_mach($list_id,$_POST['SELECT']);
	if ($_POST['onglet'] == 'SERV_GROUP')
	$nb_affect=active_serv($list_id,$_POST['SELECT'],$_POST['rule_choise']);
	
	echo "<br><font color=green>".$nb_affect." ".$l->g(604)."</font>";

}
if ($_POST['sens'] == "")
	$_POST['sens']='DESC';


if ($_POST['onglet'] == "")
$_POST['onglet'] = 'MACH';


$def_onglets['MACH']=$l->g(980); //GROUPES DYNAMIQUES
$def_onglets['SERV_GROUP']=$l->g(981); //GROUPES STATIQUES
//print_r($_POST);
//echo "<hr>";
//print_r($_GET);
//echo "<hr>";
//echo $_SESSION['ID_REQ'];
//open form
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//show onlget
onglet($def_onglets,$form_name,'onglet',7);
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td align =center>";
if (isset($select_choise)){
echo $select_choise."</td></tr><tr><td align =center>";
}else{
	$_POST['CHOISE']='REQ';
	echo "<input type='hidden' name='CHOISE' value='".$_POST['CHOISE']."'>";
}
if ($_POST['onglet'] == 'SERV_GROUP'){
	$sql_rules="select distinct rule,rule_name from download_affect_rules order by 1";
		$res_rules = mysql_query( $sql_rules, $_SESSION["readServer"] ) or die(mysql_error($_SESSION["readServer"]));
		$nb_rule=0;
		while( $val_rules = mysql_fetch_array($res_rules)) {
			$first=$val_rules['rule'];
			$list_rules[$val_rules['rule']]=$val_rules['rule_name'];
			$nb_rule++;
		}
	if ($nb_rule>1){
	$select_choise=$l->g(668).show_modif($list_rules,'rule_choise',2,$form_name);	
	echo $select_choise;
	}elseif($nb_rule == 1){
		$_POST['rule_choise']=$first;
		echo "<input type=hidden value='".$first."' name='rule_choise' id='rule_choise'>";
	}elseif ($nb_rule == 0){
		echo "<font color=RED size=4>".$l->g(982)."</font>";
	}
}


//echo "<tr><td align =center>";
if(($_POST['CHOISE'] != '' and $_POST['onglet'] == 'MACH') 
	or ($_POST['onglet'] == 'SERV_GROUP' and $_POST['rule_choise'] != '')){
		//recherche de toutes les règles pour les serveurs de redistribution
	$list_fields= array('FILE_ID'=>'e.FILEID',
							'INFO_LOC'=>'e.INFO_LOC',
							'CERT_FILE'=>'e.CERT_FILE',
							'CERT_PATH'=>'e.CERT_PATH',
							//'PACK_LOC'=>'de.PACK_LOC',
							'PACK_NAME'=>'a.NAME',
							'PRIORITY'=>'a.PRIORITY',
							'COMMENT'=>'a.COMMENT',
							'OS_NAME'=>'a.OSNAME',
							'SIZE'=>'a.SIZE'				
							);
											
	if (!isset($nb_rule) or $nb_rule>0)						
			$list_fields['SELECT']='e.FILEID';
	$table_name="LIST_PACK_SEARCH";//INSERT INTO devices(HARDWARE_ID, NAME, IVALUE) VALUES('".$val["h.id"]."', 'DOWNLOAD', $packid)
	$default_fields= array('PACK_NAME'=>'PACK_NAME','PRIORITY'=>'PRIORITY','OS_NAME'=>'OS_NAME','SIZE'=>'SIZE','SELECT'=>'SELECT');
	$list_col_cant_del=array('PACK_NAME'=>'PACK_NAME','SELECT'=>'SELECT');
	$querypack = 'SELECT distinct ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SELECT')
		$querypack .= $value.',';		
	} 
	$querypack=substr($querypack,0,-1);
	$querypack .= " from download_available a, download_enable e ";
	if ($_POST['onglet'] == 'MACH')
	$querypack .= "where a.FILEID=e.FILEID and e.SERVER_ID is null ";
	else
	$querypack .= ", hardware h where a.FILEID=e.FILEID and h.id=e.group_id and  e.SERVER_ID is not null ";
	$tab_options['QUESTION']['SELECT']="Etes-vous sûr de vouloir affecter ce paquet à ces machines?";
	$tab_options['FILTRE']=array('e.FILEID'=>'Timestamp','a.NAME'=>'Nom');
	//echo $querypack;
	$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$querypack,$form_name,100,$tab_options); 
}
echo "</td></tr></table>";


echo "</form>";
?>
