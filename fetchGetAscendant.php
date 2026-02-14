<?php
/* 
execution d'une fonction PHP qui ramène déjà un tableau qu'il suffit de convertir en json (comme recup_descendance)
*/
require_once  ("config.php");
require_once ("_functions.php");
require_once ("_get_ascendancy.php");
sql_connect();

$id = intval($_GET['id']);

$ancetres[][] = '';
$cpt_generations = 0;
$ancetres['id_indi'][0] = $id;
recup_ascendance ($ancetres,0,99999,'');
integrer_implexe(99999,'');

echo json_encode($ancetres); 

