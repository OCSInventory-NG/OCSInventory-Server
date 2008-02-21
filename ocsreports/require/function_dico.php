<?php

function choix_affect($nom_cat){
	global $_POST,$l;
	$behing='<font color=RED size=4>';
	$end='</font>';
	if ($_POST['AFFECT_TYPE'] == "NEW_CAT"){
		if (trim($_POST['NEW_CAT_edit']) != ""){
			$sql_verif_doublon="select EXTRACTED from dico_soft where extracted != formatted 
					and formatted = '".trim(str_replace("'", " ", $_POST['NEW_CAT_edit']))."'
					group by formatted";
			$result_verif_doublon = mysql_query( $sql_verif_doublon, $_SESSION["readServer"]);
			$val_doublon = mysql_fetch_array($result_verif_doublon);
			if (!(isset($val_doublon['EXTRACTED'])))
				$message['OK'] = str_replace("'", " ", $_POST['NEW_CAT_edit']);
			else
				$message['KO']=$behing.$l->g(771).$end;
		}
		else
			$message['KO']=$behing.$l->g(772).$end;
	}else{
		
		if (trim($_POST['EXIST_CAT_edit']) != "EMPTY"){
			if ($_POST['EXIST_CAT_edit'] == $nom_cat)
			$message['KO']=$behing.$l->g(774).$end;
			$message['OK']=$_POST['EXIST_CAT_edit'];
		}else
		$message['KO']=$behing.$l->g(773).$end;
	}
	return $message;
	
	
}

function maj_trans($onglet,$nom_new_cat){
	global $_POST,$l;
	$nbr_maj=0;
	foreach($_POST['check'] as $key=>$value){
					if ($onglet == "CAT"){
						if ($nom_new_cat != "IGNORED" and $nom_new_cat != "UNCHANGED")
						$sql_cat="update dico_soft set formatted='".$nom_new_cat."' where extracted = (select NAME from softwares_name_cache where ID=".$value.")";
						elseif ($nom_new_cat == "IGNORED"){
						$reqDelDico_soft="insert into dico_ignored (extracted) select NAME from softwares_name_cache where ID=".$value;
						mysql_query($reqDelDico_soft, $_SESSION["writeServer"]);	
						$sql_cat = "DELETE FROM dico_soft WHERE extracted = (select NAME from softwares_name_cache where ID=".$value.")";						
						}
						elseif ($nom_new_cat == "UNCHANGED")
						$sql_cat="update dico_soft set formatted=extracted where extracted = (select NAME from softwares_name_cache where ID=".$value.")";
						mysql_query($sql_cat, $_SESSION["writeServer"]);	
						$nbr_maj++;
					}					
					if ($onglet ==  "NEW"){
						if ($nom_new_cat == "UNCHANGED")
						$nom_champ = "NAME";
						else
						$nom_champ = "'".$nom_new_cat."'";				
						if ($nom_new_cat != "IGNORED")
						$sql_cat="insert into dico_soft (extracted,formatted) select NAME,".$nom_champ." from softwares_name_cache where ID=".$value;
						elseif ($nom_new_cat == "IGNORED")
						$sql_cat="insert dico_ignored (extracted) select NAME from softwares_name_cache where ID=".$value;
						mysql_query($sql_cat, $_SESSION["writeServer"]);	
						$nbr_maj++;				
					}
					if ($onglet ==  "IGNORED"){
						if ($nom_new_cat == "UNCHANGED")
						$nom_champ = "NAME";
						else
						$nom_champ = "'".$nom_new_cat."'";							
						$reqInsertDicoSoft="insert into dico_soft (extracted,formatted) select NAME,".$nom_champ." from softwares_name_cache where ID=".$value;
						mysql_query($reqInsertDicoSoft, $_SESSION["writeServer"]);
						$reqDelDico_ignored = "DELETE FROM dico_ignored WHERE extracted = (select NAME from softwares_name_cache where ID=".$value.")";
						mysql_query($reqDelDico_ignored, $_SESSION["writeServer"]);	
						$nbr_maj++;
					}
					if ($_POST['onglet'] ==  "UNCHANGED"){
						if ($nom_new_cat != "IGNORED")
							$sql_cat="update dico_soft set formatted='".$nom_new_cat."' where extracted = (select NAME from softwares_name_cache where ID=".$value.")";
						else{
							$reqInsertDicoIgnored="insert into dico_ignored (extracted) select NAME from softwares_name_cache where ID=".$value;
							mysql_query($reqInsertDicoIgnored, $_SESSION["writeServer"]);
							$sql_cat = "DELETE FROM dico_soft WHERE extracted = (select NAME from softwares_name_cache where ID=".$value.")";						
						}
						mysql_query($sql_cat, $_SESSION["writeServer"]);	
						$nbr_maj++;
					}
	}
	return $l->g(770)." ".$nbr_maj." ".$l->g(20)."(s)"; //mise à jour de XXX logiciel(s)
}
// $search_cache=" and cache.name like '%".$_POST['search']."%' ";
//	$search_count=" and extracted like '%".$_POST['search']."%' ";
//	}
//	else{
//		$search="";
//		$search_count = "";
function maj_trans_all($onglet,$nom_new_cat,$search_cache,$search_count){
	global $_POST,$l;
	//filtre
//	if ($_POST['search']){
//		$search_cache=" and cache.name like '%".$_POST['search']."%' ";
//		$search_count=" and extracted like '%".$_POST['search']."%' ";
//	}
//	else{
//		$search="";
//		$search_count = "";
//	}
	if ($onglet == "CAT"){
		if ($nom_new_cat != "IGNORED" and $nom_new_cat != "UNCHANGED")
		$sql_cat="update dico_soft set formatted='".$nom_new_cat."' where formatted = '".$_POST['onglet_perso']."'".$search_count;
		elseif($nom_new_cat == "IGNORED"){
			$reqDelDico_soft="insert into dico_ignored (extracted) select extracted from dico_soft where formatted='".$_POST['onglet_perso']."'".$search_count;
			mysql_query($reqDelDico_soft, $_SESSION["writeServer"]);	
			$sql_cat = "DELETE FROM dico_soft WHERE formatted = '".$_POST['onglet_perso']."'".$search_count;
		}elseif ($nom_new_cat == "UNCHANGED")
		$sql_cat="update dico_soft set formatted=extracted where formatted = '".$_POST['onglet_perso']."'".$search_count;
		mysql_query($sql_cat, $_SESSION["writeServer"]);
	}
	if ($onglet == "NEW"){
		if ($nom_new_cat == "UNCHANGED")
			$nom_champ = "NAME";
		else
			$nom_champ = "'".$nom_new_cat."'";	
		if ($nom_new_cat != "IGNORED")
			$sql_cat="insert into dico_soft (extracted,formatted) select NAME,".$nom_champ." from softwares_name_cache cache where NAME like '".$_POST['onglet_bis']."%'".$search_cache;					
		elseif($nom_new_cat == "IGNORED")
			$sql_cat="insert into dico_ignored (extracted) select NAME from softwares_name_cache cache where NAME like '".$_POST['onglet_bis']."%'".$search_cache;	
		mysql_query($sql_cat, $_SESSION["writeServer"]);		
	}
	if ($onglet == "IGNORED"){
		//on rajoute le "where" et on enleve le "and"
		if ($search_count != "")
		$search_count = " where ".substr($search_count,4);
		
		if ($nom_new_cat == "UNCHANGED")
			$nom_champ = "extracted";
		else
			$nom_champ = "'".$nom_new_cat."'";	
		$sql_cat="insert into dico_soft (extracted,formatted) select extracted,$nom_champ from dico_ignored".$search_count;					
		mysql_query($sql_cat, $_SESSION["writeServer"]);	
		$sql_cat="DELETE FROM dico_ignored".$search_count;		
		mysql_query($sql_cat, $_SESSION["writeServer"]);			
	}
	if ($onglet == "UNCHANGED"){
		if ($nom_new_cat != "IGNORED")
		$sql_cat="update dico_soft set formatted= '".$nom_new_cat."' where formatted=extracted".$search_count;
		else{
			$sqlInsertDicoIgnored="insert into dico_ignored (extracted) select extracted from dico_soft where formatted=extracted".$search_count;	
			mysql_query($sqlInsertDicoIgnored, $_SESSION["writeServer"]);	
			$sql_cat="DELETE FROM dico_soft where formatted=extracted".$search_count;				
		}					
		mysql_query($sql_cat, $_SESSION["writeServer"]);		
			
	}
	return $l->g(769);		
}
?>
