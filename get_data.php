<?php
require_once ("_functions.php");
require_once ("_sql_requests.php");
require_once ("languages/".$_REQUEST['lang'].".php");

// function callFunctionDynamically($functionName, ...$params) 
function callFunctionDynamically($functionName) 
{
  if (empty($_REQUEST["sex"]) OR $_REQUEST["sex"] == "ALL") {$_REQUEST["sex"] = "_";}  // le caractere % est interdit dans une urldecode
  if (!isset($_REQUEST['exp']))  {$_REQUEST['exp']   = "";}

  $params = func_get_args();
// print_r($params); // debug
  // Retirer le premier argument ($functionName)
  array_shift($params);
  // Appeler la fonction avec les paramètres
  return call_user_func_array($functionName, $params);
}

$pool = sql_connect();
if (empty($_GET['p1']) )   $_GET['p1'] = "";
if (empty($_GET['p2']) )   $_GET['p2'] = "";
if (empty($_GET['p3']) )   $_GET['p3'] = "";
if (empty($_GET['p4']) )   $_GET['p4'] = "";
if (empty($_GET['p5']) )   $_GET['p5'] = "";
if (empty($_GET['p6']) )   $_GET['p6'] = "";
if (empty($_GET['pag']) )  $_GET['pag'] = "";
if (empty($_GET['sort_column']) ) $_GET['sort_column'] = "1";
if (empty($_GET['sort_order']) )  $_GET['sort_order'] = "ASC";

// $p4 = urldecode($_GET['p4']);
// $p4 = addslashes(rtrim($p4));

$query = callFunctionDynamically($_GET['fonc'],$_GET['p1'],$_GET['p2'],$_GET['p3'],$_GET['p4'],$_GET['p5'],$_GET['p6'],$_GET['pag']);
// echo ' | Query: '.$query ;return;  //debug
$start = isset($_GET['start']) ? intval($_GET['start']) : 0;

// limit 20 for statistics chart
if ($_GET['sort_column'] == 7) 
{ $limit = 20;
} else 
{ $limit = 200;
}

$sort_column = isset($_GET['sort_column']) ? mysqli_real_escape_string($pool, $_GET['sort_column']) : '1';
$sort_order = isset($_GET['sort_order']) ? mysqli_real_escape_string($pool, $_GET['sort_order']) : 'ASC';

// SQL queries passed as an array of strings
$sql_queries = explode (';', $query);
$indice_fin = count($sql_queries) - 1;
$sql_queries[$indice_fin] .= " ORDER BY $sort_column $sort_order LIMIT $start, $limit";
// print_r2($sql_queries);return;
foreach ($sql_queries as $query) 
{   if (!mysqli_query($pool, $query)) 
	{   echo json_encode(array('error' => mysqli_error($pool)));
        mysqli_close($pool);
        exit();
    }
}

$result = mysqli_query($pool, $sql_queries[$indice_fin]);

if (mysqli_num_rows($result) > 0) 
{   $data = array();
    while($row = mysqli_fetch_assoc($result)) 
	{ $data[] = $row;
    }
    echo json_encode($data);
} else 
{ echo json_encode(array());
}

mysqli_close($pool);
?>
