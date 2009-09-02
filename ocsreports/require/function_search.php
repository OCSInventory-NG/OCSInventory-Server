<?php
/*
 * Fichier de fonctions pour la recherche multicrit�re.
 */

//d�finition du poids de chaque table pour optimiser la recherche d'abord sur les tables "peut couteuse"
$weight_table=array("HARDWARE"=>1, 
					"DRIVES"=>5,
					"GROUPS_CACHE"=>2,
					"SOFTWARES"=>10,
					"ACCOUNTINFO"=>1,
					"BIOS"=>3,
					"MONITORS"=>1,
					"NETWORKS"=>3,
					"REGISTRY"=>5,
					"DOWNLOAD_HISTORY"=>6,
					"DEVICES"=>3,
					"VIDEOS"=>2);
asort($weight_table); 

//utilisation des tables de cache pour:
if ($_SESSION["usecache"] == true){
	//liste des tables
	$table_cache=array('SOFTWARES'=>'SOFTWARES_NAME_CACHE');
	//liste des champs correspondants ou la recherche doit se faire
	$field_cache=array('SOFTWARES_NAME_CACHE'=>'NAME');
}
//liste des tables qui ne doivent pas faire des fusions de requ�te
//cas pour les tables multivalu�e
$tab_no_fusion=array("DEVICES","REGISTRY","DRIVES","SOFTWARES","DOWNLOAD_HISTORY");




//d�finition des libell�s des champs
$lbl_fields_calcul['DRIVES']=array($l->g(838)=>'drives.LETTER',
								   $l->g(839)=>'drives.TYPE',
								   $l->g(840)=>'drives.FILESYSTEM',
								   $l->g(841)=>'drives.TOTAL',
								   $l->g(842)=>'drives.FREE',
								   $l->g(843)=>'drives.VOLUMN');
$lbl_fields_calcul['GROUPS_CACHE']=array( $l->g(844) => 'groups_cache.GROUP_ID',
										  $l->g(845) => 'groups_cache.STATIC');
$lbl_fields_calcul['SOFTWARES']=array( $l->g(846) => 'softwares.PUBLISHER',
									   $l->g(847) => 'softwares.NAME',
									   $l->g(848) => 'softwares.VERSION',
									   $l->g(849) => 'softwares.FOLDER',
									   $l->g(850) => 'softwares.COMMENTS');
$lbl_fields_calcul['BIOS']=array($l->g(851)=>'bios.SMANUFACTURER',
								 $l->g(852)=>'bios.SMODEL',
								 $l->g(853)=>'bios.SSN',
								 $l->g(854)=>'bios.TYPE',
								 $l->g(855)=>'bios.BMANUFACTURER',
								 $l->g(856)=>'bios.BVERSION',
								 $l->g(857)=>'bios.BDATE' );
$lbl_fields_calcul['MONITORS']=array( $l->g(858)=> 'monitors.MANUFACTURER',
									  $l->g(859)=> 'monitors.CAPTION',
									  $l->g(860) => 'monitors.DESCRIPTION',
									  $l->g(861) => 'monitors.TYPE',
									  $l->g(862) => 'monitors.SERIAL');
$lbl_fields_calcul['NETWORKS']=array($l->g(863) => 'networks.DESCRIPTION',
									 $l->g(864) => 'networks.TYPE',
									 $l->g(865) => 'networks.TYPEMIB',
									 $l->g(866) => 'networks.SPEED' ,
									 $l->g(867) => 'networks.MACADDR',
									 $l->g(868) => 'networks.STATUS',
									 $l->g(869) => 'networks.IPADDRESS',
									 $l->g(870) => 'networks.IPMASK',
									 $l->g(871) => 'networks.IPSUBNET',
									 $l->g(872) => 'networks.IPGATEWAY',
									 $l->g(873) => 'networks.IPDHCP');
$lbl_fields_calcul['REGISTRY']=array($l->g(874) => 'registry.NAME',
									 $l->g(875) => 'registry.REGVALUE');

//fonction qui ex�cute les requetes de la recherche 
//et qui retourne les ID des machines qui match.
function execute_sql_returnID($list_id,$execute_sql,$no_cumul='',$table_name){
	global $l;
	//on parcourt le tableau de requetes
	foreach ($execute_sql as $weight => $id){
		$i=0;
//		echo "<br><br>";
//		print_r_V2($id);
//		echo "<br><br>";
		
		//on prends toutes les requetes qui ont le m�me poids
		while ($id[$i]){
//			echo "<font color=green>";
//			print_r($id[$i]);
//			echo "</font>";
			//on cherche a savoir si on est sur la table hardware
 			//dans ce cas, la concat des id doit se faire avec le champ ID
 			if (substr_count($id[$i],"from hardware")){
 			$name_field_id=" ID ";
 			$fin_sql=" and deviceid<>'_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_' ";
 			}
 			else{
 			$name_field_id=" HARDWARE_ID ";
 			$fin_sql="";
 			if ($no_cumul == "")
 			$_SESSION['SQL_DATA_FIXE'][$table_name][]=$id[$i];
 			else
 			$_SESSION['SQL_DATA_FIXE'][$table_name][]=ereg_replace("like", "not like", $id[$i]);
 			}
			//si une liste d'id de machine existe,
			//on va concat la requ�te avec les ID des machines
	 		if ($list_id != "" and $no_cumul == ''){	 		
	 			if (is_array($list_id))
	 			$list=implode(',',$list_id);
	 			else
	 			$list=$list_id;
	 			$id[$i].= " AND ".$name_field_id." IN (".$list.")";
	 			unset($list_id);
	 		}
	 		$id[$i].=$fin_sql;
	 		//echo "<br><br><b>".$id[$i]."</b><br><br>";
	 		$result = mysql_query($id[$i], $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
			while($item = mysql_fetch_object($result)){
				$list_id[$item->HARDWARE_ID]=$item->HARDWARE_ID;
				foreach ($item as $field=>$value){
					if ($field != "HARDWARE_ID" and $field != "ID")
					$tab_options['VALUE'][$field][$item->HARDWARE_ID]=$value;
//				//echo "<br>FIELD=>".$field."; value=>".$value;
				}
			}
	 		if ($_SESSION['DEBUG'] == 'ON')
	 		echo "<br>".$l->g(5001).$id[$i].$l->g(5002).$weight;
	 		//si aucun id trouv� => end
	 		if ($list_id == '')
	 		return ;
		$i++;	
		}	
	}
	return array($list_id,$tab_options);
}



//fonction pour ordonner les requetes en fonction 
//du poids de la table
function class_weight($list_sql){
	global $weight_table;
 	foreach ($list_sql as $table_name=>$id){
 		$poids=$weight_table[$table_name];
 		foreach($id as $i=>$sql)
 			$execute_sql[$poids][]=$sql.'))'; //ajout de la derni�re parenth�se pour fermer la requ�te
 	}
// 	if ($sens == 'ASC')
 	ksort($execute_sql);
// 	else
// 	ksort($execute_sql);
	return $execute_sql;
}


//fonction qui permet de prendre en compte les requ�tes interm�diaires pour 
//la cr�ation des groupes dynamiques
function traitement_cache($sql_temp,$field_modif,$field_value,$field_value_complement){

	if ($sql_temp != ""){
		if ($field_modif == "field_value")
			$field_value= " (".$sql_temp.") ";
		else
			$field_value_complement= " IN (".$sql_temp.") ";
	}			
	$toto= array('field_value'=>$field_value,'field_value_complement'=>$field_value_complement);
	return $toto;
}

//fonction qui permet de passer en SESSION
//les requetes pour la cr�ation des groupes dynamiques
function sql_group_cache($cache_sql){
	unset($_SESSION['SEARCH_SQL_GROUP']);
	//requ�te de recherche "normale" (ressemble, exactement)
	if ($cache_sql['NORMAL']){

		foreach ($cache_sql['NORMAL'] as $poids=>$list){
			$i=0;
			while ($list[$i]){
				$fin_sql="";
				if (substr_count($list[$i],"from hardware"))
 					$fin_sql=" and deviceid<>'_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_' ";
 				else
 					$fin_sql="";
			$_SESSION['SEARCH_SQL_GROUP'][]=$list[$i].$fin_sql;
			$i++;
			}
		
		}
	}
	//requ�te de recherche "diff�rent", "n'appartient pas"
	if ($cache_sql['DIFF']){
		foreach ($cache_sql['DIFF'] as $poids=>$list){
			$i=0;
			while ($list[$i]){
				$fin_sql="";
				if (substr_count($list[$i],"from hardware"))
 					$fin_sql=" and deviceid<>'_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_' ";
 				else
 					$fin_sql="";
				$_SESSION['SEARCH_SQL_GROUP'][]="select distinct id as HARDWARE_ID from hardware where id not in (".$list[$i].")".$fin_sql;
			$i++;
			}
		
		}
		
	}
	//print_r($_SESSION['SEARCH_SQL_GROUP']);
		
}
//fonction pour prendre en compte les jockers dans la saisie (* et ?)
function jockers_trait($field_value){
	$field_value_modif=$field_value;
	//prise en compte du caract�re * pour les champs
 	$count_ast=substr_count($field_value,"*");
 	//si au moins un * a �t� trouv�
 	if ($count_ast>0)
 		$field_value_modif = str_replace("*", "%", $field_value); 	
 
  	//prise en compte du caract�re ? pour les champs
 	$count_intero=substr_count($field_value_modif,"?");
 	//si au moins un ? 	a �t� trouv�
 	if ($count_intero>0)
 			$field_value_modif = str_replace("?", "_", $field_value_modif);	 	
 	//on retourne la valeur trait�e
 	//echo "<br>".$field_value_modif."<br>".$field_value."<br>";
 	if ($field_value_modif == $field_value)
 	return "'%".$field_value."%'";
 	else
 	return "'".$field_value_modif."'";
	
}

//fonction pour traiter les recherches sur les dates
function compair_with_date($field,$field_value){
		//modification d'un champ texte en date dans certains cas
 		if ($field == "LASTDATE" or $field == "LASTCOME" or $field == "REGVALUE"){
 			$tab_date = explode('/', $field_value);
 			//on applique le traitement que si la date est valide
 			if (@checkdate ($tab_date[1],$tab_date[0],$tab_date[2])){
 				$field= " unix_timestamp(".$field.") ";
				$tab_date = explode('/', $field_value);
				$field_value= mktime (0,0,0,$tab_date[1],$tab_date[0],$tab_date[2]);
 			}
 		}
 		return array('field'=>$field,'field_value'=>$field_value);
}



//fonction qui permet de cr�er le d�but des requ�tes � ex�cuter
function generate_sql($table_name)
{
	global $weight_table,$lbl_fields_calcul;
	 if ($table_name == "HARDWARE"){
 			$VALUE_id="ID";
 			$entre=" as HARDWARE_ID";
 		//	$sql_id_fin=" and deviceid<>'_SYSTEMGROUP_' AND deviceid <> '_DOWNLOADGROUP_' ";
	}
	else{
 			$VALUE_id="HARDWARE_ID"; 
 		//	$field_to_add=witch_field_more($weight_table);
 			$complement_id=",";
 			if (isset($lbl_fields_calcul[$table_name])){
	 			foreach ($lbl_fields_calcul[$table_name] as $key=>$value){
	 				$complement_id .= $value." as '".$key."',"; 				
	 			}
 			}
 			$complement_id= substr($complement_id,0,-1);
 		//	$complement_id=",".implode(',',$lbl_fields_calcul[$table_name]);
 		//	$sql_id_fin="";
 			//$entre="";
	}
	$sql_temp="select distinct ".$VALUE_id.$entre.$complement_id." from ".strtolower($table_name)." where (";
	$sql_cache="select distinct ".$VALUE_id.$entre." from ".strtolower($table_name)." where (";
	return array('sql_temp'=>$sql_temp,'sql_cache'=>$sql_cache);
}


function witch_field_more($tab_table){
	foreach($tab_table as $table=>$poids){
		$table_min=strtolower($table);
		$sql_show_colomn="SHOW COLUMNS FROM ".$table_min;
		$result_show_colomn = mysql_query($sql_show_colomn, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
		
		while($item = mysql_fetch_object($result_show_colomn)){
					//print_r($item);
			if ($item ->Field != 'ID' and $item ->Field != 'HARDWARE_ID')
				$list_fields[$table][]=$item ->Field;
		}
		
	}
	return $list_fields;
}
 //fonction qui permet d'afficher la ligne de recherche en fonction 
//du type du champ
function show_ligne($value,$color,$id_field,$ajout,$form_name){
	global $optSelectField, $opt2SelectField, $opt2Select,
		   $optSelect2Field, $opt3Select, $optSelect, $optArray,$l;
	$nameField=$value."-".$id_field;
	if ($ajout != ''){
		$and_or="<select name='SelAndOr-".$nameField."' id='SelAndOr-".$nameField."'>";
		$and_or .= "<option value='AND' ".($_POST['SelAndOr-'.$nameField] == "AND" ? " selected":"")." >AND</option>";
		$and_or .= "<option value='OR' ".($_POST['SelAndOr-'.$nameField] == "OR" ? " selected":"")." >OR</option>";
		$and_or .= "</select>";
		
	}
	//si le champ comporte une valeur du champ select par d�faut
	if (array_key_exists($value.'-SELECT',$optArray))
	//on prend les valeurs du champ
	$champ_select=$optArray[$value.'-SELECT'];
	else //si on garde les valeurs par d�faut
	$champ_select=array('exact'=> $l->g(410),'ressemble'=>$l->g(129)
					,'diff'=>$l->g(130)
					);

	//on g�n�re le premier champ select
	$select="<select name='SelComp-".$nameField."' id='SelComp-".$nameField."'>";
		foreach ($champ_select as $k=>$v){
			//si un javascript a �t� pass� en param�tre
			if ($k!='javascript'){
				//on remplace la chaine g�n�rique field_name du javascript par le vrai nom de champ
				$champ_select['javascript'][$k] =str_replace("field_name", $nameField, $champ_select['javascript'][$k]);
				$select .= "<option value='".$k."' ".($_POST['SelComp-'.$nameField] == $k ? " selected":"")." ".$champ_select['javascript'][$k].">".$v."</option>";
			}
		}										
	$select .= "</select>";
	
	//on affiche le d�but de ligne
	echo "<tr bgcolor=$color><td align=left><a href=\"javascript:;\"><img src='image/supp.png' onclick='pag(\"".$id_field."\",\"delfield\",\"".$form_name."\");'></a>
		  </td><td>";
	if ($ajout != '')
	echo $and_or;
	echo "&nbsp;".$optArray[$value]."</td>";
	//TITRE,CHAMP (EGAL,LIKE,NOTLIKE),valeur
	if( array_key_exists($value,$optSelectField)){		
		echo "<td>".$select."&nbsp;&nbsp;<input name='InputValue-".$nameField."' id='InputValue-".$nameField."' value=\"".stripslashes($_POST["InputValue-".$nameField])."\">&nbsp;";
		if ($optSelectField[$value."-LBL"] == "calendar")
		echo calendars("InputValue-".$nameField,"DDMMYYYY");
		echo "</td></tr>";
		//echo $value."-LBL".$id_field;
		
	}
	//TITRE,CHAMPSELECT,(pour $optSelect) 
	//et les champs suivants en plus pour $opt2SelectField: CHAMP (EGAL,LIKE,NOTLIKE) et valeur 
	if( array_key_exists($value,$opt2SelectField) or array_key_exists($value,$optSelect)){
		if (array_key_exists($value,$opt2SelectField)){
			$data=$opt2SelectField;
			//nom en Value3 car le traitement doit se faire sur la valeur de ce champ (cas particulier)
			$name_select='SelFieldValue3';
		}
		else{
			$data=$optSelect;
			$name_select='SelFieldValue';
		}
		$select2="<select name='".$name_select."-".$nameField."' id='".$name_select."-".$nameField."'>";
		
		if (is_array($data[$value.'-SQL1'])){
			foreach ($data[$value.'-SQL1'] as $k=>$v){	
				$select2 .= "<option value='".$k."' ".($_POST[$name_select."-".$nameField] == $k ? " selected":"").">".$v."</option>";
			}
		}else{
			$result = mysql_query( $data[$value.'-SQL1'], $_SESSION["readServer"] );
			while( $val = mysql_fetch_array( $result ) ) {
				foreach ($val as $name_of_field=>$value_of_request){
					if (!is_numeric($name_of_field) and $name_of_field != 'ID'){
						if (!isset($val['ID']))
							$val['ID']=$value_of_request;
						$select2 .= "<option value='".$val['ID']."' ".($_POST[$name_select.'-'.$nameField] == $val['ID'] ? " selected":"").">".$value_of_request."</option>";
					}
				}
				
			}
		}
		$select2 .= "</select>";
		echo "<td>".$select2;
		if (array_key_exists($value,$opt2SelectField)){
			if ($opt2SelectField[$value."-LBL"] == "calendar")
			$opt2SelectField[$value."-LBL"]= calendars("InputValue-".$nameField,"DDMMYYYY");
			echo $select."&nbsp;&nbsp;<input name='InputValue-".$nameField."' id='InputValue-".$nameField."' value=\"".stripslashes($_POST["InputValue-".$nameField])."\">&nbsp;".$opt2SelectField[$value."-LBL"];
		}
		echo "</td></tr>";
	}
	//TITRE,CHAMP (EGAL,LIKE,NOTLIKE),CHAMPSELECT
	if( array_key_exists($value,$opt2Select)){
		$selectValue="<select name='SelFieldValue-".$nameField."' id='SelFieldValue-".$nameField."' >";
		if (is_array($opt2Select[$value.'-SQL1'])){
			foreach ($opt2Select[$value.'-SQL1'] as $k=>$v){		
				$selectValue .= "<option value='".$k."' ".($_POST['SelFieldValue-'.$nameField] == $k ? " selected":"").">".$v."</option>";
			}
		}else{
			$result = mysql_query( $opt2Select[$value.'-SQL1'], $_SESSION["readServer"] );
			while( $val = mysql_fetch_array( $result ) ) {
				if (!isset($val['ID']))
				$val['ID']=$val['NAME'];
					$selectValue .= "<option value='".$val['ID']."' ".($_POST['SelFieldValue-'.$nameField] == $val['ID'] ? " selected":"").">".$val['NAME']."</option>";
			}
		}
		$selectValue .= "</select>";
		echo "<td>".$select.$selectValue."&nbsp;&nbsp;</td></tr>";
	}
	//TITRE,CHAMPSELECT,valeur1,valeur2
	if( array_key_exists($value,$optSelect2Field)){
		//gestion de la vision du deuxieme champ de saisi
		//on fonction du POST
		if ($_POST['SelComp-'.$nameField] == "between")
		$display="inline";
		else
		$display="none";
		
		echo "<td>".$select."&nbsp;&nbsp;<input name='InputValue-".$nameField."' id='InputValue-".$nameField."' value=\"".stripslashes($_POST["InputValue-".$nameField])."\">
				 <div style='display:".$display."' id='FieldInput2-".$nameField."'>&nbsp;--&nbsp;<input name='InputValue2-".$nameField."' value=\"".stripslashes($_POST["InputValue2-".$nameField])."\"></div>".$optSelect2Field[$value."-LBL"]."</td></tr>";
	}
	
	if( array_key_exists($value,$opt3Select)){
		$selectValue1="<select name='SelFieldValue-".$nameField."' id='SelFieldValue-".$nameField."'>";
		$result = mysql_query( $opt3Select[$value.'-SQL1'], $_SESSION["readServer"] );
		while( $val = mysql_fetch_array( $result ) ) {
			if (!isset($val['ID']))
			$val['ID']=$val['NAME'];
				$selectValue1 .= "<option value='".$val['ID']."' ".($_POST['SelFieldValue-'.$nameField] == $val['ID'] ? " selected":"").">".$val['NAME']."</option>";
		}
		$selectValue1 .= "</select>";
		
		$selectValue2="<select name='SelFieldValue2-".$nameField."' id='SelFieldValue2-".$nameField."'>";
		$result = mysql_query( $opt3Select[$value.'-SQL2'], $_SESSION["readServer"] );
		while( $val = mysql_fetch_array( $result ) ) {
			if (!isset($val['ID']))
			$val['ID']=$val['NAME'];
				$selectValue2 .= "<option value='".$val['ID']."' ".($_POST['SelFieldValue2-'.$nameField] == $val['ID'] ? " selected":"").">".$val['NAME']."</option>";
		}
		$selectValue2 .= "</select>";
		echo "<td>".$select."&nbsp;".$l->g(667).":".$selectValue1."&nbsp;".$l->g(546).":".$selectValue2."</td></tr>";	
		
		
	}	
}

//fonction qui permet d'utiliser un calendrier dans un champ
function calendars($NameInputField,$DateFormat)
{
	return "<a href=\"javascript:NewCal('".$NameInputField."','".$DateFormat."',false,24,null);\"><img src=\"image/cal.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Pick a date\">";
}


function add_trait_select($img,$list_id,$form_name)
{
	global 	$l;
	$_SESSION['ID_REQ']=$list_id;
	echo "<script language=javascript>
		function garde_check(image,id)
		 {
			var idchecked = '';
			for(i=0; i<document.".$form_name.".elements.length; i++)
			{
				if(document.".$form_name.".elements[i].name.substring(0,5) == 'check'){
			        if (document.".$form_name.".elements[i].checked)
						idchecked = idchecked + document.".$form_name.".elements[i].name.substring(5) + ',';
				}
			}
			idchecked = idchecked.substr(0,(idchecked.length -1));
			window.open(\"multi_lot.php?img=\"+image+\"&idchecked=\"+idchecked,\"rollo\",\"location=0,status=0,scrollbars=1,menubar=0,resizable=0,width=800,height=500\");
			
		}
	</script>";
	echo "<table align='center' width='30%' border='0'>";
	echo "<tr><td>";
	foreach ($img as $key=>$value){
		echo "<td align=center><a href=# onclick=garde_check(\"".$key."\",\"".$list_id."\")><img src='".$key."' title='".$value."' ></a></td>";
	}
 echo "</tr></tr></table>";    

	
}
?>
