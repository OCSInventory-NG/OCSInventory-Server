<?php
require_once('require/function_table_html.php');
require_once('require/function_config_generale.php');
echo "<script language=javascript>
		function garde_valeur(did,form_name,hidden_name){
				document.getElementById(hidden_name).value=did;
				document.getElementById(form_name).submit();
		}

</script>";

if ($_POST['RESET']){ 
unset($_POST['search']);
unset($_POST['NBRE']);
}

//search all onglet
if( $_SESSION["lvluser"] == ADMIN) {
	$sql_list_alpha ="select substr(trim(name),1,1) alpha, name,count(*) nb from softwares,accountinfo a 
						where ".$_SESSION["mesmachines"]."
						and a.hardware_id=softwares.HARDWARE_ID and";			
}else{
	$sql_list_alpha="select substr(trim(name),1,1) alpha,count(*)nb,name from softwares where";
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
 if (!(isset($_POST["pcparpage"])))
 $_POST["pcparpage"]=20;
 echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td align=center>";
$machNmb = array(5,10,15,20,50,100);
$pcParPageHtml = $l->g(340).": <select name='pcparpage' onChange='document.".$form_name.".submit();'>";
foreach( $machNmb as $nbm ) {
	$pcParPageHtml .=  "<option".($_POST["pcparpage"] == $nbm ? " selected" : "").($countHl%2==1?" class='hi'":"").">$nbm</option>";
	$countHl++;
}
$pcParPageHtml .=  "</select></td></tr><tr><td align=center>";
echo $pcParPageHtml;
if ((isset($_POST['search']) and $_POST['search'] != "") or
	((isset($_POST['NBRE']) and $_POST['NBRE'] != "")))
echo "<font color=red size=3><b>".$l->g(767)."</b></font>";
if (isset($_POST["pcparpage"])){
	$limit=$_POST["pcparpage"];
	$deb_limit=$_POST['page']*$_POST["pcparpage"];
$fin_limit=$limit;
	
}
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
 	while($item_search_soft = mysql_fetch_object($result_search_soft)){
 		$list_soft.=str_replace("'","\'",$item_search_soft -> name)."','";
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
$reqCount="select count(*) nb from (".$sql.") toto";

$sql.="	order by 2 desc limit ".$deb_limit.",".$fin_limit;
$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$valCount = mysql_fetch_array($resCount);
$_SESSION["forcedRequest"]=$sql_csv;
$result = mysql_query( $sql, $_SESSION["readServer"]);
	$i=0;
	while($colname = mysql_fetch_field($result)){
		if ($colname->name != 'id')
		$entete[$i++]=$colname->name;
	}
	
	$i=0;
	while($item = mysql_fetch_object($result)){
		
		$data[$i][$entete[0]]=$deb.$item ->name.$fin;
		$data[$i][$entete[1]]=$item ->nbre;
		$i++;
		}
	$titre=$l->g(768)." ".$valCount['nb'];
	$width=60;
	$height=300;
	tab_entete_fixe($entete,$data,$titre,$width,$height);
	if (isset($_POST["pcparpage"]) and $_POST["pcparpage"] != 0)
	$nbpage= ceil($valCount['nb']/$_POST["pcparpage"]);
if ($nbpage >1){
	$up=$_POST['page']+1;
	$down=$_POST['page']-1;
	echo "</tr><tr><td align=center>";
	if ($_POST['page'] > 0)
	echo "<img src='image/prec24.png' OnClick='garde_valeur(\"".$down."\",\"".$form_name."\",\"page\")'>";
	//if ($nbpage<10){
		$i=0;
		while ($i<$nbpage){
			if ($_POST['page'] == $i)
			echo "<font color=red>".$i."</font> ";
			else
			echo "<a OnClick='garde_valeur(\"".$i."\",\"".$form_name."\",\"page\")''>".$i."</a> ";
			$i++;
		}

//	}else{
//		
//		
//	}
	if ($_POST['page']< $nbpage-1)
	echo "<img src='image/proch24.png' OnClick='garde_valeur(\"".$up."\",\"".$form_name."\",\"page\")'>";
	
}
echo "<br><table bgcolor='#66CCCC'><tr><td colspan=2 align=center >FILTRES</td></tr><tr><td align=right>".$l->g(382).": <input type='input' name='search' value='".$_POST['search']."'>
				<td rowspan=2><input type='submit' value='".$l->g(393)."'><input type='submit' value='".$l->g(396)."' name='RESET'>
		</td></tr><tr><td align=right>nbre <select name='COMPAR'>
			<option value='<' ".($_POST['COMPAR'] == '<'?'selected':'')."><</option>
			<option value='>' ".($_POST['COMPAR'] == '>'?'selected':'').">></option>
			<option value='=' ".($_POST['COMPAR'] == '='?'selected':'').">=</option>
		</select><input type='input' name='NBRE' value='".$_POST['NBRE']."' ".$numeric."></td></tr>
		<tr><td colspan=2 align=center><a href='ipcsv.php'>".$l->g(136)." ".$l->g(765)."</a></td></tr></table>
		";
echo "<input type='hidden' id='page' name='page' value=''>";
 echo "</form></table>";
?>