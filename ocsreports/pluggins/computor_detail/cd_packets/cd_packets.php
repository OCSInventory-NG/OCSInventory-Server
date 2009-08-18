<?php
	print_item_header($l->g(512));
	$form_name="affich_packets";
	$table_name=$form_name;
	echo "<form name='".$form_name."' id='".$form_name."' method='POST' action=''>";
	$list_fields=array($l->g(475) => 'PKG_ID',
					   $l->g(49) => 'NAME',
					   $l->g(440)=>'PRIORITY',
					   $l->g(464)=>'FRAGMENTS',
					   $l->g(462)." Ko"=>'SIZE',
					   $l->g(25)=>'OSNAME',
					   'COMMENT'=>'COMMENT');
	$list_col_cant_del=array($l->g(475)=>$l->g(475),$l->g(49)=>$l->g(49));
	$default_fields= $list_col_cant_del;
	$pack_sup="<b><font color=red>".textDecode($l->g(561))."</font></b>";
	$queryDetails  = "SELECT PKG_ID,NAME,PRIORITY,FRAGMENTS,round(SIZE/1024,2) as SIZE,OSNAME,COMMENT
						FROM download_history h LEFT JOIN download_available a ON h.pkg_id=a.fileid where hardware_id=$systemid and name is not null
						union
					 SELECT PKG_ID,'".$pack_sup."','".$pack_sup."','".$pack_sup."','".$pack_sup."','".$pack_sup."','".$pack_sup."'
						FROM download_history h LEFT JOIN download_available a ON h.pkg_id=a.fileid where hardware_id=$systemid and name is null";
	tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$queryDetails,$form_name,80,$tab_options);
	echo "</form>";
?>