<?php
$header_html="NO";
require_once("header.php");

if( $_SESSION["lvluser"]!=LADMIN && $_SESSION["lvluser"]!=SADMIN  )
	die("FORBIDDEN");
	$year_mouth['Dec']=12;
	$year_mouth['Nov']=11;
	$year_mouth['Oct']=10;
	$year_mouth['Sep']=9;
	$year_mouth['Aug']=8;
	$year_mouth['Jul']=7;
	$year_mouth['Jun']=6;
	$year_mouth['May']=5;
	$year_mouth['Apr']=4;
	$year_mouth['Mar']=3;
	$year_mouth['Feb']=2;
	$year_mouth['Jan']=1;
	
$sql="select count(*) c from devices d,
download_enable d_e,download_available d_a
where d.name='DOWNLOAD'
and d_e.id=d.ivalue
and d_a.fileid=d_e.fileid
and d_e.fileid='".$_GET['stat']."'";
$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$item = mysql_fetch_object($result);
$total_mach=$item->c;

$sql="select d.hardware_id as id,d.comments as date_valid from devices d,
download_enable d_e,download_available d_a
where d.name='DOWNLOAD' 
and tvalue='".$_GET['ta']."' 
and comments is not null
and d_e.id=d.ivalue
and d_a.fileid=d_e.fileid
and d_e.fileid='".$_GET['stat']."'";

$result = mysql_query($sql, $_SESSION["readServer"]) or die(mysql_error($_SESSION["readServer"]));
$nb_4_hour=array();
//$total_mach=0;
while($item = mysql_fetch_object($result)){
	//echo $item->date_valid."<br>";
	unset($data_temp,$day,$year,$hour_temp,$hour);
	$data_temp=explode(' ',$item->date_valid);
	if ($data_temp[2] != '')
	$day=$data_temp[2];
	else
	$day=$data_temp[3];
	
	$mouth=$data_temp[1];
	if (isset($data_temp[5]))
	$year=$data_temp[5];
	else
	$year=$data_temp[4];
//	print_r($data_temp);
//	echo "=>".$year."<br>";
	$hour_temp=explode(':',$data_temp[3]);
	$hour=$hour_temp[0];
	if ($hour<12)
	$hour=12;
	else
	$hour=00;
	$timestamp=mktime ($hour,0,0,$year_mouth[$mouth],$day,$year);
	if (isset($nb_4_hour[$timestamp]))
	$nb_4_hour[$timestamp]++;
	else
	$nb_4_hour[$timestamp]=1;
	//$total_mach++;
}

ksort($nb_4_hour);
//print_r($nb_4_hour);
foreach ($nb_4_hour as $key=>$value){
	$ancienne+=$value;
	$legende[]=date ( "d/m/Y H:00" ,$key);
	$data[]=(($ancienne*100) / $total_mach);
	
}
$img_width= count($data)*30;

if (isset($data) or $data == ""){
header ("Content-type: image/png");
Histogramme($data,$img_width,600,$legende);
}else
echo $sql."<center><font color=red><b>".$l->g(989)."</b></font></center>";

function ImageColorLight($im,$color,$mod)
{
	if ($color<0)
	$color=2;
  $rvb=ImageColorsForIndex($im,$color);
  // On teste les débordements
  if(($mod+$rvb['red'])>255) $rvb['red']=255-$mod;
  if(($mod+$rvb['green'])>255) $rvb['green']=255-$mod;
  if(($mod+$rvb['blue'])>255) $rvb['blue']=255-$mod;
  if(($mod+$rvb['red'])<0) $rvb['red']=-$mod;
  if(($mod+$rvb['green'])<0) $rvb['green']=-$mod;
  if(($mod+$rvb['blue'])<0) $rvb['blue']=-$mod;

  // On définit la nouvelle couleur
  return ImageColorAllocate($im,$mod+$rvb['red'],$mod+$rvb['green'],$mod+$rvb['blue']);
}

function drawPNG($im)
{
  imagePNG($im);
//  echo "\"graphique\"/";
}

function Histogramme($valeurs,$img_width,$img_height,$legende)
{
  
$n_val=count($valeurs);           // Nombre de valeurs à inclure dans le graph
$somme=array_sum($valeurs);       // Somme des valeurs

$margin_x=10;
$margin_y=10;

$leg_larg=100;      // Largeur réservée à la largeur (à droite)
$leg_top=10;        // Espace entre le haut de l'image et le début de la première "box
$leg_bottom=2;
$box_height=8;      // Hauteur d'une box
$box_width=8;       // Largeur d'une box
$leg_space_y=8;     // Espace vertical entre deux box

$im=ImageCreate(intval($img_width),intval($img_height)); //dessus du camembert

$white=ImageColorAllocate($im,255,255,255);
ImageColorTransparent($im,$white);

$black=ImageColorAllocate($im,0,0,0);

$graph_width=$img_width-($leg_larg+2*$margin_x);
$graph_height=$img_height-(2*$margin_y);

// On calcule l'échelle
$max=100;    // valeur max du graphique, on envoie des % la valeur est donc fixée à 100
$scale_y=$graph_height/$max;
$scale_x=$graph_width/$n_val;
$scale_x=12; // Largeur d'une barre
$space_x=6;  // espace entre 2 barres, cette valeur doit être >= à $relief_x

$relief_x=intval($scale_x/2);     // décalage sur l'axe X pour le relief
$relief_y=intval($scale_x/2);     // Pareil pour Y

// ---------- On dessine la base du graphique en relief ----------

$col_base=ImageColorAllocate($im,192,192,192);
$base=array(0,$img_height,
       $graph_width,$img_height,
       $graph_width+3*$relief_x,$img_height-(3*$relief_y),
       3*$relief_x,$img_height-(3*$relief_y)
       );
ImageFilledPolygon($im, $base, 4, $col_base);

$col_cotes=ImageColorLight($im,$col_base,32);
$cotes=array(0,$img_height,
       3*$relief_x,$img_height-(3*$relief_y),
       3*$relief_x,$img_height-(3*$relief_y+$graph_height),
       0,$img_height-($graph_height)
       );
ImageFilledPolygon($im, $cotes, 4, $col_cotes);

ImageFilledRectangle($im,$graph_width+3*$relief_x,$img_height-(3*$relief_y),

$relief_x,$img_height-(3*$relief_y+$graph_height),$col_cotes);

ImageLine($im,3*$relief_x,$img_height-(3*$relief_y),3*$relief_x,$img_height-(3*$relief_y+$graph_height),$col_base);
ImageLine($im,0,$img_height,0,$img_height-($graph_height),$col_base);

// -----------------------------------------------------------------

for($i=0;$i< $n_val;$i++)
{
       $col=ImageColorAllocate($im,255/($n_val/($i+1)),0,255-255/($n_val/($i+1)));

       $start_x=$margin_x+$i*($scale_x+$space_x);
       $start_y=$margin_y;


       // Partie latérale du relief
       $relief=array($start_x+$scale_x,$img_height-($start_y),
             $start_x+$scale_x+$relief_x,$img_height-($start_y+$relief_y),
             $start_x+$scale_x+$relief_x,$img_height-($start_y+$valeurs[$i]*$scale_y+$relief_y),
             $start_x+$scale_x,$img_height-($start_y+$valeurs[$i]*$scale_y),
             );
       ImageFilledPolygon($im, $relief, 4, $col);

       // Partie supérieure du relief
       $col=ImageColorLight($im,$col,16);      // On éclaircit la couleur
       $relief=array($start_x,$img_height-($start_y+$valeurs[$i]*$scale_y),
              $start_x+$scale_x,$img_height-($start_y+$valeurs[$i]*$scale_y),
              $start_x+$scale_x+$relief_x,$img_height-($start_y+$valeurs[$i]*$scale_y+$relief_y),
              $start_x+$relief_x,$img_height-($start_y+$valeurs[$i]*$scale_y+$relief_y)
             );
       ImageFilledPolygon($im, $relief, 4, $col);

       // Partie plane qui correspond à l'histogramme en 2D
       $col=ImageColorLight($im,$col,48);      // On éclaircit la couleur
       ImageFilledRectangle($im,$start_x,$img_height-$start_y,

       $start_x+$scale_x,$img_height-($start_y+$valeurs[$i]*$scale_y),$col);

Imagettftext($im,7,90,$start_x+$scale_x+$relief_x/2,$img_height-($start_y+$valeurs[$i]*$scale_y+$relief_y+2),
$black,"./fonts/verdana.ttf",intval($valeurs[$i])."% (".$legende[$i].")");

}

drawPNG($im);
ImageDestroy($im);
}
?>
