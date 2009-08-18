<?php

if ($_POST['LOCK'] != '' and isset($_POST['LOCK'])){
	if ($_POST['CHOISE'] == 'SEL'){
		$array_id=$_GET['idchecked'];		
	}elseif ($_POST['CHOISE'] == 'REQ' or !isset($select_choise)){
		$array_id=implode(',',$_SESSION['ID_REQ']);		
		
	}
	$_SESSION["TRUE_mesmachines"]=$_SESSION["mesmachines"];
	$_SESSION["mesmachines"]=" a.hardware_id in (".$array_id.")";
//	$i=0;
//	while (isset($array_id[$i])){
//	deleteDid($array_id[$i]);
//	$i++;
//	}
	echo "<script language='javascript'> window.opener.document.multisearch.submit();self.close();</script>";
}

//open form
PrintEnTete($l->g(976));
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''><div align=center>";
if (isset($select_choise)){
echo $l->g(977)." ".$select_choise."";
}
if ($_POST['CHOISE'] != "" or !isset($select_choise)){echo $rep;
	echo "<br><br><b>".$l->g(978)."</b>";
	echo "<br><br>".$l->g(979);
	echo "<br><br><input type='submit' value=\"Locker\" name='LOCK'>";
}
echo "</div></form>";//<input type=submit value='Supprimer TOUTES les machines?' name='delete'>

?>