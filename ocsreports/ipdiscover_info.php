<?php
/*
 * Created on 7 mai 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
require ('fichierConf.class.php');
$form_name='fuser';
$ban_head='no';
$no_error='YES';
require_once("header.php");

//recherche de la personne connectée
if (isset($_SESSION['TRUE_USER']))
$user=$_SESSION['TRUE_USER'];
else
$user=$_SESSION['loggeduser'];

//suppression d'une adresse mac
if(isset($_POST['SUP_PROF'])){
	$del=mysql_escape_string($_POST['SUP_PROF']);
	mysql_query("DELETE FROM netmap WHERE mac='".$del."'", $_SESSION["writeServer"] ) or die(mysql_error());
	mysql_query("DELETE FROM network_devices WHERE macaddr='".$del."'", $_SESSION["writeServer"] ) or die(mysql_error());
	unset($_SESSION['DATA_CACHE']['IPDISCOVER_'.$_GET['prov']]);
	
}
//identification d'une adresse mac
if (isset($_POST['Valid_modif_x'])){
	if (trim($_POST['COMMENT']) == "")
	$ERROR= $l->g(942);
	if (trim($_POST['TYPE']) == "")
	$ERROR= $l->g(943);
	if (isset($ERROR) and $_POST['MODIF_ID'] != '')
	$_POST['USER']=$_POST['USER_ENTER'];

	if (!isset($ERROR)){
		$post=xml_escape_string($_POST);
		if ($post['USER_ENTER'] != ''){
			$sql="update network_devices 
					set DESCRIPTION = '".$post['COMMENT']."',
					TYPE = '".$post['TYPE']."',
					MACADDR = '".$post['mac']."',
					USER = '".$user."' where ID='".$_POST['MODIF_ID']."'";			
		}else{		
			$sql="insert into network_devices (DESCRIPTION,TYPE,MACADDR,USER)
			  VALUES('".$post['COMMENT']."',
			  '".$post['TYPE']."',
			  '".$post['mac']."',
			  '".$user."')";
		}
		mysql_query( $sql , $_SESSION["writeServer"]) or die(mysql_error($_SESSION["writeServer"]));
		//suppression du cache pour prendre en compte la modif
		unset($_SESSION['DATA_CACHE']['IPDISCOVER_'.$_GET['prov']]);
	}else{		
		$_POST['MODIF']=$_POST['mac'];
	}	
}

//formulaire de saisie de l'identification de l'adresse mac
if (isset($_POST['MODIF']) and $_POST['MODIF'] != ''){
	
	//cas d'une modification de la donnée déjà saisie
	if ($_GET['prov'] == "ident" and !isset($_POST['COMMENT'])){
		$id=mysql_escape_string($_POST['MODIF']);
		$sql="select DESCRIPTION,TYPE,MACADDR,USER from network_devices where id ='".$id."'";
		$res = mysql_query($sql, $_SESSION["readServer"] );
		$val = mysql_fetch_array( $res );
		$_POST['COMMENT']=$val['DESCRIPTION'];
		$_POST['MODIF']=$val['MACADDR'];
		$_POST['TYPE']=$val['TYPE'];
		$_POST['USER']=	$val['USER'];
		$_POST['MODIF_ID']=$id;
	}
	$tab_hidden['USER_ENTER']=$_POST['USER'];	
	$tab_hidden['MODIF_ID']=$_POST['MODIF_ID'];	
	echo "<br>";
	echo "<br>";
	//si on est dans le cas d'une modif, on affiche le login qui a saisi la donnée
	if ($_POST['MODIF_ID'] != ''){
		$tab_typ_champ[3]['DEFAULT_VALUE']=$_POST['USER'];
		$tab_typ_champ[3]['INPUT_NAME']="USER";
		$tab_typ_champ[3]['INPUT_TYPE']=3;
		$tab_name[3]=$l->g(944)." : ";
		
		$title=$l->g(945);		
	}else{
		$title=$l->g(946);	
	}
	
	$tab_typ_champ[0]['DEFAULT_VALUE']=$_POST['MODIF'];
	$tab_typ_champ[0]['INPUT_NAME']="MAC";
	$tab_typ_champ[0]['INPUT_TYPE']=3;
	$tab_name[0]=$l->g(95).": ";
	
	$tab_typ_champ[1]['DEFAULT_VALUE']=$_POST['COMMENT'];
	$tab_typ_champ[1]['INPUT_NAME']="COMMENT";
	$tab_typ_champ[1]['INPUT_TYPE']=0;
	$tab_typ_champ[1]['CONFIG']['SIZE']=60;
	$tab_typ_champ[1]['CONFIG']['MAXLENGTH']=255;
	$tab_name[1]=$l->g(53).": ";
	
	$sql="select distinct NAME from devicetype ";
	$res=mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	while ($row=mysql_fetch_object($res)){
		$list_type[$row->NAME]=$row->NAME;
	}
	$tab_typ_champ[2]['DEFAULT_VALUE']=$list_type;
	$tab_typ_champ[2]['INPUT_NAME']="TYPE";
	$tab_typ_champ[2]['INPUT_TYPE']=2;
	$tab_name[2]=$l->g(66).": ";
	
	
	//printEnTete("Ajout d'un nouveau périphérique");

	$tab_hidden['mac']=$_POST['MODIF'];	
	if (isset($ERROR))
	echo "<font color=red><b>".$ERROR."</b></font>";
	tab_modif_values($tab_name,$tab_typ_champ,$tab_hidden,$title,$comment="");	
}
else{ //affichage des périphériques
	if (!(isset($_POST["pcparpage"])))
		 $_POST["pcparpage"]=5;
	if (isset($_GET['value'])){
		$netid=mysql_escape_string($_GET['value']);
		if ($_GET['prov'] == "no_inv"){
			$title=$l->g(947);
			$sql="SELECT ip, mac, mask, date, name FROM netmap WHERE netid='".$netid."' AND mac NOT IN (SELECT DISTINCT(macaddr) FROM networks) 
			AND mac NOT IN (SELECT DISTINCT(macaddr) FROM network_devices)";
			$list_fields= array($l->g(34) => 'ip','MAC'=>'mac',
								$l->g(208)=>'mask',
								$l->g(232)=>'date',
								$l->g(318)=>'name',
								'SUP'=>'mac',
								'MODIF'=>'mac');
			$tab_options['MODIF']['IMG']="image/prec16.png";
			$tab_options['LBL']['MODIF']=$l->g(114);
			$default_fields= $list_fields;
		}elseif($_GET['prov'] == "ident"){
			$title=$l->g(948);
			$sql="select n.ID,n.TYPE,n.DESCRIPTION,a.IP,a.MAC,a.MASK,a.NETID,a.NAME,a.date,n.USER
				 from network_devices n LEFT JOIN netmap a ON a.mac=n.macaddr
				 where netid='".$netid."'";
				 $list_fields= array($l->g(66) => 'TYPE',$l->g(53)=>'DESCRIPTION',
								$l->g(34)=>'IP',
								'MAC'=>'MAC',
								$l->g(208)=>'MASK',
								$l->g(316)=>'NETID',
								$l->g(318)=>'NAME',
								$l->g(232)=>'date',
								$l->g(369)=>'USER',
								'SUP'=>'MAC',
								'MODIF'=>'ID');
				$default_fields= array($l->g(34)=>$l->g(34),$l->g(66)=>$l->g(66),$l->g(53)=>$l->g(53),
									'MAC'=>'MAC',$l->g(232)=>$l->g(232),$l->g(369)=>$l->g(369),'SUP'=>'SUP','MODIF'=>'MODIF');

		}
		printEnTete($title);
		echo "<br><br>";		
		$tab_options['LBL']['MAC']=$l->g(95);		
		$tab_options['FILTRE']['ip']=$l->g(66);
		$list_col_cant_del=array($l->g(66)=>$l->g(66),'SUP'=>'SUP','MODIF'=>'MODIF');
		$table_name="IPDISCOVER_".$_GET['prov'];
		$form_name=$table_name;
		echo "<form name='".$form_name."' id='".$form_name."' action='' method='post'>";
		$result_exist=tab_req($table_name,$list_fields,$default_fields,$list_col_cant_del,$sql,$form_name,80,$tab_options); 
		echo "</form>";
	}
}
require_once($_SESSION['FOOTER_HTML']);
?>
