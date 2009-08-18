<script language=javascript>
		
		function convertToUpper(v_string){
         v_string.value=v_string.value.toUpperCase();
		}
		
		function codeTouche(evenement) {
        for (prop in evenement) {
                if(prop == 'which') return(evenement.which);
        }
        return(evenement.keyCode);
        }
		
		function pressePapierNS6(evenement,touche)
		{
        var rePressePapierNS = /[cvxz]/i;

        for (prop in evenement) if (prop == 'ctrlKey') isModifiers = true;
        if (isModifiers) return evenement.ctrlKey && rePressePapierNS.test(touche);
        else return false;
		}
			
		function scanTouche(evenement,exReguliere) {
        var reCarSpeciaux = /[\x00\x08\x0D\x03\x16\x18\x1A]/;
        var reCarValides = exReguliere;
        var codeDecimal  = codeTouche(evenement);
        var car = String.fromCharCode(codeDecimal);
        var autorisation = reCarValides.test(car) || reCarSpeciaux.test(car) || pressePapierNS6(evenement,car);
        var toto = autorisation;
        return autorisation;
        }		       
</script>
<?php 
	$list_fields=array();
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
$majuscule="onKeyPress=\"return scanTouche(event,/[0-9 a-z A-Z]/)\" onkeydown='convertToUpper(this)'
		  onkeyup='convertToUpper(this)' 
		  onblur='convertToUpper(this)'
		  onclick='convertToUpper(this)'";
$chiffres="onKeyPress=\"return scanTouche(event,/[0-9]/)\" onkeydown='convertToUpper(this)'
		  onkeyup='convertToUpper(this)' 
		  onblur='convertToUpper(this)'
		  onclick='convertToUpper(this)'";

	$form_name="affich_tag";
	$table_name=$form_name;
	if (isset($_POST['Valid_modif_x'])){
		if ($_POST['TAG_MODIF'] == TAG_LBL)
		$lbl_champ='TAG';
		else
		$lbl_champ=$_POST['TAG_MODIF'];
		$sql=" update accountinfo set ".$lbl_champ."='";
		if ($_POST['FIELD_FORMAT'] == "date")
		$sql.= dateToMysql($_POST['NEW_VALUE'])."'";
		else
		$sql.= xml_encode($_POST['NEW_VALUE'])."'";
		$sql.=" where hardware_id=".$systemid; 
		mysql_query($sql, $_SESSION["writeServer"]);
		//regénération du cache
		$tab_options['CACHE']='RESET';
	}
	
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";

	$queryDetails = "SELECT * FROM accountinfo WHERE hardware_id=$systemid";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item=mysql_fetch_array($resultDetails,MYSQL_ASSOC);
	$i=0;
	$queryDetails = "";
	while (@mysql_field_name($resultDetails,$i)){
		if(mysql_field_type($resultDetails,$i)=="date"){
			//echo dateFromMysql($item[mysql_field_name($resultDetails,$i)])." => ".mysql_field_name($resultDetails,$i);
			$value = "'".dateFromMysql($item[mysql_field_name($resultDetails,$i)])."'";
		}else
			$value = mysql_field_name($resultDetails,$i);
		$lbl=mysql_field_name($resultDetails,$i);	
		if ($lbl != 'HARDWARE_ID'){
			if ($lbl == 'TAG')
			$lbl=TAG_LBL;
			$queryDetails .= "SELECT hardware_id as ID,'".$lbl."' as libelle, ".$value." as valeur FROM accountinfo WHERE hardware_id=".$systemid." UNION ";
		}
		$type_field[$lbl]=mysql_field_type($resultDetails,$i);
		$i++;
	}
	$queryDetails=substr($queryDetails,0,-6);
	$list_fields['Information']='libelle';
	$list_fields['Valeur']='valeur';
	//$list_fields['SUP']= 'ID';
	$list_fields['MODIF']= 'libelle';
	$list_col_cant_del=$list_fields;
	$default_fields= $list_fields;

	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	//print_r($type_field);
	if (isset($_POST['MODIF']) and $_POST['MODIF'] != ''){
		switch ($type_field[$_POST['MODIF']]){
			case "int" : $java = $chiffres;
							break;
			case "string"  : $java = $majuscule;
							break;
			case "date"  : $java = "READONLY ".dateOnClick('NEW_VALUE');
							break;
			default : $java;
		}
		
		$truename=$_POST['MODIF'];
		if ($_POST['MODIF'] == TAG_LBL)
			$truename='TAG';			
		if ($type_field[$_POST['MODIF']]=="date"){
		$tab_typ_champ[0]['COMMENT_BEHING'] =datePick('NEW_VALUE');
		$tab_typ_champ[0]['DEFAULT_VALUE']=dateFromMysql($item[$truename]);
		}else
		$tab_typ_champ[0]['DEFAULT_VALUE']=$item[$truename];
		$tab_typ_champ[0]['INPUT_NAME']="NEW_VALUE";
		$tab_typ_champ[0]['INPUT_TYPE']=0;
		$tab_typ_champ[0]['CONFIG']['JAVASCRIPT']=$java;
		$tab_typ_champ[0]['CONFIG']['MAXLENGTH']=100;
		$tab_typ_champ[0]['CONFIG']['SIZE']=40;
		$data_form[0]=$_POST['MODIF'];
		tab_modif_values($data_form,$tab_typ_champ,array('TAG_MODIF'=>$_POST['MODIF'],'FIELD_FORMAT'=>$type_field[$_POST['MODIF']]),$l->g(895),"");
		
	}
	echo "</form>";
?>