<?php
/*
OCS Graphics ADD by Nicolas DEROUET
*/
echo "<LINK REL='StyleSheet' TYPE='text/css' HREF='css/graphic.css'>\n";

function percent_bar($status)
{
if (!is_numeric($status)) { return $status; }
if (($status<0) or ($status>100)) { return $status; }
return "<div class='percent_bar'><!--".str_pad($status, 3, "0", STR_PAD_LEFT)."-->
<div class='percent_status' style='width:".$status."px;'>&nbsp;</div>
<div class='percent_text'>".$status."%</div>
</div>";
}

?>