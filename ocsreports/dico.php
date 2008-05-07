<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2008-05-07 15:41:12 $$Author: airoine $($Revision: 1.17 $)
$form_name='admin_param';
require_once('require/function_table_html.php');
require_once('require/function_dico.php');
//more one onglet => must add verif for reset $_POST['page']
if ($_POST['old_onglet_perso'] != $_POST['onglet_perso'] or $_POST['old_onglet_bis'] != $_POST['onglet_bis'])
	$_POST['page']=0;
echo "<script language=javascript>
		function confirme(did,form_name,hidden_name){
			if(confirm('".$l->g(640)." '+did+'?')){
				garde_post(did,hidden_name,form_name)
			}
		}
		function active(id, sens) {
				var mstyle = document.getElementById(id).style.display	= (sens!=0?\"block\" :\"none\");
			}	

		function checkall()
		 {
			for(i=0; i<document.".$form_name.".elements.length; i++)
			{
			    if(document.".$form_name.".elements[i].name.substring(0,5) == 'check'){
			        if (document.".$form_name.".elements[i].checked)
						document.".$form_name.".elements[i].checked = false;
					else
						document.".$form_name.".elements[i].checked = true;
				}
			}
		}
		function garde_post(did,hidden_name,form_name){
				garde_valeur(did,hidden_name);
				post(form_name);
		}
</script>";

//definition of onglet
$def_onglets['CAT']='CATEGORIES'; //Categories
$def_onglets['NEW']='NEW'; //nouveau logiciels
$def_onglets['IGNORED']='IGNORED'; //ignoré
$def_onglets['UNCHANGED']='UNCHANGED'; //unchanged
if ($_POST['onglet'] == "")
$_POST['onglet']="CAT";
//reset search
if (isset($_POST['RESET']))
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
//delete categorie
if(isset($_POST['supp']) and $_POST['supp']!=""){	
	$reqDcat = "DELETE FROM dico_soft WHERE formatted='".$_POST['supp']."'";
	mysql_query($reqDcat, $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));	
	
}
//transfert soft
if($_POST['TRANSF'] == "TRANSF"){	
	if ($_POST['onglet'] ==  "CAT")
		$nom_cat=$_POST['onglet_perso'];
	if ($_POST['onglet'] ==  "NEW")
		$nom_cat="";
	if ($_POST['onglet'] ==  "IGNORED" or $_POST['onglet'] ==  "UNCHANGED")
		$nom_cat=$_POST['onglet'];
			
	if (isset($nom_cat)){
		$nom_champ=choix_affect($nom_cat);
		if (!isset($nom_champ['KO'])){
			if (!isset($_POST['all_item']) and isset($_POST['check']))
				$message=maj_trans($_POST['onglet'],$nom_champ['OK']);
			elseif(isset($_POST['all_item']))
				$message=maj_trans_all($_POST['onglet'],$nom_champ['OK'],$search_cache,$search_count);
		}else
		$message=$nom_champ['KO'];
	}
}
//form open
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
//show onglet
onglet($def_onglets,$form_name,"onglet",0);
 $limit=nb_page($form_name);

if ($search_count != "" or $search_cache != "")
echo "<font color=red><b>".$l->g(767)."</b></font>";
/*******************************************************CAS OF CATEGORIES*******************************************************/
if ($_POST['onglet'] == 'CAT' or !isset($_POST['onglet'])){

	$sql_list_cat="select formatted  name
		  from dico_soft where extracted!=formatted ".$search_count." group by formatted";
	 $result_list_cat = mysql_query( $sql_list_cat, $_SESSION["readServer"]);
	 $i=0;
	 while($item_list_cat = mysql_fetch_object($result_list_cat)){
	 	if ($i==0)
		$first_onglet=$item_list_cat -> name;
		$list_cat[$item_list_cat -> name]=$item_list_cat -> name;
		$i++;
	 }
	 if (!isset($list_cat[$_POST['onglet_perso']]))
	 $_POST['onglet_perso']=$first_onglet;
	 onglet($list_cat,$form_name,"onglet_perso",7);
	 if ($search_count == "")
	 echo "<a href=# OnClick='confirme(\"".$_POST['onglet_perso']."\",\"".$form_name."\",\"supp\");'><img src=image/supp.png></a></td></tr><tr><td>";
	 $reqCount="";
	
	$reqCount="select count(extracted) nb from dico_soft where formatted='".$_POST['onglet_perso']."'".$search_count;
	
	$sql="select extracted name,id from dico_soft left join softwares_name_cache cache on dico_soft.extracted=cache.name
			 where formatted='".$_POST['onglet_perso']."' ".$search_count."
	order by 1 desc  limit ".$limit['BEGIN'].",".$limit['END'];
	
 
 
}

/*******************************************************CAS OF NEW*******************************************************/
if ($_POST['onglet'] == 'NEW'){
	//Search all first letter for all tab
	
	//first: create list of soft for sql request 
	
	$search_dico_soft="select extracted name from dico_soft";
	$result_search_dico_soft = mysql_query( $search_dico_soft, $_SESSION["readServer"]);
	$list_dico_soft="'";
	while($item_search_dico_soft = mysql_fetch_object($result_search_dico_soft)){
		$list_dico_soft.=str_replace("'","\'",$item_search_dico_soft -> name)."','";
	}
	$list_dico_soft=substr($list_dico_soft,0,-2);
	
	if($list_dico_soft == "")
		$list_dico_soft="''";
		
	$search_ignored_soft="select extracted name from dico_ignored";
	$result_search_ignored_soft = mysql_query( $search_ignored_soft, $_SESSION["readServer"]);
	$list_ignored_soft="'";
	while($item_search_ignored_soft = mysql_fetch_object($result_search_ignored_soft)){
		$list_ignored_soft.=str_replace("'","\'",$item_search_ignored_soft -> name)."','";
	}
	$list_ignored_soft=substr($list_ignored_soft,0,-2);
	
	if($list_ignored_soft == "")
	$list_ignored_soft="''";
	
	//utilisation du cache	
	if ($_SESSION["usecache"] == 1){
		$table_cache="softwares_name_cache";
	}else{ //non utilisation du cache
		$table_cache="softwares";
	}
	$sql_list_alpha="select substr(trim(name),1,1) alpha
				 from ".$table_cache." cache 
				 where substr(trim(name),1,1) is not null and name not in (".$list_dico_soft.")
			and name not in (".$list_ignored_soft.")".$search_cache;	
	 $result_list_alpha = mysql_query( $sql_list_alpha, $_SESSION["readServer"]);
	 $i=0;
//execute the query only if necessary 
if($_SESSION['REQ_ONGLET_SOFT'] != $sql_list_alpha or !isset($_POST['onglet_bis'])){
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
	if (!isset($list_alpha[str_replace('\"','"',$_POST['onglet_bis'])]))
	$_POST['onglet_bis']=$first;
	//execute the query only if necessary 
	$_SESSION['REQ_ONGLET_SOFT']= $sql_list_alpha;
	$_SESSION['ONGLET_SOFT']=$list_alpha;
}
	
	//search all soft for the tab as selected 
	$search_soft="select name from ".$table_cache." cache
			where name like '".$_POST['onglet_bis']."%'
			and name not in (".$list_dico_soft.")
			and name not in (".$list_ignored_soft.") ".$search_cache;
	$result_search_soft = mysql_query( $search_soft, $_SESSION["readServer"]);
	$list_soft="'";
	$nb_debut=$limit['BEGIN'];
	$nb_fin=$limit['END']+$nb_debut;
	$count_soft=0;
	$num_rows_soft=0;
 	while($item_search_soft = mysql_fetch_object($result_search_soft)){
 		if ($count_soft < $nb_fin and $count_soft>=$nb_debut){
		 		$list_soft.=str_replace("'","\'",$item_search_soft -> name)."','";
		 		$name_verif[$item_search_soft -> name]=$item_search_soft -> name;
		 		$nb_debut++;
		 		$num_rows_soft++;
		 } 	
		 $count_soft++;
 	}
 	$list_soft=substr($list_soft,0,-2);
 	if ($list_soft == "")
 	$list_soft="''";

	$sql="select name, count(name) nbre from softwares 
			where name in (".$list_soft.") and name != ''
			group by name
			order by 1 ";
}

/*******************************************************CAS OF IGNORED*******************************************************/
if ($_POST['onglet'] == 'IGNORED'){
	$reqCount="select count(extracted) nb from dico_ignored";
	//on enlève le AND et on met le where devant
	if ($search_count != "")
	$modif_search = " where ".substr($search_count,4);
	$reqCount.=$modif_search;
	$sql="select extracted name, cache.id 
			from dico_ignored left join softwares_name_cache cache on cache.name=dico_ignored.extracted 
			".$modif_search."
		 limit ".$limit['BEGIN'].",".$limit['END'];

}

/*******************************************************CAS OF UNCHANGED*******************************************************/
if ($_POST['onglet'] == 'UNCHANGED'){
	$reqCount="select count(extracted) nb from dico_soft where extracted=formatted".$search_count;
	$sql="select extracted name, cache.id
		 from dico_soft left join softwares_name_cache cache on cache.name=dico_soft.extracted
	 	where extracted=formatted ".$search_cache."
		limit ".$limit['BEGIN'].",".$limit['END'];
}
if (!isset($count_soft)){
	$resCount = mysql_query($reqCount, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$valCount = mysql_fetch_array($resCount);
}else
$valCount['nb']=$count_soft;
$result = mysql_query( $sql, $_SESSION["readServer"]);
$num_rows_reality = mysql_num_rows($result);
	$i=0;
	while($colname = mysql_fetch_field($result)){
		if ($colname->name != 'id')
		$entete[$i++]=$colname->name;
	}
		$entete[$i]="SELECT<input type='checkbox' name='ALL' id='ALL' Onclick='checkall();'>";
	$i=0;
	while($item = mysql_fetch_object($result)){
		if ($num_rows_reality != $num_rows_soft)
		$view_ok[$item ->name]=$item ->name;
		$data[$i][$entete[0]]=$item ->name;
		if ($_POST['onglet'] == 'NEW' )
		$data[$i][$entete[1]]=$item ->nbre;
		$data[$i][$entete[2]]="<input type='checkbox' name='check[]' value='".(isset($item ->id)?$item ->id:str_replace("'","\'",$item -> name))."' id='".$i."'>";
		$i++;
		}
	if ($num_rows_reality != $num_rows_soft and $_POST['onglet'] == 'NEW'){
		if (isset($name_verif)){
			foreach ($name_verif as $key){
				if (!isset($view_ok[$key])){
				$data[$i][$entete[0]]=$key;
				$data[$i][$entete[1]]="0";
				$data[$i][$entete[2]]="<input type='checkbox' onclick='return false'>";
				$i++;
				}	
			}
			$expl_cache="<font color=green>".$l->g(812)."</font>";
		}
		
	}	
	$titre=$l->g(768)." ".$valCount['nb'];
	$width=60;
	$height=300;
if ($_POST['onglet'] == 'NEW'){
	if (isset($expl_cache))
echo $expl_cache;
		 onglet($_SESSION['ONGLET_SOFT'],$form_name,"onglet_bis",20);
}
	tab_entete_fixe($entete,$data,$titre,$width,$height);

show_page($valCount['nb'],$form_name);

echo "<input type='hidden' id='supp' name='supp' value=''>";
echo "<input type='hidden' id='detail' name='detail' value=''>";
echo "<input type='hidden' id='TRANSF' name='TRANSF' value=''>";
echo "</td></tr>";


/******************************************CHAMP DE TRANSFERT********************************************/
//récupération de toutes les catégories
$sql_list_categories="select distinct(formatted) name from dico_soft where formatted!=extracted";
$result_list_categories = mysql_query( $sql_list_categories, $_SESSION["readServer"]);
$list_categories['EMPTY']="";

while($item_list_categories = mysql_fetch_object($result_list_categories)){
	$list_categories[$item_list_categories ->name]=$item_list_categories ->name;	
}
//définition de toutes les options possible
$choix_affect['EMPTY']="";
$choix_affect['NEW_CAT']=$l->g(385);
$choix_affect['EXIST_CAT']=$l->g(387);
$list_categories['IGNORED']="IGNORED";
$list_categories['UNCHANGED']="UNCHANGED";
echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td align='right'>";
echo "<input name='all_item' id='all_item' type='checkbox' >
		".$l->g(384);
echo"<select name='AFFECT_TYPE' style='background-color:white;'>";
$countHl=0;
foreach ($choix_affect as $key=>$value){	
	echo "<option value='".$key."' OnClick=\"active('NEW_CAT_div',";
	if ($key == "NEW_CAT") 	echo "1";
	else echo "0";
	echo ");active('EXIST_CAT_div',";
	if ($key == "EXIST_CAT") 	echo "1";
	else echo "0";
	echo ");\"";
	echo ($countHl%2==1?"":"class='hi'").">".$value."</option>";
$countHl++;
}
echo "</select>";
echo "</td><td align=left><div id='NEW_CAT_div' style='display:none'>
		<input type='text' size='20' maxlength='20' id='NEW_CAT_edit' name='NEW_CAT_edit' style='background-color:white;'>
		<input type='button' name='TRANSF' value='".$l->g(13)."' onclick='garde_post(\"TRANSF\",\"TRANSF\",\"".$form_name."\")'>
	</div>";
echo "</td><td><div id='EXIST_CAT_div' style='display:none'>";
echo "<select name='EXIST_CAT_edit' style='background-color:white;'>";
$countHl=0;
foreach ($list_categories as $key=>$value){	
	echo "<option value='".$key."' ";
	if ($_POST['NEW_CAT_SELECT'] == $key )
	echo " selected";
	echo ($countHl%2==1?"":"class='hi'").">".$value."</option>";
	$countHl++;
}

echo "</select><input type='button' name='TRANSF' value='".$l->g(13)."' onclick='garde_post(\"TRANSF\",\"TRANSF\",\"".$form_name."\")'></div>";
echo "</td></tr>";
/******************************************SEARCH BUTTON********************************************/
echo "<tr><td>
	<input name='search' id='search' value='".$_POST["search"]."' style='background-color:white;'>
	<input type='Submit' name='valid_search' value='";
echo $l->g(393)."'>";
echo "<input type='Submit' value='".$l->g(396)."' name='RESET'></td>";
//fermeture des tableaux et du formulaire
echo "</tr></table></table></form>";


?>