<?php 
//export liste des machines ayant un logiciel suspect

$header_html="NO";
require_once("header.php");
//add all soft you don't want in your export
$list_no_soft=array();

if (strrpos($_SESSION["forcedRequest"],'having nb <')){
$result=mysql_query($_SESSION["forcedRequest"], $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
while( $val = mysql_fetch_array($result) ){
		$no_show='OK';
		foreach ($list_no_soft as $key=>$value){
			//echo "<br>name=> ".utf8_decode($val['name'])."  value".$value;
			if (strstr(utf8_decode($val['name']), $value)){
			//echo "    KO";
				$no_show='KO';
			}
		}
		if (trim($val['name']) != '' and $no_show == 'OK') 
		$list_soft[]=addslashes(utf8_decode($val['name']));
}
$fields= array("a.tag"=>TAG_LBL,
			   "s.name"=>$l->g(20),
			   "h.name"=>$l->g(23),
			   "h.userid"=>$l->g(24),
			   "h.description"=>$l->g(53),
			   "h.lastdate"=>$l->g(133));
$sql=" select ";
$affich="";
foreach($fields as $sql_field=>$lbl){
	$affich.=$lbl.";";
	$sql .= $sql_field." as ".$lbl.",";
}
$affich.="\r\n";
$sql=substr($sql,0,-1);
$sql.=" from softwares s,hardware h, accountinfo a
		where ".$_SESSION["mesmachines"]." 
			and a.hardware_id=h.ID
			and s.HARDWARE_ID=h.id
			and s.name in ('".implode("','",$list_soft)."')";
$result=mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
while( $val = mysql_fetch_array($result) ){
	foreach ($fields as $sql_field=>$lbl){
		$affich.=$val[$lbl].";";
	}
	$affich.="\r\n";
}
AddLog("EXTRACT_SOFT_SUSPECT",$_SESSION["forcedRequest"]);
// iexplorer problem
if( ini_get("zlib.output-compression"))
	ini_set("zlib.output-compression","Off");
	
header("Pragma: public");
header("Expires: 0");
header("Cache-control: must-revalidate, post-check=0, pre-check=0");
header("Cache-control: private", false);
header("Content-type: application/force-download");
header("Content-Disposition: attachment; filename=\"export.csv\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".strlen($affich));
echo $affich;
}else
echo "<b><font color=red size=5>".$l->g(924)."</font></b>";
?>
