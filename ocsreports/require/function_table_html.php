<?php
/*
 *  
 * function for new tab
 * 
 * 
 */
//javascript for confirmation delete
function confirm_supp($nom)
{
	global $l,$_GET;
	echo "<script language=javascript>
		function confirme(did){
			if(confirm(\"".$l->g(640)." ".$nom." \"+did+\" ?\"))
				window.location=\"index.php?multi=".$_GET['multi']."&suppAcc=\"+did;
		}
	</script> ";
}
//ascending and descending sort
function tri($sql)
{
	global $_GET;
	
	if ($_GET['sens']){
	$sens=$_GET['sens'];
	$col=$_GET['col'];
	}
	else{
	$sens="ASC";
	$col=1;
	}	
	
	$sql= $sql." order by ".$col." ".$sens;
	return $sql;
	
}

//fonction qui permet d'afficher un tableau de données
/*
 * $entete_colonne = array ; => ex: $i=0;
									while($colname = mysql_fetch_field($result))
										$entete2[$i++]=$colname->name;
 * $data= array; => ex: $i=0;
						while($item = mysql_fetch_object($result)){
							$data2[$i]['ID']=$item ->ID;
							$data2[$i]['PRIORITY']=$up.$item ->PRIORITY.$down;
							$data2[$i]['TITLE']=$item ->TITLE;
							}
 * $titre= varchar => ex: "Administration des messages"
 * $width= taille tableau => ex: "60"
 * $height= taille tableau => ex: "300"
 * $lien = array ; => liste des colonnes qui ont le tri
 * 
 */
 function tab_entete_fixe($entete_colonne,$data,$titre,$width,$height,$lien=array())
{
	global $_GET,$l;
	if ($_GET['sens'] == "ASC"){
	$sens="DESC";
	}
	else
	{
	$sens="ASC";
	}

	if(isset($data[0]))
	{
	?>
	<script language=javascript>		
	function changerCouleur(obj, state) {
			if (state == true) {
				bcolor = obj.style.backgroundColor;
				fcolor = obj.style.color;
				obj.style.backgroundColor = '#FFDAB9';
				obj.style.color = 'red';
				return true;
			} else {
				obj.style.backgroundColor = bcolor;
				obj.style.color = fcolor;
				return true;
			}
			return false;
		}
	</script>
	<?
	if ($titre != "")
	printEnTete($titre);
	echo "<br><div class='tableContainer' id='data' style=\"width:".$width."%;\"><table cellspacing='0' class='ta'><tr>";
		//titre du tableau
	$i=1;
	foreach($entete_colonne as $k=>$v)
	{
		if (in_array($v,$lien))
		echo "<th class='ta'><a href='index.php?multi=".$_GET['multi']."&sens=".$sens."&col=".$i."'>".$v."</a></th>";
		else
		echo "<th class='ta'><font size=1>".$v."</font></th>";	
		$i++;		
	}
	echo "
    </tr>
    <tbody class='ta'>";
	
	$i=0;
	$j=0;
	//lignes du tableau
	while (isset($data[$i]))
	{
		($j % 2 == 0 ? $color = "#f2f2f2" : $color = "#ffffff");
		echo "<tr class='ta' bgcolor='".$color."'  onMouseOver='changerCouleur(this, true);' onMouseOut='changerCouleur(this, false);'>";
		foreach ($data[$i] as $k=>$v)
		{
			if ($v == "") $v="&nbsp";
			echo "<td class='ta' >".$v."</td>";
			
		}
		$j++;
		echo "</tr><tr>";
		$i++;
	}
	echo "</tr></tbody></table></div>";	
	}
	else{
	echo "<center><font size=5 color=red>".$l->g(766)."</font></center>";
	return FALSE;
	}
	
}
//variable pour la fonction champsform
$num_lig=0;
/* fonction liée à show_modif
 * qui permet de créer une ligne dans le tableau de modification/ajout
 * $title = titre à l'affichage du champ
 * $value_default = - pour un champ text ou input, la valeur par défaut du champ.
 * 					- pour un champ select, liste des valeurs du champ
 * $input_name = nom du champ que l'on va récupérer en $_POST
 * $input_type = 0 : <input type='text'>
 * 				 1 : <textarea>
 * 				 2 : <select><option>
 * $donnees = tableau qui contient tous les champs à afficher à la suite
 * $nom_form = si un select doit effectuer un reload, on y met le nom du formulaire à reload
*/
function champsform($title,$value_default,$input_name,$input_type,&$donnees,$nom_form=''){
	global $num_lig;
	$donnees['tab_name'][$num_lig]=$title;	
	$donnees['tab_typ_champ'][$num_lig]['DEFAULT_VALUE']=$value_default;
	$donnees['tab_typ_champ'][$num_lig]['INPUT_NAME']=$input_name;
	$donnees['tab_typ_champ'][$num_lig]['INPUT_TYPE']=$input_type;
	if ($nom_form != "")
	$donnees['tab_typ_champ'][$num_lig]['RELOAD']=$nom_form;
	$num_lig++;
	return $donnees;
	
}

/*
 * fonction liée à tab_modif_values qui permet d'afficher le champ défini avec la fonction champsform
 * $name = nom du champ
 * $input_name = nom du champ récupéré dans le $_POST
 * $input_type = 0 : <input type='text'>
 * 				 1 : <textarea>
 * 				 2 : <select><option>
 * $input_reload = si un select doit effectuer un reload, on y met le nom du formulaire à reload
 * 
 */
function show_modif($name,$input_name,$input_type,$input_reload = "")
{

	global $_POST;
	if ($input_type == 1)
	return "<textarea name='".$input_name."' cols='30' rows='5' onFocus=\"this.style.backgroundColor='white'\" onBlur=\"this.style.backgroundColor='#C7D9F5'\"\>".textDecode($name)."</textarea>";
	elseif ($input_type ==0)
	return "<input type='text' name='".$input_name."' value=\"".textDecode($name)."\" onFocus=\"this.style.backgroundColor='white'\" onBlur=\"this.style.backgroundColor='#C7D9F5'\">";
	elseif($input_type ==2){
		
		$champs="<select name='".$input_name."'";
		if ($input_reload != "") $champs.=" onChange='document.".$input_reload.".submit();'";
		$champs.="><option value=''></option>";
		foreach ($name as $key=>$value){
			$champs.= "<option value='".$key."'";
			if ($_POST[$input_name] == $key )
			$champs.= " selected";
			$champs.= ">".$value."</option>";
		}
		$champs.="</select>";
		return $champs;
	}
	
}

function tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title="",$comment="")
{
	global $l;
	echo "<form name='CHANGE' action='' method='POST'>";
	echo "<table align='center' width='65%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
	echo "<tr><td colspan=10 align='center'><font color=red><b><i>".$title."</i></b></font></td></tr>";
        foreach ($tab_name as $key=>$values)
	{
		echo "<tr><td>".$values."</td><td>".$tab_typ_champ[$key]['COMMENT_BEFORE'].show_modif($tab_typ_champ[$key]['DEFAULT_VALUE'],$tab_typ_champ[$key]['INPUT_NAME'],$tab_typ_champ[$key]['INPUT_TYPE'],$tab_typ_champ[$key]['RELOAD']).$tab_typ_champ[$key]['COMMENT_BEHING']."</td></tr>";
	}
 echo "<tr ><td colspan=10 align='center'><i>".$comment."</i></td></tr>";
	echo "<tr><td><input title='".$l->g(625)."' type='image'  src='image/modif_valid_v2.png' name='Valid_modif'>";
	echo "<input title='".$l->g(626)."' type='image'  src='image/modif_anul_v2.png' name='Reset_modif'></td></tr>";


        echo "</table>";    
    if ($tab_hidden != ""){                 
		foreach ($tab_hidden as $key=>$value)
		{
			echo "<input type='hidden' name='".$key."' value='".$value."'>";
	
		}
    }
	echo "</form>";
}


function tab_list_error($data,$title)
{
	global $l;

	echo "<br>";
		echo "<table align='center' width='50%' border='0'  bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>";
		echo "<tr><td colspan=20 align='center'><font color='RED'>".$title."</font></td></tr><tr>";	
		$i=0;
		$j=0;
		while ($data[$i])
		{
			if ($j == 10)
			{
				echo "</tr><tr>";
				$j=0;	
			}
			echo "<td align='center'>".$data[$i]."<td>";
			$i++;
			$j++;
		}
		echo "</td></tr></table>";
	
}



function onglet($def_onglets,$form_name,$post_name,$ligne)
{
	/*This fnction use code of Douglas Bowman (Sliding Doors of CSS)
	http://www.alistapart.com/articles/slidingdoors/
	THANKS!!!!
		$def_onglets is array like :  	$def_onglets[$l->g(499)]=$l->g(499); //Serveur
										$def_onglets[$l->g(728)]=$l->g(728); //Inventaire
										$def_onglets[$l->g(312)]=$l->g(312); //IP Discover
										$def_onglets[$l->g(512)]=$l->g(512); //Télédéploiement
										$def_onglets[$l->g(628)]=$l->g(628); //Serveur de redistribution 
		
	behing this function put this lign:
	echo "<form name='modif_onglet' id='modif_onglet' method='POST' action='index.php?multi=4'>";
	
	At the end of your page, close this form
	$post_name is the name of var will be post
	$ligne is if u want have onglet on more ligne*/
	if ($def_onglets != ""){
	echo "<script language=javascript>

  		function recharge2(onglet,form_name,post_name){
			document.getElementById(post_name).value=onglet;
			document.getElementById(form_name).submit();	
		}
		</script>";
	echo "<LINK REL='StyleSheet' TYPE='text/css' HREF='css/onglets.css'>\n";
	echo "<table cellspacing='5' BORDER='0' ALIGN = 'Center' CELLPADDING='0'><tr><td><div id='header'>
	<ul>";
	$current="";
	$i=0;
	  foreach($def_onglets as $key=>$value){
	  	if ($i == $ligne){
	  		echo "<br><br>";
	  		$i=0;
	  		
	  	}
	  	echo "<li ";
	  	if ($_POST[$post_name] == $key or (!isset($_POST[$post_name]) and $current != 1)){
	 		 echo "id='current'";  
	 		 $current=1;
	  	}
	  	echo "><a OnClick='recharge2(\"".$key."\",\"".$form_name."\",\"".$post_name."\")'>".$value."</a></li>";
	  $i++;	
	  }	
	echo "</ul>
	</div></td></tr></table>";
	echo "<input type='hidden' id='".$post_name."' name='".$post_name."' value='".$_POST[$post_name]."'>";
	}
}



?>
