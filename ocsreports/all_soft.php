<?php
require_once('require/function_table_html.php');
echo "<script language=javascript>
		function garde_valeur(did,form_name,hidden_name){
				document.getElementById(hidden_name).value=did;
				document.getElementById(form_name).submit();
		}

</script>";

if ($_POST['RESET']) 
unset($_POST['search']);


//search all onglet
if( $_SESSION["lvluser"] == ADMIN ) {
	$sql_list_alpha ="select substr(trim(name),1,1) alpha from softwares,accountinfo a 
						where ".$_SESSION["mesmachines"]."
						and a.hardware_id=softwares.HARDWARE_ID ";
	if (isset($_POST['search']) and $_POST['search'] != "")
	$sql_list_alpha.=" and softwares.name like '%".$_POST['search']."%'";
			$sql_list_alpha.=" and substr(trim(name),1,1) is not null order by 1";	
}else{
	$sql_list_alpha="select substr(trim(name),1,1) alpha
						 from softwares_name_cache cache ";
	$sql_list_alpha.=" where substr(trim(name),1,1) is not null";	
	if (isset($_POST['search']) and $_POST['search'] != "")
	$sql_list_alpha.=" and name like '%".$_POST['search']."%'";
}

$result_list_alpha = mysql_query( $sql_list_alpha, $_SESSION["readServer"]);
 while($item_list_alpha = mysql_fetch_object($result_list_alpha)){
		if (!isset($_POST['onglet_bis']))
			$_POST['onglet_bis']=strtoupper($item_list_alpha -> alpha);
			$list_alpha[strtoupper($item_list_alpha -> alpha)]=strtoupper($item_list_alpha -> alpha);
			if (isset($first)){
				$first=$list_alpha[strtoupper($item_list_alpha -> alpha)];				
			}
		 }
if (!isset($list_alpha[$_POST['onglet_bis']]))
$_POST['onglet_bis']=$first;

$form_name = "all_soft";
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
 onglet($list_alpha,$form_name,"onglet_bis",20);
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
if (isset($_POST['search']) and $_POST['search'] != "")
echo "<font color=red size=3><b>".$l->g(767)."</b></font>";
if (isset($_POST["pcparpage"])){
	$limit=$_POST["pcparpage"];
	$deb_limit=$_POST['page']*$_POST["pcparpage"];
$fin_limit=$limit;
	
}
if( $_SESSION["lvluser"] == ADMIN ) {
	$reqCount="select  count(name) nb from softwares,accountinfo a 
			where ".$_SESSION["mesmachines"];
			if (isset($_POST['search']) and $_POST['search'] != "")
	$reqCount.=" and name like '%".$_POST['search']."%' ";
	$reqCount.=" and a.hardware_id=softwares.HARDWARE_ID
				and name like '".$_POST['onglet_bis']."%'";


$sql="select  name, count(name) nbre from softwares,accountinfo a 
			where ".$_SESSION["mesmachines"]."
				and a.hardware_id=softwares.HARDWARE_ID
				and name like '".$_POST['onglet_bis']."%'";
if (isset($_POST['search']) and $_POST['search'] != "")
	$sql.=" and name like '%".$_POST['search']."%' ";
$sql.="	group by name
		order by 2 desc  limit ".$deb_limit.",".$fin_limit;
	
	
}else{
	$reqCount="select count(distinct cache.name) nb
	from softwares,softwares_name_cache cache 
	where softwares.NAME=cache.name
	and cache.name like '".$_POST['onglet_bis']."%'";
	if (isset($_POST['search']) and $_POST['search'] != "")
	$reqCount.=" and cache.name like '%".$_POST['search']."%' ";

	
	$sql="select cache.name  name,count(cache.ID) nbre,cache.id
	from softwares,softwares_name_cache cache 
	where softwares.NAME=cache.name
	and cache.name like '".$_POST['onglet_bis']."%'";
	if (isset($_POST['search']) and $_POST['search'] != "")
		$sql.=" and cache.name like '%".$_POST['search']."%' ";
	$sql.="	group by cache.name
	order by 2 desc  limit ".$deb_limit.",".$fin_limit;
}
$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$valCount = mysql_fetch_array($resCount);

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
echo "<br><input type='input' name='search' value='".$_POST['search']."'>
		<input type='submit' value='".$l->g(393)."'><input type='submit' value='".$l->g(396)."' name='RESET'>";
echo "<input type='hidden' id='page' name='page' value=''>";
 echo "</form></table>";
?>
