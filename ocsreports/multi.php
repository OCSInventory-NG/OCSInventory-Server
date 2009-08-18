<?php
/*
 * Nouvelle recherche multicritère
 * avec utilisation du cache
 * et possibilité de multivaluer les champs
 * 
 */
//limite du nombre de résultat
//sur les tables de cache
//ex: software_name_cache, osname_cache...

$limit_result_cache=1000;
//intégration des fonctions liées à la recherche multicritère
require_once('require/function_search.php');
//fonction machines
require_once('require/function_computors.php');
//nom du formulaire de la page
$form_name='multisearch';
//nom du tableau d'affichage
$table_tabname="TAB_MULTICRITERE";	
//cas où l'on arrive d'une autre page
//ex: la page des stats
//$_SESSION['DEBUG'] = 'ON';
if (isset($_GET['prov'])){
	unset($_POST);
	foreach ($_SESSION as $key=>$value){
		$valeur=explode("-", $key); 
		if ($valeur[0] == "InputValue" or $valeur[0] == "SelFieldValue" or $valeur[0] == "SelFieldValue3"	or $valeur[0] == "SelAndOr" or $valeur[0] == "SelComp" )
			unset($_SESSION[$key]);
	}
	if ($_GET['prov'] == "stat"){
		$tab_session[]="DEVICES-DOWNLOAD";	
		$tab_stat=array('SelComp-DEVICES-DOWNLOAD-0'=>"exact",'SelFieldValue-DEVICES-DOWNLOAD-0'=>$_GET['id_pack'],'SelFieldValue2-DEVICES-DOWNLOAD-0'=>$_GET['stat']);//unset($_SESSION);
	}
	if ($_GET['prov'] == "ipdiscover" or $_GET['prov'] == "ipdiscover1"){
		$tab_session[]="NETWORKS-IPSUBNET";	
		$tab_stat['SelComp-NETWORKS-IPSUBNET-0']="exact";
		$tab_stat['InputValue-NETWORKS-IPSUBNET-0']=$_GET['value'];//unset($_SESSION);
	}
	if ($_GET['prov'] == "ipdiscover1"){
		$tab_session[]="DEVICES-IPDISCOVER";
		$tab_session[]="DEVICES-IPDISCOVER";	
		$tab_stat['SelComp-DEVICES-IPDISCOVER-1'] = "exact";
		$tab_stat['SelFieldValue-DEVICES-IPDISCOVER-1']="1";
		$tab_stat['SelAndOr-DEVICES-IPDISCOVER-2']=	"OR";
		$tab_stat['SelComp-DEVICES-IPDISCOVER-2'] = "exact";
		$tab_stat['SelFieldValue-DEVICES-IPDISCOVER-2']="2";
	}
	if (isset($tab_stat)){
		unset($_SESSION['multiSearch']);
		foreach ($tab_session as $key=>$value)
			$_SESSION['multiSearch'][]=$value;
			
		foreach ($tab_stat as $key=>$value)
			$_POST[$key]=$value;		
		$_POST['Valid-search']=$l->g(30);
		$_POST['multiSearch'] = $l->g(32);
		$_POST['Valid']=1;
	}	
}
//initialisation du tableau
//$list_fields_calcul=array();
//ouverture du formulaire
echo "<form name='".$form_name."' id='".$form_name."' action='index.php?multi=".$_GET['multi']."' method='post'>";

//recherche des différents champs de accountinfo
$field_of_accountinfo=witch_field_more(array('ACCOUNTINFO'=>1));
$j=0;
while($field_of_accountinfo['ACCOUNTINFO'][$j]){
	
	if ($field_of_accountinfo['ACCOUNTINFO'][$j] == "TAG"){
		$list_fields_account_info[TAG_LBL]="a.".$field_of_accountinfo['ACCOUNTINFO'][$j];
		$optaccountinfo['ACCOUNTINFO-'.$field_of_accountinfo['ACCOUNTINFO'][$j]]="Tag: ".TAG_LBL;
	}else{
		$optaccountinfo['ACCOUNTINFO-'.$field_of_accountinfo['ACCOUNTINFO'][$j]]="Tag: ".$field_of_accountinfo['ACCOUNTINFO'][$j];
		$list_fields_account_info["Tag: ".$field_of_accountinfo['ACCOUNTINFO'][$j]]="a.".$field_of_accountinfo['ACCOUNTINFO'][$j];
	}
	$j++;
}
//si on ajoute un champ de recherche
//on efface les données précedemment en cache
if ($_POST['delfield']!="" or $_POST['multiSearch'] != $l->g(32)){
	unset($_POST['Valid-search']);
	unset($_SESSION['ID_REQ']);
 	unset($_SESSION['DATA_CACHE'][$table_tabname]);
}
//cas d'une suppression de machine
if ($_POST['SUP_PROF'] != ''){	
	deleteDid($_POST['SUP_PROF']);
	//on force la valeur cachée de la validation du formulaire 
	//pour rejouer la requete et ne pas utiliser le cache
	$_POST['Valid']="SUP";
}
//for save field and value
if ($_POST['Valid-search'] and $_POST['Valid'] != ''){
	foreach ($_POST as $key=>$value){
		$valeur=explode("-", $key); 
		if ($valeur[0] == "InputValue" or $valeur[0] == "SelFieldValue" or $valeur[0] == "SelFieldValue3"	or $valeur[0] == "SelAndOr" or $valeur[0] == "SelComp")
		{	$_SESSION[$key]=$value;
		}
	}
}else{
	foreach ($_SESSION as $key=>$value){
		$valeur=explode("-", $key); 
		if ($valeur[0] == "InputValue" or $valeur[0] == "SelFieldValue" or $valeur[0] == "SelFieldValue3"	or $valeur[0] == "SelAndOr" or $valeur[0] == "SelComp" )
			$_POST[$key]=$value;
	}
	
}

 if ($_POST['multiSearch'] != '' and $_POST['multiSearch'] != $l->g(32))
{
	$_SESSION['multiSearch'][]=$_POST['multiSearch'];
	arsort($_SESSION['multiSearch']);
}

 //cas de la réinitialisation
if ($_POST['reset'] != ""){
	unset($_SESSION['ID_REQ']);
 	unset ($_SESSION['multiSearch']);
 	unset($_SESSION['DATA_CACHE'][$table_tabname]);
 	unset ($_POST);
}

if ($_POST['delfield'] != ""){
unset ($_SESSION['multiSearch'][$_POST['delfield']]);
}
 
 //une recherche est demandée sur des critères
 //pas d'utilisation de cache
 //bouton de validation actionné
 if ($_POST['Valid-search'] and $_POST['Valid'] != ''){
 	unset($_SESSION['SQL_DATA_FIXE']);
 	unset($_SESSION['ID_REQ']);
 	$sqlRequest_Group="";
 	//on commence par déomposer tous les poste pour
 	//définir les différentes tables, champs de recherche, valeur à rechercher
 	$i=0;
 	//parcourt du tableau de POST
 	foreach ($_POST as $key=>$value){
 		//on récupère uniquement les POST qui nous intéressent
 		if ($key != 'Valid-search' and $key != 'multiSearch'){
 			//en fonction du nom de la variable, on arrive a savoir quel est la recherche demandée
 			$valeur=explode("-", $key); 
 		
 			if ($valeur[0] == "InputValue" 
 					  or $valeur[0] == "SelFieldValue")
 				{
 				//en position 1 du tableau, on a toujours le nom de la table sur laquelle s'effectue la recherche
 				$table[$i]=$valeur[1];
 				//en position 2 du tableau, on a toujours le nom du champ sur lequel on effectue la recherche
 				$field[$i]=$valeur[2];
 				//en position 3 on a le numéro du champ.
 				$fieldNumber[$i]=$valeur[3];
 				//on récupère l'élément de comparaison
 				$field_compar[$i]=$_POST["SelComp-".$table[$i]."-".$field[$i]."-".$fieldNumber[$i]];
 				
 				//si le champ de saisi est à vide, on annule la recherche sur ce champ
 				if ($value == ''){
 					unset($table[$i]);
 					unset($field[$i]);
 					unset($field_compar[$i]);
 					unset($fieldNumber[$i]);
 				}else{ //sinon, on la prend en compte	
		 				//en fonction de la valeur en position 0, on sait quelle genre de recherche on doit effecuter
	 				//si on a un SelComp, on récupère la valeur saisie
		 			if ($valeur[0] == "InputValue" or $valeur[0] == "SelFieldValue"){ 				
 						$field_value[$i]=$value;
 						//on vérifie que le premier champ d'une recherche multicritère
 						//ou l'on a plusieur fois le même champ n'est pas vide
 						//ex:  3 * le champ IP ADD mais avec le premier champ vide.
 						//		on se retrouve donc avec un champ AND/OR sur le deuxième champ IP ADD
 						//		qu'il ne faut pas prendre en compte
 						if ($i!=0){
 							$k=$i;
 							//on regarde si dans les champs précédent on a bien
 							//le même champ pour traiter le champ AND/OR
 							while ($k>0){
 								if ($table[$k] == $table[$i] and $field[$k] == $field[$i]){
 									$field_and_or[$i]=$_POST["SelAndOr-".$table[$i]."-".$field[$i]."-".$fieldNumber[$i]];
 																
 								}
 								$k--;
 							}		 							
 						} 						
 						if (isset($_POST[$valeur[0]."2-".$table[$i]."-".$field[$i]."-".$fieldNumber[$i]]))
 						$field_value_complement[$i]=$_POST[$valeur[0]."2-".$table[$i]."-".$field[$i]."-".$fieldNumber[$i]];
 						elseif (isset($_POST["SelFieldValue3-".$valeur[1]."-".$field[$i]."-".$fieldNumber[$i]])){
 						$field_value_complement[$i]=$_POST["SelFieldValue3-".$table[$i]."-".$field[$i]."-".$fieldNumber[$i]];
 						}
 		 			}		
 		 			$i++; 				
		 		}
		 			
 			}
	 			
	 				
	 				
	 				
	 	}
 	}

if ($_SESSION['DEBUG'] == 'ON'){
 	echo "<font color=black align=center>".$l->g(5009)."<table align=center border=2><tr><td>table=</td>";
 	if (isset($table)){
 		foreach($table as $key=>$value)
 		echo "<td>".$key."</td><td>".$value."</td>";
 	}
 	echo "</tr><tr><td>".$l->g(5010)."</td>";
	 	if (isset($field)){
	 	foreach($field as $key=>$value)
	 	echo "<td>".$key."</td><td>".$value."</td>";
 	}
 	echo "</tr><tr><td>".$l->g(5011)."</td>";
 	if (isset($field_compar)){
	 	foreach($field_compar as $key=>$value)
	 	echo "<td>".$key."</td><td>".$value."</td>";
 	}
 	echo "</tr><tr><td>".$l->g(5012)."</td>";
 	if (isset($field_value)){
	 	foreach($field_value as $key=>$value)
	 	echo "<td>".$key."</td><td>".$value."</td>";
 	}
 	echo "</tr><tr><td>".$l->g(5013)."</td>";
 	if (isset($field_value_complement)){
	 	foreach($field_value_complement as $key=>$value)
	 	echo "<td>".$key."</td><td>".$value."</td>";
 	}
 	echo "</tr><tr><td>".$l->g(5014)."</td>";
 	if (isset($field_and_or)){
	 	foreach($field_and_or as $key=>$value){
	 		if ($value != '')
	 			echo "<td>".$key."</td><td>".$value."</td>";
	 	}
 	}
 	echo "</tr></table></font>";
}
 	$i=0;
 	//tableau des requêtes à executer
 	//qui est contruit au fur et a mesure
	$sql_search=array();
 	while ($table[$i]){
 		//initialisation de la variable des requêtes temporaires
 		$sql_temp="";
 		if ($field_compar[$i] == "" and substr($field_value[$i],0,4) != "ALL_")
 		$field_compar[$i] = "exact";
 		//traitement du champ de comparaison
		switch ($field_compar[$i]) {
					case "exact":
						$field_compar[$i]=" = ";
						$field_value[$i]="'".$field_value[$i]."'";
						break;
					case "ressemble":
						$field_compar[$i]=" like ";
						break;
					case "small":
						$field_compar[$i]=" < ";
						break;
					case "tall":
						$field_compar[$i]=" > ";
						break;
					case "diff":
						$field_compar[$i]=" like ";
						$field_compar_origine[$i]="diff";
						//la gestion de diff est particulière
						//et nécessite plus de code (voir plus loin dans le code)
						break;
				
					case "list":
						$field_compar[$i]= " IN ";
						$field_value[$i]=" (".$field_value[$i].")";
						break;
					case "notlist":
						$field_compar[$i]= " NOT IN ";
						$field_value[$i]=" (".$field_value[$i].")";
						break;
					default:
						$field_compar[$i]=" ".$field_compar[$i]." ";
		}
		
 		//Prise en compte des jockers sur le champ de saisie uniquement sur les champs de comparaison 'like'
		if ($field_compar[$i] == " like " and  $table[$i] != "DEVICES" and $field[$i] != 'DOWNLOAD')
 		$field_value[$i]=jockers_trait($field_value[$i]);
 		//traitement d'un champ quand c'est une date
 		$new_value=compair_with_date($field[$i],$field_value[$i]);
		$field[$i]=$new_value['field'];
		$field_value[$i]=$new_value['field_value'];
		//gestion de tous les linux et de tous les windows
 		if (substr($field_value[$i],0,4) == "ALL_" and $field[$i] == "OSNAME"){
	 		if ($field_value[$i] == "ALL_LINUX"){
	 		$sql_temp="select distinct osname from hardware where osname like '%Linux%'";
	 		}
	 		elseif($field_value[$i] == "ALL_WIN")
	 		$sql_temp="select distinct osname from hardware where osname like '%win%'";
	 		$result_temp = mysql_query( $sql_temp, $_SESSION["readServer"] );
			while( $val_temp = mysql_fetch_array($result_temp) ) {
				$list[]=addslashes($val_temp['osname']); 						
			}
			if (!isset($list)){
					$ERROR=$l->g(955);
			}else{
				$field_compar[$i]=" IN ";
				$field_value[$i]=" ('".implode("','",$list)."')";
				$field_modif="field_value";
			}
			unset($list);
 		}

		//traitement du cas particulier des recherches sur la table DEVICES
		//le champs de de comparaison ne se fait pas sur $field_value[$i]
		//le champs $field_compar doit donc se reporter sur le champs complémentaire
		if ($table[$i] == "DEVICES"){
			$original_field=$field[$i];
			$original_field_value_complement=$field_value_complement[$i];
 			$field_value_complement[$i]=$field_value[$i];
 			$field[$i]="NAME";
 			$field_value[$i] = "'".$original_field."'";
 			//traitement pour le télédéploiement		
 			if ($field_value[$i] == "'DOWNLOAD'"){
 				//on utilise pas le champ ivalue
 				unset($ivalue);
 				//requete pour trouver tous les ID 
 				//dans ce cas, le champ de recherche doit etre à null
				if ($original_field_value_complement == $l->g(482))
					$tvalue = " AND TVALUE IS NULL ";
				//gestion de TOUT SAUF SUCCESS
				elseif ($original_field_value_complement == "***".$l->g(548)."***")
					$tvalue=" AND TVALUE not like 'SUC%' ";
				//gestion de Toutes les erreurs
				elseif ($original_field_value_complement == "***".$l->g(956)."***")
					$tvalue=" AND TVALUE like 'ERR%' ";
				//gestion de TOUS LES SUCCESS
				elseif ($original_field_value_complement == "***".$l->g(957)."***")
					$tvalue=" AND TVALUE like 'SUC%' ";
				elseif ($original_field_value_complement == "***".$l->g(509)."***")
					$tvalue="";
				else
					$tvalue = " AND TVALUE = '".$original_field_value_complement."'";
				//echo $field_value_complement[$i];
				//recherche des id activés de ce paquet
				$sql_temp="select id from download_enable";
				if ($field_value_complement[$i] != "'NULL'" and 
						$field_value_complement[$i] != "NULL")
				 $sql_temp.=" where fileid=".$field_value_complement[$i];
				$result_temp = mysql_query( $sql_temp, $_SESSION["readServer"] );
				while( $val_temp = mysql_fetch_array($result_temp) ) {
						$list[]=addslashes($val_temp['id']); 						
					}
					//echo $sql_temp;
				if (!isset($list)){
					$ERROR=$l->g(958);
				}else{
					$field_value_complement[$i]=" IN ('".implode("','",$list)."')";
					$field_modif="field_value_complement";
				}
				
				unset($list);
 			}//gestion de la configuration des fréquences
 			elseif ($field_value[$i] == "'FREQUENCY'" or $field_value[$i] == "'IPDISCOVER'"){
 				
 				//on n'utilise pas le champs tvalue
 				unset($tvalue);
 				
 				if ( !strstr($field_value_complement[$i], 'DEFAULT')){
 					if ($field_value_complement[$i] != "'PERSO'") //gestion des cas normaux
	 				$field_value_complement[$i]=" = ".$field_value_complement[$i];
	 				else //gestion des valeurs de fréquences personnalisées
	 				$field_value_complement[$i]=" NOT IN ('0','-1')";
 				}elseif (strstr($field_value_complement[$i], 'DEFAULT')){
 					$type_default=explode ( 'DEFAULT', $field_value_complement[$i]);
	 				//si on demande la valeur DEFAULT de frequency,
		 			//on se retrouve a rechercher les ID des machines
		 			//dans la table hardware qui ne sont pas dans DEVICES avec
		 			//comme name='FREQUENCY'
		 			$sql_frequency="select hardware_id from devices where name=".$field_value[$i];
		 			if( isset($type_default[1]) and $type_default[1] != "'")
					$sql_frequency.=" and IVALUE = ".$type_default[1]{0};
		 			$result_frequency = mysql_query( $sql_frequency, $_SESSION["readServer"] );
		 			$list_frequency="";
					while( $val_frequency = mysql_fetch_array($result_frequency) ) {
						$list_frequency .=  $val_frequency['hardware_id'].',';
					}
					//on vide le champ de comparaison 
					//pour ne pas entrer dans la boucle de traitement
		 			$field_compar[$i]=" NOT IN ";
					//création de la fin de requête de recherche
					$field_value[$i] =" (".substr($list_frequency,0,-1).")";
					$field_modif="field_value";
					//la requete doit se faire sur la table hardware et sur le champ ID
					$table[$i]="HARDWARE";
					$field[$i]="ID";
					$field_value_complement[$i]="";
 				}
 			} 			
		}
		//utilisation du cache
		if (isset($table_cache)){
			//si on est sur une table de cache
			if ($table_cache[$table[$i]]){
				//on remet à zero le tableau de logiciels
				unset($list);
				//champ sur lequel s'effectue la recherche
				$field_temp=$field_cache[$table_cache[$table[$i]]];
				if ($field_temp == $field[$i]){
					$sql_temp="select ".$field_temp." as name from ".strtolower($table_cache[$table[$i]])." where ".$field_temp.$field_compar[$i].$field_value[$i];
					$result_temp = mysql_query( $sql_temp, $_SESSION["readServer"] );
					$count_result=0;
					while( $val_temp = mysql_fetch_array($result_temp) ) {
						$list[]=addslashes($val_temp['name']); 
						if ($limit_result_cache>$count_result)
						$count_result++;			
						else{
							$ERROR=$l->g(959);
							break;
						}			
					}
					if (!isset($list)){
						$ERROR=$l->g(960);
					}else{
						$field_compar[$i]=" IN ";
						$field_value[$i]=" ('".implode("','",$list)."')";
						$field_modif="field_value";
						unset($list);
					}
				}
			}
				
			
		}
		
		//gestion du champ complémentaire en fonction de la table
		//si le champs complémentaire existe
		if (isset($field_value_complement[$i]) and $field_value_complement[$i] != ""){
			switch ($table[$i]) {
						case "HARDWARE":
						//on est dans un cas de recherche entre 2 valeurs
							$field[$i]=$field[$i];
							$field_value_complement[$i]="AND ".$field[$i]." > '".$field_value_complement[$i]."'";
							$field_compar[$i]=" < ";
							break;
						case "DRIVES":
						//cas des partitions linux
							if (substr($field_value_complement[$i],0,1) == '/')
								$field_value_complement[$i]=" AND VOLUMN = '".$field_value_complement[$i]."' ";
							else
								$field_value_complement[$i]=" AND LETTER = '".$field_value_complement[$i]."' ";
							break;
						case "REGISTRY":
							$field_value_complement[$i]=" AND NAME = '".$field_value_complement[$i]."' ";
							break;
						case "DEVICES":
							$field_value_complement[$i]=" AND IVALUE ".$field_value_complement[$i].$tvalue;
							break;
						default:
						$ERROR= $l->g(5015).$table[$i];
			}
		}
		if ($_SESSION['DEBUG'] == 'ON'){
		echo "<font color=green><B><br><br>".$l->g(5016).$table[$i]."<br>".$l->g(5017).$field[$i]."<br>".$l->g(5018).$field_compar[$i]."<br>".$l->g(5019).$field_value[$i]."<br>".$l->g(5020).$field_value_complement[$i]."<br>".$l->g(5021).$field_and_or[$i]."</B></font>";	
		}
		//si une erreur a été rencontrée
		//le traitement est arrêté (gain de temps)
		if (isset($ERROR)){
		echo "<br><b><font color=red size=4>".$ERROR."</font></b><br>";
		break;
		}
		//si on est dans le cas d'une recherche sur "différent",
		//on va créer les requêtes dans le tableau $sql_seach['DIFF']
		if ($field_compar_origine[$i] == "diff")
		$operation="DIFF";
		else //autremant dans les autres cas, on va créer le tableau de requête dans $sql_seach['NORMAL']
		$operation="NORMAL";
		//recherche du dernier index de la derniere requete sur la table
		if (isset($sql_seach[$operation][$table[$i]])){
				foreach ($sql_seach[$operation][$table[$i]] as $index=>$poub)
				$k=$index;
		}else
		$k="";
		
		//gestion du champ AND OR dans les requetes
		if ($field_and_or[$i] == "" and $operation == "DIFF")
			$field_and_or[$i]="OR";
		if ($field_and_or[$i] == "" and $operation == "NORMAL")
			$field_and_or[$i]="AND";
					
		if ($field_and_or[$i] == "AND")		
		$field_and_or[$i]=" ) AND ( ";
		else
		$field_and_or[$i]=" OR ";
		
		
		//gestion de la non fusion des requêtes pour les tables définies
		//si on n'est pas dans le cas de "AND/OR" (deux fois le même champ)
		if (in_array ($table[$i], $tab_no_fusion) and ($field_and_or[$i] == "" or !isset($sql_seach[$operation][$table[$i]]))){
			$traitement=generate_sql($table[$i]);
			$sql_seach[$operation][$table[$i]][$i]=$traitement['sql_temp']." ( ".$field[$i].$field_compar[$i].$field_value[$i].$field_value_complement[$i];
			//si une requête intermédiaire a été jouée
			//il faut donc la prendre en compte pour la création des groupes
			$trait_cache=traitement_cache($sql_temp,$field_modif,$field_value[$i],$field_value_complement[$i]);
			$sql_cache[$operation][$table[$i]][$i]=$traitement['sql_cache']." ( ".$field[$i].$field_compar[$i].$trait_cache['field_value'].$trait_cache['field_value_complement'];
		}//si on est dans le cas "AND/OR", on concat les requêtes
		elseif(in_array ($table[$i], $tab_no_fusion) and $field_and_or[$i] != "" and isset($sql_seach[$operation][$table[$i]])){
				$sql_seach[$operation][$table[$i]][$k].= $field_and_or[$i].$field[$i].$field_compar[$i].$field_value[$i].$field_value_complement[$i];		
			$trait_cache=traitement_cache($sql_temp,$field_modif,$field_value[$i],$field_value_complement[$i]);
			$sql_cache[$operation][$table[$i]][$k].= $field_and_or[$i].$field[$i].$field_compar[$i].$trait_cache['field_value'].$trait_cache['field_value_complement'];		
			
		}//si on est dans un cas normal, on fusionne toutes les requêtes
		else{	
			//si la requête existe déjà	
			if (isset($sql_seach[$operation][$table[$i]])){
				//si le champ "AND/OR" est vide, on doit concat des champs différents de la même table
//				if ($field_and_or[$i] == "")
//					$field_and_or[$i]="AND";
				$sql_seach[$operation][$table[$i]][$k].= $field_and_or[$i].$field[$i].$field_compar[$i].$field_value[$i].$field_value_complement[$i];		
				$trait_cache=traitement_cache($sql_temp,$field_modif,$field_value[$i],$field_value_complement[$i]);
				$sql_cache[$operation][$table[$i]][$k].= $field_and_or[$i].$field[$i].$field_compar[$i].$trait_cache['field_value'].$trait_cache['field_value_complement'];			
			}//si la requête n'existe pas
			else{	
				//on la crée	
				$traitement=generate_sql($table[$i]);
				$sql_seach[$operation][$table[$i]][$i]=$traitement['sql_temp']." ( ".$field[$i].$field_compar[$i].$field_value[$i].$field_value_complement[$i];
				$trait_cache=traitement_cache($sql_temp,$field_modif,$field_value[$i],$field_value_complement[$i]);
				$sql_cache[$operation][$table[$i]][$i]=$traitement['sql_cache']." ( ".$field[$i].$field_compar[$i].$trait_cache['field_value'].$trait_cache['field_value_complement'];
			}
		}
		//stockage de la table sur laquelle on requete
		//pour afficher les champs correspondant
		$list_tables_request[$table[$i]]=$table[$i];
		//si une erreur a été rencontrée
		//le traitement est arrêté (gain de temps)
//		if (isset($ERROR)){
//			echo "ATTENTION: ERREUR ".$ERROR;
//			break;
//		}
		$i++;
 		
	}
	

$list_id="";
//traitement sur les requetes
//echo "<br><br>";
//		print_r_V2($sql_seach);
//		echo "<br><br>";
	//si un tableau de requête existe
 	if (isset($sql_seach)){
 		//on commence par traiter le cas normal
 		if (isset($sql_seach['NORMAL'])){
 			$execute_sql['NORMAL']=class_weight($sql_seach['NORMAL']);
 			$cache_sql['NORMAL']=class_weight($sql_cache['NORMAL']);
 		}
 		if (isset($sql_seach['DIFF'])){
 			$execute_sql['DIFF']=class_weight($sql_seach['DIFF']);
 			$cache_sql['DIFF']=class_weight($sql_cache['DIFF']);	
 		}
 	}
	
	//execution des requêtes
	//si l'utilisateur a des droits limités
	//restriction des id 
	 if ($_SESSION['mesmachines'] != "" and isset($_SESSION['mesmachines'])){
		$list_id_restraint=substr(substr(computor_list_by_tag(),1),0,-1);
	 }	
	
	 echo "<font color=red>";
	 if (isset($execute_sql['NORMAL'])){
	 	//print_r($execute_sql['NORMAL']);
		 if ($_SESSION['DEBUG'] == 'ON')
			 echo "<br><b>".$l->g(5022)."</b><br>";
		 $result=execute_sql_returnID($list_id_restraint,$execute_sql['NORMAL'],'',$table_tabname);
		// echo "toto";
		 $list_id_norm=$result[0];
		  if ($list_id_norm == "")
		  $no_result="YES";
		 $tab_options=$result[1];
	 }
	 if (isset($execute_sql['DIFF']) and $no_result != "YES"){
 	 	if ($_SESSION['DEBUG'] == 'ON')
	 		echo "<br><br><b>".$l->g(5023)."</b><br>";
	 	$result=execute_sql_returnID('',$execute_sql['DIFF'],'NO_CUMUL',$table_tabname);	
	 	$list_id_diff=$result[0];
	 	//print_r($tab_options);
//	 	if (isset($tab_options))
//	 	$tab_options=array_merge ($result[1],$tab_options);
//	 	else
//		$tab_options=$result[1];
	 }
	 echo "</font>";
	 //pour le traitement des champs
	 if ($list_id_diff != ""){
		 $sql="select distinct ID from hardware where ID NOT IN (".implode(',',$list_id_diff).")";	
		 if ($list_id_norm != ""){
		 $sql.=" AND ID IN (".implode(',',$list_id_norm).")";
		 }elseif ($list_id_restraint != ""){
		 	$sql.=" AND ID IN (".$list_id_restraint.")";
		 }
		 $result = mysql_query($sql, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
		// echo $sql;
		while($item = mysql_fetch_object($result))
		$list_id[]=$item->ID;
	 }else
	 $list_id=$list_id_norm;
	 $_SESSION['ID_REQ']=$list_id;
	// print_r($list_tables_request);
	 $_SESSION['list_tables_request'][$table_tabname]=$list_tables_request;
	 //passage en SESSION des requêtes pour les groupes dynamiques
	 sql_group_cache($cache_sql);
 }
 
 //Utilisation du cache pour éviter de rejouer la recherche
if (($_POST['Valid-search'] and $_POST['Valid'] == '')){
	 //	print_r($_SESSION['list_tables_request']);
	//recupération de la liste des ID
	$list_id=$_SESSION['ID_REQ'];
	//récupération des tables touchées par les requetes
	$list_tables_request=$_SESSION['list_tables_request'][$table_tabname];
	
}


//echo $list_id;
/********************************************AFFICHAGE DES RESULTATS********************************************/
if ($list_id != "")	{	
	$list_fields= array('Machine: id'=>'h.ID',
						'Machine: '.$l->g(46)=>'h.LASTDATE',
						"Machine: ".$l->g(820)=>'h.LASTCOME',
						'NAME'=>'h.NAME',
						"Machine: ".$l->g(24)=>'h.USERID',
						"Machine: ".$l->g(25)=>'h.OSNAME',
						"Machine: ".$l->g(357)=>'h.USERAGENT',
						"Réseau: ".$l->g(33)=>'h.WORKGROUP',						
						"Machine: ".$l->g(26)=>'h.MEMORY',
						"Machine: ".$l->g(569)=>'h.PROCESSORS',
						"Machine: ".$l->g(34)=>'h.IPADDR',
						"Machine: ".$l->g(53)=>'h.DESCRIPTION',
						"Machine: ".$l->g(354)=>'h.FIDELITY',					
						"Machine: ".$l->g(351)=>'h.PROCESSORN',
						"Machine: ".$l->g(350)=>'h.PROCESSORT',
						"Machine: ".$l->g(50)=>'h.SWAP',
						"Machine: ".$l->g(111)=>'h.WINPRODKEY',
						"Machine: ".$l->g(553)=>'h.WINPRODID');
	$list_fields=array_merge ($list_fields_account_info,$list_fields);
	$queryDetails = 'SELECT ';
	//changement de nom lors de la requete
	$tab_options['AS']['h.NAME']="name_of_machine";
	foreach ($list_fields as $key=>$value){
				$queryDetails .= $value;
				if ($tab_options['AS'][$value])
					$queryDetails .=" as ".$tab_options['AS'][$value];	
				$queryDetails .=",";	
	} 
	$queryDetails=substr($queryDetails,0,-1);
	$queryDetails .= " from hardware h left join accountinfo a on h.id=a.hardware_id ";
	$queryDetails .= " where ";
	$queryDetails .= "  h.deviceid <>'_SYSTEMGROUP_' AND h.deviceid <> '_DOWNLOADGROUP_' and  ";
	$queryDetails .= " h.id in (".implode(',',$list_id).") group by h.ID ";

	foreach ($list_tables_request as $table_name_4_field){
		if ($lbl_fields_calcul[$table_name_4_field]){
//			if ($table_name_4_field == "REGISTRY")
//			$tab_options['AS']['NAME']="name_of_registry";
			$list_fields=array_merge ($list_fields,$lbl_fields_calcul[$table_name_4_field]);
			
		}
	}
	ksort($list_fields);
	$list_fields['SUP']='h.ID';
	$list_fields['CHECK']='h.ID';
	$list_col_cant_del=array('SUP'=>'SUP','NAME'=>'NAME','CHECK'=>'CHECK');
	$default_fields=array(TAG_LBL=>TAG_LBL,'Machine: '.$l->g(46)=>'Machine: '.$l->g(46),"Machine: ".$l->g(820)=>"Machine: ".$l->g(820),'NAME'=>'NAME',"Machine: ".$l->g(24)=>"Machine: ".$l->g(24),"Machine: ".$l->g(25)=>"Machine: ".$l->g(25),"Machine: ".$l->g(357)=>"Machine: ".$l->g(357),'SUP'=>'SUP','CHECK'=>'CHECK');

	//print_r($list_fields);
	//on modifie le type de champs en numéric de certain champs
	//pour que le tri se fasse correctement
	//$tab_options['TRI']['SIGNED']['a.TAG']="a.TAG";

	//choix des fonctionnalitées pour les utilisateurs 
	$list_fonct["image/groups_search.png"]="Groupes";
	$list_fonct["image/sup_search.png"]="Supprimer";
	$list_fonct["image/cadena_ferme.png"]="Lock du résultat";
	if ($_SESSION["lvluser"] == SADMIN){
		$list_fonct["image/config_search.png"]="Configuration personnalisée";
		$list_fonct["image/tele_search.png"]="télédéployer";
	}
	//activation des LOGS	
	$tab_options['LOGS']='SEARCH_RESULT';
//print_r($tab_options);
	tab_req($table_tabname,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,'95',$tab_options);
	add_trait_select($list_fonct,$list_id,$form_name);
	echo "<input type='hidden' value='".$_POST['Valid-search']."' name='Valid-search'>";
}elseif($_POST['Valid-search'] != '')
$no_result="NO RESULT";

if ($no_result == "NO RESULT" and !isset($ERROR)){
	echo "<font color=RED size=5><div align=center>".$l->g(42)."</div></font>";
}

$aff_field_search= "<br>
<b>".$l->g(31).":&nbsp;&nbsp;&nbsp;</b>
<select name='multiSearch' OnChange=\"".$form_name.".submit();\">";
$countHl=0;

if ($_SESSION["mesmachines"] != '')
		$list_id_computor=computor_list_by_tag();
//pour tous les tableaux:
//TABLE-NOMCHAMP =>lbl du champ
//option: TABLE-NOMCHAMP-LBL => commentaire à ajouter après le champ de saisi

//composotion du tableau
// option: TABLE-NOMCHAMP-SELECT =>array des valeurs du champ select ou requete sql (affichage du select)
// si option absente le select affiche array('exact'=> 'EXACTEMENT','ressemble'=>'RESSEMBLE','diff'=>'DIFFERENT')
//a l'affichage on se retrouve avec le lbl du champ,un select et un champ de saisi
$optSelectField=array( "HARDWARE-IPADDR"=>$l->g(82).": ".$l->g(34),
			   "NETWORKS-MACADDR"=>$l->g(82).": ".$l->g(95),
			   "ACCOUNTINFO-TAG-SELECT"=>array("exact"=>$l->g(410),"list"=>$l->g(961)." ".TAG_LBL." ".$l->g(962),"notlist"=>$l->g(963)." ".TAG_LBL." ".$l->g(962)),
			   "SOFTWARES-NAME"=>$l->g(20).": ".$l->g(49),
			   "SOFTWARES-VERSION"=>$l->g(20).": ".$l->g(277),
			   "HARDWARE-DESCRIPTION"=>$l->g(25).": ".$l->g(53),
			   "HARDWARE-USERDOMAIN"=>$l->g(82).": ".$l->g(557),
			   "BIOS-BVERSION"=>$l->g(273).": ".$l->g(209),
			   "HARDWARE-USERID"=>$l->g(243).": ".$l->g(49),
			   "HARDWARE-OSCOMMENTS"=>$l->g(25).": ".$l->g(51),
			   "NETWORKS-IPGATEWAY"=>$l->g(82).": ".$l->g(207),
			   "NETWORKS-IPSUBNET"=>$l->g(82).": ".$l->g(331),
			   "NETWORKS-IPDHCP"=>$l->g(82).": ".$l->g(281),
			   "BIOS-SSN"=>$l->g(273).": ".$l->g(36),
			   "BIOS-SMODEL"=>$l->g(273).": ".$l->g(65),
			   "HARDWARE-NAME"=>$l->g(729).": ".$l->g(49),
			   "HARDWARE-PROCESSORT"=>$l->g(54).": ".$l->g(66),
			   "BIOS-SMANUFACTURER"=>$l->g(273).": ".$l->g(284),
			   "MONITORS-SERIAL"=>$l->g(554),
			   "MONITORS-DESCRIPTION"=>$l->g(556),
			   "MONITORS-MANUFACTURER"=>$l->g(555),
			    "DRIVES-VOLUMN"=>$l->g(92).": ".$l->g(964),
			   "BIOS-BMANUFACTURER"=>$l->g(273).": ".$l->g(284),
			   "BIOS-BVERSION"=>$l->g(273).": ".$l->g(277),
			   "HARDWARE-LASTDATE"=>"OCS: ".$l->g(46),
			   "HARDWARE-LASTDATE-LBL"=>"calendar",
			   "HARDWARE-LASTDATE-SELECT"=>array("exact"=>$l->g(410),"small"=>$l->g(346),"tall"=>$l->g(347)),
			   "HARDWARE-LASTCOME"=>"OCS: ".$l->g(820),
			   "HARDWARE-LASTCOME-LBL"=>"calendar",
			   "HARDWARE-LASTCOME-SELECT"=>array("exact"=>$l->g(410),"small"=>$l->g(346),"tall"=>$l->g(347)),
			   "HARDWARE-WORKGROUP"=>$l->g(82).": ".$l->g(33));
	//ajout des champs de accountinfo
$optSelectField = array_merge($optaccountinfo,$optSelectField);
//composotion du tableau
// TABLE-NOMCHAMP-SQL1 => requete avec les champs ID (option) et NAME. Peut également être un tableau de données
//à l'affichage on se retrouve avec le lbl du champ et un select
$optSelect=array("HARDWARE-OSNAME"=>$l->g(729).": ".$l->g(25),
				 "HARDWARE-OSNAME-SQL1"=>"select 'ALL_LINUX' as ID, '".$l->g(965)."' as NAME union select 'ALL_WIN', '".$l->g(966)."' union select OSNAME,OSNAME from hardware_osname_cache where osname != '' ",
				 "VIDEOS-RESOLUTION"=>$l->g(965).": ".$l->g(62),
				 "VIDEOS-RESOLUTION-SQL1"=>"select DISTINCT RESOLUTION as 'ID', RESOLUTION as 'NAME' from videos ".(isset($list_id_computor)? " where hardware_id in ".$list_id_computor : '')." order by 1");
//composotion du tableau
//option : TABLE-NOMCHAMP-SELECT =>array des valeurs du champ select ou requete sql (1er select)
// TABLE-NOMCHAMP-SQL1 => requete avec les champs ID (option) et NAME. Peut également être un tableau de données (2eme select)
//à l'affichage on se retrouve avec  le lbl du champ, 2 select et un champ de saisi
$opt2SelectField=array("REGISTRY-REGVALUE"=>$l->g(211).": ".$l->g(212),
				"REGISTRY-REGVALUE-SQL1"=>"select NAME from registry_name_cache order by 1",
				"REGISTRY-REGVALUE-LBL"=>"calendar",
				"REGISTRY-REGVALUE-SELECT"=>array('exact'=> $l->g(410),'ressemble'=>$l->g(129),
					'diff'=>$l->g(130),
					"small"=>$l->g(346),"tall"=>$l->g(347)),
				 "DRIVES-FREE"=>$l->g(92).": ".$l->g(45),
				 "DRIVES-FREE-SQL1"=>"select distinct LETTER from drives where letter != '' ".(isset($list_id_computor)? " and hardware_id in ".$list_id_computor : '')."
									 union select distinct volumn from drives where letter = '' and volumn != '' ".(isset($list_id_computor)? " and hardware_id in ".$list_id_computor : '')." order by 1",
				 "DRIVES-FREE-LBL"=>"MB",
				 "DRIVES-FREE-SELECT"=>array('exact'=> $l->g(410),"small"=>$l->g(201),"tall"=>$l->g(202)));

//composotion du tableau
//option : TABLE-NOMCHAMP-SELECT =>array des valeurs du champ select ou requete sql (1er select)
// TABLE-NOMCHAMP-SQL1 => requete avec les champs ID (option) et NAME. Peut également être un tableau de données (2eme select)
//à l'affichage on se retrouve avec le lbl du champ et 2 select
$opt2Select=array("HARDWARE-USERAGENT"=>"OCS: ".$l->g(966),
				 "HARDWARE-USERAGENT-SQL1"=>"select distinct USERAGENT as 'NAME' from hardware where USERAGENT != '' ".(isset($list_id_computor)? " and id in ".$list_id_computor : '')." order by 1",
				 "HARDWARE-USERAGENT-SELECT"=>array('exact'=>$l->g(410)
				 									,'diff'=>$l->g(130)
				 									),
				 "DEVICES-IPDISCOVER"=>$l->g(107).": ".$l->g(312),
				 "DEVICES-IPDISCOVER-SQL1"=>array("1"=>$l->g(502),"2"=>$l->g(503),"0"=>$l->g(506),"DEFAULT1"=>$l->g(504),"DEFAULT0"=>$l->g(505)),
				 "DEVICES-IPDISCOVER-SELECT"=>array('exact'=>$l->g(410)
				 									,'diff'=>$l->g(130)
				 									),
				 "DEVICES-FREQUENCY"=>$l->g(107).": ".$l->g(429),
				 "DEVICES-FREQUENCY-SQL1"=>array("0"=>$l->g(485),"DEFAULT"=>$l->g(488),"-1"=>$l->g(486),"PERSO"=>$l->g(487)),
				 "DEVICES-FREQUENCY-SELECT"=>array('exact'=>$l->g(410)
												,'diff'=>$l->g(130)
												),
				 "GROUPS_CACHE-GROUP_ID"=>$l->g(583).": ".$l->g(49),
				 "GROUPS_CACHE-GROUP_ID-SQL1"=>"select ID,NAME from hardware where deviceid = '_SYSTEMGROUP_' order by 2",
				 "GROUPS_CACHE-GROUP_ID-SELECT"=>array('exact'=>$l->g(967)
				 										,'diff'=>$l->g(968)
				 										),
				 "DOWNLOAD_HISTORY-PKG_ID"=>$l->g(512).": ".$l->g(969),
				 "DOWNLOAD_HISTORY-PKG_ID-SQL1"=>"select FILEID as ID,NAME from download_available order by 2",
				 "DOWNLOAD_HISTORY-PKG_ID-SELECT"=>array('exact'=>$l->g(507)
				 									,'diff'=>$l->g(508)
				 									),

				 );
//à l'affichage on se retrouve avec  le lbl du champ, un select et deux champs de saisi
//option : TABLE-NOMCHAMP-SELECT =>array des valeurs du champ select ( select)
//ATTENTION: le deuxième champ de saisi est invisible. Pour le rendre visible, faire passer
//un javascript dans le lbl_default avec genre: onclick='document.getElementById(\"between-field_name\").style.display=\"block\";'
//la valeur "field_name" est ensuite transformé par le vrai nom de champ
$lbl_default= array('exact'=> $l->g(410),'ressemble'=>$l->g(129)
					,'diff'=>$l->g(130)
					,'small'=>$l->g(201),'tall'=>$l->g(202),'between'=>$l->g(203),
						  'javascript'=>array('exact'=> "onclick='document.getElementById(\"FieldInput2-field_name\").style.display=\"none\";'",
											  'ressemble'=>"onclick='document.getElementById(\"FieldInput2-field_name\").style.display=\"none\";'",
											  'diff'=>"onclick='document.getElementById(\"FieldInput2-field_name\").style.display=\"none\";'",
											  'small'=>"onclick='document.getElementById(\"FieldInput2-field_name\").style.display=\"none\";'",
											  'tall'=>"onclick='document.getElementById(\"FieldInput2-field_name\").style.display=\"none\";'",
											  'between'=>"onclick='document.getElementById(\"FieldInput2-field_name\").style.display=\"inline\";'"));

$optSelect2Field=array("HARDWARE-MEMORY"=>$l->g(25).": ".$l->g(26),
					   "HARDWARE-MEMORY-LBL"=>"Mo",
					   "HARDWARE-MEMORY-SELECT"=>$lbl_default,
					   "HARDWARE-PROCESSORS"=>$l->g(54).": ".$l->g(377),
					   "HARDWARE-PROCESSORS-LBL"=>"Mhz",
					   "HARDWARE-PROCESSORS-SELECT"=>$lbl_default);
//composotion du tableau
//option : TABLE-NOMCHAMP-SELECT =>array des valeurs du champ select ou requete sql (1er select)
// TABLE-NOMCHAMP-SQL1 => requete avec les champs ID (option) et NAME. Peut également être un tableau de données (2eme select)
// TABLE-NOMCHAMP-SQL2 => requete avec les champs ID (option) et NAME. Peut également être un tableau de données (3eme select)

//à l'affichage on se retrouve avec  le lbl du champ et 3 select
$opt3Select=array("DEVICES-DOWNLOAD"=>$l->g(512).": ".$l->g(970),
				 "DEVICES-DOWNLOAD-SQL1"=>"select 'NULL' as 'ID', '***".$l->g(509)."***' as NAME "./*union select 'ALL' as ID,'***TOUS LES PAQUETS***' as NAME */"union select FILEID as ID,NAME from download_available order by 2",
				 "DEVICES-DOWNLOAD-SQL2"=>"select '***".$l->g(509)."***' as 'NAME' union select '***".$l->g(548)."***' union select '***".$l->g(956)."***' union select '***".$l->g(957)."***' union select '".$l->g(482)."' union select distinct TVALUE from devices where name='DOWNLOAD' and tvalue!='' order by 1",
				 "DEVICES-DOWNLOAD-SELECT"=>array('exact'=>$l->g(507),'diff'=>$l->g(508))
				 );
$optArray = array_merge( $optSelectField,
						 $opt2SelectField,
						 $opt2Select,
						 $optSelect,
						 $optSelect2Field,
						 $opt3Select);
asort($optArray);
$countHl++;
$aff_field_search.= "<option".($countHl%2==1?" class='hi'":"").">".$l->g(32)."</option>"; $countHl++;

foreach( $optArray as $key=>$value) {
	if (substr($key,-5,-1) != '-SQL'
		and substr($key,-7) != '-SELECT'
		and substr($key,-4) != '-LBL'
		){
		$aff_field_search.= "<option".($countHl%2==1?" class='hi'":"")." value='".$key."'>".$value."</option>";
		$countHl++;
	}
}
$aff_field_search.= "</select>
<img src='image/delete_all.png' onclick='pag(\"ok\",\"reset\",\"".$form_name."\");' alt='".$l->g(41)."'>
<input type=hidden name='reset' id='reset' value=''>";
echo $aff_field_search;
//echo "<br><br><i>Un problème de performance sur la base nous a obligé a supprimer les recherches avec 'DIFFERENT' ou 'N'AYANT PAS'";
//echo "<br>Nous allons corriger cela le plus rapidement possible. Merci de votre compréhension.</i><br><br>";

if (isset($_SESSION['multiSearch']) and $_SESSION['multiSearch'] != null){
	echo "<table border=1 class= 'Fenetre' WIDTH = '75%' ALIGN = 'Center' CELLPADDING='5'>";
	$c=0;
	foreach ($_SESSION['multiSearch'] as $k=>$v){
		if (!isset($alreadyExist[$v])){
			$alreadyExist[$v]='YES';	
			$ajout='';
		}else
			$ajout=$v;
		$color= $c%2==0?"#F2F2F2":"#FFFFFF";
		show_ligne($v,$color,$k,$ajout,$form_name);
		$c++;
	}
	echo "<tr><td colspan=100 align=right><input type=submit taborder=1 name='Valid-search' value=".$l->g(30)." onclick='pag(\"VALID\",\"Valid\",\"".$form_name."\");'></td></tr>";
	echo "</table>";
	echo "<input type=hidden name=Valid id=Valid value=''>";
}
echo "<input type=hidden name=delfield id=delfield value=''>";
echo "</form>";	
echo $l->g(358);

