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
//Modified on $Date: 2008-02-27 12:34:12 $$Author: hunal $($Revision: 1.10 $)
require_once('require/function_telediff.php');
if ($_POST['DEL_ALL'] != ''){
	$sql_listIDdel="select distinct ID from download_enable where FILEID=".$_POST['DEL_ALL'];
	$res_listIDdel = mysql_query( $sql_listIDdel, $_SESSION["readServer"] );
	while( $val_listIDdel = mysql_fetch_array( $res_listIDdel ) ) {
			$listIDdel[]=$val_listIDdel['ID'];
	}	
	if ($listIDdel != '')
	$reqSupp = "DELETE FROM devices WHERE name='DOWNLOAD' AND ivalue in (".implode(',',$listIDdel).")";
	@mysql_query($reqSupp, $_SESSION["writeServer"]) or die(mysql_error());	
		
	@mysql_query("DELETE FROM download_enable WHERE FILEID=".$_POST['DEL_ALL'], $_SESSION["writeServer"]) or die(mysql_error());		
	echo "<script>window.opener.document.packlist.submit(); self.close();</script>";	
}
if ($_POST['SUP_PROF'] != ''){
	$reqSupp = "DELETE FROM devices WHERE name='DOWNLOAD' AND ivalue = ".$_POST['SUP_PROF'];
	@mysql_query($reqSupp, $_SESSION["writeServer"]) or die(mysql_error());	
		
	@mysql_query("DELETE FROM download_enable WHERE ID=".$_POST['SUP_PROF'], $_SESSION["writeServer"]) or die(mysql_error());		
}

$sql_details="select distinct priority,fragments,size from download_available where fileid=".$_GET['timestamp'];
$res_details = mysql_query( $sql_details, $_SESSION["readServer"] );
$val_details = mysql_fetch_array( $res_details ) ;
$tps="<br>".$l->g(992)." : <b><font color=red>".tps_estimated($val_details)."</font></b>";
PrintEnTete( $l->g(481).$tps);	
echo "<br>";
$form_name="tele_actives";
//ouverture du formulaire	
echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
$list_fields= array($l->g(460)=>'e.ID',
							'Timestamp'=>'e.FILEID',
							$l->g(470)=>'e.INFO_LOC',
							$l->g(471)=>'e.PACK_LOC',
							$l->g(49)=>'a.NAME',
							$l->g(440)=>'a.PRIORITY',
							$l->g(480)=>'a.FRAGMENTS',
							$l->g(462)=>'a.SIZE',
							$l->g(25)=>'a.OSNAME',
							'SUP'=>'e.ID');
$table_name="LIST_ACTIVES";
$default_fields= $list_fields;
$list_col_cant_del=array($l->g(460)=>$l->g(460),'SUP'=>'SUP');
$querypack = 'SELECT distinct ';
foreach ($list_fields as $key=>$value){
		if( $key != 'SUP')
		$querypack .= $value.',';		
} 
$querypack=substr($querypack,0,-1);
$querypack .= " from download_enable e RIGHT JOIN download_available a ON a.fileid = e.fileid
				where e.FILEID=".$_GET['timestamp'];
$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$querypack,$form_name,95,$tab_options); 
if ($result_exist != "")
echo "<a href=# OnClick='confirme(\"\",\"".$_GET['timestamp']."\",\"".$form_name."\",\"DEL_ALL\",\"".$l->g(900)."\");'><img src='image/sup_search.png' title='Supprimer' ></a>";
echo "<input type='hidden' id='DEL_ALL' name='DEL_ALL' value=''>";
echo "</form>";
echo "<center>".$l->g(552)."</center>";







?>