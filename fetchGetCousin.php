<?php
require_once  ("config.php");
require_once ("_functions.php");
require_once ("_get_ascendancy.php");
require_once ("_get_descendancy.php");
require_once ("_sql_requests.php");
sql_connect();

$p1 = intval($_GET['p1']);
$p2 = intval($_GET['p2']);
$p3 = intval($_GET['p3']);

$cousins = recup_cousin($p1, $p2, $p3); 
echo json_encode($cousins); 

