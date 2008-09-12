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
/*
 * Created on 6 fï¿½vr. 2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

 	$numeric="onKeyPress='return scanTouche(event,/[0-9]/)' 
		  onkeydown='convertToUpper(this)'
		  onkeyup='convertToUpper(this)' 
		  onblur='convertToUpper(this)'
		  onclick='convertToUpper(this)'";
		  
	$sup1="<br><font color=green size=1><i> (".$l->g(759)." 1)</i></font>";
	$sup10="<br><font color=green size=1><i> (".$l->g(759)." 10)</i></font>";
/*
 * 
 * function for add ligne in tab
 * $name= varchar : name of ligne
 * $lbl= varchar : wording of the ligne
 * $type= varchar : type of the ligne. in (radio,checkbox,input,text,select)
 * $data= array : data of type ex: 'BEGIN'=> text behing the field,
 * 								   'VALUE'=> value of the field
 * 								   'END'=> text after the field
 * 								   'SIZE'=> field size
 * 								   'MAXLENGHT'=> field MAXLENGHT
 * 								   'JAVASCRIPT'=> if you want a javascript on the field
 * 								   'CHECK' => only for checkbox for ckeck the good boxes
 * $data_hidden = data of hidden ex: 'HIDDEN'=> name of field when you clic on, a hidden field appear
 * 									 'HIDDEN_VALUE'=> value of the hidden field
 * 									 'END'=> what you see after the field
 * 									 'JAVASCRIPT'=> if you want a javascript on the hidden field
 */
 function ligne($name,$lbl,$type,$data,$data_hidden='',$readonly=''){
 

 	echo "<TR height=65px bgcolor='#F2F2F2' BORDERCOLOR='#9894B5'><td align='center' width='150px'>".$name;
	echo "<br><font size=1 color=green><i>".$lbl."</i></font></td><td align='left' width='150px'>";
	//si on est dans un type bouton ou boite ï¿½ cocher
 	if ($type=='radio' or $type=='checkbox'){
 		if ($data_hidden != ''){
 			//javascript for hidden or show an html DIV 
		 	echo "<script language='javascript'>
			function active(id, sens) {
				var mstyle = document.getElementById(id).style.display	= (sens!=0?\"block\" :\"none\");
			}	
			</script>"; 			
 		}
 		//si le champ hidden est celui qui doit ï¿½tre affichï¿½ en entrï¿½e, il faut afficher le champ
 		//echo "<br>hidden ==".$data_hidden['HIDDEN']."      value ==".$data['VALUE'];
 		if ($data_hidden['HIDDEN']==$data['VALUE'])
 		$display="block";
 		else
 		$display="none";
 		//var for name of chekbox
 		$i=1;
 		//pour toutes les valeurs
 		foreach ($data as $key=>$value){
 			//sauf la valeur ï¿½ afficher
 			if ($key !== 'VALUE' and $key !== 'CHECK'){
  				echo "<input type='".$type."' value='".$key."' id='".$name."' ";
 				if ($readonly != '')
 				echo "disabled=\"disabled\"";
 				echo "name='".$name;
 				if ($type=='checkbox'){
 					echo "_".$i;
 					$i++;
 				}
 				echo "'";
 				//si un champ hidden est demandï¿½, on gï¿½re l'affichage par javascript
	 			if ($data_hidden != '' and  $data_hidden['HIDDEN'] == $key){
	 				echo "OnClick=\"active('".$name."_div',1);\"";
	 			}elseif ($data_hidden != '' and  $data_hidden['HIDDEN'] != key){
	 				echo "OnClick=\"active('".$name."_div',0);\"";	 				
	 			}
	 			if ($data['VALUE'] == $key or isset($data['CHECK'][$key]))
	 			echo "checked";
	 			echo ">".$value; 
	 			if ($data_hidden != '' and  $data_hidden['HIDDEN'] == $key){
	 				echo "<div id='".$name."_div' style='display:".$display."'><input type='text' size='".($data_hidden['SIZE']?$data_hidden['SIZE']:"3")."' maxlength='".($data_hidden['SIZE']?$data_hidden['SIZE']:"2")."' id='".$name."_edit' name='".$name."_edit' value='".$data_hidden['HIDDEN_VALUE']."' ".$data_hidden['JAVASCRIPT'].">".$data_hidden['END']."</div>"; 	
	 			}
	 			echo "<br>";
 			}
 		}

 	}elseif($type=='input'){
 		if ($readonly != '')
 		$ajout_readonly=" disabled=\"disabled\" style='color:black; background-color:#e1e1e2;'";
 		echo $data['BEGIN']."<input ".$ajout_readonly."  type='text' name='".$name."' id='".$name."' value='".$data['VALUE']."' size=".$data['SIZE']." maxlength=".$data['MAXLENGHT']." ".$data['JAVASCRIPT'].">".$data['END']; 		
 	}elseif($type=='text'){
 		echo $data[0];
 	}elseif($type=='select'){
 		echo "<select name='".$name."'";
		if (isset($data['RELOAD'])) echo " onChange='document.".$data['RELOAD'].".submit();'";
		foreach ($data['SELECT_VALUE'] as $key=>$value){
			echo "<option value='".$key."'";
			if ($data['VALUE'] == $key )
			echo " selected";
			echo ">".$value."</option>";
		}
		echo "</select>";
 		//array('VALUE'=>$values['tvalue']['OCS_FILES_FORMAT'],'SELECT_VALUE'=>array('OCS'=>'OCS','XML'=>'XML'))
 	}
 	echo "</td></tr>";
 	
 }
 
function debut_tab($config){
	
	echo "<table cellspacing='".$config['CELLSPACING']."'
				 width='".$config['WIDTH']."' 
				BORDER='".$config['BORDER']."' 
				ALIGN = '".$config['ALIGN']."' 
				CELLPADDING='".$config['CELLPADDING']."' 
				BGCOLOR='".$config['BGCOLOR']."' 
				BORDERCOLOR='".$config['BORDERCOLOR']."'>";
	
}
//function 
function verif_champ(){
	global $_POST,$l;
	$supp1=array("DOWNLOAD_CYCLE_LATENCY","DOWNLOAD_FRAG_LATENCY","DOWNLOAD_PERIOD_LATENCY",
				 "DOWNLOAD_PERIOD_LENGTH","DOWNLOAD_TIMEOUT","PROLOG_FREQ","IPDISCOVER_MAX_ALIVE",
			     "GROUPS_CACHE_REVALIDATE","GROUPS_CACHE_OFFSET","LOCK_REUSE_TIME","INVENTORY_CACHE_REVALIDATE",
				 "IPDISCOVER_BETTER_THRESHOLD","GROUPS_CACHE_OFFSET","GROUPS_CACHE_REVALIDATE","INVENTORY_FILTER_FLOOD_IP_CACHE_TIME",
				 "SESSION_VALIDITY_TIME","IPDISCOVER_LATENCY");
	$supp10=array("IPDISCOVER_LATENCY");
	$i=0;
	while ($supp1[$i]){
		if ($_POST[$supp1[$i]] < 1 and isset($_POST[$supp1[$i]]))
			$tab_error[$supp1[$i]]='1';
		$i++;
	}
	$i=0;
	while ($supp10[$i]){
		if ($_POST[$supp10[$i]] < 10 and isset($_POST[$supp10[$i]]))
			$tab_error[$supp10[$i]]='10';	
		$i++;
	}
	return $tab_error;
	//echo $l->g(759);
	//$error=  "doit être supérieur à 1!!!";
	
	
}



function fin_tab($form_name,$disable=''){
	global $l;
	if ($disable != '')
	$gris="disabled=disabled";
	else
	$gris="";
	echo "<tr><td align=center colspan=100><input type='submit' name='Valid' value='".$l->g(103)."' align=center $gris></td></tr>";
	echo "</table>";
	
}

function look_default_values($field_name){
	$sql="select NAME,IVALUE,TVALUE from config where NAME in ('".implode("','", $field_name)."')";
	$resdefaultvalues = mysql_query( $sql, $_SESSION["readServer"]);
	while($item = mysql_fetch_object($resdefaultvalues)){
			$result['name'][$item ->NAME]=$item ->NAME;
			$result['ivalue'][$item ->NAME]=$item ->IVALUE;
			$result['tvalue'][$item ->NAME]=$item ->TVALUE;
	}
	return $result;
}

function look_perso_values($champs,$origine,$systemid){
	//not a sql query
	if ($origine != ""){
	//looking for value of systemid
	$sql_value_idhardware="select * from devices where name != 'DOWNLOAD' and hardware_id=".$systemid;
	$result_value = mysql_query($sql_value_idhardware, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while($value=mysql_fetch_array($result_value)) {
		$optvalue[$value["NAME"] ] = $value["IVALUE"];
		}
	return $optvalue;
	}else{
		$list_hardware_id="";
		$lareq = getPrelim( $_SESSION["storedRequest"] );
		if( ! $res = @mysql_query( $lareq, $_SESSION["readServer"] ))
			return false;
		while( $val = @mysql_fetch_array($res)) {
		$hardware_id['list'].=$val["h.id"].",";
		$hadware_id['tab']=$val["h.id"];
		}
		$hardware_id['list'] = substr($hardware_id['list'],0,-1);
		return $hardware_id;
	 
	}
	
}


function update_default_value($POST){
	global $l;
	$i=0;
	
	foreach ($POST as $key=>$value){
		unset($sql);
		unset($optexist);
		$sql_exist=" select NAME from config where NAME='".$key."'";
		$result_exist = mysql_query($sql_exist, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		while($value_exist=mysql_fetch_array($result_exist)) {
			$optexist[$value_exist["NAME"] ] = $value_exist["NAME"];
		}
		if ($value != ""){
				if ($key != 'onglet'
					and $key != 'old_onglet' 
					and $key != 'Valid' 
					and $key != 'LOCAL_SERVER'
					and $key != 'DOWNLOAD_SERVER_URI' 
					and $key != 'DOWNLOAD_SERVER_DOCROOT'
					and $key != 'OCS_FILES_FORMAT'
					and $key != 'OCS_FILES_PATH'
					and substr($key,0,18) != 'AUTO_DUPLICATE_LVL' 
					and $key != 'FREQUENCY' and $key!= 'FREQUENCY_edit'
					and $key != 'IPDISCOVER' and $key != 'IPDISCOVER_edit'
					and $key != 'DOWNLOAD_PACK_DIR' and $key != 'DOWNLOAD_PACK_DIR_edit'
					and $key != 'IPDISCOVER_IPD_DIR' and $key != 'IPDISCOVER_IPD_DIR_edit'){
						if (isset($optexist))
						$sql="update config set IVALUE = ".$value." where NAME ='".$key."'";
						else
						$sql="insert into config (IVALUE, NAME) value (".$value.",'".$key."')";
					
				//echo $sql."<br>";
					
				}
				elseif(($key == 'DOWNLOAD_PACK_DIR' and $value == 'CUSTOM')
						or($key == 'IPDISCOVER_IPD_DIR' and $value == 'CUSTOM')){
					if (isset($optexist) and trim($POST[$key."_edit"]) != '')
					$sql="update config set TVALUE = '".$POST[$key."_edit"]."' where NAME ='".$key."'";
					elseif(trim($POST[$key."_edit"]) != '')
					$sql="insert into config (TVALUE, NAME) value ('".$POST[$key."_edit"]."','".$key."')";
				}
				elseif(($key == 'DOWNLOAD_PACK_DIR' or $key == 'IPDISCOVER_IPD_DIR')
						and trim($POST[$key."_edit"]) != ''){
					$sql="delete from config where NAME ='".$key."'";
					
				}
				elseif ($key == 'OCS_FILES_FORMAT' or 
						$key == 'LOCAL_SERVER' or 
						$key == 'DOWNLOAD_SERVER_URI' or 
						$key == 'DOWNLOAD_SERVER_DOCROOT' or
						$key == 'OCS_FILES_PATH'){
							if (isset($optexist))
							$sql="update config set TVALUE = '".$value."' where NAME ='".$key."'";
							else
							$sql="insert into config (TVALUE, NAME) value ('".$value."','".$key."')";
				}
				elseif(substr($key,0,18) == 'AUTO_DUPLICATE_LVL'){
					$i+=auto_duplicate_lvl_poids($value,2);		
					//if (isset($optexist))
					$sql="update config set IVALUE = ".$i." where NAME ='AUTO_DUPLICATE_LVL'";	
					//else
					//$sql="insert into config (IVALUE, NAME) value (".$i.",'AUTO_DUPLICATE_LVL')";
				}
				elseif($key == 'FREQUENCY' and $value != 'CUSTOM'){
					if ($value == 'ALWAYS') $ivalue=0; else $ivalue=-1;
						if (isset($optexist))
						$sql="update config set IVALUE = ".$ivalue." where NAME ='".$key."'";
						else
						$sql="insert into config (IVALUE, NAME) value (".$ivalue.",'".$key."')";
				}
				elseif($key == 'FREQUENCY' and $value == 'CUSTOM' and $POST[$key."_edit"] != ""){
					if (isset($optexist))
					$sql="update config set IVALUE = ".$POST[$key."_edit"]." where NAME ='".$key."'";
					else
					$sql="insert into config (IVALUE, NAME) value (".$POST[$key."_edit"].",'".$key."')";
				}
				elseif($key == 'IPDISCOVER' and $value == 'OFF'){
					if (isset($optexist))
					$sql="update config set IVALUE = 0 where NAME ='".$key."'";
					else
					$sql="insert into config (IVALUE, NAME) value (0,'".$key."')";
				}
				elseif($key == 'IPDISCOVER' and $value == 'ON' and $POST[$key."_edit"] != ""){
					if (isset($optexist))
					$sql="update config set IVALUE = ".$POST[$key."_edit"]." where NAME ='".$key."'";	
					else
					$sql="insert into config (IVALUE, NAME) value (".$POST[$key."_edit"].",'".$key."')";
				}
				
				if (isset($sql)){
					if( ! @mysql_query( $sql, $_SESSION["writeServer"] )) {
						echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
						return false;
					}else
					$MAJ=$l->g(711);
				}
		}
		if ($POST['onglet'] == $l->g(499) and $i==0){
			$sql="update config set IVALUE = ".$i." where NAME ='AUTO_DUPLICATE_LVL'";
			if( ! @mysql_query( $sql, $_SESSION["writeServer"] )) {
						echo "<br><center><font color=red><b>ERROR: MySql connection problem<br>".mysql_error($_SESSION["writeServer"])."</b></font></center>";
						return false;
					}		
		}
	}
	return $MAJ;
	

}

function auto_duplicate_lvl_poids($value,$entree_sortie){
	//dï¿½finition du poids des auto_duplicate_lvl
 	$poids['HOSTNAME']=1;
 	$poids['SERIAL']=2;
 	$poids['MACADRESSE']=4;
 	$poids['MODEL']=8;	
 	//si on veut les cases cochï¿½es par rapport ï¿½ un chiffre
 	if ($entree_sortie == 1){
 		//gestion des poids pour connaitre les cases cochï¿½es.
 	//ex: si AUTO_DUPLICATE_LVL == 7 on a les cases HOSTNAME (de poids 1), SERIAL (de poids 2) et MACADRESSE (de poids 4) 
 	//cochï¿½es (1+2+4=7)
 		foreach ($poids as $k=>$v){
 			if ($value & $v)
 			$check[$k]=$k;
 		}
 	}//si on veut le chiffre par rapport a la case cochï¿½e
 	else{
 		foreach ($poids as $k=>$v){
 			if ($k == $value)
 			$check=$v;
 		}
 		
 	}
 	
 return $check;
}
 function pageGUI($form_name){
 	global $l,$numeric,$sup1;
 		//what ligne we need?
 	$champs=array( 'LOCAL_PORT'=>'LOCAL_PORT',
				  'LOCAL_SERVER'=>'LOCAL_SERVER',
				  'DOWNLOAD_PACK_DIR'=>'DOWNLOAD_PACK_DIR',
				  'IPDISCOVER_IPD_DIR'=>'IPDISCOVER_IPD_DIR');
	$values=look_default_values($champs);
	if (isset($values['tvalue']['DOWNLOAD_PACK_DIR']))
	$select_pack='CUSTOM';
	else
	$select_pack='DEFAULT';
	if (isset($values['tvalue']['IPDISCOVER_IPD_DIR']))
	$select_ipd='CUSTOM';
	else
	$select_ipd='DEFAULT';
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'90%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
	ligne('LOCAL_PORT',$l->g(566),'input',array('VALUE'=>$values['ivalue']['LOCAL_PORT'],'SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
	ligne('LOCAL_SERVER',$l->g(565),'input',array('BEGIN'=>'HTTP://','VALUE'=>$values['tvalue']['LOCAL_SERVER'],'SIZE'=>50,'MAXLENGHT'=>254));
	ligne('DOWNLOAD_PACK_DIR',$l->g(775),'radio',array('DEFAULT'=>"Par dï¿½faut(".$_SERVER["DOCUMENT_ROOT"]."/DOWNLOAD)",'CUSTOM'=>"Personnaliser",'VALUE'=>$select_pack),
		array('HIDDEN'=>'CUSTOM','HIDDEN_VALUE'=>$values['tvalue']['DOWNLOAD_PACK_DIR'],'SIZE'=>70,'END'=>"/DOWNLOAD"));
	ligne('IPDISCOVER_IPD_DIR',$l->g(776),'radio',array('DEFAULT'=>"Par dï¿½faut(".$_SERVER["DOCUMENT_ROOT"]."/oscreport/IPD)",'CUSTOM'=>"Personnaliser",'VALUE'=>$select_ipd),
		array('HIDDEN'=>'CUSTOM','HIDDEN_VALUE'=>$values['tvalue']['IPDISCOVER_IPD_DIR'],'SIZE'=>70,'END'=>"/IPD"));
		
	fin_tab($form_name);
 	
 }
 
 
 
 function pageteledeploy($form_name){
 	global $l,$numeric,$sup1; 
	//open array;		
	//what ligne we need?
	$champs=array('DOWNLOAD'=>'DOWNLOAD',
				  'DOWNLOAD_CYCLE_LATENCY'=>'DOWNLOAD_CYCLE_LATENCY',
				  'DOWNLOAD_FRAG_LATENCY'=>'DOWNLOAD_FRAG_LATENCY',
				  'DOWNLOAD_GROUPS_TRACE_EVENTS'=>'DOWNLOAD_GROUPS_TRACE_EVENTS',
				  'DOWNLOAD_PERIOD_LATENCY'=>'DOWNLOAD_PERIOD_LATENCY',
				  'DOWNLOAD_TIMEOUT'=>'DOWNLOAD_TIMEOUT',
				  'DOWNLOAD_PERIOD_LENGTH'=>'DOWNLOAD_PERIOD_LENGTH',
				  'DEPLOY'=>'DEPLOY');
	
 	$values=look_default_values($champs);			 
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'70%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
	//create diff lign for general config	
 		ligne('DOWNLOAD',$l->g(417),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['DOWNLOAD'])); 	
 		ligne('DOWNLOAD_CYCLE_LATENCY',$l->g(720),'input',array('VALUE'=>$values['ivalue']['DOWNLOAD_CYCLE_LATENCY'],'END'=>$l->g(511).$sup1,'SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
  		ligne('DOWNLOAD_FRAG_LATENCY',$l->g(721),'input',array('VALUE'=>$values['ivalue']['DOWNLOAD_FRAG_LATENCY'],'END'=>$l->g(511).$sup1,'SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
 		ligne('DOWNLOAD_GROUPS_TRACE_EVENTS',$l->g(758),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['DOWNLOAD_GROUPS_TRACE_EVENTS'])); 	
  		ligne('DOWNLOAD_PERIOD_LATENCY',$l->g(722),'input',array('VALUE'=>$values['ivalue']['DOWNLOAD_PERIOD_LATENCY'],'END'=>$l->g(511).$sup1,'SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
 		ligne('DOWNLOAD_TIMEOUT',$l->g(424),'input',array('VALUE'=>$values['ivalue']['DOWNLOAD_TIMEOUT'],'END'=>$l->g(496).$sup1,'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
  		ligne('DOWNLOAD_PERIOD_LENGTH',$l->g(723),'input',array('VALUE'=>$values['ivalue']['DOWNLOAD_PERIOD_LENGTH'],'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
		ligne('DEPLOY',$l->g(414),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['DEPLOY']));
	//	ligne('DOWNLOAD_PERIOD_LENGTH',$l->g(723),'input',array('VALUE'=>$values['ivalue']['DOWNLOAD_PERIOD_LENGTH'],'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
	//end array
	fin_tab($form_name);
 }
 
function pagegroups($form_name){
 	global $l,$numeric,$sup1;	 
 	//open array;		
	//what ligne we need?
	$champs=array('ENABLE_GROUPS'=>'ENABLE_GROUPS',
				  'GROUPS_CACHE_OFFSET'=>'GROUPS_CACHE_OFFSET',
				  'GROUPS_CACHE_REVALIDATE'=>'GROUPS_CACHE_REVALIDATE');
	
 	$values=look_default_values($champs);			 
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'70%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
	//create diff lign for general config	
 	//create diff lign for general config	
 		ligne('ENABLE_GROUPS',$l->g(736),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['ENABLE_GROUPS'])); 	
 		ligne('GROUPS_CACHE_OFFSET',$l->g(737),'input',array('END'=>$l->g(511).$sup1,'VALUE'=>$values['ivalue']['GROUPS_CACHE_OFFSET'],'SIZE'=>5,'MAXLENGHT'=>6,'JAVASCRIPT'=>$numeric)); 	
 		ligne('GROUPS_CACHE_REVALIDATE',$l->g(738),'input',array('END'=>$l->g(511).$sup1,'VALUE'=>$values['ivalue']['GROUPS_CACHE_REVALIDATE'],'SIZE'=>5,'MAXLENGHT'=>6,'JAVASCRIPT'=>$numeric)); 	
 
	fin_tab($form_name);
 	
 	
}
 
 function pageserveur($form_name){
 	global $l,$numeric,$sup1;	 
 	
 	//what ligne we need?
 	$champs=array('LOGLEVEL'=>'LOGLEVEL',
				  'PROLOG_FREQ'=>'PROLOG_FREQ',				  
				  'AUTO_DUPLICATE_LVL'=>'AUTO_DUPLICATE_LVL',
				  'SECURITY_LEVEL'=>'SECURITY_LEVEL',
				  'LOCK_REUSE_TIME'=>'LOCK_REUSE_TIME',
				  'TRACE_DELETED'=>'TRACE_DELETED',
				  'SESSION_VALIDITY_TIME'=>'SESSION_VALIDITY_TIME');
 	$values=look_default_values($champs);
 	if (isset($champs['AUTO_DUPLICATE_LVL']))
 	//on utilise la fonction pour connaï¿½tre les cases cochï¿½es correspondantes au chiffre en base de AUTO_DUPLICATE_LVL
 	$check=auto_duplicate_lvl_poids($values['ivalue']['AUTO_DUPLICATE_LVL'],1);
  	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'80%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
	ligne('LOGLEVEL',$l->g(416),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['LOGLEVEL']));
	ligne('PROLOG_FREQ',$l->g(564),'input',array('END'=>$l->g(730).$sup1,'VALUE'=>$values['ivalue']['PROLOG_FREQ'],'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));	
	ligne('AUTO_DUPLICATE_LVL',$l->g(427),'checkbox',array('HOSTNAME'=>'hostname','SERIAL'=>'Serial','MACADRESSE'=>'macaddress','MODEL'=>'model','CHECK'=>$check));
	ligne('SECURITY_LEVEL',$l->g(739),'input',array('VALUE'=>$values['ivalue']['SECURITY_LEVEL'],'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric),'',"readonly");	
	ligne('LOCK_REUSE_TIME',$l->g(740),'input',array('END'=>$l->g(511).$sup1,'VALUE'=>$values['ivalue']['LOCK_REUSE_TIME'],'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));	
	ligne('TRACE_DELETED',$l->g(415),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['TRACE_DELETED']));
	ligne('SESSION_VALIDITY_TIME',$l->g(777),'input',array('END'=>$l->g(511).$sup1,'VALUE'=>$values['ivalue']['SESSION_VALIDITY_TIME'],'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));	
	 	
	fin_tab($form_name);
 	
 }
 function pageinventory($form_name){
 	global $l,$numeric,$sup1;
 		//what ligne we need?
 	$champs=array('FREQUENCY'=>'FREQUENCY',
				  'INVENTORY_DIFF'=>'INVENTORY_DIFF',
				  'INVENTORY_TRANSACTION'=>'INVENTORY_TRANSACTION',
				  'INVENTORY_WRITE_DIFF'=>'INVENTORY_WRITE_DIFF',
				  'INVENTORY_SESSION_ONLY'=>'INVENTORY_SESSION_ONLY');
	$values=look_default_values($champs);
// 	//gestion du champ FREQUENCY
// 	if (isset($champs['FREQUENCY'])){
// 		$frequency=$values['ivalue']['FREQUENCY'];  	
//	 	if ($default_values['ivalue']['FREQUENCY'] != 0 and $values['ivalue']['FREQUENCY']!=-1)
//	 	$values['ivalue']['FREQUENCY']='CUSTOM';
// 	}
 	
	if ($values['ivalue']['FREQUENCY'] == 0 and isset($values['ivalue']['FREQUENCY']))
	$optvalueselected = 'ALWAYS';
	elseif($values['ivalue']['FREQUENCY'] == -1)
	$optvalueselected = 'NEVER';
	else
	$optvalueselected ='CUSTOM';
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'70%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
		ligne('FREQUENCY',$l->g(494),'radio',array('ALWAYS'=>$l->g(485),'NEVER'=>$l->g(486),'CUSTOM'=>$l->g(487),'VALUE'=>$optvalueselected),array('HIDDEN'=>'CUSTOM','HIDDEN_VALUE'=>$values['ivalue']['FREQUENCY'],'END'=>$l->g(496),'JAVASCRIPT'=>$numeric));
		ligne('INVENTORY_DIFF',$l->g(741),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_DIFF']));
		ligne('INVENTORY_TRANSACTION',$l->g(742),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_TRANSACTION']));
		ligne('INVENTORY_WRITE_DIFF',$l->g(743),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_WRITE_DIFF']));
		ligne('INVENTORY_SESSION_ONLY',$l->g(744),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_SESSION_ONLY']));
	fin_tab($form_name);
 	
 }
 function pageregistry($form_name){
 	global $l,$numeric,$sup1;
 		//what ligne we need?
 	$champs=array('REGISTRY'=>'REGISTRY');
	$values=look_default_values($champs);
	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'70%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
	ligne('REGISTRY',$l->g(412),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['REGISTRY']));
 	fin_tab($form_name);
 }
 
 function pageipdiscover($form_name){
 	global $l,$numeric,$sup1,$sup10;
 	//what ligne we need?
 	$champs=array('IPDISCOVER'=>'IPDISCOVER',
 				  'IPDISCOVER_BETTER_THRESHOLD'=>'IPDISCOVER_BETTER_THRESHOLD',
				  'IPDISCOVER_LATENCY'=>'IPDISCOVER_LATENCY',
				  'IPDISCOVER_MAX_ALIVE'=>'IPDISCOVER_MAX_ALIVE',
				  'IPDISCOVER_NO_POSTPONE'=>'IPDISCOVER_NO_POSTPONE',
				  'IPDISCOVER_USE_GROUPS'=>'IPDISCOVER_USE_GROUPS');
 	
 	$values=look_default_values($champs);
 	if (isset($champs['IPDISCOVER'])){
 		$ipdiscover=$values['ivalue']['IPDISCOVER'];
 		//gestion des diffï¿½rentes valeurs de l'ipdiscover
 		if ($values['ivalue']['IPDISCOVER'] != 0)
 		$values['ivalue']['IPDISCOVER']='ON';
 		else
 		$values['ivalue']['IPDISCOVER']='OFF';
 	}
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'70%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5'));
 	ligne('IPDISCOVER',$l->g(425),'radio',array('ON'=>'ON','OFF'=>'OFF','VALUE'=>$values['ivalue']['IPDISCOVER']),array('HIDDEN'=>'ON','HIDDEN_VALUE'=>$ipdiscover,'END'=>$l->g(729),'JAVASCRIPT'=>$numeric));
	ligne('IPDISCOVER_BETTER_THRESHOLD',$l->g(746),'input',array('VALUE'=>$values['ivalue']['IPDISCOVER_BETTER_THRESHOLD'],'END'=>$l->g(496).$sup1,'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
	ligne('IPDISCOVER_LATENCY',$l->g(567),'input',array('VALUE'=>$values['ivalue']['IPDISCOVER_LATENCY'],'END'=>$l->g(732).$sup10,'SIZE'=>2,'MAXLENGHT'=>4,'JAVASCRIPT'=>$numeric));
	ligne('IPDISCOVER_MAX_ALIVE',$l->g(419),'input',array('VALUE'=>$values['ivalue']['IPDISCOVER_MAX_ALIVE'],'END'=>$l->g(496).$sup1,'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
	ligne('IPDISCOVER_NO_POSTPONE',$l->g(747),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['IPDISCOVER_NO_POSTPONE']));
	ligne('IPDISCOVER_USE_GROUPS',$l->g(748),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['IPDISCOVER_USE_GROUPS']));
	
	fin_tab($form_name);
 }
 
 
 function pageredistrib($form_name){
 	global $l,$numeric,$sup1;
 	//what ligne we need?
 	$champs=array('DOWNLOAD_SERVER_URI'=>'DOWNLOAD_SERVER_URI',
				  'DOWNLOAD_SERVER_DOCROOT'=>'DOWNLOAD_SERVER_DOCROOT');
 	$values=look_default_values($champs);
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'80%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5')); 
 	ligne('DOWNLOAD_SERVER_URI',$l->g(726),'input',array('BEGIN'=>'HTTP://','VALUE'=>$values['tvalue']['DOWNLOAD_SERVER_URI'],'SIZE'=>50,'MAXLENGHT'=>254));
 	ligne('DOWNLOAD_SERVER_DOCROOT',$l->g(727),'input',array('VALUE'=>$values['tvalue']['DOWNLOAD_SERVER_DOCROOT'],'SIZE'=>50,'MAXLENGHT'=>254));
	fin_tab($form_name);
 }
 
 function pagefilesInventory($form_name){
 	global $l,$numeric,$sup1;
 	//what ligne we need?
 	$champs=array('GENERATE_OCS_FILES'=>'GENERATE_OCS_FILES',
				  'OCS_FILES_FORMAT'=>'OCS_FILES_FORMAT',
				  'OCS_FILES_OVERWRITE'=>'OCS_FILES_OVERWRITE',
				  'OCS_FILES_PATH'=>'OCS_FILES_PATH');
 	$values=look_default_values($champs);
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'80%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5')); 
 	ligne('GENERATE_OCS_FILES',$l->g(749),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['GENERATE_OCS_FILES']));
	ligne('OCS_FILES_FORMAT',$l->g(750),'select',array('VALUE'=>$values['tvalue']['OCS_FILES_FORMAT'],'SELECT_VALUE'=>array('OCS'=>'OCS','XML'=>'XML')));
 	ligne('OCS_FILES_OVERWRITE',$l->g(751),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['OCS_FILES_OVERWRITE']));
	ligne('OCS_FILES_PATH',$l->g(752),'input',array('VALUE'=>$values['tvalue']['OCS_FILES_PATH'],'SIZE'=>50,'MAXLENGHT'=>254));
	fin_tab($form_name);
 }
 
 function pagefilter($form_name){
 	global $l,$numeric,$sup1;
 	//what ligne we need?
 	$champs=array('PROLOG_FILTER_ON'=>'PROLOG_FILTER_ON',
				  'INVENTORY_FILTER_ENABLED'=>'INVENTORY_FILTER_ENABLED',
				  'INVENTORY_FILTER_FLOOD_IP'=>'INVENTORY_FILTER_FLOOD_IP',
				  'INVENTORY_FILTER_FLOOD_IP_CACHE_TIME'=>'INVENTORY_FILTER_FLOOD_IP_CACHE_TIME',
				  'INVENTORY_FILTER_ON'=>'INVENTORY_FILTER_ON');
 	$values=look_default_values($champs);
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'80%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5')); 
	ligne('PROLOG_FILTER_ON',$l->g(753),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['PROLOG_FILTER_ON']));
	ligne('INVENTORY_FILTER_ENABLED',$l->g(754),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_FILTER_ENABLED']));
	ligne('INVENTORY_FILTER_FLOOD_IP',$l->g(755),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_FILTER_FLOOD_IP']));
	ligne('INVENTORY_FILTER_FLOOD_IP_CACHE_TIME',$l->g(756),'input',array('VALUE'=>$values['ivalue']['INVENTORY_FILTER_FLOOD_IP_CACHE_TIME'],'END'=>$l->g(511).$sup1,'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric));
	ligne('INVENTORY_FILTER_ON',$l->g(757),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['INVENTORY_FILTER_ON']));
 	fin_tab($form_name);
 }
 
 function pagewebservice($form_name){
 	global $l,$numeric,$sup1;
 	//what ligne we need?
 	$champs=array('WEB_SERVICE_ENABLED'=>'WEB_SERVICE_ENABLED',
				  'WEB_SERVICE_RESULTS_LIMIT'=>'WEB_SERVICE_RESULTS_LIMIT',
				  'WEB_SERVICE_PRIV_MODS_CONF'=>'WEB_SERVICE_PRIV_MODS_CONF');
 	$values=look_default_values($champs);
 	debut_tab(array('CELLSPACING'=>'5',
					'WIDTH'=>'80%',
					'BORDER'=>'0',
					'ALIGN'=>'Center',
					'CELLPADDING'=>'0',
					'BGCOLOR'=>'#C7D9F5',
					'BORDERCOLOR'=>'#9894B5')); 
					echo "<tr><td align=center colspan=100><font size=4 color=red><b>".$l->g(764)."</b></font></td></tr>";
	ligne('WEB_SERVICE_ENABLED',$l->g(761),'radio',array(1=>'ON',0=>'OFF','VALUE'=>$values['ivalue']['WEB_SERVICE_ENABLED']),'',"readonly");
	ligne('WEB_SERVICE_RESULTS_LIMIT',$l->g(762),'input',array('VALUE'=>$values['ivalue']['WEB_SERVICE_RESULTS_LIMIT'],'END'=>$l->g(511).$sup1,'SIZE'=>1,'MAXLENGHT'=>3,'JAVASCRIPT'=>$numeric),'',"readonly");
	ligne('WEB_SERVICE_PRIV_MODS_CONF',$l->g(763),'input',array('VALUE'=>$values['tvalue']['WEB_SERVICE_PRIV_MODS_CONF'],'SIZE'=>50,'MAXLENGHT'=>254),'',"readonly");
 	fin_tab($form_name,"disabled");
 }
?>
