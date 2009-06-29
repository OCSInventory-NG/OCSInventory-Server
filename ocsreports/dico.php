<?php 
/*
 * New version of dico page 
 * 
 */
require_once('require/function_table_html.php');
require_once('require/function_dico.php');
//use or not cache
if ($_SESSION['usecache'])
	$table="softwares_name_cache";
else
	$table="softwares";
//form name
$form_name='admin_param';
//form open
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//definition of onglet
$def_onglets['CAT']='CATEGORIES'; //Categories
$def_onglets['NEW']='NEW'; //nouveau logiciels
$def_onglets['IGNORED']='IGNORED'; //ignoré
$def_onglets['UNCHANGED']='UNCHANGED'; //unchanged
//défault => first onglet
if ($_POST['onglet'] == "")
$_POST['onglet']="CAT";
//reset search
if ($_POST['RESET']=="RESET")
unset($_POST['search']);
//filtre
if ($_POST['search']){
	$search_cache=" and cache.name like '%".$_POST['search']."%' ";
	$search_count=" and extracted like '%".$_POST['search']."%' ";
}
else{
	$search="";
	$search_count = "";
}
//show first lign of onglet
onglet($def_onglets,$form_name,"onglet",0);
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>
<tr><td align='center' colspan=10>";
//attention=> result with restriction
if ($search_count != "" or $search_cache != "")
echo "<font color=red><b>".$l->g(767)."</b></font>";
/**************************************ACTION ON DICO SOFT**************************************/

//transfert soft
if($_POST['TRANS'] == "TRANS"){	
	if ($_POST['all_item'] != ''){
		$list_check=search_all_item($_POST['onglet'],$_POST['onglet_soft']);
	}else{
		
		foreach ($_POST as $key=>$value){
			if (substr($key, 0, 5) == "check"){
				$list_check[]=substr($key, 5);
			} 				
		}
	}
	if ($list_check != '')	
	trans($_POST['onglet'],$list_check,$_POST['AFFECT_TYPE'],$_POST['NEW_CAT'],$_POST['EXIST_CAT']);	
}
//delete a soft in list => return in 'NEW' liste
if ($_POST['SUP_PROF'] != ""){
	del_soft($_POST['onglet'],array($_POST['SUP_PROF']));
}
/************************************END ACTION**************************************/

if ($_POST['onglet'] != $_POST['old_onglet'])
unset($_POST['onglet_soft']);
/*******************************************************CAS OF CATEGORIES*******************************************************/
if ($_POST['onglet'] == 'CAT'){
	//search all categories
	$sql_list_cat="select formatted  name
		  from dico_soft where extracted!=formatted ".$search_count." group by formatted";
	 $result_list_cat = mysql_query( $sql_list_cat, $_SESSION["readServer"]);
	 $i=1;
	 while($item_list_cat = mysql_fetch_object($result_list_cat)){
	 	if ($i==1)
		$first_onglet=$i;
		$list_cat[$i]=$item_list_cat -> name;
		$i++;
	 }
	 //delete categorie
	if(isset($_POST['SUP_CAT']) and $_POST['SUP_CAT']!=""){	
		if ($_POST['SUP_CAT'] == 1)
		$first_onglet=2;
		$reqDcat = "DELETE FROM dico_soft WHERE formatted='".$list_cat[$_POST['SUP_CAT']]."'";
		mysql_query($reqDcat, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		unset($list_cat[$_POST['SUP_CAT']]);		
	}
	//no selected? default=>first onglet
	 if ($_POST['onglet_soft']=="" or !isset($list_cat[$_POST['onglet_soft']]))
	 $_POST['onglet_soft']=$first_onglet;
	 //show all categories
	 onglet($list_cat,$form_name,"onglet_soft",7);
	 //You can delete or not?
	 if ($i != 1 and isset($list_cat[$_POST['onglet_soft']]))
	 echo "<a href=# OnClick='return confirme(\"\",\"".$_POST['onglet_soft']."\",\"".$form_name."\",\"SUP_CAT\",\"".$l->g(640)."\");'><img src=image/supp.png></a></td></tr><tr><td>";
	$list_fields= array('SOFT_NAME'=>'EXTRACTED',
						'ID'=>'ID',
						'SUP'=>'ID',
						'CHECK'=>'ID'
								);
	$table_name="CAT_EXIST";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT distinct ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$querydico .= $value.',';		
	} 
	$querydico=substr($querydico,0,-1);
	$querydico .= " from dico_soft left join ".$table." cache on dico_soft.extracted=cache.name
			 where formatted='".$list_cat[$_POST['onglet_soft']]."' ".$search_count." group by EXTRACTED";
}
/*******************************************************CAS OF NEW*******************************************************/
if ($_POST['onglet'] == 'NEW'){
	$search_dico_soft="select extracted name from dico_soft";
	$result_search_dico_soft = mysql_query( $search_dico_soft, $_SESSION["readServer"]);
	$list_dico_soft="'";
	while($item_search_dico_soft = mysql_fetch_object($result_search_dico_soft)){
		$list_dico_soft.=$item_search_dico_soft -> name."','";
	}
	$list_dico_soft=substr($list_dico_soft,0,-2);
	
	if($list_dico_soft == "")
		$list_dico_soft="''";
		
	$search_ignored_soft="select extracted name from dico_ignored";
	$result_search_ignored_soft = mysql_query( $search_ignored_soft, $_SESSION["readServer"]);
	$list_ignored_soft="'";
	while($item_search_ignored_soft = mysql_fetch_object($result_search_ignored_soft)){
		$list_ignored_soft.=addslashes($item_search_ignored_soft -> name)."','";
	}
	$list_ignored_soft=substr($list_ignored_soft,0,-2);
	
	if($list_ignored_soft == "")
	$list_ignored_soft="''";

	$sql_list_alpha="select distinct substr(trim(name),1,1) alpha
				 from ".$table." cache 
				 where substr(trim(name),1,1) is not null and name not in (".$list_dico_soft.")
			and name not in (".$list_ignored_soft.") ".$search_cache;	
	$first='';
	//execute the query only if necessary 
	if($_SESSION['REQ_ONGLET_SOFT'] != $sql_list_alpha){
		$result_list_alpha = mysql_query( $sql_list_alpha, $_SESSION["readServer"]);
		$i=1;
		 while($item_list_alpha = mysql_fetch_object($result_list_alpha)){
		 	if (strtoupper($item_list_alpha -> alpha) != "" 
				and strtoupper($item_list_alpha -> alpha) != Ã
				and strtoupper($item_list_alpha -> alpha) != Â
				and strtoupper($item_list_alpha -> alpha) != Ä){
					if ($first == ''){
						$first=$i;
					}
					$list_alpha[$i]=strtoupper($item_list_alpha -> alpha);
					$i++;
		 	}
		}
		//execute the query only if necessary 
		$_SESSION['REQ_ONGLET_SOFT'] = $sql_list_alpha;
		$_SESSION['ONGLET_SOFT'] = $list_alpha;
		$_SESSION['FIRST_DICO'] = $first;
	}else{
		$list_alpha=$_SESSION['ONGLET_SOFT'];
	}
	if (!isset($_POST['onglet_soft']))
	$_POST['onglet_soft']=$_SESSION['FIRST_DICO'];
	 onglet($list_alpha,$form_name,"onglet_soft",20);
	
	//search all soft for the tab as selected 
	$search_soft="select distinct name from ".$table." cache
			where name like '".$_SESSION['ONGLET_SOFT'][$_POST['onglet_soft']]."%'
			and name not in (".$list_dico_soft.")
			and name not in (".$list_ignored_soft.") ".$search_cache;
	$result_search_soft = mysql_query( $search_soft, $_SESSION["readServer"]);
	$list_soft="'";
 	while($item_search_soft = mysql_fetch_object($result_search_soft)){
		 		$list_soft.=addslashes($item_search_soft -> name)."','";
 	}
 	$list_soft=substr($list_soft,0,-2);
 	if ($list_soft == "")
 	$list_soft="''";

	$list_fields= array('SOFT_NAME'=>'NAME',
						'ID'=>'ID',
	 					 'QTE'=> 'QTE',
    					 'CHECK'=>'ID');
	$table_name="CAT_NEW";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','QTE'=>'QTE','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'CHECK' and $key != 'QTE')
		$querydico .= $value.',';		
		elseif ($key == 'QTE')
		$querydico .= ' count(NAME) as '.$value.',';
	} 
	$querydico=substr($querydico,0,-1);
	$querydico .= " from softwares 
			where name in (".$list_soft.") and name != ''
			group by name ";
}
/*******************************************************CAS OF IGNORED*******************************************************/
if ($_POST['onglet'] == 'IGNORED'){
	$list_fields= array('SOFT_NAME'=>'EXTRACTED',
						'ID'=>'ID',
						'SUP'=>'ID',
						'CHECK'=>'ID'
								);
	$table_name="CAT_IGNORED";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$querydico .= $value.',';		
	} 
	if ($search_count != ""){
		$modif_search = " where ".substr($search_count,5);
	}
	$querydico=substr($querydico,0,-1);
	$querydico .= " from dico_ignored left join ".$table." cache on cache.name=dico_ignored.extracted ".$modif_search." group by EXTRACTED ";
}
/*******************************************************CAS OF UNCHANGED*******************************************************/
if ($_POST['onglet'] == 'UNCHANGED'){
	$list_fields= array('SOFT_NAME'=>'EXTRACTED',
						'ID'=>'ID',
						'SUP'=>'ID',
						'CHECK'=>'ID'
								);
	$table_name="CAT_UNCHANGE";
	$default_fields= array('SOFT_NAME'=>'SOFT_NAME','SUP'=>'SUP','CHECK'=>'CHECK');
	$list_col_cant_del=array('SOFT_NAME'=>'SOFT_NAME','CHECK'=>'CHECK');
	$querydico = 'SELECT ';
	foreach ($list_fields as $key=>$value){
		if($key != 'SUP' and $key != 'CHECK')
		$querydico .= $value.',';		
	} 
	$querydico=substr($querydico,0,-1);
	$querydico .= " from dico_soft left join ".$table." cache on cache.name=dico_soft.extracted
	 	where extracted=formatted ".$search_cache." group by EXTRACTED ";
}
$_SESSION['query_dico']=$querydico;
$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$querydico,$form_name,80); 
echo "</td></tr>";
$search=show_modif(stripslashes($_POST['search']),"search",'0');
$trans= "<input name='all_item' id='all_item' type='checkbox' ".(isset($_POST['all_item'])? " checked ": "").">".$l->g(384);
//récupération de toutes les catégories
$sql_list_categories="select distinct(formatted) name from dico_soft where formatted!=extracted";
$result_list_categories = mysql_query( $sql_list_categories, $_SESSION["readServer"]);
while($item_list_categories = mysql_fetch_object($result_list_categories)){
	$list_categories[$item_list_categories ->name]=$item_list_categories ->name;	
}
//définition de toutes les options possible
$choix_affect['NEW_CAT']=$l->g(385);
$choix_affect['EXIST_CAT']=$l->g(387);
$list_categories['IGNORED']="IGNORED";
$list_categories['UNCHANGED']="UNCHANGED";
$trans.=show_modif($choix_affect,"AFFECT_TYPE",'2',$form_name);
if ($_POST['AFFECT_TYPE'] == 'EXIST_CAT'){
	$trans.=show_modif($list_categories,"EXIST_CAT",'2');	
	$verif_field="EXIST_CAT";
}
elseif ($_POST['AFFECT_TYPE'] == 'NEW_CAT'){
	$trans.=show_modif(stripslashes($_POST['NEW_CAT']),"NEW_CAT",'0');
	$verif_field="NEW_CAT";
}	

if ($_POST['AFFECT_TYPE']!='')
$trans.= "<input type='button' name='TRANSF' value='".$l->g(13)."' onclick='return verif_field(\"".$verif_field."\",\"TRANS\",\"".$form_name."\");'>";

echo "<tr><td>".$search."<input type='submit' value='".$l->g(393)."'><input type='button' value='".$l->g(396)."' onclick='return pag(\"RESET\",\"RESET\",\"".$form_name."\");'>";
if ($result_exist != FALSE)
echo "<div align=right> ".$trans."</div>";
echo "</td></tr></table></table>";
echo "<input type='hidden' name='RESET' id='RESET' value=''>";
echo "<input type='hidden' name='TRANS' id='TRANS' value=''>";
echo "<input type='hidden' name='SUP_CAT' id='SUP_CAT' value=''>";
echo "</form>";
?>