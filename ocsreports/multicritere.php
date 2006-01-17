<?
//====================================================================================
// OCS INVENTORY REPORTS
// Copyleft Pierre LEMMET 2005
// Web: http://ocsinventory.sourceforge.net
//
// This code is open source and may be copied and modified as long as the source
// code is always made freely available.
// Please refer to the General Public Licence http://www.gnu.org/ or Licence.txt
//====================================================================================
//Modified on 12/14/2005

	printEnTete($l->g(9));
	if( !isset($_SESSION["optCol"]) ) {
		$reqCol = "SELECT * FROM accountinfo LIMIT 0,1";
		$resCol = mysql_query($reqCol, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		while($colname=mysql_fetch_field($resCol))
			if( $colname->name != TAG_NAME )
				$_SESSION["optCol"][] = $colname->name;	
	}	
	
	include("req.class.php");
	$indLigne=0;
	$softPresent = false;
	$cuPresent = -1;
	if($_POST["reset"]==$l->g(41))
	{
		unset($_SESSION["OPT"]);
		unset($_SESSION["reqs"]);
	}
	else if($_POST["selOpt"])
	{
		$_POST["selOpt"] = urldecode( $_POST["selOpt"] );
		if(   (! is_array($_SESSION["OPT"]))  ||   ( !in_array($_POST["selOpt"],$_SESSION["OPT"]))) {
			$_SESSION["OPT"][]=stripslashes($_POST["selOpt"]);
		}
	}	
	else if($_POST["sub"]==$l->g(30))
	{
		unset($_SESSION["query"]);
					
		$i=0; $nb=0; 
		$laRequete="SELECT a.".TAG_NAME." AS \"".TAG_LBL."\",$selectH";			
		unset( $_SESSION["reqs"] );
		for($i=0;$i<$_POST["max"];$i++)	{				

			$_SESSION["reqs"][ urldecode($_POST["lbl_".$i]) ] = array( $_POST["act_".$i], urldecode($_POST["chm_".$i]), $_POST["ega_".$i], 
			strtr($_POST["val_".$i],"\"","'"), strtr($_POST["val2_".$i],"\"","'"), $_POST["valreg_".$i] ); 
							
			if(!isset($_POST["act_".$i]))
				continue;
				
			if( ($_POST["chm_".$i]=="name") && $_POST["ega_".$i]==$l->g(129) ) {			
				$laRequete.=", s.name AS \"".$l->g(20)."\"";
			}
				
			$nb++;			
		}
		$laRequete.=" FROM hardware h,accountinfo a,bios b,";				
			
		$softTable = false ;
		for($i=0;$i<$_POST["max"];$i++)
		{
			if(!isset($_POST["act_".$i]))
			continue;
			
			//jokers
			$_POST["val_".$i] = strtr($_POST["val_".$i], "?*", "_%");
			
			if( isFieldDate($_POST["chm_".$i]) ) {
				$_POST["val_".$i] = dateToMysql($_POST["val_".$i]);
			}
						
			if( ($_POST["chm_".$i]=="name") && ! $softTable && $_POST["ega_".$i]==$l->g(129) ) {			
				$laRequete.=" softwares s,";
				$softTable = true ;
			}
			
			if( ($_POST["chm_".$i]=="regval" || $_POST["chm_".$i]=="regname") && $_POST["ega_".$i]==$l->g(129)) {
					$laRequete.=" registry r,";
			}
			
			if($_POST["chm_".$i]=="smonitor") {
					$laRequete.=" monitors m,";
			}

			if(($_POST["chm_".$i]=="ipmask"||$_POST["chm_".$i]=="ipgateway"||$_POST["chm_".$i]=="ipaddr"||$_POST["chm_".$i]=="ipsubnet") && !$netTable) {
				$laRequete.=" networks n,";
				$netTable=true;
			}
			
		}
		
		if($laRequete[strlen($laRequete)-1]==",")
			$laRequete[strlen($laRequete)-1]=" ";
			
		$laRequete.="WHERE h.deviceid=a.deviceid AND h.deviceid=b.deviceid ";
			
		
		for($i=0;$i<$_POST["max"];$i++)
		{				
			if(!isset($_POST["act_".$i]))
				continue;
			
			if( $_POST["act_".$i]="checked" && ! (  ($cuPresent != -1 ) && $_POST["chm_".$i] == "cu") )
			{				
				// cas particulier avec LOGICIEL DIFFERENT DE
				if( ($_POST["chm_".$i] == "name" /*|| $_POST["chm_".$i] == "version" */) && ($_POST["ega_".$i] == $l->g(130) || $unSoftnEgal) ) {
					$softDiff[]=Array($_POST["val_".$i],$_POST["ega_".$i]);
					continue ;
				}
				
				// cas particulier avec registry DIFFERENT DE
				if( $_POST["chm_".$i] == "regname" && $_POST["ega_".$i] == $l->g(130) ) {
					$regDiff=Array($_POST["val_".$i],$_POST["valreg_".$i]);
					continue ;
				}
				
				if($nb>0)
				{
					$laRequete.=" AND ";						
				}
				
				$forceEgal=false;
				
				if($_POST["chm_".$i]=="regname") {
					$laRequete.="r.deviceid=h.deviceid AND ";
					$regPres = true;
				}
										
				switch($_POST["chm_".$i])
				{
					case "ssn": $laRequete.="b.ssn";break;
					case "bmanufacturer": $laRequete.="b.bmanufacturer";break;
					case "bversion": $laRequete.="b.bversion";break;
					case "smanufacturer": $laRequete.="b.smanufacturer";break;
					case "smodel": $laRequete.="b.smodel";break;
					case "ipmask": $laRequete.="n.deviceid=h.deviceid AND n.ipmask";break;
					case "ipgateway": $laRequete.="n.deviceid=h.deviceid AND n.ipgateway";break;
					case "ipsubnet": $laRequete.="n.deviceid=h.deviceid AND n.ipsubnet";break;
					case "regname": 
							if( $_POST["valreg_".$i] != $l->g(265) )
								$laRequete.="r.regvalue='".$_POST["valreg_".$i]."' AND ";
							$laRequete.="r.name";
							$forceEgal=true;
							break;					
					
					case "name": $laRequete.="s.deviceid=h.deviceid AND s.name";
							$softPresent = true;
							if( $_POST["ega_".$i] == $l->g(129) )
								$unSoftnEgal = true ;
							break;			
							
					case "ORDEROWNER": $laRequete.="a.orderowner";break;
					case "ORDERID": $laRequete.="a.orderid";break;
					case "PRODUCTID": $laRequete.="a.productid";break;
					case "BILLDATE": $laRequete.="a.billnbr";break;
					case "cu": $laRequete.="a.".TAG_NAME;$forceEgal=true;break;
					case "processors": $laRequete.="h.processors";$forceEgal=true;break;
					case "memory": $laRequete.="h.memory";$forceEgal=true;break;
					case "osname": $laRequete.="h.osname";$forceEgal=true;break;
					case "userid": $laRequete.="h.userid";break;
					case "ipaddr": $laRequete.="n.deviceid=h.deviceid AND n.ipaddress";break;
					case "useragent": $laRequete.="h.useragent";$forceEgal=true;break;
					case "workgroup": $laRequete.="h.workgroup";break;
					case "hname": $laRequete.="h.name";break;
					case "lastdate": $laRequete.="h.lastdate";break;
					case "smonitor": $laRequete.="m.deviceid=h.deviceid AND m.serial";break;
					default: $laRequete.="a.".$_POST["chm_".$i]; break;
				}		
				
				switch($_POST["ega_".$i])
				{
					case $l->g(129): $laRequete.=" LIKE ";$forceLike=true; break;						
					case $l->g(130): $laRequete.=" NOT LIKE ";$forceLike=true; break;					
					case $l->g(346):
					case $l->g(201): $laRequete.="<";break;
					case $l->g(347):
					case $l->g(202): $laRequete.=">";break;
					case $l->g(203): $laRequete.="<'".$_POST["val2_".$i]."' AND h.".$_POST["chm_".$i].">";break;
					//case $l->g(204): $laRequete.=">'".$_POST["val2_".$i]."' OR h.".$_POST["chm_".$i]."<";break;
					default: $laRequete.=" LIKE "; $forceLike=true;break;
				}
				if( $forceEgal || !$forceLike )
					$laRequete.="'".$_POST["val_".$i]."'";	
				else
					$laRequete.="'%".$_POST["val_".$i]."%'";				
			}			
		}
		
		if( $nb > 0 ) {		
			$laRequeteF=$laRequete;	
			//val ega
			for($ii=0;$ii<sizeof($softDiff);$ii++) {
				if($softDiff[$ii][1] == $l->g(130) )
					$condSoft = "NOT ";
				else
					$condSoft = "";
				$laRequeteF .= "AND h.deviceid {$condSoft}IN(SELECT DISTINCT(ss.deviceid) FROM softwares ss WHERE ss.name LIKE '%".$softDiff[$ii][0]."%')";
			}
			
			if(sizeof($regDiff)>=1) {
				if($regDiff[1]!=$l->g(265))
					$valRegR = " AND rr.regvalue = '".$regDiff[1]."'";
				
				$laRequeteF .= "AND h.deviceid NOT IN(SELECT DISTINCT(rr.deviceid) FROM registry rr WHERE rr.name = '".$regDiff[0]."' $valRegR)";
			}
			
			$tok = split(  "FROM hardware h,accountinfo a",$laRequeteF);
			$requeteCount = "SELECT COUNT(DISTINCT h.deviceid) FROM hardware h,accountinfo a".$tok[1];		
				
			$laRequeteF .=  " GROUP BY h.deviceid";
			
			$lbl="Recherche multicritères";	
			$lblChmp[0]=NULL; 		
			$req=new Req($lbl,$laRequeteF,$requeteCount,$lblChmp,$sqlChmp,$typChmp,NULL,true); // Instanciation du nouvel objet de type "Req"
			//echo 	$laRequeteF;	
			
			ShowResults($req,true,false,false,true,false,true);
			$_SESSION["query"]=$laRequeteF;				
		}
		
	}
	else if($_GET["c"] || $_GET["av"] || $_GET["page"] || isset($_GET["pcparpage"]) || isset($_GET["newcol"])  )
	{
		$tok = split( "FROM hardware h,accountinfo a",$_SESSION["query"]);
		$tok2 = "SELECT COUNT(DISTINCT h.deviceid) FROM hardware h,accountinfo a".$tok[1];		
		$requeteCount = split( "GROUP BY" , $tok2 );
				
		$lbl="Recherche multicritères";	
		$lblChmp[0]=NULL; 		
		$req=new Req($lbl,$_SESSION["query"],$requeteCount[0],$lblChmp,$sqlChmp,$typChmp,NULL,true); // Instanciation du nouvel objet de type "Req"		
		
		ShowResults($req,true,false,false,true,false,true);		
	}	
	
	

?>

<br>
<table border=0 width=80% align=center><tr align=right><td width=50%>
<form name='optionss' action='index.php?multi=1' method='post'><b><?echo $l->g(31);?>:&nbsp;&nbsp;&nbsp;</b> 
<select name=selOpt OnChange="optionss.submit();"><?

$optArray = array($l->g(34), $l->g(33), $l->g(20)." (1)", $l->g(20)." (2)", $l->g(26), $l->g(35),
$l->g(36), $l->g(207), $l->g(25), $l->g(24), $l->g(27), $l->g(65), $l->g(284), $l->g(64), $l->g(359), 
TAG_LBL, $l->g(357), $l->g(46),$l->g(257),$l->g(331),$l->g(209));

$optArray  = array_merge( $optArray, $_SESSION["optCol"]);
sort($optArray);
$countHl++;
echo "<option".($countHl%2==1?" class='hi'":"").">".$l->g(32)."</option>"; $countHl++;

foreach( $optArray as $val) {
	if( !in_array($val,$_SESSION["OPT"]) && $val!="DEVICEID") {
		$countHl++;
		echo "<option".($countHl%2==1?" class='hi'":"").">$val</option>";
	}
}

?>
</select>
</form></td><td align=left>
<form method=post name=res action=index.php?multi=1><input taborder=2 type=submit name=reset value=<?echo $l->g(41);?>></form></td>
</td></tr></table>

<?

if($_SESSION["OPT"]!=0)
{	
	echo "<form name=machine action=index.php?multi=1 method=post><table border=1 class= 'Fenetre' WIDTH = '75%' ALIGN = 'Center' CELLPADDING='5'>";
	
	$ligne[] = array( $l->g(34),"ipaddr","hardware","",2,5);
	$ligne[] = array( $l->g(33),"workgroup","hardware","SELECT workgroup FROM hardware GROUP BY workgroup",1,1);
	$ligne[] = array( $l->g(20)." (1)","name","softwares","",2,5);
	$ligne[] = array( $l->g(20)." (2)","name","softwares","",2,5);
	/*$ligne[] = array( $l->g(20)." (3)","name","softwares","",2,5);
	$ligne[] = array( $l->g(20)." (4)","name","softwares","",2,5);*/
	$ligne[] = array( $l->g(26),"memory","hardware","",2,3,"MO");
	$ligne[] = array( $l->g(35),"hname","hardware","SELECT name FROM hardware GROUP BY name",2,1);
	$ligne[] = array( $l->g(46),"lastdate","hardware","",2,2,"",true);
	$ligne[] = array( $l->g(357),"useragent","hardware","SELECT useragent FROM hardware GROUP BY useragent",1,1);
	$ligne[] = array( $l->g(36),"ssn","bios","",2,1);	
	$ligne[] = array( $l->g(64),"smanufacturer","bios","",2,1);
	$ligne[] = array( $l->g(65),"smodel","bios","",2,1);
	$ligne[] = array( $l->g(284),"bmanufacturer","bios","",2,1);
	$ligne[] = array( $l->g(207),"ipgateway","networks","",2,5);
	$ligne[] = array( $l->g(331),"ipsubnet","networks","",2,5);
	$ligne[] = array( $l->g(25),"osname","hardware","SELECT osname FROM hardware GROUP BY osname",1,1);
	$ligne[] = array( $l->g(24),"userid","hardware","SELECT userid FROM hardware GROUP BY userid",2,1);
	$ligne[] = array( $l->g(27),"processors","hardware","",2,3,"MHZ");
	$ligne[] = array( $l->g(257),"regname","hardware","SELECT DISTINCT(name) FROM registry",1,6);	
	$ligne[] = array( $l->g(359),"smonitor","hardware","",2,1);
	$ligne[] = array( $l->g(209),"bversion","bios","",2,1);

	$ligne[] = array( TAG_LBL,"cu","accountinfo","",2,1);

	foreach($_SESSION["optCol"] AS $col) {
		if($col!="TAG"&&$col!="DEVICEID") {
			$isDate = isFieldDate($col);
			$ligne[]  =  array( $col,$col,"hardware","accountinfo",2,$isDate ? 2 : 1,"",$isDate);
		}
	}
	
	foreach( $ligne as $laLigne) {
		$colATrier[] = $laLigne[0];
	}
	
	sort($colATrier);
	foreach($colATrier as $nomLigne) {
		foreach($ligne as $laLigne) {
			if($laLigne[0] == $nomLigne) {
				afficheLigne($laLigne);
				break;
			}
		}
	}	
	
	$color=$indLigne%2==0?"#F2F2F2":"#FFFFFF";
	echo "<tr bgcolor='$color'><td colspan='3' align='right'><input type='hidden' name='max' value='$indLigne'>";
	
	if($_SESSION["OPT"]!=0)
	{
		echo "<input type=submit taborder=1 name=sub value=".$l->g(30).">";
	}	
	echo "</td></tr></table></form>";
	if($_SESSION["OPT"]!=0)
	{
		echo "<center><i>".$l->g(358)."</i></font></center><br>";
	}
}

function afficheLigne($ligne)
{	
	global $indLigne,$l,$_POST;	
	
	$label = $ligne[0];
	$champ = $ligne[1];
	$table = $ligne[2];
	$laRequete = $ligne[3];
	$combo = isset($ligne[4]) ? $ligne[4] : 1 ;
	$type = isset($ligne[5]) ? $ligne[5] : 1 ;
	$leg = isset($ligne[6]) ? $ligne[6] : "" ;
	$isDate = isset($ligne[7]) ? $ligne[7] : false ;

	if(is_array($_SESSION["OPT"])) {
		if(!in_array($label,$_SESSION["OPT"]))
			return;
	}
	else
		return;
	
	$color=$indLigne%2==0?"#F2F2F2":"#FFFFFF";
	$suff="_".$indLigne;

	echo"		
	<tr bgcolor=$color>
		<td>
			<input type=checkbox id='act$suff' name='act$suff'".($_SESSION["reqs"][$label][0]=="on"?" checked":"").">&nbsp;".$l->g(205)."</input>
			<input type=hidden name='chm$suff' value=$champ>
			<input type=hidden name='lbl$suff' value='".urlencode($label)."'>
		</td>
		<td>
			$label
		</td>
		<td>";
			
		
		if($type != 4 ) {
		
			
			echo "<select OnClick='act$suff.checked=true' name='ega$suff'>			
			<option".($_SESSION["reqs"][$label][2]==$l->g(129)?" selected":"").">".$l->g(129)."</option>
			<option".($_SESSION["reqs"][$label][2]==$l->g(130)?" selected":"").">".$l->g(130)."</option>";
	
			if( $isDate) {
				echo "<option".($_SESSION["reqs"][$label][2]==$l->g(346)?" selected":"").">".$l->g(346)."</option><option".($_SESSION["reqs"][$label][2]==$l->g(347)?" selected":"").">".$l->g(347)."</option>"; 
			}
			else if( $type==2||$type==3 )
			{
				echo "<option".($_SESSION["reqs"][$label][2]==$l->g(201)?" selected":"").">".$l->g(201)."</option><option".($_SESSION["reqs"][$label][2]==$l->g(202)?" selected":"").">".$l->g(202)."</option>";
			}
			if ($type==3)
			{
				echo "<option".($_SESSION["reqs"][$label][2]==$l->g(203)?" selected":"").">".$l->g(203)."</option>";//<option".($_POST["ega$suff"]==$l->g(204)?" selected":"").">".$l->g(204)."</option>";		
			}
		}
		else
			echo $l->g(129);
		
		echo	"</select>&nbsp;&nbsp;";
			
	if($combo==1)
	{
		echo "<select OnClick='act$suff.checked=true' name='val$suff'>";	
		$res=mysql_query($laRequete, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		
		while($row=mysql_fetch_array($res))
		{
			if($row[0]=="") continue;	
			$selected = $row[0]== $_SESSION["reqs"][$label][3] ?" selected":"";
			echo "<option$selected>".$row[0]."</option>\n";	
		}
		
		echo "</select>";
	}
	else
	{
		if( $isDate ) {
			echo "<input READONLY ".dateOnClick("val$suff","act$suff")." OnClick='act$suff.checked=true' name='val$suff' id='val$suff' value='"./*dateFromMysql(*/$_SESSION["reqs"][$label][3]/*)*/."'>".datePick("val$suff","act$suff");
		}
		else
			echo "<input OnClick='act$suff.checked=true' name='val$suff' value=\"".stripslashes($_SESSION["reqs"][$label][3])."\">";
		
		if ($type==3) // deux inputs pour "entre machin et truc"
		{
			echo "&nbsp;&nbsp;--&nbsp;&nbsp;<input OnClick='act$suff.checked=true' name='val2$suff' value='".$_SESSION["reqs"][$label][4]."'>";
		}	
	}
	if($type==6) {
			$reqRes = mysql_query("SELECT DISTINCT(regvalue) FROM registry", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"])); //todo mesmachines
			echo "&nbsp;&nbsp;".$l->g(224).":&nbsp;&nbsp;<select OnClick='act$suff.checked=true' name='valreg$suff'>
			<option>".$l->g(265)."</option>";
					
			while($row=mysql_fetch_array($reqRes))
			{
				if($row[0]=="") continue;	
				$selected = $row[0]== $_SESSION["reqs"][$label][5] ?" selected":"";
				echo "<option$selected>".$row[0]."</option>\n";	
			}
			
			echo "</select>";			
	}	
	
	echo "&nbsp;&nbsp;&nbsp;$leg</td></tr>";
	$indLigne++;
}

function isFieldDate($nom) {
	if( $nom == "lastdate" )
		return true;
		
	$reqType = "SELECT $nom FROM accountinfo";
	if( $resType = @mysql_query($reqType, $_SESSION["readServer"])) {
		$valType = mysql_fetch_field($resType);
		return ($valType->type == "date");
	}
	return false;
}

		
?>