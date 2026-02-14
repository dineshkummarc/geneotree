<?php
require_once  ("config.php");
require_once ("_functions.php");
require_once ("_get_descendancy.php");
sql_connect();

$id    = intval($_GET['id']);
$ADDRC = intval($_GET['ADDRC']);

$nb_generations_desc = 99999;
$descendants ['id_indi'] [0] = $id;
recup_descendance (0,0,$nb_generations_desc = 99999, $perf = '', $flag_maria = '');  // 

$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);

echo json_encode($descendants); 

