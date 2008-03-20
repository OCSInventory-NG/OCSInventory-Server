<?php
/*
 * Created on 13 nov. 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 $exlu_group=" deviceid != '_SYSTEMGROUP_' 
									and deviceid != '_DOWNLOADGROUP_' ";
 require_once('require/function_table_html.php');
echo "<script language=javascript>
		function garde_valeur(form_name,did,hidden_name,did2,hidden_name2){
				document.getElementById(hidden_name).value=did;
				document.getElementById(hidden_name2).value=did2;
				document.getElementById(form_name).submit();
		}

</script>";
//function for only count before show result
 function query_on_table_count($name,$lbl_data,$tablename="hardware"){
 	global $exlu_group,$list_on_hardware,$form_name,$data,$data_detail,$titre,$list_on_else,$list_no_show;
 	if (!isset($list_no_show[$name])){
	 	$sql_on_hardware="select count(".$name.") c
						from ".$tablename." h ";
		if ($tablename=="hardware"){
			if ($list_on_hardware == "")
				$sql_on_hardware.=" where ".$exlu_group;
			else
			    $sql_on_hardware.=$list_on_hardware." and ".$exlu_group;
		}
		else
		$sql_on_hardware.=$list_on_else;
		$sql_on_hardware.="	group by ".$name;
		$sql_on_hardware = "select count(*) c from (".$sql_on_hardware.") temp where temp.c != 0";
	 	$result_on_hardware = mysql_query( $sql_on_hardware, $_SESSION["readServer"]);
		$item_on_hardware = mysql_fetch_object($result_on_hardware);
		$data['nb_'.$name]['data']="<a OnClick='garde_valeur(\"".$form_name."\",\"".$name."\",\"detail\",\"".$tablename."\",\"tablename\")'>".$item_on_hardware->c."</a>";
		$data['nb_'.$name]['lbl']=$lbl_data;
 	}
 }
 
//function for show all result  
 function query_on_table($name,$lbl_data,$lbl_data_detail,$tablename="hardware"){
 	global $exlu_group,$list_on_hardware,$form_name,$data,$data_detail,$titre,$list_on_else,$list_no_show;
 	if (!isset($list_no_show[$name])){
	 	$sql_on_hardware="select count(".$name.") c, ".$name." 
						from ".$tablename." h ";
		if ($tablename=="hardware"){
			if ($list_on_hardware == "")
				$sql_on_hardware.=" where ".$exlu_group;
			else
			    $sql_on_hardware.=$list_on_hardware." and ".$exlu_group;
		}else
		$sql_on_hardware.=$list_on_else;
		$sql_on_hardware.="	group by ".$name."
							order by 1 desc ";
	 	$result_on_hardware = mysql_query( $sql_on_hardware, $_SESSION["readServer"]);
		$nb_lign=0;
		while($item_on_hardware = mysql_fetch_object($result_on_hardware)){
			if ($item_on_hardware -> c != 0){
			$data_detail[$name][$nb_lign]['lbl']=$item_on_hardware ->$name;
			$data_detail[$name][$nb_lign]['data']= $item_on_hardware -> c;
		 	$nb_lign++;
			}
		}
		$titre[$name]=$lbl_data_detail;
 	}
 }
 //function for count result
 function query_with_condition($wherecondition,$lbl_data,$name_data,$tablename="hardware"){
 	global $exlu_group,$data,$titre,$list_hardware_id,$list_id,$list_no_show;
 	if (!isset($list_no_show[$name_data])){
	 	$sql_count="select count(*) c from ".$tablename." h ".$wherecondition." ";
	 	if ($tablename=="hardware")
	 	$sql_count.=$list_hardware_id." and ".$exlu_group;
	 	else
	 	$sql_count.=$list_id;
	 	$sql_count.=" order by 1 desc";
	 	$result_count = mysql_query( $sql_count, $_SESSION["readServer"]);
		$item_count = mysql_fetch_object($result_count);
		$data[$name_data]['data']= $item_count -> c;
	 	$data[$name_data]['lbl']=$lbl_data;
 	}

 }
 
//for SADMIN only 
if( $_SESSION["lvluser"] == SADMIN) {
	 if ($_POST['DELETE_OPTION'] != "" and isset($_POST['DELETE_OPTION'])){
			$sql_not_show="insert into config (NAME,IVALUE) values ('OSC_REPORT_".$_POST['DELETE_OPTION']."',1)";
			mysql_query( $sql_not_show, $_SESSION["writeServer"] );

	 }
	 if ($_POST['USE_OPTION'] != "" and isset($_POST['USE_OPTION'])){
			$sql_show="delete from config where name='OSC_REPORT_".$_POST['USE_OPTION']."'";
			
			mysql_query( $sql_show, $_SESSION["writeServer"] );

	 }
	if (isset($_POST['Valid'])){	
		foreach ($_POST as $key=>$value){
			
			if ($value != "" and $key != "Valid" and $key != 'onglet'){
				$sql="delete from config where NAME='GUI_REPORT_".$key."'";
				mysql_query( $sql, $_SESSION["writeServer"] );
				$sql="insert into config (NAME,IVALUE) value ('GUI_REPORT_".$key."',".$value.")";
				mysql_query( $sql, $_SESSION["writeServer"] );
				
			}
			
		}
	}
}
  
 //witch fields not show
 $sql_search_option="select substr(NAME,12) NAME from config where name like 'OSC_REPORT_%'";
 $result_search_option = mysql_query( $sql_search_option, $_SESSION["readServer"]);
while($item_search_option = mysql_fetch_object($result_search_option))
	$list_no_show[$item_search_option ->NAME]=$item_search_option ->NAME;	

//witch option fields
 $sql_search_option="select substr(NAME,12) NAME,IVALUE from config where name like 'GUI_REPORT_%'";
 $result_search_option = mysql_query( $sql_search_option, $_SESSION["readServer"]);
while($item_search_option = mysql_fetch_object($result_search_option))
	$list_option[$item_search_option ->NAME]=$item_search_option ->IVALUE;	

//all fields repart on categories
$repart=array("WORKGROUP"=>"ELSE",
			  "TAG"=>"ELSE",
			  "IPSUBNET"=>"ELSE",
			  "NB_NOTIFIED"=>"ELSE",
			  "NB_ERR"=>"ELSE",
			  "OSNAME"=>"SOFT",
 			  "USERAGENT"=>"SOFT",
			  "PROCESSORT"=>"HARD",
		      "RESOLUTION"=>"HARD",
			  "NB_LIMIT_FREQ_H"=>"HARD",
			  "NB_LIMIT_FREQ_M"=>"HARD",
			  "NB_LIMIT_FREQ_B"=>"HARD",
			  "NB_LIMIT_MEM_H"=>"HARD",
			  "NB_LIMIT_MEM_M"=>"HARD",
			  "NB_LIMIT_MEM_B"=>"HARD",
			  "NB_ALL_COMPUTOR"=>"ACTIVITY",
			  "NB_COMPUTOR"=>"ACTIVITY",
			  "NB_CONTACT"=>"ACTIVITY",
			  "NB_INV"=>"ACTIVITY",
			  "NB_4_MOMENT"=>"ACTIVITY");
//all lbl fields
$lbl_field=array("WORKGROUP"=>$l->g(778),
			  "TAG"=>$l->g(779),
			  "IPSUBNET"=>$l->g(780),
			  "NB_NOTIFIED"=>$l->g(781),
			  "NB_ERR"=>$l->g(782),
			  "OSNAME"=>$l->g(783),
 			  "USERAGENT"=>$l->g(784),
			  "PROCESSORT"=>$l->g(785),
		      "RESOLUTION"=>$l->g(786),
			  "NB_LIMIT_FREQ_H"=>$l->g(787),
			  "NB_LIMIT_FREQ_M"=>$l->g(788),
			  "NB_LIMIT_FREQ_B"=>$l->g(789),
			  "NB_LIMIT_MEM_H"=>$l->g(790),
			  "NB_LIMIT_MEM_M"=>$l->g(791),
			  "NB_LIMIT_MEM_B"=>$l->g(792),
			  "NB_ALL_COMPUTOR"=>$l->g(793),
			  "NB_COMPUTOR"=>$l->g(794),
			  "NB_CONTACT"=>$l->g(795),
			  "NB_INV"=>$l->g(796),
			  "NB_4_MOMENT"=>$l->g(797));

//définition des onglets
$data_on['ACTIVITY']=$l->g(798);
$data_on['SOFT']=strtoupper($l->g(20));
$data_on['HARD']=$l->g(799);
$data_on['ELSE']=$l->g(800);
		  
foreach ($repart as $key=>$value){
	$list_champs[$key]=$key;
}
if (isset($list_no_show)){
	$list_champs=array_diff($list_champs,$list_no_show);
	//create field list for configuration (add a field)
	foreach ($list_no_show as $key=>$value){
		$list_no_show_cat[$key]=$lbl_field[$value]." (".$data_on[$repart[$key]].")";	
	}
}
//create field list for configuration (delete a field)
foreach ($list_champs as $key=>$value){
	$show_on[$repart[$key]]=$repart[$key];
	$list_champs_cat[$key]=$lbl_field[$value]." (".$data_on[$repart[$key]].")";	
}

//show only onglet not empty
foreach ($data_on as $key=>$value){
	if (!isset($show_on[$key]))
	unset($data_on[$key]);
	elseif (!isset($default))
	$default = $key;
}

//onglet que pour Admins
if( $_SESSION["lvluser"] == SADMIN) {
$data_on['CONFIG']=strtoupper($l->g(107));
if (!isset($default))
	$default = 'CONFIG';
}

//if no onglet selected
if (!isset($_POST['onglet']) and isset($default))
 $_POST['onglet']=$default;
elseif(!isset($default))
echo "<table align=center><tr><td align=center><img src='image/fond.png'></td></tr></table>";

if (isset($default)){
	$form_name = "console";
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	 onglet($data_on,$form_name,"onglet",8);
	  echo "<table cellspacing='5' width='80%' BORDER='0' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'><tr><td align=center>";
	if( $_SESSION["lvluser"] == ADMIN) {
		$sql_hardware_id="select hardware_id id from accountinfo a  where ".$_SESSION["mesmachines"];
		$result_hardware_id = mysql_query( $sql_hardware_id, $_SESSION["readServer"]);
		$list_hardware_id="";
		$nb_computor=0;
		while($item_hardware_id = mysql_fetch_object($result_hardware_id)){
			$nb_computor++;
			$list_hardware_id.=$item_hardware_id ->id.",";		
		}
		$list_hardware=substr($list_hardware_id,0,-1);
		$list_hardware_id = " and h.id in(".$list_hardware.")";
		$list_id = " and h.hardware_id in(".$list_hardware.")";
	}
	if (substr($list_hardware_id,4)!="")
		$list_on_hardware=" where ".substr($list_hardware_id,4);
	if (substr($list_id,4)!="")
		$list_on_else=" where ".substr($list_id,4);
	if ($_POST['onglet'] == "ACTIVITY"){
		
		//count number of all computers
		if (!isset($list_no_show['NB_ALL_COMPUTOR']) and !isset($list_no_show['NB_COMPUTOR'])){
			$sql_count_computer="select count(*) c from hardware h 
								 where ".$exlu_group;
			$result_count_computer = mysql_query( $sql_count_computer, $_SESSION["readServer"]);
			$item_count_computer = mysql_fetch_object($result_count_computer);
			if (!isset($list_no_show['NB_ALL_COMPUTOR'])){
				$data['NB_ALL_COMPUTOR']['data']=$item_count_computer-> c;
				$data['NB_ALL_COMPUTOR']['lbl']=$lbl_field['NB_ALL_COMPUTOR'];
			}
			if (!isset($list_no_show['NB_COMPUTOR'])){
				if (isset($nb_computor))
		 		$data['NB_COMPUTOR']['data']= $nb_computor;
		 		else
		 		$data['NB_COMPUTOR']['data']=$item_count_computer-> c;
		 		$data['NB_COMPUTOR']['lbl']=$lbl_field['NB_COMPUTOR'];
			}
		}
	 	query_with_condition("where lastcome > date_format(sysdate(),'%Y-%m-%d 00:00:00') ",
							 $lbl_field['NB_CONTACT'],'NB_CONTACT');
		query_with_condition("where lastdate > date_format(sysdate(),'%Y-%m-%d 00:00:00') ",
							 $lbl_field['NB_INV'],'NB_INV');
		query_with_condition("where unix_timestamp(lastdate) < unix_timestamp(sysdate())-(".$list_option['NOT_VIEW']."*86400) ",
							 $lbl_field['NB_4_MOMENT']." ".$list_option['NOT_VIEW']." ".$l->g(496),'NB_4_MOMENT');
	
	}
	
	if ($_POST['onglet'] == "ELSE"){
		query_on_table_count("WORKGROUP",$lbl_field["WORKGROUP"]);
		query_on_table_count("TAG",$lbl_field["TAG"],"accountinfo");
		query_on_table_count("IPSUBNET",$lbl_field["IPSUBNET"],"networks");
		query_with_condition("where name='DOWNLOAD' and tvalue='NOTIFIED'",$lbl_field['NB_NOTIFIED'],'NB_NOTIFIED',"devices");
		query_with_condition("where name='DOWNLOAD' and substring(tvalue,1,3)='ERR'",$lbl_field['NB_ERR'],'NB_ERR',"devices");
	}
	if ($_POST['onglet'] == "SOFT"){
		query_on_table_count("OSNAME",$lbl_field["OSNAME"]);
		query_on_table_count("USERAGENT",$lbl_field["USERAGENT"]);
		
	}
	
	if ($_POST['onglet'] == "HARD"){
		query_on_table_count("PROCESSORT",$lbl_field["PROCESSORT"]);
		query_on_table_count("RESOLUTION",$lbl_field["RESOLUTION"],"videos");
		query_with_condition("where processors>=".$list_option['PROC_MAX'],$lbl_field['NB_LIMIT_FREQ_H']." ".$list_option['PROC_MAX']." Mhz",'NB_LIMIT_FREQ_H');
		query_with_condition("where processors<=".$list_option['PROC_MINI'],$lbl_field['NB_LIMIT_FREQ_M']." ".$list_option['PROC_MINI']." Mhz",'NB_LIMIT_FREQ_M');
		query_with_condition("where processors>".$list_option['PROC_MINI']." and processors<".$list_option['PROC_MAX'],$lbl_field['NB_LIMIT_FREQ_B']." ".$list_option['PROC_MINI']." Mhz ".$l->g(582)." ".$list_option['PROC_MAX']." Mhz",'NB_LIMIT_FREQ_B');
		query_with_condition("where memory>=".$list_option['RAM_MAX'],$lbl_field['NB_LIMIT_MEM_H']." ".$list_option['RAM_MAX']." Mo",'NB_LIMIT_MEM_H');
		query_with_condition("where memory<=".$list_option['RAM_MINI'],$lbl_field['NB_LIMIT_MEM_M']." ".$list_option['RAM_MINI']." Mo",'NB_LIMIT_MEM_M');
		query_with_condition("where memory>".$list_option['RAM_MINI']." and memory <".$list_option['RAM_MAX'],$lbl_field['NB_LIMIT_MEM_B']." ".$list_option['RAM_MINI']." Mo ".$l->g(582)." ".$list_option['RAM_MAX']." Mo",'NB_LIMIT_MEM_B');
	}
	
	if ($_POST['onglet'] == "CONFIG"){
		require_once('require/function_config_generale.php');
		debut_tab(array('CELLSPACING'=>'5',
						'WIDTH'=>'70%',
						'BORDER'=>'0',
						'ALIGN'=>'Center',
						'CELLPADDING'=>'0',
						'BGCOLOR'=>'#C7D9F5',
						'BORDERCOLOR'=>'#9894B5'));
		if ($list_champs_cat != ""){
			$list_champs_cat['']="";
			ksort($list_champs_cat);
			ligne('DELETE_OPTION',$l->g(801),'select',array('SELECT_VALUE'=>$list_champs_cat,'RELOAD'=>$form_name));
		}
		if ($list_no_show_cat != ""){
			$list_no_show_cat['']="";
			ksort($list_no_show_cat);
	 		ligne('USE_OPTION',$l->g(802),'select',array('VALUE'=>$_POST['USE_OPTION'],'SELECT_VALUE'=>$list_no_show_cat,'RELOAD'=>$form_name));
		}
	 	ligne('NOT_VIEW',$l->g(803),'input',array('VALUE'=>$list_option['NOT_VIEW'],'END'=>$l->g(496),'SIZE'=>2,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
	 	ligne('PROC_MINI',$l->g(804),'input',array('VALUE'=>$list_option['PROC_MINI'],'END'=>'Mhz','SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
	 	ligne('PROC_MAX',$l->g(805),'input',array('VALUE'=>$list_option['PROC_MAX'],'END'=>'Mhz','SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
	 	ligne('RAM_MINI',$l->g(806),'input',array('VALUE'=>$list_option['RAM_MINI'],'END'=>'M','SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
	 	ligne('RAM_MAX',$l->g(807),'input',array('VALUE'=>$list_option['RAM_MAX'],'END'=>'M','SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
		fin_tab($form_name);
		
		
	}
	
	echo "<table>";
	if (isset($data)){
		 foreach ($data as $key=>$value){
		 	echo "<tr height=30px bgcolor='#F2F2F2' BORDERCOLOR='#9894B5'>
				<td align='center' width='300px'><font size=2>".$value['lbl']."</font></td>
				<td align='center' width='150px'><B>".$value['data']."</B></td></tr>";
		 	
		 }
	}
	 echo "</table></table>";
	echo "<input type='hidden' id='detail' name='detail' value=''>";
	echo "<input type='hidden' id='tablename' name='tablename' value=''>";
	
	
	if ($_POST['detail'] != "" and isset($_POST['detail'])){
		query_on_table($_POST['detail'],$lbl_field[$_POST['detail']],$l->g(808),$_POST['tablename']);
		$entete[]="NAME";
		$entete[]="QTE";
		$width=60;
		$height=300;
		//print_r($data_detail);
		tab_entete_fixe($entete,$data_detail[$_POST['detail']],$titre[$_POST['detail']],$width,$height);
	}
	echo "</form>";
}
?>


