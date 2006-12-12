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
//Modified on $Date: 2006-12-12 10:49:14 $$Author: plemmet $($Revision: 1.3 $)

?>
<br><form name=pass action=# method=post>
<table BORDER='0' WIDTH = '35%' ALIGN = 'Center' CELLPADDING='0' BORDERCOLOR='#9894B5'>
	<tr>
		<td><b><?echo $l->g(237)?>:</b></td>
		<td><input name=pass1 type=password size=15></td>
	</tr>
	<tr>
		<td><b><?echo $l->g(238)?>:</b></td>
		<td><input name=pass2 type=password size=15></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><input name=subPass type=submit value=<?echo $l->g(13)?>></td>
	</tr>
</table>
</form>		
