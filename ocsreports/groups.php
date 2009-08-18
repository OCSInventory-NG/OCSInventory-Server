<?php
/*
 * Page des groupes
 * 
 */ 
require_once('require/function_groups.php');
require_once('require/function_computors.php');
//ADD new static group
if($_POST['Valid_modif_x']){
	$result=creat_group ($_POST['NAME'],$_POST['DESCR'],'','','STATIC');
	if ($result['RESULT'] == "OK"){
		$color="green";
		unset($_POST['add_static_group']);
	}else
	$color="red";
	$msg=$result['RESULT'];	
}
//annule la création d'un groupe statique
if ($_POST['Reset_modif_x']) 
 unset($_POST['add_static_group']);
 
//if no SADMIN=> view only your computors
if ($_SESSION["lvluser"] == ADMIN)
	$mycomputors=computor_list_by_tag();

//View for all profils?
if (isset($_POST['CONFIRM_CHECK']) and  $_POST['CONFIRM_CHECK'] != "")
	$result=group_4_all($_POST['CONFIRM_CHECK']);

//if delete group
if ($_POST['SUP_PROF'] != ""){
	$result=delete_group($_POST['SUP_PROF']);	
	if ($result['RESULT'] == "ERROR")
	$color="red";
	else
	$color="green";
	$msg=$result['LBL'];
}
//si un message
if ($msg != "")
echo "<font color = ".$color." ><b>".$result['LBL']."</b></font>";

//ouverture du formulaire de la page
$form_name='groups';
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//if SADMIN=> view all groups
if ($_SESSION["lvluser"] != ADMIN){
	$def_onglets['DYNA']=$l->g(810); //Dynamic group
	$def_onglets['STAT']=$l->g(809); //Static group centraux
	$def_onglets['SERV']=strtoupper($l->g(651));
	if ($_POST['onglet'] == "")
	$_POST['onglet']="STAT";	
	//show onglet
	onglet($def_onglets,$form_name,"onglet",0);
	echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td align =center>";
}else{	
	$_POST['onglet']="STAT";
}

$list_fields= array('GROUP_NAME'=>'h.NAME',
					'GROUP_ID' =>'h.ID',
						'DESCRIPTION'=>'h.DESCRIPTION',
						'CREATE'=>'h.LASTDATE',
						'NBRE'=>'NBRE');
//only for admins
if ($_SESSION["lvluser"] == SADMIN){
	if ($_POST['onglet'] == "STAT")
		$list_fields['CHECK']= 'ID';
	$list_fields['SUP']= 'ID';	
}
//changement de nom à l'affichage des champs	
$tab_options['LBL']['CHECK']="Visible";
$tab_options['LBL']['GROUP_NAME']="Nom";

$table_name="LIST_GROUPS";
$default_fields= array('GROUP_NAME'=>'GROUP_NAME','DESCRIPTION'=>'DESCRIPTION','CREATE'=>'CREATE','NBRE'=>'NBRE','SUP'=>'SUP','CHECK'=>'CHECK');
$list_col_cant_del=array('GROUP_NAME'=>'GROUP_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
$querygroup = 'SELECT distinct ';
foreach ($list_fields as $key=>$value){
	if($key != 'SUP' and $key != 'CHECK' and $key != 'NBRE')
	$querygroup .= $value.',';		
} 
$querygroup=substr($querygroup,0,-1);
//requete pour les groupes de serveurs
if ($_POST['onglet'] == "SERV"){
	$querygroup .= " from hardware h,download_servers ds where ds.group_id=h.id and h.deviceid = '_DOWNLOADGROUP_'";	
	//calcul du nombre de machines par groupe de serveur
	$sql_nb_mach="SELECT count(*) nb, group_id
					from download_servers group by group_id";
}else{ //requete pour les groupes 'normaux'
	$querygroup .= " from hardware h,groups g";
	$querygroup .="	where g.hardware_id=h.id and h.deviceid = '_SYSTEMGROUP_' ";
	if ($_POST['onglet'] == "DYNA")
		$querygroup.=" and ((g.request is not null and trim(g.request) != '') 
							or (g.xmldef is not null and trim(g.xmldef) != ''))";
	elseif ($_POST['onglet'] == "STAT")
		$querygroup.=" and (g.request is null or trim(g.request) = '')
					    and (g.xmldef  is null or trim(g.xmldef) = '') ";
	if($_SESSION["lvluser"] == ADMIN)
		$querygroup.=" and h.workgroup='GROUP_4_ALL' ";

	//calcul du nombre de machines par groupe
	$sql_nb_mach="SELECT count(*) nb, group_id
					from groups_cache gc,hardware h where h.id=gc.hardware_id ";
	if($_SESSION["lvluser"] == ADMIN)
			$sql_nb_mach.=" and gc.hardware_id in ".$mycomputors;		
	$sql_nb_mach .=" group by group_id";

}
$result = mysql_query($sql_nb_mach, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
while($item = mysql_fetch_object($result)){
	//on force les valeurs du champ "nombre" à l'affichage
	$tab_options['VALUE']['NBRE'][$item -> group_id]=$item -> nb;
}
	
//Modif ajoutée pour la prise en compte 
//du chiffre à rajouter dans la colonne de calcul
//quand on a un seul groupe et qu'aucune machine n'est dedant.
if (!isset($tab_options['VALUE']['NBRE']))
$tab_options['VALUE']['NBRE'][]=0;
//on recherche les groupes visible pour cocher la checkbox à l'affichage
if ($_POST['onglet'] == "STAT"){
	$sql="select id from hardware where workgroup='GROUP_4_ALL'";
	$result = mysql_query($sql, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
	while($item = mysql_fetch_object($result)){
		$_POST['check'.$item ->id]="check";
	}
}
//on ajoute un javascript lorsque l'on clic sur la visibilité du groupe pour tous
$tab_options['JAVA']['CHECK']['NAME']="NAME";
$tab_options['JAVA']['CHECK']['QUESTION']=$l->g(811);
$tab_options['FILTRE']=array('NAME'=>$l->g(679),'DESCRIPTION'=>$l->g(636));
//affichage du tableau
$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$querygroup,$form_name,100,$tab_options); 

//si super admin, on donne la possibilité d'ajouter un nouveau groupe statique	
if ($_SESSION["lvluser"]==SADMIN){
	echo "</td></tr></table>";	
	if ($_POST['onglet'] == "STAT")
		echo "<BR><input type='submit' name='add_static_group' value='".$l->g(587)."'>";
}

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
	
	echo "<br><br><table align='center' width='65%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
	echo "<tr>".$tdhd.$l->g(577).$tdhf.$tdhdpb.$name.$tdhfpb;
	echo "</tr>";
	echo $tdhd."</b></td><td  align='left' width='20%' colspan='3'>";
	echo "</tr><tr>".$tdhd.$l->g(53).$tdhf.$tdhdpb.$description.$tdhfpb;
	echo "<tr><td align='left' colspan=4>".$button_valid."&nbsp&nbsp".$button_reset."&nbsp&nbsp".$img_modif."</td></tr>";
	echo "$tdhfpb</table>";
	echo "<input type='hidden' id='add_static_group' name='add_static_group' value='BYHIDDEN'>";
}
//fermeture du formulaire
echo "</form>";
?>
