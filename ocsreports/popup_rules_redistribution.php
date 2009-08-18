<?php
/*
 * Created on 25 oct. 2007
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 *
 */
 
//require ('fichierConf.class.php');
//require('req.class.php');
//require_once('require/function_table_html.php');
require_once('require/function_server.php');
$ban_head='no';
require_once("header.php");
if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
	die("FORBIDDEN");
?>
<script>
function check() {
	var msg = "";

		if (document.AFFECT_RULE.rule.value == "")	{
		document.AFFECT_RULE.rule.style.backgroundColor = "RED";
		msg += "Choisissez une régle d'affectation";
	}
	if (msg == "") Reporter();
	else	{
		alert(msg);
		return(false);
	}
}

function Reporter()
		{
			opener.document.forms['TELE_AFFECT_RULE'].RULE_AFFECT.value=document.AFFECT_RULE.rule.value;
			opener.document.forms['TELE_AFFECT_RULE'].GROUP_ID.value=document.AFFECT_RULE.GROUP_ID.value;		
			opener.document.forms['TELE_AFFECT_RULE'].TIMESTAMP.value=document.AFFECT_RULE.TIMESTAMP.value;			
			window.opener.document.forms['TELE_AFFECT_RULE'].submit();
			opener=self;
            self.close();
		}
</script>



<?php
$result = mysql_query("select distinct rule,rule_name from download_affect_rules", $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
while($item = mysql_fetch_object($result))
	$list.="<option value='".$item->rule."'>".$item->rule_name."</option>";	

echo "<form name='AFFECT_RULE' action='' method='POST' onSubmit=\"check();\">";
echo "<br>
<table align='center' width='95%' border='0' cellspacing=20 bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'>
	<tr><td colspan=20 align='center'><font color=red>".$l->g(667)." ".$_GET['paq_name']."</font></td></tr>
	<tr height='30px'> <td align='left'>".$l->g(668)." </td><td><select id='rule' name='rule' ><option value=''>".$l->g(32)."</option>".$list."</select></td></tr>
	<tr height='30px'><td align=center><input type='submit' name='valid_server'></td><td><input type='reset' name='annul' value='".$l->g(113)."' onclick='self.close();'></td></tr>		
</table>
";
echo "<input type='hidden' name='GROUP_ID' value='".$_GET['GROUP_ID']."'>";
echo "<input type='hidden' name='TIMESTAMP' value='".$_GET['timestamp']."'>";
echo "</form>";
?>
