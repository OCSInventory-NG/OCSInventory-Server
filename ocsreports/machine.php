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
//Modified on 12/13/2005

if( isset($_GET["sessid"])){
	session_id($_GET["sessid"]);
	session_start();
	
	if( !isset($_SESSION["loggeduser"]) ) {
		die("FORBIDDEN");
	}
}
else
	die("FORBIDDEN");
	
require ('preferences.php');

if(isset($_GET['systemid']))
{
	$systemid = urldecode($_GET['systemid']);
	if($systemid == "")
	{
		echo "Please Supply A System ID";
		exit;
  	}
}

if (isset($_GET['state']))
{
	$state = $_GET['state'];
	if ($state == "MAJ")
		echo "<script language='javascript'>window.location.reload();</script>\n";		
}// fin if
		
$queryMachine    = "SELECT * FROM hardware WHERE (DEVICEID='$systemid')";
$result   = mysql_query( $queryMachine, $_SESSION["readServer"] ) or mysql_error($_SESSION["readServer"]);
$item     = mysql_fetch_object($result);

echo "<html>\n";
echo "<head>\n";
echo "<TITLE>$aff -$systemid</TITLE>\n";
echo "<LINK REL='StyleSheet' TYPE='text/css' HREF='css/ocsreports.css'>\n";
echo "<script language='javascript'>\n";
echo "\tfunction Ajouter_donnees(systemid)\n";
echo "\t{\n";
echo "\t\twindow.open(\"./ajout_maj.php?action=ajouter_donnees&systemid=\" + systemid, \"_self\");\n";
echo "\t}\n\n";
echo "\tfunction MAJ_donnees(systemid,sessid)\n";
echo "\t{\n";
echo "\t\twindow.open(\"./ajout_maj.php?action=MAJ_donnees&sessid=\" +sessid+ \"&systemid=\" + systemid, \"_self\");\n";
echo "\t}\n";
echo "</script>\n";
echo "</head>\n";
echo "<body style='font: Tahoma' alink='#000000' vlink='#000000' link='#000000' bgcolor='#ffffff' text='#000000'>\n";

// COMPUTER SUMMARY
$tdhdpb = "<td  align='left' width='20%'>";
$tdhfpb = "</td>";
$tdhd = "<td  align='left' width='20%'><b>";
$tdhf = ":</b></td>";

echo "<table width='100%' border='0' bgcolor='#C7D9F5' style='border: solid thin; border-color:#A1B1F9'><tr><td width='50%'>";

echo "<table width='70%' align='center' border='0' bgcolor='#C7D9F5'>";
echo "<tr>".$tdhd.$l->g(49).$tdhf.$tdhdpb.utf8_decode($item->NAME).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(33).$tdhf.$tdhdpb.utf8_decode($item->WORKGROUP).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(46).$tdhf.$tdhdpb.dateTimeFromMysql(utf8_decode($item->LASTDATE)).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(34).$tdhf.$tdhdpb.utf8_decode($item->IPADDR).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(24).$tdhf.$tdhdpb.utf8_decode($item->USERID).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(26).$tdhf.$tdhdpb.utf8_decode($item->MEMORY).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(50).$tdhf.$tdhdpb.utf8_decode($item->SWAP).$tdhfpb."</tr>";

echo getNetName($systemid);

echo "</table></td><td>";

echo "<table width='70%' align='center' border='0' bgcolor='#C7D9F5'>";
echo "<tr>".$tdhd.$l->g(274).$tdhf.$tdhdpb.utf8_decode($item->OSNAME).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(275).$tdhf.$tdhdpb.utf8_decode($item->OSVERSION).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(286).$tdhf.$tdhdpb.utf8_decode($item->OSCOMMENTS).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(51).$tdhf.$tdhdpb.utf8_decode($item->WINCOMPANY).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(348).$tdhf.$tdhdpb.utf8_decode($item->WINOWNER).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(111).$tdhf.$tdhdpb.utf8_decode($item->WINPRODID).$tdhfpb."</tr>";
echo "<tr>".$tdhd.$l->g(357).$tdhf.$tdhdpb.utf8_decode($item->USERAGENT).$tdhfpb."</tr>";

echo "</table></td></tr></table>";
//*/// END COMPUTER SUMMARY

echo "<br><table width='100%' border='0' bgcolor='#C7D9F5' cellpadding='4' style='border: solid thin; border-color:#A1B1F9'>";
echo "<tr>";
echo "<td  align='right' width='15%'><b>Description:</td>";
echo "<td  width='85%'>".utf8_decode($item->DESCRIPTION)."</td>";
echo "</tr>";
echo "</table><br>";

$td1	  = "<td height=20px id='color' align='center'><FONT FACE='tahoma' SIZE=2 color=blue><b>";
$td2      = "<td height=20px bgcolor='white' align='center'>";
$td3      = $td2;
$td4      = "<td height=20px bgcolor='#F0F0F0' align='center'>";
$tab      = Array($l->g(56),$l->g(54),$l->g(26)."(s)",$l->g(63),$l->g(92),$l->g(273),$l->g(96),$l->g(61),$l->g(91),$l->g(97),$l->g(82),$l->g(272),$l->g(79),$l->g(93),$l->g(271),$l->g(20),$l->g(270),$l->g(211));
echo"<center>";
		for($i=0;$i<18;$i++) {
			echo"<font face=Verdana size=-1><a align='center' style=\"text-decoration:underline\" href='machine.php?sessid=".session_id()."&systemid=".urlencode(stripslashes($systemid))."&option=$i'>".$tab[$i]."</a></font> &nbsp;&nbsp;&nbsp;";
			if($i==8)	echo"<br>";
		}
echo"</center><br><br>"	;

if($_GET["tout"]==1)
{
	print_inventory($systemid);
	print_proc($systemid);
	print_memories($systemid);
	print_storages($systemid);
	print_drives($systemid);
	print_bios($systemid);
	print_sounds($systemid);
	print_videos($systemid);
	print_inputs($systemid);
	print_monitors($systemid);
	print_networks($systemid);
	print_ports($systemid);
	print_printers($systemid);
	print_controllers($systemid);
	print_slots($systemid);
	print_softwares($systemid);
	print_modems($systemid);
	print_registry($systemid);
}

switch ($_GET["option"]) :
	case '0'  : print_inventory($systemid);
						break;
	case '1'  : print_proc($systemid);
						break;
	case '2'  : print_memories($systemid);
						break;
	case '3'  : print_storages($systemid);
						break;
	case '4'  : print_drives($systemid);
						break;
	case '5'  : print_bios($systemid);
						break;
	case '6'  : print_sounds($systemid);
						break;
	case '7'  : print_videos($systemid);
						break;
	case '8'  : print_inputs($systemid);
						break;
	case '9'  : print_monitors($systemid);
						break;
	case '10' : print_networks($systemid);
						break;
	case '11' : print_ports($systemid);
						break;
	case '12' : print_printers($systemid);
						break;
	case '13' : print_controllers($systemid);
						break;
	case '14' : print_slots($systemid);
						break;
	case '15' : print_softwares($systemid);
						break;
	case '16' : print_modems($systemid);	
						break;
	case '17' : print_registry($systemid);
						break;	
	default: print_inventory($systemid);
						break;
	endswitch;					

echo "<br><table align='center'> <tr><td width =50%>";
echo "<a style=\"text-decoration:underline\" onClick=print()>".$l->g(214)."</a></td>";
if(!isset($_GET["tout"]))
		echo"<td width=50%><a style=\"text-decoration:underline\" href=\"machine.php?sessid=".session_id()."&systemid=".urlencode(stripslashes($systemid))."&tout=1\">".$l->g(215)."</a></td>";
		
echo "</tr></table></body>";
echo "</html>";
exit;

function print_proc($systemid)
{
	global $l,$td1,$td3;
	print_item_header($l->g(54));
	$queryDetails = "SELECT * FROM hardware WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or mysql_error($_SESSION["readServer"]);
	$item = mysql_fetch_object($resultDetails);
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(66)." </td> $td1 ".$l->g(27)." </td> $td1 ".$l->g(55)."</td></tr>";
	echo "<tr>";
	echo "$td3".utf8_decode($item->PROCESSORT)."</td>
	      $td3".utf8_decode($item->PROCESSORS)."</td>
	      $td3".utf8_decode($item->PROCESSORN)."</td>";
	echo "</tr>";
	echo "</table>";
}

function print_videos($systemid)
{
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM videos WHERE (DEVICEID = '$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
	
	print_item_header($l->g(61));
	if( mysql_num_rows($resultDetails) == 0 ) 		return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(49)." </td> $td1 ".$l->g(276)." </td>  $td1 ".$l->g(26)." (MB)</td> $td1 ".$l->g(62)."</td></tr>";	
	
	while($item = mysql_fetch_object($resultDetails))
	{
		$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		     $td3".utf8_decode($item->NAME)."      </td>
			 $td3".utf8_decode($item->CHIPSET)."   </td>
			 $td3".utf8_decode($item->MEMORY)."    </td>
			 $td3".utf8_decode($item->RESOLUTION)."</td>
			 </tr>";
	}
	echo "</table><br>";		
}

function print_storages($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM storages WHERE (DEVICEID='$systemid')";
	
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
	
	print_item_header($l->g(63));
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>  $td1 ".$l->g(64)."   </td>   $td1 ".$l->g(65)."         </td>
		  		$td1 ".$l->g(53)."  </td>    $td1 ".$l->g(66)."         </td>
		  		$td1 ".$l->g(67)." (MB) </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".utf8_decode($item->MANUFACTURER)."</td>
			  $td3".utf8_decode($item->MODEL)."       </td>
	          $td3".utf8_decode($item->DESCRIPTION)." </td>
     		  $td3".utf8_decode($item->TYPE)."        </td>
 		      $td3".utf8_decode($item->DISKSIZE)."    </td>	";
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_sounds($systemid)
{	
	global $l,$td1,$td2,$td3,$td4;
	
	$queryDetails  = "SELECT * FROM sounds WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(96));
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(64)." </td> $td1 ".$l->g(49)." </td> $td1 ".$l->g(53)." </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".utf8_decode($item->MANUFACTURER)."</td>
	          $td3".utf8_decode($item->NAME)."        </td>
		      $td3".utf8_decode($item->DESCRIPTION)." </td>";
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_softwares($systemid)
{	
	global	$l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM softwares WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(20));	
	if ( mysql_num_rows($resultDetails) == 0 )		 return;	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		
	//echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(69)."     </td> $td1 ".$l->g(49)."     </td>   $td1 ".$l->g(277)."  </td>";
	          // $td1 $rep     </td> $td1 $com     </td>  </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;	
		echo "<tr>";
		echo "$td3".htmlentities(utf8_decode($item->PUBLISHER))."</td>
			  $td3".htmlentities(utf8_decode($item->NAME))."     </td>
		      $td3".utf8_decode($item->VERSION)."  </td>";
		/*      $td3".utf8_decode($item->FOLDER)."   </td>
		      $td3".utf8_decode($item->COMMENTS)." </td>";*/
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_slots($systemid)
{	
	global	$l, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM slots WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(271));
	
	if ( mysql_num_rows($resultDetails) == 0 )		return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(49)."  </td> $td1 ".$l->g(53)."  </td>  $td1 ".$l->g(70)." </td>";
	echo "</tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".utf8_decode($item->NAME)."       </td>
		      $td3".utf8_decode($item->DESCRIPTION)."</td>
		      $td3".utf8_decode($item->DESIGNATION)."</td>";	
		echo "</tr>";
	}
	echo "</table><br>";		
}

function print_printers($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM printers WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	print_item_header($l->g(79));
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
		
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>  $td1 ".$l->g(49)."   </td>  $td1 ".$l->g(278)." </td>  $td1 ".$l->g(279)."   </td>  </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3".utf8_decode($item->NAME)."   </td>
  		      $td3".utf8_decode($item->DRIVER)." </td>
		      $td3".utf8_decode($item->PORT)."   </td>
			 </tr>";
	}
		echo "</table><br>";		
}

function print_registry($systemid)
{
	global $l, $td1, $td2, $td4, $td3;
	$queryDetails = "SELECT * FROM registry WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	print_item_header($l->g(211));
	if(mysql_num_rows($resultDetails)==0) return;	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(212)."</td>
	$td1 ".$l->g(213)."</td>
	";
	echo "</tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3 ".utf8_decode($item->NAME)."</td>
		$td3 ".utf8_decode($item->REGVALUE)."</td>
		";
		echo "</tr>";
	}
	echo "</table><br>";		
}


function print_ports($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM ports WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	print_item_header($l->g(272));
	
	if ( mysql_num_rows($resultDetails) == 0 )		return;	
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(66)."   </td>  $td1 ".$l->g(49)."   </td> $td1 ".$l->g(88)."   </td>  $td1 ".$l->g(53)."   </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		      $td3".utf8_decode($item->TYPE)."        </td>
 		      $td3".utf8_decode($item->NAME)."        </td>
		      $td3".utf8_decode($item->CAPTION)."     </td>
		      $td3".utf8_decode($item->DESCRIPTION)." </td>
			  </tr>";
	}
	echo "</table><br>";		
}

function print_networks($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM networks WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	print_item_header($l->g(82));
	if ( mysql_num_rows($resultDetails) == 0 )	 return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(53)."   </td>  $td1 ".$l->g(66)." </td>
	      $td1 ".$l->g(268)."        </td>  $td1 ".$l->g(95)." </td> $td1 ".$l->g(81)."     </td>
	      $td1 ".$l->g(34)."        </td>  $td1 ".$l->g(208)."</td>  $td1 ".$l->g(207)." </td>
	      $td1 ".$l->g(331)."     </td>$td1 ".$l->g(281)."     </td></tr>";

	while($item = mysql_fetch_object($resultDetails)) {	
		$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		$td3".utf8_decode($item->DESCRIPTION)."</td>
		$td3".utf8_decode($item->TYPE)."       </td>
		$td3".utf8_decode($item->SPEED)."      </td>
		$td3".utf8_decode($item->MACADDR)."    </td>
		$td3".utf8_decode($item->STATUS)."     </td>
		$td3".utf8_decode($item->IPADDRESS)."  </td>
		$td3".utf8_decode($item->IPMASK)."     </td>
		$td3".utf8_decode($item->IPGATEWAY)."  </td>
		$td3".utf8_decode($item->IPSUBNET)."   </td>
		$td3".utf8_decode($item->IPDHCP)."     </td></tr>";
	}
	echo "</table><br>";	
}

function print_monitors($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails = "SELECT * FROM monitors WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(97));
	
	if(mysql_num_rows($resultDetails)==0)	 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>  $td1 ".$l->g(64)." </td>  $td1 ".$l->g(80)."  </td>   $td1 ".$l->g(360)." </td>  $td1 ".$l->g(66)." </td>$td1 ".$l->g(36)." </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			$td3".utf8_decode($item->MANUFACTURER)." </td>
			$td3".utf8_decode($item->CAPTION)."      </td>
			$td3".utf8_decode($item->DESCRIPTION)."  </td>
			$td3".utf8_decode($item->TYPE)."         </td>
			$td3".utf8_decode($item->SERIAL)."         </td>
		</tr>";
	}
	echo "</table><br>";		
}

function print_modems($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM modems WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(270));
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr> $td1 ".$l->g(49)."  </td> $td1 ".$l->g(65)."  </td> $td1 ".$l->g(53)."  </td> $td1 ".$l->g(66)."  </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3".utf8_decode($item->NAME)."        </td>
 		      $td3".utf8_decode($item->MODEL)."       </td>
		      $td3".utf8_decode($item->DESCRIPTION)." </td>
		      $td3".utf8_decode($item->TYPE).        "</td>
		      </tr>";
	}
	echo "</table><br>";		
}

function print_memories($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM memories WHERE (DEVICEID='$systemid') ORDER BY capacity ASC";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		
	print_item_header($l->g(26));
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(80)."  </td>  $td1 ".$l->g(53)."  </td>  $td1 ".$l->g(83)." (MB)  </td> $td1 ".$l->g(283)."    </td>
	      $td1 ".$l->g(66)."  </td>  $td1 ".$l->g(268)."  </td>  $td1 ".$l->g(94)."      </td> </tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3 ".utf8_decode($item->CAPTION)."     </td>
		      $td3 ".utf8_decode($item->DESCRIPTION)." </td>
		      $td3 ".utf8_decode($item->CAPACITY)."    </td>
		      $td3 ".utf8_decode($item->PURPOSE)."     </td>
		      $td3 ".utf8_decode($item->TYPE)."        </td>
		      $td3 ".utf8_decode($item->SPEED)."       </td>
		      $td3 ".utf8_decode($item->NUMSLOTS)."    </td>
		      </tr>";
	}
	echo "</table><br>";		
}

function print_inputs($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;

	$queryDetails = "SELECT * FROM inputs WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(91));
	
	if ( mysql_num_rows($resultDetails) == 0 )	 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(66)."   </td>   $td1 ".$l->g(64)."   </td>    $td1 ".$l->g(80)."   </td>
	      $td1 ".$l->g(53)."   </td>   $td1 ".$l->g(84)." </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
			  $td3 ".utf8_decode($item->TYPE)."        </td>
		      $td3 ".utf8_decode($item->MANUFACTURER)."</td>
		      $td3 ".utf8_decode($item->CAPTION)."     </td>
		      $td3 ".utf8_decode($item->DESCRIPTION)." </td>
		      $td3 ".utf8_decode($item->INTERFACE)."   </td>
		     </tr>";
	}
	echo "</table><br>";		
}

function print_drives($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM drives WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(92));
	
	if ( mysql_num_rows($resultDetails) == 0 )	 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>";
	echo "$td1 ".$l->g(85)."     </td>  $td1 ".$l->g(66)."       </td> $td1 ".$l->g(86)."  </td>
		  $td1 ".$l->g(87)." (MB) </td> $td1 ".$l->g(26)." (MB)   </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
		      $td3 ".utf8_decode($item->LETTER)."     </td>
		      $td3 ".utf8_decode($item->TYPE)."       </td>
		      $td3 ".utf8_decode($item->FILESYSTEM)." </td>
		      $td3 ".utf8_decode($item->TOTAL)."      </td>
		      $td3 ".utf8_decode($item->FREE)."       </td>
			  </tr>";
	}
	echo "</table><br>";		
}

function print_bios($systemid)
{	
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM bios WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(273));
	
	if ( mysql_num_rows($resultDetails) == 0 ) 	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr>$td1 ".$l->g(36)."  </td>	  $td1 ".$l->g(64)."  </td>	  $td1 ".$l->g(65)."   </td>	  $td1 ".$l->g(284)."  </td>
		  $td1 ".$l->g(209)."  </td> $td1 ".$l->g(210)."  </td> </tr>";
		  
	$item = mysql_fetch_object($resultDetails);	
	echo "<tr>";
	echo "$td3".utf8_decode($item->SSN)." </td>
	$td3".utf8_decode($item->SMANUFACTURER)." </td>
	      $td3".utf8_decode($item->SMODEL)."        </td>
		  $td3".utf8_decode($item->BMANUFACTURER)." </td>
		  $td3".utf8_decode($item->BVERSION)."      </td>
		  $td3".utf8_decode($item->BDATE)."         </td>";
	echo "</tr>";
	echo "</table><br>";
}

function print_comments($systemid)
{
	global $com, $td1, $td2, $td3, $td4;

	$queryDetails  = "SELECT * FROM comments WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
		
	print_item_header($l->g(51));
	if ( mysql_num_rows($resultDetails) == 0 )  	return;
	
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr >";
	echo "$td1 ".$l->g(51)."</td>";
	echo "</tr>";
	
	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>";
		echo "$td3".utf8_decode($item->COMMENTS)."</td>";
		echo "</tr>";
	}
	echo "</table><br>";
}

function print_controllers($systemid)
{
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails  = "SELECT * FROM controllers WHERE (DEVICEID='$systemid')";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	
	print_item_header($l->g(93));
	if ( mysql_num_rows($resultDetails) == 0 )  	return;
		
	echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
	echo "<tr > $td1 ".$l->g(64)." </td> $td1 ".$l->g(49)." </td> $td1 ".$l->g(66)." </td></tr>";

	while($item = mysql_fetch_object($resultDetails))
	{	$ii++; $td3 = $ii%2==0?$td2:$td4;
		echo "<tr>
				$td3 ".utf8_decode($item->MANUFACTURER)."</td>
		      	$td3 ".utf8_decode($item->NAME)."        </td>
		      	$td3 ".utf8_decode($item->TYPE)."        </td>
			</tr>";
	}
	echo "</table><br>";	
}

function print_inventory($systemid)
{
	global $l, $td1, $td2, $td3, $td4;
	
	$queryDetails = "SELECT * FROM accountinfo WHERE deviceid='$systemid' LIMIT 0,1";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$item=mysql_fetch_array($resultDetails,MYSQL_ASSOC);
	if( $item ) {			
		$label_bouton = "<input onmouseover=\"this.style.background='#FFFFFF';\" onmouseout=\"this.style.background='#C7D9F5'\" class='bouton' type='button' value='".$l->g(103)."' onClick='MAJ_donnees(\"$systemid\",\"".session_id()."\");' $event_mouse>";
		echo "<td  align='middle' width='20%'>$label_bouton</td>";
			
		echo "<table BORDER='0' WIDTH = '95%' ALIGN = 'Center' CELLPADDING='0' BGCOLOR='#C7D9F5' BORDERCOLOR='#9894B5'>";
		echo "<tr > $td1 ".$l->g(223)." </td> $td1 ".$l->g(224)." </td></tr>";		
		
		$indType=-1;
		foreach ($item as $k=>$v) {
			$indType++;
			if($k == "DEVICEID" || $k=="UNITID")
				continue;
			if($k == TAG_NAME)
				$k = TAG_LBL;
			else if($k == "TAG")
				continue;
				
			$ii++; $td3 = $ii%2==0?$td2:$td4;
			
			if(mysql_field_type($resultDetails,$indType)=="date")
				$v = dateFromMysql($v);
				
			echo "<tr>$td3 $k</td>$td3 $v</tr>";		
		}		
		
		echo "</table><br>";
	}	
}

function getNetName($did) {
	global $tdhd,$tdhf,$tdhdpb,$tdhfpb,$l;
	
	//echo "<tr>"$tdhd.$l->g(50).$tdhf.      $tdhdpb.VAL.$tdhfpb."</tr>";
			
	$reqSub = "SELECT name FROM subnet s,networks n WHERE s.netid=n.ipsubnet AND n.deviceid='$did'";
	$resSub = mysql_query($reqSub, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
	$indice = 1;
	$returnVal = "<tr>".$tdhd.$l->g(304)." ".$indice.$tdhf;	

	while($valSub = mysql_fetch_array( $resSub )){
		if($indice != 1) {
			$returnVal .= "</tr><tr>".$tdhd.$l->g(304)." ".$indice.$tdhf;
		}
		$indice++;
		$returnVal .= $tdhdpb.$valSub["name"].$tdhfpb;
	}	
	
	$queryDetails  = "SELECT ipsubnet FROM networks WHERE DEVICEID='$did' AND ipsubnet NOT IN(SELECT netid FROM subnet)";
	$resultDetails = mysql_query($queryDetails, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));	
	while($item = mysql_fetch_array($resultDetails)) {
		if($indice != 1) {
			$returnVal .= "</tr><tr>".$tdhfpb.$tdhd.$l->g(304)." ".$indice.$tdhf;
		}
		$returnVal .= $tdhdpb.$item["ipsubnet"].$tdhfpb;
		$indice++;
	}
	
	return 	$returnVal;
}

function print_item_header($text)
{
	echo "<table align=\"center\"  width='100%'  cellpadding='4'>";
	echo "<tr>";
	echo "<td  align='center' width='100%'><b>-&nbsp;$text-</b></td>";
	echo "</tr>";
	echo "</table><br>";	
}
?>