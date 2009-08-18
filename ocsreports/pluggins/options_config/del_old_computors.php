<?php
$time_to_delete=10; //minutes
$max_computor_delete=50;


$sql="select IVALUE,TVALUE from config where NAME='INVENTORY_VALIDITY'";
$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$value=mysql_fetch_array($result);
if (isset($value['IVALUE']) and $value['IVALUE']!= 0){
	$timestamp_now=mktime(0,date("i"),0,date("m"),date("d"),date("Y"));
	echo $timestamp_now;
	echo "<br>".$value['TVALUE'];
	if ($value['TVALUE']<$timestamp_now or $value['TVALUE'] == null){		
		$timestamp_limit=mktime(0,date("i"),0,date("m"),date("d")-$value['IVALUE'],date("Y"));
		$sql="update config set TVALUE='".mktime(0,date("i")+$time_to_delete,0,date("m"),date("d"),date("Y"))."' where NAME='INVENTORY_VALIDITY'";
		mysql_query($sql, $_SESSION["writeServer"]);
		$sql="select id,lastdate,name from hardware where UNIX_TIMESTAMP(lastdate) < ".$timestamp_limit." limit ".$max_computor_delete;
		$res = mysql_query( $sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		while( $val = mysql_fetch_array($res) ){
			addLog("DELETE ".$val['name'], $l->g(820)." => ".$val['lastdate']." DATE < ".date("d/m/y H:i:s",$timestamp_limit));			
		}        
	}
	//dans 10 minutes

}

?>
