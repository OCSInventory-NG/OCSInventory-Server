<?php
	print_item_header($l->g(908));
	$td1	  = "<td height=20px id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>";
	$td2      = "<td height=20px bgcolor='white' align='center'>";
	$queryDetails = 'SELECT JAVANAME,JAVAPATHLEVEL,JAVACOUNTRY,JAVACLASSPATH,JAVAHOME FROM javainfo WHERE hardware_id='.$systemid;
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
	$item = mysql_fetch_object($resultDetails);
	echo '<br><table BORDER="0" WIDTH = "95%" ALIGN = "Center" CELLPADDING="0" BGCOLOR="#C7D9F5" BORDERCOLOR="#9894B5">';
	echo "<tr>";
	echo $td1."JAVANAME </td> ".$td1." JAVAPATHLEVEL </td> ".$td1."JAVACOUNTRY</td>".$td1."JAVACLASSPATH</td>".$td1."JAVAHOME</td></tr>";
	echo "<tr>";
	echo "$td2".textDecode($item->JAVANAME)."</td>
	      $td2".textDecode($item->JAVAPATHLEVEL)."</td>
	      $td2".textDecode($item->JAVACOUNTRY)."</td>
	      $td2".textDecode($item->JAVACLASSPATH)."</td>
	      $td2".textDecode($item->JAVAHOME)."</td>";
	echo "</tr>";
	echo "</table><br>";
	if (!isset($_POST['SHOW']))
		$_POST['SHOW'] = 'NOSHOW';
	$form_name="affich_mobile";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields= array('ID'=>'ID',
						'JOURNALLOG'=>'JOURNALLOG',
						'LISTENERNAME'=>'LISTENERNAME',
						'DATE'=>'DATE',
						'STATUS'=>'STATUS',
						'ERRORCODE'=>'ERRORCODE');
	//$list_fields['SUP']= 'ID';
	$list_col_cant_del['ID']='ID';
	$default_fields= $list_fields;
	$queryDetails  = "SELECT ";
	foreach ($list_fields as $lbl=>$value){
			$queryDetails .= $value.",";		
	}
	$queryDetails  = substr($queryDetails,0,-1)." FROM journallog WHERE (hardware_id=$systemid)";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";

?>