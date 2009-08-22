<?php 
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2006
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on $Date: 2008-06-18 13:26:31 $$Author: airoine $($Revision: 1.14 $)
require_once('require/function_telediff.php');
$form_name='packlist';
//ouverture du formulaire	
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
PrintEnTete($l->g(465));

if($_POST["SUP_PROF"] != "") {
	del_pack($_POST["SUP_PROF"]);
	//regénération du cache
		$tab_options['CACHE']='RESET';
}

//suppression en masse
if ($_POST['del_check'] != ''){
	 foreach (explode(",", $_POST['del_check']) as $key){
	 	del_pack($key);
	 	//regénération du cache
		$tab_options['CACHE']='RESET';	 	
	 }	
}

if (!$_POST['SHOW_SELECT']){
$_POST['SHOW_SELECT']='download';

}
echo "<BR>".show_modif(array('download'=>$l->g(990),'server'=>$l->g(991)),'SHOW_SELECT',2,$form_name)."<BR><BR>";
//recherche du répertoire de création des paquets
if ($_POST['SHOW_SELECT'] == 'download'){
		$sql_document_root="select tvalue from config where NAME='DOWNLOAD_PACK_DIR'";
}else
		$sql_document_root="select tvalue from config where NAME='DOWNLOAD_REP_CREAT'";
		
$res_document_root = mysql_query( $sql_document_root, $_SESSION["readServer"] );
$val_document_root = mysql_fetch_array( $res_document_root );
$document_root = $val_document_root["tvalue"];
//if no directory in base, take $_SERVER["DOCUMENT_ROOT"]
if (!isset($document_root)){
		$document_root = $_SERVER["DOCUMENT_ROOT"]."/download/";
		if ($_POST['SHOW_SELECT'] == "server")
			$document_root .="server/";
		
	}
$rep = $document_root;
$dir = @opendir($rep);
if ($dir){
	while($f = readdir($dir)){
		if (is_numeric ($f))
		$tab_options['SHOW_ONLY']['ZIP'][$f]=$f;
	}
	if (!$tab_options['SHOW_ONLY']['ZIP'])
	$tab_options['SHOW_ONLY']['ZIP']='NULL';
}else
$tab_options['SHOW_ONLY']['ZIP']='NULL';

//javascript pour l'activation manuelle
echo "<script language='javascript'>
		function manualActive()
		 {
			var msg = '';
			var lien = '';
			if( isNaN(document.getElementById('manualActive').value) || document.getElementById('manualActive').value=='' )
				msg = '".$l->g(473)."';
			if( document.getElementById('manualActive').value.length != 10 )
				msg = '".$l->g(474)."';
			if (msg != ''){
				document.getElementById('manualActive').style.backgroundColor = 'RED';
				alert (msg);
				return false;
			}else{
				lien='tele_popup_active.php?active='+ document.getElementById('manualActive').value;
 				window.open(lien,\"active\",\"location=0,status=0,scrollbars=0,menubar=0,resizable=0,width=550,height=350\");
					
			}	
	}
	</script>";


$list_fields= array('Timestamp'=>'FILEID',
							'SHOWACTIVE'=>'NAME',
							$l->g(440)=>'PRIORITY',
							$l->g(464)=>'FRAGMENTS',
							$l->g(462)." Ko"=>'round(SIZE/1024,2)',
							$l->g(25)=>'OSNAME',
							'COMMENT'=>'COMMENT',
							'NO_NOTIF'=>'NO_NOTIF',
							'NOTI'=>'NOTI',
							'SUCC'=>'SUCC',
							'ERR_'=>'ERR_',
							'ZIP'=>'FILEID',
							'STAT'=>'FILEID',
							'ACTIVE'=>'FILEID',
							'SUP'=>'FILEID',
							'CHECK'=>'FILEID'			
							);
$tab_options['LBL_POPUP']['SUP']='NAME';
$table_name="LIST_PACK";
$default_fields= array('Timestamp'=>'Timestamp',
					   'SHOWACTIVE'=>'SHOWACTIVE',
					   'CHECK'=>'CHECK','NOTI'=>'NOTI','SUCC'=>'SUCC',
					   'ERR_'=>'ERR_','SUP'=>'SUP','ACTIVE'=>'ACTIVE','STAT'=>'STAT','ZIP'=>'ZIP');
$list_col_cant_del=array('SHOWACTIVE'=>'SHOWACTIVE','SUP'=>'SUP','ACTIVE'=>'ACTIVE','STAT'=>'STAT','ZIP'=>'ZIP','CHECK'=>'CHECK');
$querypack = 'SELECT distinct ';
foreach ($list_fields as $key=>$value){
		if($key != 'SELECT' 
			and $key != 'ZIP' 
			and $key != 'STAT' 
			and $key != 'ACTIVE' 
			and $key != 'SUP'
			and $key !='CHECK'
			and $key !='NO_NOTIF'
			and $key !='NOTI'
			and $key !='SUCC'
			and $key !='ERR_')
		//	if ()
		$querypack .= $value.',';		
} 
//pas de tri possible sur les colonnes de calcul
$tab_options['NO_TRI']['NOTI']=1;
$tab_options['NO_TRI']['NO_NOTIF']=1;
$tab_options['NO_TRI']['SUCC']=1;
$tab_options['NO_TRI']['ERR_']=1;

$querypack=substr($querypack,0,-1);
$querypack .= " from download_available ";
if ($_POST['SHOW_SELECT'] == 'download')
$querypack .= " where comment not like '[PACK REDISTRIBUTION%' or comment is null or comment = ''";
else
$querypack .= " where comment like '[PACK REDISTRIBUTION%'";
//echo $querypack;
$tab_options['LBL']=array('ZIP'=>"Archives",
							  'STAT'=>$l->g(574),
						      'ACTIVE'=>$l->g(431),
							  'SHOWACTIVE'=>$l->g(49),
							  'NO_NOTIF'=>$l->g(432),
							  'NOTI'=>$l->g(1000),
							  'SUCC'=>$l->g(572),
							  'ERR_'=>$l->g(344));
$tab_options['REQUEST']['STAT']='select distinct fileid AS FIRST from devices d,download_enable de where d.IVALUE=de.ID';
$tab_options['FIELD']['STAT']='FILEID';
$tab_options['REQUEST']['SHOWACTIVE']='select distinct fileid AS FIRST from download_enable';
$tab_options['FIELD']['SHOWACTIVE']='FILEID';
//on force le tri desc pour l'ordre des paquets
if (!$_POST['sens'])
	$_POST['sens']='DESC';
$_SESSION['SQL_DATA_FIXE'][$table_name]['ERR_']="select concat('<font color=red>',count(*),'</font>') as ERR_,de.FILEID
			from devices d,download_enable de 
			where d.IVALUE=de.ID  and d.name='DOWNLOAD' 
			and d.tvalue LIKE 'ERR_%' group by FILEID";
$_SESSION['SQL_DATA_FIXE'][$table_name]['SUCC']="select concat('<font color=green>',count(*),'</font>') as SUCC,de.FILEID
			from devices d,download_enable de 
			where d.IVALUE=de.ID  and d.name='DOWNLOAD' and d.tvalue LIKE 'SUCCESS%' group by FILEID";
$_SESSION['SQL_DATA_FIXE'][$table_name]['NOTI']="select concat('<font color=grey>',count(*),'</font>') as NOTI,de.FILEID
			from devices d,download_enable de 
			where d.IVALUE=de.ID  and d.name='DOWNLOAD' and d.tvalue LIKE 'NOTI%' group by FILEID";	
$_SESSION['SQL_DATA_FIXE'][$table_name]['NO_NOTIF']="select count(*) as NO_NOTIF,de.FILEID
			from devices d,download_enable de 
			where d.IVALUE=de.ID  and d.name='DOWNLOAD' and d.tvalue IS NULL group by FILEID";							
	
$tab_options['FILTRE']=array('FILEID'=>'Timestamp','NAME'=>$l->g(49));
$tab_options['TYPE']['ZIP']=$_POST['SHOW_SELECT'];
$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$querypack,$form_name,95,$tab_options); 
	//traitement par lot
$img['image/sup_search.png']=$l->g(162);
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
			confirme('',idchecked,\"".$form_name."\",\"del_check\",\"".$l->g(900)."\");
		}
	</script>";
	echo "<table align='center' width='30%' border='0'>";
	echo "<tr><td>";
	//foreach ($img as $key=>$value){
		echo "<td align=center><a href=# onclick=garde_check(\"image/sup_search.png\",\"\")><img src='image/sup_search.png' title='".$l->g(162)."' ></a></td>";
	//}
 echo "</tr></tr></table>";
if ($_POST['SHOW_SELECT'] == 'download'){
	$config_input=array('MAXLENGTH'=>10,'SIZE'=>15);
	$activ_manuel=show_modif($_POST['manualActive'],'manualActive',0,'',$config_input);
	echo "<b>".$l->g(476)."</b>&nbsp;&nbsp;&nbsp;".$l->g(475)." : ".$activ_manuel."";
	echo "<a href='#' OnClick='manualActive();'><img src='image/activer.png'></a>";
}
	echo "<input type='hidden' id='del_check' name='del_check' value=''>";
echo "</form>";








?>