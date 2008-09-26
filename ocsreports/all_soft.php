<?php
require_once('require/function_table_html.php');
require_once('require/function_config_generale.php');
if ($_POST['RESET']){ 
unset($_POST['search']);
unset($_POST['NBRE']);
}
if ($_POST['OLD_ONGLET'] != $_POST['onglet_bis'])
$_POST['page']=0;

//search all onglet
if( $_SESSION["lvluser"] == ADMIN) {
	$sql_list_alpha ="select substr(trim(name),1,1) alpha, name ";
	if (isset($_POST['NBRE']) and $_POST['NBRE'] != "")
	$sql_list_alpha .=",count(*) nb ";
	$sql_list_alpha .=" from softwares,accountinfo a 
						where ".$_SESSION["mesmachines"]."
						and a.hardware_id=softwares.HARDWARE_ID and";			
}else{
	$sql_list_alpha="select substr(trim(name),1,1) alpha,name ";
	if (isset($_POST['NBRE']) and $_POST['NBRE'] != "")
	$sql_list_alpha.=" ,count(*) nb ";
	$sql_list_alpha.= " from ";
	//BEGIN use CACHE
	if ($_SESSION["usecache"] == 1 
		and !(isset($_POST['NBRE']) and $_POST['NBRE'] != "") 
		and !(isset($_POST['search']) and $_POST['search'] != ""))
	$sql_list_alpha.="softwares_name_cache where ";
	else
	$sql_list_alpha.="softwares where ";
}
if (isset($_POST['search']) and $_POST['search'] != "")
	$sql_list_alpha.=" softwares.name like '%".$_POST['search']."%' and";
$sql_list_alpha.=" substr(trim(name),1,1) is not null group by name ";	
	if (isset($_POST['NBRE']) and $_POST['NBRE'] != "")
	$sql_list_alpha.=" having nb ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
	$sql_list_alpha.=" order by 1";

//execute the query only if necessary 
if($_SESSION['REQ_ONGLET_SOFT'] != $sql_list_alpha or !isset($_POST['onglet_bis'])){
	$result_list_alpha = mysql_query( $sql_list_alpha, $_SESSION["readServer"]);
 	while($item_list_alpha = mysql_fetch_object($result_list_alpha)){
 		if (strtoupper($item_list_alpha -> alpha) != "" 
			and strtoupper($item_list_alpha -> alpha) != Ã
			and strtoupper($item_list_alpha -> alpha) != Â
			and strtoupper($item_list_alpha -> alpha) != Ä){
				if (!isset($_POST['onglet_bis']))
					$_POST['onglet_bis']=strtoupper($item_list_alpha -> alpha);
				$list_alpha[strtoupper($item_list_alpha -> alpha)]=strtoupper($item_list_alpha -> alpha);
				if (!isset($first)){
					$first=$list_alpha[strtoupper($item_list_alpha -> alpha)];				
				}
 		}
	}
	
	if (!isset($list_alpha[str_replace('\"','"',$_POST['onglet_bis'])])){
		$_POST['onglet_bis']=$first;
	}
	$_SESSION['REQ_ONGLET_SOFT']= $sql_list_alpha;
	$_SESSION['ONGLET_SOFT']=$list_alpha;
}
$form_name = "all_soft";
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
 onglet($_SESSION['ONGLET_SOFT'],$form_name,"onglet_bis",20);
 $limit=nb_page($form_name);
if ((isset($_POST['search']) and $_POST['search'] != "") or
	((isset($_POST['NBRE']) and $_POST['NBRE'] != "")))
echo "<font color=red size=3><b>".$l->g(767)."</b></font>";

//sql query for CSV export 
$sql_csv="";
$sql_filter_name="";
if( $_SESSION["lvluser"] == ADMIN ) {
	$sql="select  name, count(name) nbre from softwares,accountinfo a 
			where ".$_SESSION["mesmachines"]."
				and a.hardware_id=softwares.HARDWARE_ID";
	$sql_csv=$sql;
	$sql_filter=" and name like '".$_POST['onglet_bis']."%'";
	if (isset($_POST['search']) and $_POST['search'] != "")
		$sql_filter_name=" and name like '%".$_POST['search']."%' ";
	$sql_groupby="	group by name";
	$sql.=$sql_filter.$sql_filter_name.$sql_groupby;
	$sql_csv.=$sql_filter_name.$sql_groupby;	
}else{
	//BEGIN use CACHE
	if ($_SESSION["usecache"] == 1){
		$search_soft="select name from softwares_name_cache 
				where name like '".$_POST['onglet_bis']."%'";
		if (isset($_POST['search']) and $_POST['search'] != "")
		$search_soft.=" and name like '%".$_POST['search']."%' ";
		$result_search_soft = mysql_query( $search_soft, $_SESSION["readServer"]);
		$list_soft="'";
		$count_soft=0;
	
	 	while($item_search_soft = mysql_fetch_object($result_search_soft)){
	 		$list_soft.=str_replace("'","\'",$item_search_soft -> name)."','";
	  		$count_soft++;
	 	}
	 	$list_soft=substr($list_soft,0,-2);
	 	if ($list_soft == "")
	 	$list_soft="''";
	
		$sql="select name, count(name) nbre from softwares 
				where name in (".$list_soft.")
				group by name";
	//END use CACHE
	}else{
		$sql="select  name, count(name) nbre from softwares 
			where name like '".$_POST['onglet_bis']."%'";
		if (isset($_POST['search']) and $_POST['search'] != "")
			$sql.=" and name like '%".$_POST['search']."%' ";
		$sql.="	group by name";
	}
	$sql_csv="select name, count(name) nbre from softwares 
			group by name";
			
}
if (isset($_POST['NBRE']) and $_POST['NBRE'] != ""){
	$sql.=" having nbre ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
	$sql_csv.=" having nbre ".$_POST['COMPAR']." ".$_POST['NBRE']." ";
}

if ((!isset($count_soft) or $count_soft == 0) or (isset($_POST['NBRE']) and $_POST['NBRE'] != "" )){
	$reqCount="select count(*) nb from (".$sql.") toto";
	$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$valCount = mysql_fetch_array($resCount);
	$count_soft=$valCount['nb'];
}

$sql.="	order by 2 desc limit ".$limit['BEGIN'].",".$limit['END'];
$_SESSION["forcedRequest"]=$sql_csv;
$result = mysql_query( $sql, $_SESSION["readServer"]);
$num_rows_reality = mysql_num_rows($result);
	$i=0;
	while($colname = mysql_fetch_field($result)){
		if ($colname->name != 'id')
		$entete[$i++]=$colname->name;
	}
	
	$i=0;
	while($item = mysql_fetch_object($result)){
		if ($num_rows_reality != $num_rows_soft)
		$view_ok[$item ->name]=$item ->name;
		$data[$i][$entete[0]]=$deb.$item ->name.$fin;
		$data[$i][$entete[1]]=$item ->nbre;
		$i++;
		}

	$titre=$l->g(768)." ".$count_soft;
	$width=60;
	$height=300;
	tab_entete_fixe($entete,$data,$titre,$width,$height);
	show_page($count_soft,$form_name);
	




echo "<br><div align=center><table bgcolor='#66CCCC'><tr><td colspan=2 align=center >FILTRES</td></tr><tr><td align=right>".$l->g(382).": <input type='input' name='search' value='".$_POST['search']."'>
				<td rowspan=2><input type='submit' value='".$l->g(393)."'><input type='submit' value='".$l->g(396)."' name='RESET'>
		</td></tr><tr><td align=right>nbre <select name='COMPAR'>
			<option value='<' ".($_POST['COMPAR'] == '<'?'selected':'')."><</option>
			<option value='>' ".($_POST['COMPAR'] == '>'?'selected':'').">></option>
			<option value='=' ".($_POST['COMPAR'] == '='?'selected':'').">=</option>
		</select><input type='input' name='NBRE' value='".$_POST['NBRE']."' ".$numeric."></td></tr>
		<tr><td colspan=2 align=center><a href='ipcsv.php'>".$l->g(136)." ".$l->g(765)."</a></td></tr></table></div>
		";

echo "<input type='hidden' name='OLD_ONGLET' value='".$_POST['onglet_bis']."'>";
 echo "</form></table>";
?>