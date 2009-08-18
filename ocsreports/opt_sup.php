<?php

if ($_POST['SUP'] != '' and isset($_POST['SUP'])){
	if ($_POST['CHOISE'] == 'SEL'){
		$array_id=explode(',',$_GET['idchecked']);		
	}elseif ($_POST['CHOISE'] == 'REQ' or !isset($select_choise)){
		if (!is_array($_SESSION['ID_REQ']))
		$array_id=explode(',',$_SESSION['ID_REQ']);	
		else
		$array_id=$_SESSION['ID_REQ'];	
		//print_r($_SESSION['ID_REQ']);
	}
	//$i=0;
	foreach ($array_id as $key=>$hardware_id){
		deleteDid($hardware_id);
		//echo $hardware_id."<br>";
		
	}
//	while (isset($array_id[$i])){
//	
//	$i++;
//	}
	//echo "<script language='javascript'> window.opener.document.multisearch.submit();self.close();</script>";
}

//open form
PrintEnTete($l->g(985));
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''><div align=center>";
if (isset($select_choise)){
echo $l->g(986)." ".$select_choise."";
}
if ($_POST['CHOISE'] != "" or !isset($select_choise)){
	echo "<br><br><input type='submit' value=\"".$l->g(122)."\" name='SUP'>";
}
echo "</div></form>";//<input type=submit value='Supprimer TOUTES les machines?' name='delete'>

?>
