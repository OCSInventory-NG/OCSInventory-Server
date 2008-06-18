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
	//var for update number
	$nbr_maj=0;
	foreach($_POST['check'] as $key=>$value){
		//cas of we have an ID or a name of soft in chekbox (use cache or not?)
		if (is_numeric($value)){
			$search_name_begin="select NAME";
			$search_name_end=" from softwares_name_cache where ID=".$value."";
		
		}else{
			//add by Inferno
			$value=str_replace("'","\'",$value);
			//end add
			$search_name_begin="'".$value."'";
			$search_name_end="";
		}
						
					//cas of CATEGORY
					if ($onglet == "CAT"){
						//cas of custom category
						if ($nom_new_cat != "IGNORED" and $nom_new_cat != "UNCHANGED")
							$sql_cat="update dico_soft set formatted='".$nom_new_cat."' where extracted = (".$search_name_begin.$search_name_end.")";
						//cas of IGNORED category
						elseif ($nom_new_cat == "IGNORED"){
							//first time, we insert into dico_ignored
							$reqDelDico_soft="insert into dico_ignored (extracted) ";
							if (!is_numeric($value))
							$reqDelDico_soft.= "values (".$search_name_begin.$search_name_end.")";
							else
							$reqDelDico_soft.= $search_name_begin.$search_name_end;
							mysql_query($reqDelDico_soft, $_SESSION["writeServer"]);	
							//second time, we delete from dico_soft
							$sql_cat = "DELETE FROM dico_soft WHERE extracted = (".$search_name_begin.$search_name_end.")";					
						}
						//cas of UNCHANGED category
						elseif ($nom_new_cat == "UNCHANGED")
						//we update dico_soft
						$sql_cat="update dico_soft set formatted=extracted where extracted = (".$search_name_begin.$search_name_end.")";
						mysql_query($sql_cat, $_SESSION["writeServer"]);	
						$nbr_maj++;
					}		
					//cas of NEW			
					if ($onglet ==  "NEW"){
						//if cat new category is UNCHANGED							
						if ($nom_new_cat == "UNCHANGED"){							
							$sql_cat="insert into dico_soft (extracted,formatted) ";
							if (!is_numeric($value))
							$sql_cat.= " values (".$search_name_begin.",".$search_name_begin.")";
							else
							$sql_cat.=$search_name_begin.",NAME ".$search_name_end;
						//if cat new category is IGNORED		
						}elseif ($nom_new_cat == "IGNORED")
						$sql_cat="insert dico_ignored (extracted) select NAME from softwares_name_cache where ID=".$value;
						//if it's custom category
						else{
							$sql_cat="insert into dico_soft (extracted,formatted) ";
							if (!is_numeric($value))
							$sql_cat.= " values (".$search_name_begin.",'".$nom_new_cat."')";
							else
							$sql_cat.=$search_name_begin.",'".$nom_new_cat."' ".$search_name_end;
						}
						mysql_query($sql_cat, $_SESSION["writeServer"]);	
						$nbr_maj++;				
					}
					//cas of IGNORED
					if ($onglet ==  "IGNORED"){
						$reqInsertDicoSoft="insert into dico_soft (extracted,formatted) ";
						//if cat new category is UNCHANGED							
						if ($nom_new_cat == "UNCHANGED"){
							if (!is_numeric($value))
								$reqInsertDicoSoft.= " values (".$search_name_begin.",".$search_name_begin.")";
							else
								$reqInsertDicoSoft.=$search_name_begin.",NAME ".$search_name_end;
						}else{
							if (!is_numeric($value))
								$reqInsertDicoSoft.= " values (".$search_name_begin.",'".$nom_new_cat."')";
							else
								$reqInsertDicoSoft.=$search_name_begin.",'".$nom_new_cat."' ".$search_name_end;
						}
						mysql_query($reqInsertDicoSoft, $_SESSION["writeServer"]);
						$reqDelDico_ignored = "DELETE FROM dico_ignored WHERE extracted = (".$search_name_begin.$search_name_end.")";
						mysql_query($reqDelDico_ignored, $_SESSION["writeServer"]);	
						$nbr_maj++;
					}
					//cas of UNCHANGED
					if ($_POST['onglet'] ==  "UNCHANGED"){
						if ($nom_new_cat != "IGNORED")
							$sql_cat="update dico_soft set formatted='".$nom_new_cat."' where extracted = (".$search_name_begin.$search_name_end.")";
						else{
							$reqInsertDicoIgnored="insert into dico_ignored (extracted) ";
							if (!is_numeric($value))
								$reqInsertDicoIgnored.= " values (".$search_name_begin.")";
							else
								$reqInsertDicoIgnored.=$search_name_begin.$search_name_end;
							mysql_query($reqInsertDicoIgnored, $_SESSION["writeServer"]);
							$sql_cat = "DELETE FROM dico_soft WHERE extracted = (".$search_name_begin.$search_name_end.")";						
						}
						mysql_query($sql_cat, $_SESSION["writeServer"]);	
						$nbr_maj++;
					}
	}
	return $l->g(770)." ".$nbr_maj." ".$l->g(20)."(s)"; //mise � jour de XXX logiciel(s)
}

function maj_trans_all($onglet,$nom_new_cat,$search_cache,$search_count){
	global $_POST,$l;

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
		if ($_SESSION["usecache"] == 1){
			$table_cache="softwares_name_cache";
		}else{ //non utilisation du cache
			$table_cache="softwares";
		}
		if ($nom_new_cat == "UNCHANGED")
			$nom_champ = "NAME";
		else
			$nom_champ = "'".$nom_new_cat."'";	
		if ($nom_new_cat != "IGNORED")
			$sql_cat="insert into dico_soft (extracted,formatted) select distinct NAME,".$nom_champ." from ".$table_cache." cache where NAME like '".$_POST['onglet_bis']."%'".$search_cache;					
		elseif($nom_new_cat == "IGNORED")
			$sql_cat="insert into dico_ignored (extracted) select distinct NAME from ".$table_cache." cache where NAME like '".$_POST['onglet_bis']."%'".$search_cache;	
		echo $sql_cat."<br>";
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
