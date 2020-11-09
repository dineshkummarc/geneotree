<?php
require_once ("_sql.inc.php");
$pool = sql_connect();

/******************* recuperation des points *****************/

$query = 'SELECT distinct x,y 
			FROM got_'.$_REQUEST['addrc'].'_commcarte';
if ($_REQUEST['carte'] !== 'US_US')	{	$query = $query.' WHERE dept = "'.mb_substr($_REQUEST['carte'],-2).'"';}
$result = sql_exec($query,0);

/**************** Creation de l'image avec les points *****************/

//print_r($_REQUEST);
$myImage=ImageCreateFromJPEG('geo/'.$_REQUEST['carte'].'.jpg');

while ($row = mysqli_fetch_row($result)) 
{	if (version_php_gd_OK())
	{	imagefilledellipse ( $myImage , $row[0]+4 , $row[1]+4 , 10, 10, 0);	// row 4 et 5 : coord en pixel, 10 largeur du cercle
	} else 
	{	imagerectangle($myImage,$row[0] , $row[1] ,$row[0]+8 , $row[1]+8, 25);
	}
}
//$query = 'DROP TABLE got_'.$_REQUEST['addrc'].'_commcarte';
//sql_exec($query);

ImageJpeg($myImage);
?>
