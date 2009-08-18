<?php
//variable d'export de tous les logiciels
//=>$_SESSION["forcedRequest"]

if ($_POST['RESET']){ 
	unset($_POST['search']);
	unset($_POST['NBRE']);
	unset($_POST['CLASS']);
}

if ($_POST['SUBMIT_FORM'])
$tab_options['CACHE']='RESET';

$sql_fin="";

$sql_list_alpha ="select substr(trim(name),1,1) alpha, name ";
if (isset($_POST['NBRE']) and $_POST['NBRE'] != "" and isset($_POST['COMPAR']) and $_POST['COMPAR'] != ""){
	$sql_list_alpha .=",count(*) nb ";	
	$sql_fin=" having nb ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
}
$sql_list_alpha .=" from ";
$and_where="";
if ($_SESSION["usecache"] == 1  and $_POST['NBRE'] == ""){
	$sql_list_alpha .=" softwares_name_cache left join dico_soft on dico_soft.extracted=softwares_name_cache.name ";
	$and_where=" where ";
}else{
	$sql_list_alpha .=" softwares left join dico_soft on dico_soft.extracted=softwares.name ";
	if ($_SESSION["mesmachines"] != ""){
		$sql_list_alpha .=",accountinfo a where ".$_SESSION["mesmachines"]." and a.hardware_id=softwares.HARDWARE_ID ";
		$and_where=" and ";
	}else
	$and_where=" where ";
}
$sql_list_alpha .=$and_where." substr(trim(name),1,1) is not null ";
if (isset($_POST['search']) and $_POST['search'] != "")
	$sql_list_alpha .=" and name like '%".$_POST['search']."%' ";
if (isset($_POST['CLASS']) and $_POST['CLASS'] != ""){
		$sql_list_alpha.=" and (dico_soft.formatted in ('".implode("','",$list_soft_by_statut[$_POST['CLASS']])."') ".$sql_default." ) ";		
	}
$sql_list_alpha .=" group by name ".$sql_fin;

//execute the query only if necessary 
if($_SESSION['REQ_ONGLET_SOFT'] != $sql_list_alpha or !isset($_POST['onglet'])){
	$result_list_alpha = mysql_query( $sql_list_alpha, $_SESSION["readServer"]);
 	while($item_list_alpha = mysql_fetch_object($result_list_alpha)){
 		if (strtoupper($item_list_alpha -> alpha) != "" 
			and strtoupper($item_list_alpha -> alpha) != Ã
			and strtoupper($item_list_alpha -> alpha) != Â
			and strtoupper($item_list_alpha -> alpha) != Ä){
				if (!isset($_POST['onglet']))
					$_POST['onglet']=strtoupper($item_list_alpha -> alpha);
				$list_alpha[strtoupper($item_list_alpha -> alpha)]=strtoupper($item_list_alpha -> alpha);
				if (!isset($first)){
					$first=$list_alpha[strtoupper($item_list_alpha -> alpha)];				
				}
 		}
	}
	
	if (!isset($list_alpha[$_POST['onglet']])){
		$_POST['onglet']=$first;
	}
	$_SESSION['REQ_ONGLET_SOFT']= $sql_list_alpha;
	$_SESSION['ONGLET_SOFT']=$list_alpha;
}
$form_name = "all_soft";
$table_name="all_soft";
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
 onglet($_SESSION['ONGLET_SOFT'],$form_name,"onglet",20);

if ((isset($_POST['search']) and $_POST['search'] != "") or
	((isset($_POST['NBRE']) and $_POST['NBRE'] != "")))
echo "<font color=red size=3><b>".$l->g(767)."</b></font>";


//utilisation du cache
if ($_SESSION["usecache"] == 1){
	$search_soft="select name from softwares_name_cache left join dico_soft on dico_soft.extracted=softwares_name_cache.name ";
	$forcedRequest=$search_soft;
	$search_soft.=" where name like '".$_POST['onglet']."%'";
	$and_where=" where ";
	if (isset($_POST['search']) and $_POST['search'] != ""){
		$forcedRequest.= $and_where." name like '%".$_POST['search']."%' ";
		$search_soft.=" and name like '%".$_POST['search']."%' ";		
		$and_where=" and ";
	}
	if (isset($_POST['CLASS']) and $_POST['CLASS'] != ""){
	//	$fin_sql=" and dico_soft.extracted is not null ";
		$forcedRequest.= $and_where." (dico_soft.formatted in ('".implode("','",$list_soft_by_statut[$_POST['CLASS']])."') ".$sql_default." ) and ";
		$search_soft.=" and (dico_soft.formatted in ('".implode("','",$list_soft_by_statut[$_POST['CLASS']])."') ".$sql_default." ) ";		
	}
	//echo $search_soft;
	$result_search_soft = mysql_query( $search_soft, $_SESSION["readServer"]);
	$list_soft="";
	//$count_soft=0;
	while($item_search_soft = mysql_fetch_object($result_search_soft)){
		$list_soft[]=mysql_escape_string($item_search_soft->name);	
		//$count_soft++;	
	}
}
if ($list_soft != ""){
	$and_where="";
	$sql="select  name , count(name) nb from softwares ";

	if (isset($_SESSION["mesmachines"]) and $_SESSION["mesmachines"] != ''){
		$sql.=",accountinfo a where ".$_SESSION["mesmachines"]." and a.hardware_id=softwares.HARDWARE_ID";
		$and_where=" and ";
	}else
	$and_where=" where ";	
	$_SESSION["forcedRequest"]=$sql.$and_where." name in (".$forcedRequest.")";
	$sql.=$and_where." name in ('".implode("','",$list_soft)."')";
	//$sql.=$fin_sql;
}else{
	$and_where="";
	$sql="select  name, count(name) nb from softwares ";
	if (isset($_SESSION["mesmachines"]) and $_SESSION["mesmachines"] != ''){
		$sql.=",accountinfo a where ".$_SESSION["mesmachines"]." and a.hardware_id=softwares.HARDWARE_ID";
		$and_where=" and ";
	}else
	$and_where=" where ";
	$_SESSION["forcedRequest"]=$sql;
	$sql.=$and_where." name like '".$_POST['onglet']."%'";
	if (isset($_POST['search']) and $_POST['search'] != ""){
		$sql.=" and name like '%".$_POST['search']."%' ";	
		$_SESSION["forcedRequest"].=$and_where."name like '%".$_POST['search']."%'" ;
	}
}

if (isset($sql)){
	$sql.=" group by name";
	$_SESSION["forcedRequest"].=" group by name";
	if ($sql_fin != ''){
	$sql.=$sql_fin;
	$_SESSION["forcedRequest"].=$sql_fin;
	}
	$list_fields= array('name'=>'name',
						'nbre'=>'nb'
						);
	$default_fields= $list_fields;
	$list_col_cant_del=$default_fields;
	//echo $sql;
	$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$sql,$form_name,80,$tab_options); 
}

echo "<br><div align=center>
<table bgcolor='#66CCCC'><tr><td colspan=2 align=center >".$l->g(735)."</td></tr><tr><td align=right>".$l->g(382).": <input type='input' name='search' value='".$_POST['search']."'>
				<td rowspan=2><input type='submit' value='".$l->g(393)."' name='SUBMIT_FORM'><input type='submit' value='".$l->g(396)."' name='RESET'>
		</td></tr><tr><td align=right>nbre <select name='COMPAR'>
			<option value='<' ".($_POST['COMPAR'] == '<'?'selected':'')."><</option>
			<option value='>' ".($_POST['COMPAR'] == '>'?'selected':'').">></option>
			<option value='=' ".($_POST['COMPAR'] == '='?'selected':'').">=</option>
		</select><input type='input' name='NBRE' value='".$_POST['NBRE']."' ".$numeric."></td></tr>";
		
	
	echo "<tr><td colspan=2 align=center><a href='ipcsv.php'>".$l->g(136)." ".$l->g(765)."</a></td></tr>";
if ($_POST['COMPAR'] == '<' and $_POST['NBRE']<=15 and $_POST['NBRE'] != "")
echo "<tr><td colspan=2 align=center><a href='exportallsoft.php'>".$l->g(912)."</a></td></tr>";
echo "</table></div>
		";
echo "</form>";
?>